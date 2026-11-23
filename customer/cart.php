<?php

require_once
__DIR__ .
'/../includes/init.php';

require_once
__DIR__ .
'/../app/helpers/Auth.php';

require_once
__DIR__ .
'/../app/controllers/CartController.php';

require_once
__DIR__ .
'/../app/helpers/Flash.php';

Auth::requireLogin();

$pageCss = 'cart.css';

$pageJs = 'app-response-modal.js';

include
__DIR__ .
'/../includes/customer-header.php';

$controller =
new CartController();

$items =
$controller->getItems(

    Auth::userId(),

    Auth::guestId()

);

$total = 0;

$flash =
Flash::get();

$hasCheckoutItems = false;

?>

<div class="jp-cart-page">

<h1>
    My Cart
</h1>


<?php if (empty($items)): ?>

<div class="jp-cart-empty">

    Your cart is empty.

</div>


<?php else: ?>


<?php foreach ($items as $item): ?>


<?php

$subtotal =
$item['price']
*
$item['quantity'];

if (
    (int)$item['is_available'] === 1
) {

    $total += $subtotal;

    $hasCheckoutItems = true;
}

?>


<div class="jp-cart-card">


    <div class="jp-cart-media">


        <?php if (!empty($item['image'])): ?>


            <?php if (
                pathinfo(
                    $item['image'],
                    PATHINFO_EXTENSION
                ) === 'mp4'
            ): ?>


            <video
            controls
            preload="metadata">

                <source
                src="<?= htmlspecialchars(Media::url($item['image'])) ?>"
                type="video/mp4">

            </video>


            <?php else: ?>


            <img
            src="<?= htmlspecialchars(Media::thumbUrl($item['image'])) ?>"
            alt="<?= htmlspecialchars($item['name']) ?>">


            <?php endif; ?>


        <?php else: ?>


            <div class="jp-cart-placeholder">

                <img src="/assets/img/no-image.svg" alt="" width="48" height="48">

            </div>


        <?php endif; ?>


    </div>


    <div class="jp-cart-details">


        <h2>

            <?= htmlspecialchars(
                $item['name']
            ) ?>

        </h2>


        <?php if (
            $item['product_type']
            === 'live_chicken'
        ): ?>


        <p>

            Breed:
            <?= htmlspecialchars(
                $item['breed'] ?? ''
            ) ?>

        </p>


        <p>

            Age:
            <?= (int)
            $item['age_weeks'] ?>
            weeks

        </p>


        <p>

            Weight:
            <?= htmlspecialchars(
                $item['weight_kg']
            ) ?>
            KG

        </p>

        <?php endif; ?>


        <?php if (
            !$item['is_available']
        ): ?>

        <p class="jp-unavailable">

            Currently unavailable

        </p>

        <?php endif; ?>


        <div class="jp-cart-controls">


            <form method="POST"
            action="cart-update">

            <input
            type="hidden"
            name="csrf_token"
            value="<?= Security::generateCsrfToken() ?>">

                <button
                type="submit"
                name="action"
                value="decrease"

                <?php if (
                    !$item['is_available']
                ): ?>

                disabled

                <?php endif; ?>>


                    -

                </button>


                <input
                type="hidden"
                name="id"
                value="<?= (int)$item['id'] ?>">


            </form>


            <span>

                <?= (int)$item['quantity'] ?>

            </span>


            <form method="POST"
            action="cart-update">

                <input
                type="hidden"
                name="csrf_token"
                value="<?= Security::generateCsrfToken() ?>">

                <button
                type="submit"
                name="action"
                value="increase"

                <?php if (
                    !$item['is_available']
                ): ?>

                disabled

                <?php endif; ?>>


                    +

                </button>


                <input
                type="hidden"
                name="id"
                value="<?= (int)$item['id'] ?>">


            </form>


            <form method="POST"
            action="cart-update">

            <input
            type="hidden"
            name="csrf_token"
            value="<?= Security::generateCsrfToken() ?>">


                <button
                type="submit"
                name="action"
                value="remove"
                class="jp-remove-btn">

                    Remove

                </button>


                <input
                type="hidden"
                name="id"
                value="<?= (int)$item['id'] ?>">


            </form>


        </div>


        <div class="jp-item-total">

        <?php if (
            $item['is_available']
        ): ?>

            Item Total:

            <strong>

                KSh
                <?= number_format(
                    $subtotal,
                    2
                ) ?>

            </strong>

        <?php else: ?>

            <span class="jp-item-excluded">

                Excluded from checkout total

            </span>

        <?php endif; ?>

        </div>



    </div>


</div>


<?php endforeach; ?>


<div class="jp-cart-summary">


    <h2>

        Cart Total

    </h2>


    <div class="jp-grand-total">

        KSh
        <?= number_format(
            $total,
            2
        ) ?>


    </div>

    <?php if ($hasCheckoutItems): ?>

    <a
    href="checkout"
    class="jp-checkout-btn">

        Proceed To Checkout

    </a>

    <?php else: ?>

    <div class="jp-no-checkout">

        No items are currently available for checkout.

    </div>

    <?php endif; ?>


</div>


<?php endif; ?>


</div>


<?php 
include
__DIR__ .
'/../includes/app-response-modal.php';
?>

<?php if ($flash): ?>

<script>

document.addEventListener(
    'DOMContentLoaded',
    function ()
    {

        showAppResponseModal({

            icon:
                <?= json_encode(
                    $flash['type'] === 'error'
                        ? '!'
                        : '✓'
                ) ?>,

            title:
                <?= json_encode(
                    $flash['type'] === 'error'
                        ? 'Attention'
                        : 'Success'
                ) ?>,

            message:
                <?= json_encode(
                    $flash['message']
                ) ?>,

            buttonText: 'OK',

            autoClose: 2000

        });

    }
);

</script>

<?php endif; ?>

<?php
include
__DIR__ .
'/../includes/customer-footer.php';
?>