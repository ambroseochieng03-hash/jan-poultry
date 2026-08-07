<?php

require_once
__DIR__ .
'/includes/init.php';

require_once
__DIR__ .
'/app/models/Product.php';

$productModel =
    new Product();

/*
|--------------------------------------------------------------------------
| Latest Products
|--------------------------------------------------------------------------
*/

$products =
    $productModel->getLatestForHomepage(
        6
    );

/*
|--------------------------------------------------------------------------
| Session
|--------------------------------------------------------------------------
*/

$isLoggedIn =
    isset($_SESSION['customer']);

$isGuest =
    isset($_SESSION['guest']);

include
__DIR__ .
'/includes/header.php';

?>

<link
rel="stylesheet"
href="/jan-poultry/public/assets/css/admin-forgot-password.css">

<link
rel="stylesheet"
href="/jan-poultry/public/assets/css/auth-response-modal.css">

<main class="home-page">

    <!-- =======================================
    HERO
    ======================================== -->

    <section class="hero-section">

        <div class="hero-content">

            <span class="hero-badge">

                Fresh Every Day

            </span>

            <h1>

                Fresh Poultry.

                <span>

                    Trusted Farmers.

                </span>

                Delivered With Care.

            </h1>

            <p>

                Browse our latest poultry products,
                discover premium quality,
                and enjoy a smooth shopping experience
                whether you're a returning customer,
                a first-time visitor,
                or simply exploring as a guest.

            </p>

            <div class="hero-buttons">

                <a
                    href="#featured-products"
                    class="btn-primary">

                    Explore Products

                </a>

                <?php if (!$isLoggedIn): ?>

                <a
                    href="/jan-poultry/auth/guest-login.php"
                    class="btn-outline">

                    Continue as Guest

                </a>

                <?php else: ?>

                <a
                    href="/jan-poultry/customer/dashboard.php"
                    class="btn-outline">

                    Open Dashboard

                </a>

                <?php endif; ?>

            </div>

        </div>

    </section>

    <!-- =======================================
    FEATURED PRODUCTS
    ======================================= -->

    <section  id="featured-products" class="home-products">

        <div class="section-header">

            <div>

                <span class="section-tag">

                    Fresh From Our Farm

                </span>

                <h2>

                    Recently Added Products

                </h2>

                <p>

                    Discover some of our newest poultry products.
                    Create an account or continue as a guest to browse the full catalogue.

                </p>

            </div>

            <a
                href="/jan-poultry/auth/register.php"
                class="section-link">

                View Everything →

            </a>

        </div>

        <div class="home-product-grid">

            <?php foreach ($products as $product): ?>

                <article
                    class="home-product-card"

                    data-name="<?= htmlspecialchars($product['name']) ?>"

                    data-image="/jan-poultry/public/uploads/products/<?= htmlspecialchars($product['image']) ?>"

                    data-category="<?= htmlspecialchars($product['category']) ?>"

                    data-type="<?= htmlspecialchars($product['product_type']) ?>"

                    data-breed="<?= htmlspecialchars($product['breed']) ?>"

                    data-description="<?= htmlspecialchars($product['description']) ?>"

                    data-available="<?= $product['is_available'] ? 'Available' : 'Coming Soon' ?>">

                    <div class="product-image">

                        <?php if (
                            pathinfo(
                                $product['image'],
                                PATHINFO_EXTENSION
                            ) === 'mp4'
                        ): ?>

                            <video
                                autoplay
                                muted
                                loop
                                playsinline
                                preload="metadata">

                                <source
                                    src="/jan-poultry/public/uploads/products/<?= htmlspecialchars($product['image']) ?>"
                                    type="video/mp4">

                            </video>

                        <?php else: ?>

                            <img
                                src="/jan-poultry/public/uploads/products/<?= htmlspecialchars($product['image']) ?>"
                                alt="<?= htmlspecialchars($product['name']) ?>">

                        <?php endif; ?>

                        <span class="availability">

                            <?= $product['is_available']
                                ? 'Available'
                                : 'Coming Soon'
                            ?>

                        </span>

                    </div>

                    <div class="product-content">

                        <h3>

                            <?= htmlspecialchars($product['name']) ?>

                        </h3>

                        <p>

                            <?= htmlspecialchars($product['category']) ?>

                        </p>

                        <div class="product-tags">

                            <span>

                                <?= htmlspecialchars($product['breed']) ?>

                            </span>

                            <span>

                                <?= htmlspecialchars($product['product_type']) ?>

                            </span>

                        </div>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    </section>


    <!-- =======================================
    WHY CHOOSE US
    ======================================== -->

    <section class="why-section reveal">

        <div class="section-header">

            <h2>

                Why Customers Choose JAN Poultry

            </h2>

        </div>

        <div class="why-grid">

            <div class="why-card">

                🐔

                <h3>

                    Farm Fresh

                </h3>

                <p>

                    Carefully selected poultry
                    sourced from trusted farms.

                </p>

            </div>

            <div class="why-card">

                🚚

                <h3>

                    Fast Delivery

                </h3>

                <p>

                    Reliable deliveries to your
                    doorstep.

                </p>

            </div>

            <div class="why-card">

                🛡

                <h3>

                    Premium Quality

                </h3>

                <p>

                    Every product is inspected
                    before delivery.

                </p>

            </div>

            <div class="why-card">

                💬

                <h3>

                    Friendly Support

                </h3>

                <p>

                    We're always ready to help
                    when you need us.

                </p>

            </div>

        </div>

    </section>

    <!-- =======================================
    STATISTICS
    ======================================== -->

    <section class="home-stats reveal">

        <div class="stat-card">

            <h2
                class="counter"
                data-target="500">

                0

            </h2>

            <p>

                Deliveries

            </p>

        </div>

        <div class="stat-card">

            <h2
                class="counter"
                data-target="98">

                0

            </h2>

            <p>

                Satisfaction %

            </p>

        </div>

        <div class="stat-card">

            <h2
                class="counter"
                data-target="24">

                0

            </h2>

            <p>

                Support

            </p>

        </div>

        <div class="stat-card">

            <h2
                class="counter"
                data-target="<?= count($products) ?>">

                0

            </h2>

            <p>

                Featured Products

            </p>

        </div>

    </section>

    <!-- =======================================
    ACCOUNT OPTIONS
    ======================================== -->

    <section class="account-options reveal">

        <div class="section-header">

            <h2>

                Continue Your Journey

            </h2>

        </div>

        <div class="account-grid">

            <div class="account-card">

                <h3>

                    Already Shopping?

                </h3>

                <p>

                    Sign in and continue where
                    you left off.

                </p>

                <a
                    href="/jan-poultry/auth/login.php">

                    Sign In

                </a>

            </div>

            <div class="account-card">

                <h3>

                    New Here?

                </h3>

                <p>

                    Create your free customer
                    account today.

                </p>

                <a
                    href="/jan-poultry/auth/register.php">

                    Create Account

                </a>

            </div>

            <div class="account-card">

                <h3>

                    Just Exploring?

                </h3>

                <p>

                    Browse products before
                    creating an account.

                </p>

                <a
                    href="/jan-poultry/auth/guest-login.php">

                    Continue as Guest

                </a>

            </div>

        </div>

    </section>

    <!-- =======================================
    RECOVER ACCOUNT
    ======================================== -->

    <section class="recover-account reveal">

        <h2>

            Need Help Accessing Your Account?

        </h2>

        <p>

            Forgotten your password?
            We'll securely send a password reset
            link if your account exists.

        </p>

        <button

            id="openForgotPasswordModal"

            class="btn-primary">

            Recover My Account

        </button>

    </section>

    <!--=====================================
    FEATURED PRODUCT MODAL
    ======================================-->

    <div
        id="featuredProductModal"
        class="product-preview-modal">

        <div class="product-preview-card">

            <button
                class="preview-close"
                id="closePreviewModal">

                &times;

            </button>

            <div class="preview-image">

                <img
                    id="previewImage"
                    src=""
                    alt=""
                    style="display:none;">

                <video
                    id="previewVideo"
                    controls
                    autoplay
                    muted
                    loop
                    playsinline
                    style="display:none; width:100%; height:100%; object-fit:cover;">

                    <source
                        id="previewVideoSource"
                        src=""
                        type="video/mp4">

                </video>

            </div>

            <div class="preview-content">

                <span
                    class="preview-badge"
                    id="previewAvailability">

                </span>

                <h2 id="previewName">

                </h2>

                <p
                    class="preview-category"
                    id="previewCategory">

                </p>

                <p
                    class="preview-description"
                    id="previewDescription">

                </p>

                <div class="preview-tags">

                    <span id="previewBreed"></span>

                    <span id="previewType"></span>

                </div>

                <div class="preview-message">

                    <h3>

                        Continue Your Journey

                    </h3>

                    <p>

                        Create a free account to browse our complete catalogue, place orders, track deliveries and enjoy the full JAN Poultry experience.

                    </p>

                </div>

                <div class="preview-buttons">

                    <a
                        href="/jan-poultry/auth/register.php"
                        class="preview-primary">

                        Create Free Account

                    </a>

                    <a
                        href="/jan-poultry/auth/guest-login.php"
                        class="preview-secondary">

                        Continue as Guest

                    </a>

                    <a
                        href="/jan-poultry/auth/login.php"
                        class="preview-login">

                        Sign In

                    </a>

                </div>

                <button

                    id="recoverAccountButton"

                    class="recover-account-link">

                    Forgot your password?
                    Recover your account

                </button>

            </div>

        </div>

    </div>

</main>

<?php

include
__DIR__ .
'/includes/auth-response-modal.php';

?>

<?php

include
__DIR__ .
'/includes/admin-forgot-password-modal.php';

?>

<script>

window.addEventListener(

    'DOMContentLoaded',

    function () {

        const form =

            document.getElementById(
                'forgotPasswordForm'
            );

        if (form) {

            form.action =

                '/jan-poultry/auth/forgot-password-ajax.php';

        }

    }

);

</script>

<?php

include
__DIR__ .
'/includes/footer.php';

?>
