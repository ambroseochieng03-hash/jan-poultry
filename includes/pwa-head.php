<?php
/* PWA tags shared by every public, auth and customer page. Include once, inside <head>. */
$jpPwaJs = (int) @filemtime(__DIR__ . '/../public/assets/js/pwa.js');
?>
<link rel="manifest" href="/manifest.webmanifest">
<meta name="theme-color" content="#0b4a24">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="JAN Poultry">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="apple-touch-icon" href="/assets/icons/apple-touch-icon.png">
<script src="/assets/js/pwa.js?v=<?= $jpPwaJs ?>" defer></script>
