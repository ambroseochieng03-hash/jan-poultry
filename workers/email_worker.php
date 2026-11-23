#!/usr/bin/env php
<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| JAN Poultry notification worker (systemd: janpoultry-email-worker.service)
|
| 1. Sends the rows pages put in email_outbox (retries after 1, 2, 4, 8 min,
|    then marks the row failed; rows stuck "sending" are put back).
| 2. Delivers the rows in push_outbox to every phone the customer enabled
|    (subscriptions the push service reports gone are deleted).
| Gentle on a shared VM: one item at a time, sleeps when idle, restarts
| itself after about an hour.
|--------------------------------------------------------------------------
*/
if (PHP_SAPI !== 'cli') {
    exit(1);
}

chdir(dirname(__DIR__));
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/services/MailService.php';
require_once __DIR__ . '/../app/services/ReceiptService.php';
require_once __DIR__ . '/../app/services/WebPush.php';

const MAX_ATTEMPTS = 5;
const IDLE_SLEEP   = 5;
const MAX_RUNTIME  = 3600;

function wlog(string $level, string $msg, array $ctx = []): void
{
    fwrite(STDOUT, json_encode(['t' => date('c'), 'level' => $level, 'msg' => $msg] + $ctx) . "\n");
}

/** @return int number of emails handled */
function processEmails(PDO $db, MailService $mail): int
{
    $db->exec("UPDATE email_outbox SET status = 'pending' WHERE status = 'sending' AND locked_at < (NOW() - INTERVAL 10 MINUTE)");
    $due = $db->query("SELECT id FROM email_outbox WHERE status = 'pending' AND next_attempt_at <= NOW() ORDER BY id LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
    $done = 0;

    foreach ($due as $id) {
        $claim = $db->prepare("UPDATE email_outbox SET status = 'sending', locked_at = NOW() WHERE id = ? AND status = 'pending'");
        $claim->execute([$id]);
        if ($claim->rowCount() !== 1) {
            continue;
        }
        $done++;

        $row = $db->query('SELECT * FROM email_outbox WHERE id = ' . (int) $id)->fetch(PDO::FETCH_ASSOC);

        $attachment = null;
        if (!empty($row['receipt_order_id'])) {
            try {
                $attachment = (new ReceiptService())->generate((int) $row['receipt_order_id']);
            } catch (Throwable $e) {
                wlog('warn', 'receipt failed, sending without it', ['id' => $id, 'error' => $e->getMessage()]);
            }
        }

        $ok = $mail->send((string) $row['to_email'], (string) $row['to_name'], (string) $row['subject'], (string) $row['body'], $attachment);

        if ($ok) {
            $db->prepare("UPDATE email_outbox SET status = 'sent', sent_at = NOW(), locked_at = NULL, last_error = NULL WHERE id = ?")->execute([$id]);
            wlog('info', 'email sent', ['id' => $id]);
            continue;
        }

        $attempts = (int) $row['attempts'] + 1;
        if ($attempts >= MAX_ATTEMPTS) {
            $db->prepare("UPDATE email_outbox SET status = 'failed', attempts = ?, locked_at = NULL, last_error = 'send failed, see worker log' WHERE id = ?")->execute([$attempts, $id]);
            wlog('error', 'email giving up', ['id' => $id]);
        } else {
            $wait = 60 * (2 ** ($attempts - 1));
            $db->prepare("UPDATE email_outbox SET status = 'pending', attempts = ?, locked_at = NULL, next_attempt_at = NOW() + INTERVAL ? SECOND, last_error = 'send failed, will retry' WHERE id = ?")->execute([$attempts, $wait, $id]);
            wlog('warn', 'email will retry', ['id' => $id, 'in_seconds' => $wait]);
        }
        sleep(1);
    }

    return $done;
}

/** @return int number of push messages handled */
function processPush(PDO $db): int
{
    $rows = $db->query("SELECT * FROM push_outbox WHERE status = 'pending' ORDER BY id LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $claim = $db->prepare("UPDATE push_outbox SET status = 'done' WHERE id = ? AND status = 'pending'");
        $claim->execute([$row['id']]);
        if ($claim->rowCount() !== 1) {
            continue;
        }

        $stmt = $db->prepare('SELECT * FROM push_subscriptions WHERE (user_id IS NOT NULL AND user_id = ?) OR (guest_id IS NOT NULL AND guest_id = ?) LIMIT 8');
        $stmt->execute([$row['user_id'], $row['guest_id']]);

        $payload = json_encode(['title' => $row['title'], 'body' => $row['body'], 'url' => $row['url'], 'tag' => $row['tag']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $sub) {
            $status = WebPush::send($sub, (string) $payload);
            if ($status >= 200 && $status < 300) {
                $db->prepare('UPDATE push_subscriptions SET last_success_at = NOW(), failures = 0 WHERE id = ?')->execute([$sub['id']]);
                wlog('info', 'push sent', ['outbox' => $row['id'], 'sub' => $sub['id']]);
            } elseif ($status === 404 || $status === 410) {
                $db->prepare('DELETE FROM push_subscriptions WHERE id = ?')->execute([$sub['id']]);
                wlog('info', 'push subscription gone, removed', ['sub' => $sub['id']]);
            } else {
                $db->prepare('UPDATE push_subscriptions SET failures = failures + 1 WHERE id = ?')->execute([$sub['id']]);
                $db->prepare('DELETE FROM push_subscriptions WHERE id = ? AND failures >= 8')->execute([$sub['id']]);
                wlog('warn', 'push failed', ['sub' => $sub['id'], 'http' => $status]);
            }
        }
    }

    return count($rows);
}

function housekeeping(PDO $db): void
{
    $db->exec("DELETE FROM push_outbox WHERE status = 'done' AND created_at < (NOW() - INTERVAL 7 DAY)");
    $db->exec("DELETE FROM email_outbox WHERE status = 'sent' AND created_at < (NOW() - INTERVAL 90 DAY)");
}

$db      = (new Database())->getConnection();
$mail    = new MailService();
$started = time();
$lastClean = 0;
$stop    = false;

if (function_exists('pcntl_signal')) {
    pcntl_async_signals(true);
    pcntl_signal(SIGTERM, static function () use (&$stop): void { $stop = true; });
    pcntl_signal(SIGINT, static function () use (&$stop): void { $stop = true; });
}

wlog('info', 'notification worker started');

while (!$stop && (time() - $started) < MAX_RUNTIME) {
    try {
        $work = processEmails($db, $mail) + processPush($db);

        if (time() - $lastClean > 3600) {
            housekeeping($db);
            $lastClean = time();
        }
        if ($work === 0) {
            sleep(IDLE_SLEEP);
        }
    } catch (Throwable $e) {
        wlog('error', 'loop error', ['error' => $e->getMessage()]);
        sleep(15);
    }
}

wlog('info', 'notification worker exiting (systemd restarts it)');
