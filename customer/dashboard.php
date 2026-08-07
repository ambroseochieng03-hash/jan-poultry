<?php

require_once
__DIR__ .
'/../includes/init.php';

require_once
__DIR__ .
'/../app/helpers/Auth.php';

require_once
__DIR__ .
'/../app/controllers/DashboardController.php';

Auth::requireLogin();

$userId =
Auth::userId();

$guestId =
Auth::guestId();

$isGuest =
Auth::isGuest();

$controller =
new DashboardController();

$ordersCount =
$controller->getOrdersCount(
    $userId,
    $guestId
);

$cartItemsCount =
$controller->getCartItemsCount(
    $userId,
    $guestId
);

$notificationsCount =
$controller->getNotificationsCount(

    $userId,

    $guestId

);

include
__DIR__ .
'/../includes/customer-header.php';

?>

<div class="dashboard-grid">

    <a
    href="orders.php"
    class="dashboard-card">

        <h3>
            Orders
        </h3>

        <div class="dashboard-number">

            <?= $ordersCount ?>

        </div>

    </a>

    <a
    href="cart.php"
    class="dashboard-card">

        <h3>
            Cart Items
        </h3>

        <div class="dashboard-number">

            <?= $cartItemsCount ?>

        </div>

    </a>

    <a
    href="notifications.php"
    class="dashboard-card">

        <h3>
            Notifications
        </h3>

        <div class="dashboard-number">

            <?= $notificationsCount ?>

        </div>

    </a>

    <a
    href="profile.php"
    class="dashboard-card">

        <h3>
            Account Type
        </h3>

        <div class="dashboard-number">

            <?= $isGuest
                ? 'Guest'
                : 'Member' ?>

        </div>

    </a>

</div>

<?php

include
__DIR__ .
'/../includes/customer-footer.php';
?>