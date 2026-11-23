<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/app/helpers/Auth.php';
require_once __DIR__ . '/app/helpers/PendingCart.php';
require_once __DIR__ . '/app/models/Product.php';
require_once __DIR__ . '/app/models/Category.php';

$products   = (new Product())->getForHome(48);
$categories = array_values(array_filter(
    (new Category())->forHome(),
    static fn ($c) => !in_array(strtolower(trim((string) $c)), ['', 'none', 'n/a', 'null'], true)
));
foreach ($products as &$jpProd) {
    if (in_array(strtolower(trim((string) ($jpProd['category'] ?? ''))), ['none', 'n/a', 'null'], true)) {
        $jpProd['category'] = '';
    }
}
unset($jpProd);

/* Contact details come from Admin > Settings. */
$settings = [];
foreach ($pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $settings[$row['setting_key']] = trim((string) $row['setting_value']);
}
$contactPhone   = (isset($settings['contact_phone']) && !str_contains($settings['contact_phone'], 'XXX')) ? $settings['contact_phone'] : '';
$contactEmail   = $settings['contact_email'] ?? '';
$contactAddress = $settings['contact_address'] ?? '';
$businessHours  = $settings['business_hours'] ?? '';

/* Who is looking at the page. */
$isCustomer = Auth::isCustomer();
$isGuest    = Auth::isGuest();
$isStaff    = Auth::isUser() && !$isCustomer;
$googleId   = (string) Env::get('GOOGLE_CLIENT_ID', '');

$cartCount = 0;
if ($isCustomer || $isGuest) {
    $q = $isCustomer
        ? $pdo->prepare('SELECT COALESCE(SUM(ci.quantity),0) FROM cart_items ci JOIN carts c ON c.id = ci.cart_id WHERE c.user_id = ?')
        : $pdo->prepare('SELECT COALESCE(SUM(ci.quantity),0) FROM cart_items ci JOIN carts c ON c.id = ci.cart_id WHERE c.guest_id = ?');
    $q->execute([$isCustomer ? Auth::userId() : Auth::guestId()]);
    $cartCount = (int) $q->fetchColumn();
} else {
    $cartCount = PendingCart::count();
}

$authNotice = '';
$authNoticeType = 'error';
foreach (['login_error', 'auth_error'] as $k) {
    if (!empty($_SESSION[$k])) { $authNotice = (string) $_SESSION[$k]; unset($_SESSION[$k]); }
}
if ($authNotice === '' && !empty($_SESSION['auth_success'])) {
    $authNotice = (string) $_SESSION['auth_success'];
    $authNoticeType = 'success';
    unset($_SESSION['auth_success']);
}
$anonymous = !$isCustomer && !$isGuest && !$isStaff;
$openAuth  = ($anonymous && in_array($_GET['auth'] ?? '', ['login', 'register', 'guest'], true)) ? $_GET['auth'] : ($authNotice !== '' && $anonymous ? 'login' : '');

$csrf    = Security::generateCsrfToken();
$baseUrl = APP_URL;
$title   = 'JAN Poultry | Live chicken and fresh chicken meat, delivered';
$desc    = 'Order live chicken and fresh chicken meat online from JAN Poultry. Browse what is available, order with or without an account and have it delivered to your address.';

$heroProduct = null;
foreach ($products as $p) {
    if (!empty($p['image']) && !Media::isVideo($p['image'])) { $heroProduct = $p; break; }
}

