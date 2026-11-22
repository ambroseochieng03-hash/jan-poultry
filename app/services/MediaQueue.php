<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use Basis\Nats\Client;
use Basis\Nats\Configuration;
use Basis\Nats\Stream\DiscardPolicy;
use Basis\Nats\Stream\RetentionPolicy;
use Basis\Nats\Stream\Stream;
use Basis\Nats\Stream\StorageBackend;

/*
|--------------------------------------------------------------------------
| JetStream queue for media jobs (image/video processing).
| Own stream (JP_MEDIA) and subject (jp.media.process): Lux's streams are
| never touched. The stream is tiny on purpose: it only carries job
| descriptions, never files.
|--------------------------------------------------------------------------
*/
final class MediaQueue
{
    public const STREAM   = 'JP_MEDIA';
    public const SUBJECT  = 'jp.media.process';
    public const CONSUMER = 'jp-media-worker';

    public static function client(float $timeout = 2.0): Client
    {
        $url = parse_url((string) Env::get('NATS_URL', 'nats://127.0.0.1:4222'));

        return new Client(new Configuration(
            host: is_array($url) && !empty($url['host']) ? $url['host'] : '127.0.0.1',
            port: is_array($url) && !empty($url['port']) ? (int) $url['port'] : 4222,
            timeout: $timeout,
        ));
    }

    public static function ensureStream(Client $client): Stream
    {
        $stream = $client->getApi()->getStream(self::STREAM);

        $stream->getConfiguration()
            ->setSubjects([self::SUBJECT])
            ->setRetentionPolicy(RetentionPolicy::WORK_QUEUE)
            ->setStorageBackend(StorageBackend::FILE)
            ->setDiscardPolicy(DiscardPolicy::NEW)
            ->setMaxBytes(16 * 1024 * 1024)
            ->setMaxMessageSize(8192)
            ->setMaxAge(86400 * 1_000_000_000);

        return $stream->createIfNotExists();
    }

    /** Persist one job; returns only after JetStream has stored it. */
    public static function publish(array $job): void
    {
        $payload = json_encode($job, JSON_UNESCAPED_SLASHES);

        if ($payload === false) {
            throw new RuntimeException('Job could not be encoded.');
        }

        $client = self::client();

        try {

            $client->getApi()->getStream(self::STREAM)->put(self::SUBJECT, $payload);

        } catch (Throwable $first) {

            // The stream may not exist yet (first upload ever): create it and retry once.
            self::ensureStream($client)->put(self::SUBJECT, $payload);
        }
    }
}
