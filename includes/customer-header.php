<?php

require_once
__DIR__ . '/../app/config/app.php';

require_once
__DIR__ . '/../app/helpers/Auth.php';

Auth::requireLogin();

$userName = Auth::name();

$isGuest = Auth::isGuest();

?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0">

<title>
Customer Portal - <?= APP_NAME ?>
</title>

<link
rel="stylesheet"
href="/assets/css/global.css">

<link
rel="stylesheet"
href="/assets/css/customer.css">

<link
rel="stylesheet"
href="/assets/css/style.css">

<link
rel="stylesheet"
href="/assets/css/components.css">

<link
rel = "stylesheet"
href = "/assets/css/app-response-modal.css">

<?php if (isset($pageCss)): ?>

<link
rel="stylesheet"
href="/assets/css/<?= htmlspecialchars($pageCss) ?>">

<?php endif; ?>

<meta name="jp-media-base" content="<?= htmlspecialchars(Media::baseUrl()) ?>">
<script src="/assets/js/jp-media.js" defer></script>
<?php include __DIR__ . '/pwa-head.php'; ?>
</head>

<body>

<div class="customer-layout">

    <?php
    include
    __DIR__ .
    '/customer-sidebar.php';
    ?>

    <main class="customer-main">

        <div class="customer-topbar">

            <button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>

            <div>

                <h2>
                    Welcome,
                    <?= htmlspecialchars($userName) ?>
                </h2>

                <?php if ($isGuest): ?>

                    <span class="badge badge-warning">
                        Guest Account
                    </span>

                <?php else: ?>

                    <span class="badge badge-success">
                        Registered Customer
                    </span>

                <?php endif; ?>

            </div>

        </div>