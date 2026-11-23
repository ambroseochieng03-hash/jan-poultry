<?php

require_once
__DIR__ .
'/../includes/init.php';

require_once
__DIR__ .
'/../app/helpers/Auth.php';

require_once
__DIR__ .
'/../app/controllers/OrderController.php';

require_once
__DIR__ .
'/../app/models/Notification.php';
require_once __DIR__ . '/../app/services/OrderNotifier.php';

require_once
__DIR__ .
'/../app/services/MailService.php';

require_once
__DIR__ .
'/../app/services/ReceiptService.php';

Auth::requireAdmin();

Security::requirePostWithCsrf('orders');



$orderId =
(int)(
    $_POST['order_id']
    ?? 0
);


$newStatus =
$_POST['status']
?? '';



if (
    !$orderId ||
    !$newStatus
) {

    header(
        'Location: orders'
    );

    exit;

}



$controller =
new OrderController();



$order =
$controller->getAdminOrder(
    $orderId
);



if (!$order) {

    header(
        'Location: orders'
    );

    exit;

}



$currentStatus =
$order['status'];



$allowedTransitions = [

    'pending' => [

        'accepted',
        'rejected'

    ],


    'accepted' => [

        'in_transit'

    ],


    'in_transit' => [

        'completed'

    ]

];



if (
    !isset(
        $allowedTransitions[$currentStatus]
    )
    ||
    !in_array(
        $newStatus,
        $allowedTransitions[$currentStatus],
        true
    )
) {


    header(
        'Location: order-view?id=' .
        $orderId
    );

    exit;

}



if ($newStatus === 'accepted') {

    $result =
    $controller->acceptOrder(
        $orderId
    );

    $updated =
    $result['success'];

} else {

    $updated =
    $controller->updateStatus(

        $orderId,

        $newStatus

    );

}

if ($newStatus === 'accepted') {

    $_SESSION['app_response'] = [

        'success' =>

            $result['success'],

        'title' =>

            $result['title']
            ??

            (
                $result['success']
                ? 'Order Accepted'
                : 'Unable To Accept Order'
            ),

        'message' =>

            $result['message'],

        'icon' =>

            $result['icon']
            ??

            (
                $result['success']
                ? '✓'
                : '!'
            ),

        'autoClose' =>

            $result['autoClose']
            ??

            5000

    ];

} else {

    $_SESSION['app_response'] = [

        'success' => $updated,

        'title' =>

            $updated
            ? 'Order Updated'
            : 'Order Update Failed',

        'message' =>

            $updated
            ? 'The order status was updated successfully.'
            : 'Unable to update the order status.'

    ];

}

// Emails go through the outbox queue; the in-app notification is saved here.
if ($updated) {
    OrderNotifier::statusChanged($orderId, $newStatus);
}

header(
    'Location: orders'
);

exit;