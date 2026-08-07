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

Auth::requireAdmin();

$pageCss =
'admin-deliveries.css';

$controller =
new OrderController();

$completedOrders =
$controller->getCompletedOrders();

$totalOrders =
$controller->getCompletedOrdersCount();

$totalRevenue =
$controller->getTotalRevenue();

$todayRevenue =
$controller->getTodayRevenue();

$monthRevenue =
$controller->getMonthRevenue();

include
__DIR__ .
'/../includes/admin-header.php';

?>

<div class="jp-delivery-page">

    <div class="jp-delivery-header">

        <h1>
            Deliveries & Sales
        </h1>

        <p>
            Completed deliveries and business performance overview.
        </p>

    </div>

    <div class="jp-delivery-stats">

        <div class="jp-delivery-stat-card">

            <span>
                Completed Orders
            </span>

            <h2>
                <?= $totalOrders ?>
            </h2>

        </div>

        <div class="jp-delivery-stat-card">

            <span>
                Total Revenue
            </span>

            <h2>

                KSh

                <?= number_format(
                    $totalRevenue,
                    2
                ) ?>

            </h2>

        </div>

        <div class="jp-delivery-stat-card">

            <span>
                Today's Revenue
            </span>

            <h2>

                KSh

                <?= number_format(
                    $todayRevenue,
                    2
                ) ?>

            </h2>

        </div>

        <div class="jp-delivery-stat-card">

            <span>
                This Month
            </span>

            <h2>

                KSh

                <?= number_format(
                    $monthRevenue,
                    2
                ) ?>

            </h2>

        </div>

    </div>

    <?php if (empty($completedOrders)): ?>

        <div class="jp-delivery-empty">

            <h3>
                No completed deliveries yet
            </h3>

            <p>
                Completed orders will appear here once deliveries are finalized.
            </p>

        </div>

    <?php else: ?>

        <div class="jp-delivery-orders-grid">

            <?php foreach ($completedOrders as $order): ?>

                <div class="jp-delivery-order-card">

                    <div class="jp-delivery-order-top">

                        <h3>

                            Order
                            #<?= (int)$order['id'] ?>

                        </h3>

                        <span
                        class="jp-delivery-status">

                            Completed

                        </span>

                    </div>

                    <div class="jp-delivery-info">

                        <strong>
                            Customer
                        </strong>

                        <p>

                            <?= htmlspecialchars(
                                $order['customer_name']
                                ?: 'Guest Customer'
                            ) ?>

                        </p>

                    </div>

                    <div class="jp-delivery-info">

                        <strong>
                            Email
                        </strong>

                        <p>

                            <?= htmlspecialchars(
                                $order['customer_email']
                                ?: '-'
                            ) ?>

                        </p>

                    </div>

                    <div class="jp-delivery-info">

                        <strong>
                            Phone
                        </strong>

                        <p>

                            <?= htmlspecialchars(
                                $order['customer_phone']
                                ?: '-'
                            ) ?>

                        </p>

                    </div>

                    <div class="jp-delivery-info">

                        <strong>
                            Completed On
                        </strong>

                        <p>

                            <?= htmlspecialchars(
                                $order['completed_at']
                            ) ?>

                        </p>

                    </div>

                    <div class="jp-delivery-info">

                        <strong>
                            Delivery Address
                        </strong>

                        <p>

                            <?= nl2br(
                                htmlspecialchars(
                                    $order['delivery_address']
                                )
                            ) ?>

                        </p>

                    </div>

                    <div class="jp-delivery-total">

                        KSh

                        <?= number_format(
                            $order['total_price'],
                            2
                        ) ?>

                    </div>

                    <a
                    href="order-view.php?id=<?= $order['id'] ?>"
                    class="jp-delivery-view-btn">

                        View Order

                    </a>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>

<?php

include
__DIR__ .
'/../includes/admin-footer.php';

?>