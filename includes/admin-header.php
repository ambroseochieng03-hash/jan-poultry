<?php

require_once
__DIR__ . '/../app/config/app.php';

require_once
__DIR__ . '/../app/helpers/Auth.php';

Auth::requireAdmin();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0">

<title>

Admin Panel - <?= APP_NAME ?>

</title>

<link
rel="stylesheet"
href="/assets/css/admin.css">

<link
rel="stylesheet"
href="/assets/css/products.css">

<link
rel="stylesheet"
href="/assets/css/app-response-modal.css">

<?php if (isset($pageCss)): ?>

<link
rel="stylesheet"
href="/assets/css/<?= htmlspecialchars($pageCss) ?>">

<?php endif; ?>

<meta name="jp-media-base" content="<?= htmlspecialchars(Media::baseUrl()) ?>">
<script src="/assets/js/jp-media.js" defer></script>
</head>

<body>

<header class="jp-admin-navbar">

    <button
    id="menuToggle"
    class="jp-admin-menu-toggle">

        ☰

    </button>

    <div class="jp-admin-navbar-title">

        JAN POULTRY ADMIN

    </div>

</header>

<div class="admin-layout">

    <?php
    include
    __DIR__ .
    '/admin-sidebar.php';
    ?>

    <main class="admin-main">