function jp_type_label(string $t): string
{
    return $t === 'live_chicken' ? 'Live chicken' : ($t === 'meat' ? 'Chicken meat' : '');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($desc) ?>">
    <meta name="theme-color" content="#0b4a24">
    <link rel="canonical" href="<?= htmlspecialchars($baseUrl) ?>/">
    <link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="JAN Poultry">
    <meta property="og:title" content="<?= htmlspecialchars($title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($desc) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($baseUrl) ?>/">
    <meta property="og:image" content="<?= htmlspecialchars($baseUrl) ?>/assets/img/og-image.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">
    <meta name="jp-media-base" content="<?= htmlspecialchars(Media::baseUrl()) ?>">
    <link rel="stylesheet" href="/assets/css/auth.css?v=<?= (int) @filemtime(__DIR__ . '/public/assets/css/auth.css') ?>">
    <link rel="stylesheet" href="/assets/css/jp-home.css?v=<?= (int) @filemtime(__DIR__ . '/public/assets/css/jp-home.css') ?>">
    <script src="/assets/js/jp-media.js?v=<?= (int) @filemtime(__DIR__ . '/public/assets/js/jp-media.js') ?>" defer></script>
    <script src="/assets/js/jp-home.js?v=<?= (int) @filemtime(__DIR__ . '/public/assets/js/jp-home.js') ?>" defer></script>
</head>
<body>

<header class="jp-nav" id="jpNav">
    <div class="jp-wrap jp-nav-row">
        <a href="/" class="jp-brand">
            <img src="/assets/img/logo.svg" alt="" width="36" height="36">
            <span>JAN Poultry</span>
        </a>
        <nav class="jp-links" aria-label="Main">
            <a href="#shop">Shop</a>
            <a href="#how">How it works</a>
            <a href="#contact">Contact</a>
        </nav>
        <div class="jp-actions">
            <?php if ($isCustomer || $isGuest): ?>
                <a class="jp-btn jp-btn-ghost" href="/cart">Cart<span class="jp-badge" data-cart-count <?= $cartCount ? '' : 'hidden' ?>><?= $cartCount ?></span></a>
                <?php if ($isCustomer): ?><a class="jp-btn jp-btn-ghost" href="/dashboard">My account</a><?php endif; ?>
                <a class="jp-btn jp-btn-line" href="/logout">Log out</a>
            <?php elseif ($isStaff): ?>
                <a class="jp-btn jp-btn-primary" href="/admin/dashboard">Admin panel</a>
            <?php else: ?>
                <button type="button" class="jp-btn jp-btn-ghost" data-auth="guest">Continue as guest<span class="jp-badge" data-cart-count <?= $cartCount ? '' : 'hidden' ?>><?= $cartCount ?></span></button>
                <a class="jp-btn jp-btn-line" href="/?auth=login" data-auth="login">Sign in</a>
                <a class="jp-btn jp-btn-primary" href="/?auth=register" data-auth="register">Create account</a>
            <?php endif; ?>
        </div>
        <button type="button" class="jp-burger" id="jpBurger" aria-label="Menu" aria-expanded="false" aria-controls="jpNav"><span></span></button>
    </div>
</header>

<div class="jp-modal jp-menu" id="jpMenu" role="dialog" aria-modal="true" aria-label="Menu" hidden>
    <div class="jp-modal-card">
        <button type="button" class="jp-modal-x" data-close-menu aria-label="Close">&times;</button>
        <nav class="jp-menu-links" aria-label="Menu">
            <a href="#shop">Shop</a>
            <a href="#how">How it works</a>
            <a href="#contact">Contact</a>
        </nav>
        <div class="jp-menu-actions">
            <?php if ($isCustomer || $isGuest): ?>
                <a class="jp-btn jp-btn-gold jp-btn-lg" href="/cart">Cart<span class="jp-badge" data-cart-count <?= $cartCount ? '' : 'hidden' ?>><?= $cartCount ?></span></a>
                <?php if ($isCustomer): ?><a class="jp-btn jp-btn-line jp-btn-lg" href="/dashboard">My account</a><?php endif; ?>
                <a class="jp-btn jp-btn-line jp-btn-lg" href="/logout">Log out</a>
            <?php elseif ($isStaff): ?>
                <a class="jp-btn jp-btn-primary jp-btn-lg" href="/admin/dashboard">Admin panel</a>
            <?php else: ?>
                <button type="button" class="jp-btn jp-btn-gold jp-btn-lg" data-auth="guest">Continue as guest<span class="jp-badge" data-cart-count <?= $cartCount ? '' : 'hidden' ?>><?= $cartCount ?></span></button>
                <a class="jp-btn jp-btn-line jp-btn-lg" href="/?auth=login" data-auth="login">Sign in</a>
                <a class="jp-btn jp-btn-primary jp-btn-lg" href="/?auth=register" data-auth="register">Create account</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<main>
    <section class="jp-hero">
        <img class="jp-hero-img" src="/assets/img/hero.webp" alt="" width="1672" height="392" fetchpriority="high">
        <div class="jp-wrap jp-hero-grid">
            <div class="jp-hero-copy">
                <h1>Fresh from our farm to your door.</h1>
                <p class="jp-lead">Live chicken and fresh chicken meat, ordered in minutes. No account needed to start.</p>
                <div class="jp-hero-cta">
                    <a class="jp-btn jp-btn-gold jp-btn-lg" href="#shop">Shop now</a>
                    <?php if (!$isCustomer && !$isGuest && !$isStaff): ?>
                        <a class="jp-btn jp-btn-outline jp-btn-lg" href="/?auth=guest" data-auth="guest">Continue as guest</a>
                    <?php else: ?>
                        <a class="jp-btn jp-btn-outline jp-btn-lg" href="#how">How it works</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="jp-trust" aria-label="Why order from us">
        <div class="jp-wrap">
            <ul class="jp-trust-grid">
                <li><span class="jp-ico"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2 4 5v6c0 5 3.4 9.4 8 11 4.6-1.6 8-6 8-11V5l-8-3Zm-1.2 14-3.6-3.6 1.4-1.4 2.2 2.2 4.6-4.6 1.4 1.4L10.8 16Z"/></svg></span><h3>Farm fresh</h3><p>Raised and handled with care</p></li>
                <li><span class="jp-ico"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 5h12v9h1.2l1.3-4H20l2 3v5h-2a2.5 2.5 0 0 1-5 0H9.5a2.5 2.5 0 0 1-5 0H2V5Zm15.2 6-.7 2H20l-1-2h-1.8ZM7 17a1 1 0 1 0 0 2 1 1 0 0 0 0-2Zm10 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2ZM0 7h4v2H0V7Zm1 4h3v2H1v-2Z"/></svg></span><h3>Delivered to you</h3><p>Straight to your address</p></li>
                <li><span class="jp-ico"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 2h3.5l.7 3H22l-2.4 9H7.4l.4 2H20v2H6.2L3 4H1V2Zm7 18a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3Zm9 0a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3Z"/></svg></span><h3>Easy ordering</h3><p>Add to order, no sign-up</p></li>
                <li><span class="jp-ico"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a8 8 0 0 0-8 8v6a3 3 0 0 0 3 3h1v-8H6v-1a6 6 0 0 1 12 0v1h-2v8h2v1h-5v2h5a3 3 0 0 0 3-3v-9a8 8 0 0 0-8-8Z"/></svg></span><h3>We are here to help</h3><p>Reach us from the footer</p></li>
            </ul>
        </div>
    </section>

    <section class="jp-section" id="shop">
        <div class="jp-wrap">
            <div class="jp-head">
                <h2>What is available</h2>
                <?php if ($isCustomer || $isGuest): ?><a class="jp-link" href="/products">Open the full shop</a><?php endif; ?>
            </div>

            <?php
            $types = [];
            foreach ($products as $p) { $types[(string) $p['product_type']] = true; }
            $tileImg = ['live_chicken' => '/assets/img/tile-live.webp', 'meat' => '/assets/img/tile-meat.webp'];
            if (count(array_intersect_key($types, $tileImg)) > 1): ?>
                <div class="jp-tiles">
                    <?php foreach ($tileImg as $t => $img): if (!isset($types[$t])) { continue; } ?>
                        <button type="button" class="jp-tile" data-type="<?= htmlspecialchars($t) ?>" aria-pressed="false"><img src="<?= $img ?>" alt="" loading="lazy"><span><?= htmlspecialchars(jp_type_label($t)) ?></span></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($categories): ?>
                <div class="jp-chips" role="tablist" aria-label="Categories">
                    <button type="button" class="jp-chip is-active" data-filter="*">All</button>
                    <?php foreach ($categories as $c): ?>
                        <button type="button" class="jp-chip" data-filter="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!$products): ?>
                <p class="jp-empty">Nothing is on sale right now. Please check back soon.</p>
            <?php else: ?>
            <div class="jp-grid" id="jpGrid">
                <?php foreach ($products as $p):
                    $stock = (int) $p['stock_quantity'];
                    $meta  = array_filter([
                        $p['breed'] ?: null,
                        $p['weight_kg'] !== null && (float) $p['weight_kg'] > 0 ? rtrim(rtrim(number_format((float) $p['weight_kg'], 2), '0'), '.') . ' kg' : null,
                        $p['age_weeks'] ? (int) $p['age_weeks'] . ' weeks' : null,
                    ]);
                ?>
                <article class="jp-card" data-type="<?= htmlspecialchars((string) $p['product_type']) ?>" data-category="<?= htmlspecialchars((string) $p['category']) ?>">
                    <div class="jp-card-img">
                        <?php if (!empty($p['image'])): ?>
                            <?php if (Media::isVideo($p['image'])): ?>
                                <img src="<?= htmlspecialchars(Media::posterUrl($p['image'])) ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
                            <?php else: ?>
                                <img src="<?= htmlspecialchars(Media::thumbUrl($p['image'])) ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
                            <?php endif; ?>
                        <?php else: ?>
                            <img class="jp-noimg" src="/assets/img/no-image.svg" alt="" loading="lazy">
                        <?php endif; ?>
                        <?php if (trim((string) $p['category']) !== ''): ?>
                            <span class="jp-tag"><?= htmlspecialchars($p['category']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="jp-card-body">
                        <p class="jp-kind"><?= htmlspecialchars(jp_type_label((string) $p['product_type'])) ?></p>
                        <h3><?= htmlspecialchars($p['name']) ?></h3>
                        <?php if ($meta): ?><p class="jp-meta"><?= htmlspecialchars(implode(' / ', $meta)) ?></p><?php endif; ?>
                        <div class="jp-card-foot">
                            <p class="jp-price">KES <?= number_format((float) $p['price']) ?></p>
                            <?php if ($stock <= 0): ?>
                                <button type="button" class="jp-btn jp-btn-disabled" disabled>Out of stock</button>
                            <?php else: ?>
                                <button type="button" class="jp-btn jp-btn-primary" data-add="<?= (int) $p['id'] ?>">Add to order</button>
                            <?php endif; ?>
                        </div>
                        <?php if ($stock > 0 && $stock <= 5): ?><p class="jp-low">Only <?= $stock ?> left</p><?php endif; ?>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <p class="jp-empty" id="jpNoMatch" hidden>No products in this category right now.</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="jp-section jp-alt" id="how">
        <div class="jp-wrap">
            <div class="jp-head"><h2>How ordering works</h2></div>
            <ol class="jp-steps">
                <li><span>1</span><h3>Choose</h3><p>Pick live chicken or fresh meat and add it to your order.</p></li>
                <li><span>2</span><h3>Confirm</h3><p>Continue as a guest or create an account. Your order is kept either way.</p></li>
                <li><span>3</span><h3>Receive</h3><p>We deliver to your address and you can follow the order status.</p></li>
            </ol>
        </div>
    </section>

    <?php if (!$isCustomer && !$isGuest && !$isStaff): ?>
    <section class="jp-section">
        <div class="jp-wrap jp-cta">
            <div>
                <h2>Order faster next time</h2>
                <p>An account keeps your details and order history, and you stay signed in on your device.</p>
            </div>
            <div class="jp-cta-actions">
                <a class="jp-btn jp-btn-primary jp-btn-lg" href="/?auth=register" data-auth="register">Create account</a>
                <a class="jp-btn jp-btn-line jp-btn-lg" href="/?auth=login" data-auth="login">Sign in</a>
            </div>
        </div>
    </section>
    <?php endif; ?>
</main>

<footer class="jp-footer" id="contact">
    <div class="jp-wrap jp-foot-grid">
        <div>
            <a href="/" class="jp-brand jp-brand-light"><img src="/assets/img/logo.svg" alt="" width="32" height="32"><span>JAN Poultry</span></a>
            <p>Live chicken and fresh chicken meat, delivered.</p>
        </div>
        <div>
            <h4>Contact</h4>
            <?php if ($contactPhone): ?><p><a href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', $contactPhone)) ?>"><?= htmlspecialchars($contactPhone) ?></a></p><?php endif; ?>
            <?php if ($contactEmail): ?><p><a href="mailto:<?= htmlspecialchars($contactEmail) ?>"><?= htmlspecialchars($contactEmail) ?></a></p><?php endif; ?>
            <?php if ($contactAddress): ?><p><?= htmlspecialchars($contactAddress) ?></p><?php endif; ?>
            <?php if ($businessHours): ?><p><?= htmlspecialchars($businessHours) ?></p><?php endif; ?>
        </div>
        <div>
            <h4>Account</h4>
            <p><a href="/?auth=login" data-auth="login">Sign in</a></p>
            <p><a href="/?auth=register" data-auth="register">Create account</a></p>
            <p><a href="/?auth=guest" data-auth="guest">Continue as guest</a></p>
        </div>
    </div>
    <div class="jp-wrap jp-copy">&copy; <?= date('Y') ?> JAN Poultry</div>
</footer>

<div class="jp-toasts" id="jpToasts" aria-live="polite"></div>

<?php if ($anonymous): ?>
<div class="jp-modal" id="authModal" role="dialog" aria-modal="true" aria-labelledby="authTitle" data-open="<?= htmlspecialchars($openAuth) ?>" hidden>
    <div class="jp-modal-card">
        <button type="button" class="jp-modal-x" data-close-auth aria-label="Close">&times;</button>
        <h2 id="authTitle">Welcome to JAN Poultry</h2>
        <p class="jp-modal-sub" id="authOrderNote" <?= $cartCount ? '' : 'hidden' ?>><strong data-cart-count-text><?= $cartCount ?></strong> item(s) in your order are saved. Sign in, register or continue as a guest. Nothing is lost.</p>
        <p class="jp-notice jp-notice-<?= $authNoticeType ?>" id="authNotice" <?= $authNotice === '' ? 'hidden' : '' ?>><?= htmlspecialchars($authNotice) ?></p>

        <div class="jp-tabs" role="tablist">
            <button type="button" role="tab" data-tab="login">Sign in</button>
            <button type="button" role="tab" data-tab="register">Create account</button>
            <button type="button" role="tab" data-tab="guest">Guest</button>
        </div>

        <form class="jp-form" data-panel="login" data-endpoint="/auth-ajax" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="action" value="login">
            <label>Email<input type="email" name="email" autocomplete="email" required></label>
            <label>Password<input type="password" name="password" autocomplete="current-password" required></label>
            <p class="jp-form-msg" role="alert" hidden></p>
            <button type="submit" class="jp-btn jp-btn-primary jp-btn-lg">Sign in</button>
            <button type="button" class="jp-textbtn" data-tab="forgot">Forgot password?</button>
        </form>

        <form class="jp-form" data-panel="register" data-endpoint="/auth-ajax" novalidate hidden>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="action" value="register">
            <label>Full name<input type="text" name="name" autocomplete="name" maxlength="100" required></label>
            <label>Email<input type="email" name="email" autocomplete="email" maxlength="150" required></label>
            <label>Phone<input type="tel" name="phone" autocomplete="tel" placeholder="0712345678" required></label>
            <label>Password (8+ characters)<input type="password" name="password" autocomplete="new-password" minlength="8" required></label>
            <p class="jp-form-msg" role="alert" hidden></p>
            <button type="submit" class="jp-btn jp-btn-primary jp-btn-lg">Create account</button>
        </form>

        <form class="jp-form" data-panel="guest" data-endpoint="/auth-ajax" novalidate hidden>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="action" value="guest">
            <label>Your name<input type="text" name="name" autocomplete="name" maxlength="60" required></label>
            <label>Phone<input type="tel" name="phone" autocomplete="tel" placeholder="0712345678" required></label>
            <p class="jp-form-msg" role="alert" hidden></p>
            <button type="submit" class="jp-btn jp-btn-primary jp-btn-lg">Continue as guest</button>
        </form>

        <form class="jp-form" data-panel="forgot" data-endpoint="/forgot-password-ajax" novalidate hidden>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <label>Account email<input type="email" name="email" autocomplete="email" required></label>
            <p class="jp-form-msg" role="alert" hidden></p>
            <button type="submit" class="jp-btn jp-btn-primary jp-btn-lg">Send reset link</button>
            <button type="button" class="jp-textbtn" data-tab="login">Back to sign in</button>
        </form>

        <?php if ($googleId !== ''): include __DIR__ . '/includes/google-signin.php'; endif; ?>
        <button type="button" class="jp-modal-keep" data-close-auth>Keep shopping</button>
    </div>
</div>
<?php endif; ?>

</body>
</html>
