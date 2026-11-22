<?php

declare(strict_types=1);

/* Copies existing local product media to R2 (run once, safe to repeat).
 *   php tools/migrate-media-to-r2.php [--dir=/path/to/products] [--dry-run]
 * Default folder: public/uploads/products. Existing R2 objects are skipped. */

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

require_once __DIR__ . '/../app/config/env.php';
Env::load();
require_once __DIR__ . '/../app/services/R2Client.php';

$dir = __DIR__ . '/../public/uploads/products';
$dry = false;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--dir=')) { $dir = substr($arg, 6); }
    if ($arg === '--dry-run') { $dry = true; }
}

if (!is_dir($dir)) { fwrite(STDERR, "Folder not found: {$dir}\n"); exit(1); }

$types = ['webp' => 'image/webp', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'mp4' => 'video/mp4'];
$r2 = R2Client::fromEnv();
$up = $skip = $bad = 0;

foreach (scandir($dir) ?: [] as $name) {
    $path = $dir . '/' . $name;
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!is_file($path) || !isset($types[$ext]) || preg_match('/^[A-Za-z0-9._-]+$/', $name) !== 1) { continue; }
    try {
        if ($r2->exists('products/' . $name)) { $skip++; echo "skip   {$name}\n"; continue; }
        if (!$dry) { $r2->putFile('products/' . $name, $path, $types[$ext]); }
        $up++; echo ($dry ? 'would  ' : 'upload ') . $name . "\n";
    } catch (Throwable $e) { $bad++; echo "FAIL   {$name}: " . $e->getMessage() . "\n"; }
}

echo "\n{$up} uploaded, {$skip} already there, {$bad} failed.\n";
exit($bad === 0 ? 0 : 1);
