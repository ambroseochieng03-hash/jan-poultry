<?php

require_once
__DIR__ .
'/../app/config/app.php';
?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0">

<title>
<?= APP_NAME ?>
</title>

<link
rel="stylesheet"
href="/assets/css/global.css">

<link
rel="stylesheet"
href="/assets/css/navbar.css">

<link
rel="stylesheet"
href="/assets/css/footer.css">

<link
rel="stylesheet"
href="/assets/css/style.css">

<link
rel="stylesheet"
href="/assets/css/home.css">

<link
rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

<meta name="jp-media-base" content="<?= htmlspecialchars(Media::baseUrl()) ?>">
<script src="/assets/js/jp-media.js" defer></script>
<?php include __DIR__ . '/pwa-head.php'; ?>
</head>

<body>

<?php
include
__DIR__ .
'/navbar.php';
?>

