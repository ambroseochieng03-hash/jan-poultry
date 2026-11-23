<?php
declare(strict_types=1);

/*
| Prints a new VAPID key pair as .env lines. Run once, on the server:
|   sudo grep -q '^VAPID_PRIVATE_KEY=' .env || sudo php tools/make-vapid.php | sudo tee -a .env >/dev/null
| The private key goes straight into .env and is never shown on screen.
*/
if (PHP_SAPI !== 'cli') {
    exit(1);
}
require_once __DIR__ . '/../app/services/WebPush.php';

$pair = WebPush::generateKeyPair();
echo "\n# Web push (VAPID). Never share the private key.\n";
echo 'VAPID_PUBLIC_KEY=' . WebPush::b64u($pair['public']) . "\n";
echo 'VAPID_PRIVATE_KEY=' . WebPush::b64u($pair['private']) . "\n";
echo "VAPID_SUBJECT=mailto:admin@janpoultry.luxempire.co.ke\n";
