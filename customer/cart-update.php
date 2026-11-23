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

/*
| Plain form posts still redirect back to the cart. Requests from cart.js
| (X-Requested-With: fetch, or Accept: application/json) get JSON instead.
*/
function jp_cart_finish(): never
{
    $ajax = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch')
        || str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');

    if ($ajax) {
        $flash = Flash::get();
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode([
            'success' => ($flash['type'] ?? '') === 'success',
            'message' => (string) ($flash['message'] ?? ''),
        ]);
        exit;
    }

    header('Location: cart');
    exit;
}

Auth::requireLogin();


if (
    $_SERVER['REQUEST_METHOD']
    !== 'POST'
) {

    jp_cart_finish();
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

    jp_cart_finish();
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

    jp_cart_finish();
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

jp_cart_finish();