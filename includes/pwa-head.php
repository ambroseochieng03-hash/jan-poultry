<?php
/* PWA tags shared by every public, auth and customer page. Include once, inside <head>. */
$jpPwaJs  = (int) @filemtime(__DIR__ . '/../public/assets/js/pwa.js');
$jpPushJs = (int) @filemtime(__DIR__ . '/../public/assets/js/push.js');
$jpVapid  = '';
if (class_exists('WebPush', false) === false && is_file(__DIR__ . '/../app/services/WebPush.php')) {
    require_once __DIR__ . '/../app/services/WebPush.php';
}
$jpPushOk = class_exists('Auth', false) && class_exists('Security', false) && (Auth::isCustomer() || Auth::isGuest());
if ($jpPushOk) {
    $jpVapid = WebPush::publicKey();
}
?>
<link rel="manifest" href="/manifest.webmanifest">
<meta name="theme-color" content="#0b4a24">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="JAN Poultry">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="apple-touch-icon" href="/assets/icons/apple-touch-icon.png">
<script src="/assets/js/pwa.js?v=<?= $jpPwaJs ?>" defer></script>
<?php if ($jpPushOk && $jpVapid !== ''): ?>
<meta name="jp-vapid" content="<?= htmlspecialchars($jpVapid, ENT_QUOTES) ?>">
<meta name="jp-csrf" content="<?= htmlspecialchars(Security::generateCsrfToken(), ENT_QUOTES) ?>">
<script src="/assets/js/push.js?v=<?= $jpPushJs ?>" defer></script>
<?php endif; ?>
