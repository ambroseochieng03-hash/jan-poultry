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

Auth::requireAdmin();

$dashboard =
new DashboardController();

$totalProducts =
$dashboard
->getProductsCount();

$totalOrders =
$dashboard
->getAdminOrdersCount();

$totalCustomers =
$dashboard
->getCustomersCount();

$totalLogs =
$dashboard
->getLogsCount();

$totalDeliveries =
$dashboard
->getCompletedDeliveriesCount();

$totalAdmins =
$dashboard
->getAdminsCount();

include
__DIR__ .
'/../includes/admin-header.php';

?>

<h1>

Admin Dashboard

</h1>

<br>

<div class="admin-grid">

    <a
    href="products.php"
    class="admin-card">

        <h3>

            Products

        </h3>

        <div class="admin-number">

            <?= $totalProducts ?>

        </div>

    </a>

    <a
    href="orders.php"
    class="admin-card">

        <h3>

            Orders

        </h3>

        <div class="admin-number">

            <?= $totalOrders ?>

        </div>

    </a>

    <a
    href="users.php"
    class="admin-card">

        <h3>

            Customers

        </h3>

        <div class="admin-number">

            <?= $totalCustomers ?>

        </div>

    </a>

    <a
    href="logs.php"
    class="admin-card">

        <h3>

            Logs

        </h3>

        <div class="admin-number">

            <?= $totalLogs ?>

        </div>

    </a>

    <a
    href="deliveries.php"
    class="admin-card">

        <h3>

            Deliveries

        </h3>

        <div class="admin-number">

            <?= $totalDeliveries ?>

        </div>

    </a>

    <a
    href="admins.php"
    class="admin-card">

        <h3>

            Admins

        </h3>

        <div class="admin-number">

            <?= $totalAdmins ?>

        </div>

    </a>

</div>

<?php

include
__DIR__ .
'/../includes/admin-footer.php';
?>
