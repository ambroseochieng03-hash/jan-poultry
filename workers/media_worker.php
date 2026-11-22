#!/usr/bin/env php
<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| JAN Poultry media worker (systemd: janpoultry-media-worker.service)
|
| Pulls ONE job at a time from the JP_MEDIA JetStream stream, converts the
| uploaded file (GD / FFmpeg), uploads the results to Cloudflare R2, then
| deletes the local copies. Built to be gentle on a shared VM: single job,
| single thread, no busy loop, restarts itself after 200 jobs.
|--------------------------------------------------------------------------
*/

if (PHP_SAPI !== 'cli') {
    exit(1);
}

chdir(dirname(__DIR__));

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/services/R2Client.php';
require_once __DIR__ . '/../app/services/MediaQueue.php';
require_once __DIR__ . '/../app/services/MediaProcessor.php';

use Basis\Nats\Consumer\AckPolicy;

const MAX_DELIVER   = 3;
const MAX_JOBS      = 200;
const STALE_SECONDS = 86400;

function wlog(string $level, string $message, array $context = []): void
{
    fwrite(STDOUT, json_encode(['t' => date('c'), 'level' => $level, 'msg' => $message] + $context) . "\n");
}

/** How many times JetStream has delivered this message (from the ACK reply subject). */
function deliveryCount(string $replyTo): int
{
    $parts = explode('.', $replyTo);

    if (count($parts) === 9) {
        return max(1, (int) $parts[4]);
    }

    if (count($parts) >= 12) {
        return max(1, (int) $parts[6]);
    }

    return 1;
}

function removeTree(string $path): void
{
    if (is_file($path) || is_link($path)) {
        @unlink($path);
        return;
    }

    if (!is_dir($path)) {
        return;
    }

    foreach (scandir($path) ?: [] as $entry) {
        if ($entry !== '.' && $entry !== '..') {
            removeTree($path . '/' . $entry);
        }
    }

    @rmdir($path);
}

/** Orphans from crashed jobs must never be able to fill the disk. */
function sweepIncoming(): void
{
    $dir = MediaProcessor::incomingDir();

    foreach (glob($dir . '/*') ?: [] as $path) {
        if (filemtime($path) < time() - STALE_SECONDS) {
            removeTree($path);
            wlog('info', 'removed stale incoming item', ['item' => basename($path)]);
        }
    }
}

/** The product keeps pointing at media that will never exist: clear it so admins see it. */
function markFailed(?string $final): void
{
    if ($final === null || $final === '') {
        return;
    }

    try {

        $pdo  = (new Database())->getConnection();
        $stmt = $pdo->prepare('UPDATE products SET image = NULL WHERE image = ?');
        $stmt->execute([$final]);

    } catch (Throwable $e) {
        wlog('error', 'could not mark product media as failed', ['error' => $e->getMessage()]);
    }
}

function handleMessage(Basis\Nats\Message\Msg $message, R2Client $r2, MediaProcessor $processor): void
{
    $delivered = deliveryCount((string) $message->replyTo);
    $job       = null;
    $work      = null;
    $started   = microtime(true);

    try {

        $job  = MediaProcessor::validateJob(json_decode((string) $message->payload, true));
        $work = MediaProcessor::incomingDir() . '/work-' . $job['base'];

        removeTree($work);
        mkdir($work, 0750, true);

        $message->progress();

        $outputs = $processor->process($job, $work);

        $message->progress();

        foreach ($outputs as $name => $output) {
            $r2->putFile('products/' . $name, $output['path'], $output['type']);
            $message->progress();
        }

        removeTree($work);
        removeTree($job['input']);

        $message->ack();

        wlog('info', 'processed', [
            'file'    => $job['final'],
            'kind'    => $job['kind'],
            'seconds' => round(microtime(true) - $started, 1),
            'objects' => array_keys($outputs),
        ]);

    } catch (PermanentMediaError $e) {

        wlog('error', 'rejected', ['file' => $job['final'] ?? null, 'reason' => $e->getMessage()]);

        markFailed($job['final'] ?? null);

        if ($work !== null) {
            removeTree($work);
        }

        if ($job !== null) {
            removeTree($job['input']);
        }

        $message->ack();

    } catch (Throwable $e) {

        wlog('error', 'failed', [
            'file'      => $job['final'] ?? null,
            'attempt'   => $delivered,
            'error'     => $e->getMessage(),
        ]);

        if ($delivered >= MAX_DELIVER) {

            markFailed($job['final'] ?? null);

            if ($work !== null) {
                removeTree($work);
            }

            if ($job !== null) {
                removeTree($job['input']);
            }

            $message->term();

        } else {

            if ($work !== null) {
                removeTree($work);
            }

            $message->nack(15 * $delivered);
        }
    }
}

/* ----------------------------------------------------------------- main */

$stop = false;

if (function_exists('pcntl_async_signals')) {
    pcntl_async_signals(true);
    pcntl_signal(SIGTERM, static function () use (&$stop): void { $stop = true; });
    pcntl_signal(SIGINT, static function () use (&$stop): void { $stop = true; });
}

if (!is_dir(MediaProcessor::incomingDir())) {
    wlog('error', 'incoming folder does not exist', ['dir' => MediaProcessor::incomingDir()]);
    exit(1);
}

try {

    $r2        = R2Client::fromEnv();
    $processor = new MediaProcessor();
    $client    = MediaQueue::client(5.0);
    $stream    = MediaQueue::ensureStream($client);

    $consumer = $stream->getConsumer(MediaQueue::CONSUMER);

    $consumer->getConfiguration()
        ->setSubjectFilter(MediaQueue::SUBJECT)
        ->setAckPolicy(AckPolicy::EXPLICIT)
        ->setMaxDeliver(MAX_DELIVER)
        ->setMaxAckPending(1)
        ->setAckWait(1_200_000_000_000); // 20 minutes, in nanoseconds

    $consumer->setBatching(1)->setExpires(0)->create();

    $queue = $consumer->getQueue();
    $queue->setTimeout(2);

    wlog('info', 'worker started', ['stream' => MediaQueue::STREAM, 'consumer' => MediaQueue::CONSUMER]);

    sweepIncoming();

    $done      = 0;
    $lastSweep = time();

    while (!$stop && $done < MAX_JOBS) {

        $message = $queue->fetch();

        if ($message === null || $message->payload->isEmpty()) {

            if (time() - $lastSweep > 3600) {
                sweepIncoming();
                $lastSweep = time();
            }

            sleep(2);

            continue;
        }

        handleMessage($message, $r2, $processor);

        $done++;

        gc_collect_cycles();
    }

    wlog('info', 'worker exiting', ['jobs' => $done, 'signal' => $stop]);

} catch (Throwable $e) {

    // Connection problems and the like: exit so systemd restarts us cleanly.
    wlog('error', 'worker crashed', ['error' => $e->getMessage()]);

    exit(1);
}
