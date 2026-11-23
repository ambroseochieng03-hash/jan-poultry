<?php

require_once
__DIR__ .
'/../includes/init.php';

require_once
__DIR__ .
'/../app/helpers/Auth.php';

require_once
__DIR__ .
'/../app/helpers/Flash.php';

require_once
__DIR__ .
'/../app/models/Cart.php';

require_once
__DIR__ .
'/../app/models/Order.php';

/*echo '<pre>';

var_dump($_SESSION);

echo '</pre>';

exit; */

Auth::requireLogin();

$cartModel =
new Cart();

$orderModel =
new Order();

$userId =
Auth::userId();

$guestId =
Auth::guestId();

$items =
$cartModel->getItems(
    $userId,
    $guestId
);

$validTotal = 0;

foreach ($items as $item) {

    if (
        !$item['is_available']
    ) {
        continue;
    }

    if (
        $item['quantity']
        >
        $item['stock_quantity']
    ) {
        continue;
    }

    $validTotal +=
        (
            $item['price']
            *
            $item['quantity']
        );
}

if ($validTotal <= 0) {

    Flash::set(
        'error',
        'No valid products available for checkout.'
    );

    header(
        'Location: cart'
    );

    exit;
}

if (empty($items)) {

    header(
        'Location: cart'
    );

    exit;
}

$total = $validTotal;

$isGuest =
Auth::isGuest();


$customerName = '';
$customerEmail = '';
$customerPhone = '';
$deliveryAddress = '';

$orderCompleted = false;

$modalTitle = '';

$modalMessage = '';

if (
    $_SERVER['REQUEST_METHOD']
    === 'POST'
) {


    if (
        !Security::verifyCsrfToken(
            $_POST['csrf_token']
            ?? ''
        )
    ) {

        Flash::set(
            'error',
            'Invalid security token.'
        );

        header(
            'Location: checkout'
        );

        exit;
    }


    $deliveryAddress =
    trim(
        $_POST['delivery_address']
        ?? ''
    );


    if (
        empty($deliveryAddress)
    ) {

        Flash::set(
            'error',
            'Delivery address is required.'
        );

        header(
            'Location: checkout'
        );

        exit;
    }



    if ($isGuest) {


        $customerName =
        Auth::name();


        $customerEmail =
        trim(
            $_POST['email']
            ?? ''
        );


        $customerPhone =
        trim(
            $_POST['phone']
            ?? ''
        );


        if (
            empty($customerEmail)
            ||
            empty($customerPhone)
        ) {

            Flash::set(
                'error',
                'Email and phone number are required.'
            );

            header(
                'Location: checkout'
            );

            exit;
        }


    } else {


        $user =
        $orderModel
        ->getUserDetails(
            $userId
        );


        $customerName =
        $user['name'];


        $customerEmail =
        $user['email'];


        $customerPhone =
        $user['phone'];

    }

    $orderId =
    $orderModel->createOrder(

        $userId,

        $guestId,

        $customerName,

        $customerEmail,

        $customerPhone,

        $deliveryAddress,

        $total

    );

    foreach ($items as $item) {

        $orderModel
        ->addOrderItem(

            $orderId,

            $item['product_id'],

            $item['quantity'],

            $item['price']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | EMPTY CART
    |--------------------------------------------------------------------------
    */

    $cartId =
    $cartModel->getCartId(
        $userId,
        $guestId
    );

    $db =
    (new Database())
    ->getConnection();

    $stmt =
    $db->prepare(
        "DELETE
         FROM cart_items
         WHERE cart_id = ?"
    );

    $stmt->execute([
        $cartId
    ]);

    $orderCompleted = true;

    $modalTitle =
        'Order Received';

    $modalMessage =
        'Your order has been received successfully and is now being processed.';
}

$pageCss = 'checkout.css';

$pageJs = 'app-response-modal.js';

include
__DIR__ .
'/../includes/customer-header.php';
?>

<div class="jp-checkout-page">

    <div class="jp-checkout-card">

    <h1>

        Checkout

    </h1>

    <h3>

        Total:

        KSh

        <?= number_format(
            $total,
            2
        ) ?>

    </h3>

    <form method="POST"
    class="jp-checkout-form">


    <input
    type="hidden"
    name="csrf_token"
    value="<?= Security::generateCsrfToken() ?>">



    <?php if ($isGuest): ?>


    <label class="jp-checkout-label">
    Email
    </label>

    <input
    class="jp-checkout-input"
    type="email"
    name="email"
    required>



    <label>
    Phone
    </label>

    <input
    class="jp-checkout-input"
    type="text"
    name="phone"
    required>


    <?php endif; ?>



    <label style="color: black; font-weight: bold;">
    Delivery Address
    </label>

    <textarea
    class="jp-checkout-textarea"
    name="delivery_address"
    required></textarea>



    <button
    type="submit"
    class="jp-checkout-btn">

    Confirm Order

    </button>


    </form>

    </div>

</div>

<?php

include
__DIR__ .
'/../includes/app-response-modal.php';
?>

<?php if ($orderCompleted): ?>

<script>

document.addEventListener(
    'DOMContentLoaded',
    function ()
    {

        showAppResponseModal({

            icon: '✓',

            title: <?= json_encode($modalTitle) ?>,

            message: <?= json_encode($modalMessage) ?>,

            buttonText: 'OK',

            autoClose: 2000,

            redirect: 'orders'

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
