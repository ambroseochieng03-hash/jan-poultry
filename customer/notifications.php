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



$pageCss =
'notifications.css';



$notificationModel =
new Notification();



if (Auth::isUser()) {


    $notifications =
    $notificationModel
    ->getUserNotifications(
        Auth::userId()
    );


}
else {


    $notifications =
    $notificationModel
    ->getGuestNotifications(
        Auth::guestId()
    );


}



include
__DIR__ .
'/../includes/customer-header.php';


?>


<div class="jp-notifications-page">



    <div class="jp-notifications-header">


        <h1>

            Notifications

        </h1>


        <p>

            Stay connected with your JAN Poultry orders and updates.

        </p>


    </div>





    <?php if (empty($notifications)): ?>


        <div class="jp-notification-empty">


            <div class="jp-notification-empty-icon">

                🔔

            </div>


            <h3>

                No notifications yet

            </h3>


            <p>

                When you place an order, updates will appear here.

            </p>


        </div>



    <?php else: ?>





        <div class="jp-notifications-container">



        <?php foreach ($notifications as $notification): ?>



            <div class="jp-notification-card">



                <div class="jp-notification-top">


                    <div class="jp-notification-icon">

                        🔔

                    </div>


                    <div>


                        <h3>

                            <?= htmlspecialchars(
                                $notification['title']
                            ) ?>

                        </h3>


                        <?php if (
                            !empty(
                                $notification['order_id']
                            )
                        ): ?>


                            <span class="jp-notification-order">

                                Order #

                                <?= (int)
                                $notification['order_id'] ?>

                            </span>


                        <?php endif; ?>


                    </div>


                </div>





                <div class="jp-notification-message">


                    <?= nl2br(
                        htmlspecialchars(
                            $notification['message']
                        )
                    ) ?>


                </div>





                <div class="jp-notification-footer">



                    <small>

                        Received:

                        <?= date(
                            'd M Y, h:i A',
                            strtotime(
                                $notification['created_at']
                            )
                        ) ?>


                    </small>





                    <div class="jp-notification-actions">



                        <?php if (
                            !empty(
                                $notification['order_id']
                            )
                        ): ?>


                        <a
                        href="/order-view?id=<?= (int)$notification['order_id'] ?>"
                        class="jp-notification-view-btn">


                            View Order


                        </a>


                        <?php endif; ?>





                        <form
                        method="POST"
                        action="notification-delete">

                            <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">


                            <input
                            type="hidden"
                            name="notification_id"
                            value="<?= (int)$notification['id'] ?>">



                            <button
                            type="submit"
                            class="jp-notification-dismiss-btn">


                                Dismiss


                            </button>


                        </form>



                    </div>


                </div>



            </div>



        <?php endforeach; ?>


        </div>



    <?php endif; ?>



</div>



<?php

include
__DIR__ .
'/../includes/customer-footer.php';

?>