<?php

require_once
__DIR__ .
'/../includes/init.php';


require_once
__DIR__ .
'/../app/helpers/Auth.php';


require_once
__DIR__ .
'/../app/models/Notification.php';



Auth::requireLogin();



$notificationId =
(int)(
    $_POST['notification_id']
    ?? 0
);



if (!$notificationId) {

    header(
        'Location: notifications.php'
    );

    exit;

}



$model =
new Notification();



$model->delete(

    $notificationId,

    Auth::userId(),

    Auth::guestId()

);



header(
    'Location: notifications.php'
);

exit;