<?php

declare(strict_types=1);

/* Health check for the media pipeline. Run on the server as the app user:
 *   sudo -u janpoultry php tools/media-check.php
 * Prints OK/FAIL per component and never prints secrets. */

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

require_once __DIR__ . '/../app/config/env.php';
Env::load();
require_once __DIR__ . '/../app/services/R2Client.php';
require_once __DIR__ . '/../app/services/MediaQueue.php';
require_once __DIR__ . '/../app/services/MediaProcessor.php';

$failed = 0;
$check = function (string $name, callable $fn) use (&$failed): void {
    try { echo str_pad($name, 34) . 'OK   ' . $fn() . "\n"; }
    catch (Throwable $e) { $failed++; echo str_pad($name, 34) . 'FAIL ' . $e->getMessage() . "\n"; }
};

$check('MEDIA_DRIVER', fn () => (string) Env::get('MEDIA_DRIVER', 'local') === 'r2' ? 'r2' : throw new RuntimeException('is not "r2" in .env'));
$check('incoming folder writable', function () {
    $d = MediaProcessor::incomingDir();
    return is_dir($d) && is_writable($d) ? $d : throw new RuntimeException($d . ' missing or not writable');
});
$check('ffmpeg / ffprobe present', function () {
    foreach ([(string) Env::get('FFMPEG_BIN', '/usr/bin/ffmpeg'), (string) Env::get('FFPROBE_BIN', '/usr/bin/ffprobe')] as $b) {
        if (!is_executable($b)) { throw new RuntimeException($b . ' not found'); }
    }
    return '';
});
$check('PHP cURL extension', fn () => extension_loaded('curl') ? '' : throw new RuntimeException('install php-curl (needed for R2 uploads)'));
$check('GD with WebP', fn () => function_exists('imagewebp') ? '' : throw new RuntimeException('GD has no WebP support'));
$check('NATS + JetStream stream', function () {
    $client = MediaQueue::client();
    $stream = MediaQueue::ensureStream($client);
    $info = $stream->info();
    return MediaQueue::STREAM . ' (' . ($info->state->messages ?? '?') . ' queued)';
});
$check('R2 write / read / delete', function () {
    $r2 = R2Client::fromEnv();
    $key = 'healthcheck/' . bin2hex(random_bytes(6)) . '.txt';
    $tmp = tempnam(sys_get_temp_dir(), 'r2');
    file_put_contents($tmp, 'ok');
    try {
        $r2->putFile($key, $tmp, 'text/plain', 'no-store');
        if (!$r2->exists($key)) { throw new RuntimeException('object not found after upload'); }
        $r2->delete($key);
        if ($r2->exists($key)) { throw new RuntimeException('object still there after delete'); }
    } finally { @unlink($tmp); }
    return 'bucket ' . Env::get('R2_BUCKET', '');
});

echo $failed === 0 ? "\nAll checks passed.\n" : "\n{$failed} check(s) failed.\n";
exit($failed === 0 ? 0 : 1);
