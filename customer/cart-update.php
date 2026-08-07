<?php

require_once
__DIR__ .
'/../includes/init.php';

require_once
__DIR__ .
'/../app/helpers/Auth.php';

require_once
__DIR__ .
'/../app/helpers/Security.php';

require_once
__DIR__ .
'/../app/helpers/Flash.php';

require_once
__DIR__ .
'/../app/controllers/CartController.php';

Auth::requireLogin();


if (
    $_SERVER['REQUEST_METHOD']
    !== 'POST'
) {

    header(
        'Location: cart.php'
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
        'Invalid security token.'
    );

    header(
        'Location: cart.php'
    );

    exit;
}


$action =
$_POST['action']
?? '';

$itemId =
(int)(
    $_POST['id']
    ?? 0
);


if (
    $itemId <= 0
) {

    Flash::set(
        'error',
        'Invalid cart item.'
    );

    header(
        'Location: cart.php'
    );

    exit;
}


$cart =
new CartController();

$userId =
Auth::userId();

$guestId =
Auth::guestId();

$result = false;


switch ($action) {


    case 'increase':

        $result =
        $cart->increaseQuantity(

            $userId,

            $guestId,

            $itemId

        );

        Flash::set(

            $result['success']
                ? 'success'
                : 'error',

            $result['message']

        );

    break;

    case 'decrease':

        if (
            $cart->decreaseQuantity(
                $userId,
                $guestId,
                $itemId
            )
        ) {

            Flash::set(
                'success',
                'Cart updated successfully.'
            );

        } else {

            Flash::set(
                'error',
                'Unable to decrease quantity.'
            );

        }

    break;



    case 'remove':

        if (
            $cart->removeItem(
                $userId,
                $guestId,
                $itemId
            )
        ) {

            Flash::set(
                'success',
                'Item removed from cart.'
            );

        } else {

            Flash::set(
                'error',
                'Unable to remove item.'
            );

        }

    break;



    default:

        Flash::set(
            'error',
            'Invalid cart action.'
        );

}

header(
    'Location: cart.php'
);

exit;