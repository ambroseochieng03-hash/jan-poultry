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

Auth::requireLogin();

require_once
__DIR__ .
'/../app/helpers/Flash.php';


if (
    $_SERVER['REQUEST_METHOD']
    !== 'POST'
) {

    header(
        'Location: products'
    );

    exit;
}


if (
    !Security::verifyCsrfToken(
        $_POST['csrf_token']
        ?? ''
    )
) {

    Flash::set(
        'error',
        'Invalid security token. Please try again.'
    );

    header(
        'Location: products'
    );

    exit;
}


$productId =
(int)(
    $_POST['product_id']
    ?? 0
);

if ($productId <= 0) {

    header(
        'Location: products'
    );

    exit;
}

$cart =
new CartController();

$result =
$cart->addItem(

    Auth::userId(),

    Auth::guestId(),

    $productId

);

Flash::set(

    $result['success']
        ? 'success'
        : 'error',

    $result['message']

);


header(
    'Location: products'
);

exit;
