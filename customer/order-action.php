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
'/../app/controllers/OrderController.php';

Auth::requireLogin();

if (
    $_SERVER['REQUEST_METHOD']
    !== 'POST'
) {

    header(
        'Location: orders'
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
        'Location: orders'
    );

    exit;
}

$orderId =
(int)(
    $_POST['order_id']
    ?? 0
);

$action =
$_POST['action']
?? '';

$controller =
new OrderController();

$userId =
Auth::userId();

$guestId =
Auth::guestId();

switch ($action) {

    case 'cancel':

        $order =
        $controller->getSingleOrder(

            $orderId,

            $userId,

            $guestId

        );

        if (!$order) {

            Flash::set(
                'error',
                'Order not found.'
            );

            break;

        }

        if ($order['status'] === 'accepted') {

            $result =
            $controller
            ->cancelAcceptedOrder(
                $orderId
            );

            Flash::set(

                $result['success']
                ? 'success'
                : 'error',

                $result['message']

            );

        } else {

            $updated =
            $controller->cancelOrder(

                $orderId,

                $userId,

                $guestId

            );

            Flash::set(

                $updated
                ? 'success'
                : 'error',

                $updated
                ? 'Order cancelled successfully.'
                : 'Failed to cancel order.'

            );

        }

    break;

    case 'delete':

        $controller->deleteOrder(

            $orderId,

            $userId,

            $guestId

        );

    break;
}

header(
    'Location: orders'
);

exit;