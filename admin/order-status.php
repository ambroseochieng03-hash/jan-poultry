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

$notification =
new Notification();

$mailService =
new MailService();

$receiptService =
new ReceiptService();

if ($updated) {

    $notification =
    new Notification();

    $title = '';
    $message = '';
    $type = $newStatus;

    switch ($newStatus) {

        case 'accepted':

            $title =
            'Order Confirmed';

            $message =
            'Thank you for choosing JAN Poultry. '
            .
            'Your Order #'
            .
            $orderId
            .
            ' has been approved and is now being prepared by our team.';

            break;


        case 'in_transit':

            $title =
            'Your Order Is On The Way';

            $message =
            'Great news. Your Order #'
            .
            $orderId
            .
            ' is now on its way to your delivery location.';

            break;


        case 'completed':

        $title =
        'Delivery Completed';

        $message =
        'Your order has been successfully delivered.

        Attached to this email is your official JAN Poultry receipt for record keeping.

        Thank you for trusting JAN Poultry. We appreciate your support and look forward to serving you again.';

        break;


        case 'rejected':

            $title =
            'Order Update';

            $message =
            'Unfortunately, Order #'
            .
            $orderId
            .
            ' could not be processed. '
            .
            'Please contact JAN Poultry if you need assistance.';

            break;
    }

    $attachmentPath =
    null;

    if (
        $newStatus === 'completed'
    ) {

        $attachmentPath =
        $receiptService->generate(
            $orderId
        );

    }

    $notification->create(

        $order['user_id']
        ?? null,

        $order['guest_id']
        ?? null,

        $orderId,

        $type,

        $title,

        $message

    );

    $email =
    $order['customer_email']
    ?? '';

    $name =
    $order['customer_name']
    ?? 'Customer';

    $html = '

    <div
    style="
    max-width:700px;
    margin:auto;
    background:#ffffff;
    font-family:Arial,sans-serif;
    ">

        <div
        style="
        background:#07111d;
        padding:30px;
        text-align:center;
        ">

            <h1
            style="
            margin:0;
            color:#d4af37;
            ">

            JAN POULTRY

            </h1>

            <p
            style="
            color:#ffffff;
            margin-top:10px;
            ">

            Fresh Poultry Products & Delivery

            </p>

        </div>

        <div
        style="
        padding:30px;
        ">

            <h2
            style="
            color:#07111d;
            ">

            ' .
            htmlspecialchars($title) .
            '

            </h2>

            <p
            style="
            color:#444;
            line-height:1.8;
            ">

            ' .
            nl2br(
                htmlspecialchars(
                    $message
                )
            ) .
            '

            </p>

            <div
            style="
            background:#f8f8f8;
            padding:20px;
            border-left:4px solid #d4af37;
            margin-top:20px;
            ">

                Order Number:
                <strong>

                #' .
                $orderId .
                '

                </strong>

            </div>

        </div>

        <div
        style="
        background:#07111d;
        color:#ffffff;
        text-align:center;
        padding:20px;
        ">

            Thank you for choosing JAN Poultry.

        </div>

    </div>

    ';

    if (
        !empty($email)
    ) {

        $mailService->send(

            $email,

            $name,

            $title,

            $html,

            $attachmentPath

        );

    }
}


header(
    'Location: orders'
);

exit;