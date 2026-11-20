<?php

require_once
__DIR__ .
'/../includes/init.php';

require_once
__DIR__ .
'/../app/controllers/ProductController.php';

$pageCss =
'product-details-modal.css';

$pageJs =
'product-details-modal.js';

include
__DIR__ .
'/../includes/customer-header.php';

$controller =
new ProductController();

$products = $controller->getAll();

require_once
__DIR__ .
'/../app/helpers/Flash.php';

$flash =
Flash::get();

?>

<div class="products-header">

    <h1>
        Premium Poultry Products
    </h1>

    <p>
        Browse live chickens and
        fresh poultry products.
    </p>

</div>

<?php if ($flash): ?>

<div class="customer-modal-overlay"
id="flashModal">

    <div class="customer-modal">

        <div class="modal-icon">

            <?=
            $flash['type'] === 'success'
            ? '✅'
            : '⚠️'
            ?>

        </div>


        <h2>

            <?= htmlspecialchars(
                ucfirst($flash['type'])
            ) ?>

        </h2>


        <p>

            <?= htmlspecialchars(
                $flash['message']
            ) ?>

        </p>


        <div class="modal-actions">


            <?php if (
                $flash['type']
                === 'success'
            ): ?>

            <a
            href="cart"
            class="btn btn-primary">

                View Cart

            </a>

            <?php endif; ?>


            <button
            type="button"
            id="closeFlashModalBtn"
            class="btn btn-outline">

                <?= 
                $flash['type']
                === 'success'
                ? 'Continue Shopping'
                : 'Okay'
                ?>

            </button>


        </div>

    </div>

</div>

<?php endif; ?>

<div class="products-grid">

<?php foreach ($products as $product): ?>

    <div class="product-card">

        <div class="product-image">

            <?php if (!empty($product['image'])): ?>

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
            controls
            preload="metadata">
                <source
                src="/uploads/products/<?= htmlspecialchars($product['image']) ?>"
                type="video/mp4">
            </video>

            <?php else: ?>

            <img
            src="/uploads/products/<?= htmlspecialchars($product['image']) ?>"
            alt="<?= htmlspecialchars($product['name']) ?>">

            <?php endif; ?>

            <?php else: ?>

            <div class="product-placeholder">
            🐔
            </div>

            <?php endif; ?>

        </div>

        <div class="product-body">

            <h3>

                <?= htmlspecialchars(
                    $product['name']
                ) ?>

            </h3>

            <p class="product-description">

                <?= htmlspecialchars(
                    $product['description']
                ) ?>

            </p>

            <?php if (
                $product['product_type']
                === 'live_chicken'
            ): ?>

                <div class="product-meta">

                    Age:
                    <?= (int)
                    $product['age_weeks'] ?>
                    weeks

                </div>

                <div class="product-meta">

                    Weight:
                    <?= htmlspecialchars(
                        $product['weight_kg']
                    ) ?>
                    kg

                </div>

            <?php endif; ?>

            <div class="product-price">

                KSh
                <?= number_format(
                    $product['price'],
                    2
                ) ?>

            </div>

            <div class="product-stock">

            <?php if (
                (int)$product['stock_quantity'] > 0
            ): ?>

                Stock:
                <?= (int)$product['stock_quantity'] ?>

            <?php else: ?>

                <span class="out-of-stock">

                    Out Of Stock

                </span>

            <?php endif; ?>

            </div>

            <div class="product-actions">

                <a
                href="#"
                class="btn btn-outline details-btn"

                data-product-id="<?= (int) $product['id'] ?>"

                data-name="<?= htmlspecialchars($product['name']) ?>"

                data-description="<?= htmlspecialchars($product['description']) ?>"

                data-image="<?= htmlspecialchars($product['image']) ?>"

                data-price="<?= number_format($product['price'], 2) ?>"

                data-stock="<?= (int) $product['stock_quantity'] ?>"

                data-available="<?= (int) $product['is_available'] ?>"

                data-product-type="<?= htmlspecialchars($product['product_type']) ?>"

                data-age="<?= (int) $product['age_weeks'] ?>"

                data-weight="<?= htmlspecialchars($product['weight_kg']) ?>"

                data-csrf="<?= Security::generateCsrfToken() ?>">

                    Details

                </a>


                <?php if (

                    $product['is_available']
                    &&
                    (int)$product['stock_quantity'] > 0

                ): ?>

                <form
                method="POST"
                action="add-to-cart">

                    <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= Security::generateCsrfToken() ?>">


                    <input
                    type="hidden"
                    name="product_id"
                    value="<?= (int)$product['id'] ?>">


                    <button
                    type="submit"
                    class="add-cart-btn">

                        Add To Cart

                    </button>

                </form>


                <?php else: ?>


                <button
                type="button"
                class="add-cart-btn disabled"
                disabled>

                    <?php if (
                        (int)$product['stock_quantity'] <= 0
                    ): ?>

                        Out Of Stock

                    <?php else: ?>

                        Currently Unavailable

                    <?php endif; ?>

                </button>


                <?php endif; ?>


            </div>

        </div>

    </div>

<?php endforeach; ?>

</div>

<?php

include
__DIR__ .
'/../includes/product-details-modal.php';

include
__DIR__ .
'/../includes/customer-footer.php';

?>
