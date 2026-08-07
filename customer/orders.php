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

Auth::requireLogin();

$pageCss = 'orders.css';

include
__DIR__ .
'/../includes/customer-header.php';

$controller =
new OrderController();

$orders =
$controller->getUserOrders(

    Auth::userId(),

    Auth::guestId()

);

?>

<div class="orders-page">

<h1>

My Orders

</h1>

<?php if (empty($orders)): ?>

<div class="empty-orders">

    You have not placed any orders yet.

</div>

<?php else: ?>

<?php foreach ($orders as $order): ?>   

<?php

$statusClass = match (
    $order['status']
) {

    'pending'
        => 'jp-status-pending',

    'accepted'
        => 'jp-status-accepted',

    'in_transit'
        => 'jp-status-transit',

    'completed'
        => 'jp-status-completed',

    'rejected'
        => 'jp-status-rejected',

    'cancelled'
        => 'jp-status-cancelled',

    default
        => 'jp-status-pending'
};

?>    

<div class="jp-order-card">

    <div class="jp-order-header">

        <h3>

            Order #<?= (int)$order['id'] ?>

        </h3>

        <span
        class="jp-status-badge <?= $statusClass ?>">

            <?= ucfirst(
                str_replace(
                    '_',
                    ' ',
                    $order['status']
                )
            ) ?>

        </span>

    </div>

    <div class="jp-order-body">

        <div class="jp-order-row">

            <span>

                Ordered On

            </span>

            <strong>

                <?= htmlspecialchars(
                    $order['created_at']
                ) ?>

            </strong>

        </div>

        <div class="jp-order-row">

            <span>

                Total Amount

            </span>

            <strong>

                KSh
                <?= number_format(
                    $order['total_price'],
                    2
                ) ?>

            </strong>

        </div>

        <?php if (
            !empty(
                $order['delivery_address']
            )
        ): ?>

        <div class="jp-order-address">

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

        <?php endif; ?>

    </div>

    <div class="jp-order-actions">

        <a
            href="order-view.php?id=<?= (int)$order['id'] ?>"
            class="jp-view-order-btn">

            View Order →

        </a>

        <?php if (
            in_array(
                $order['status'],
                ['pending', 'accepted'],
                true
            )
        ): ?>

            <form method="POST" action="order-action.php">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= Security::generateCsrfToken() ?>">

                <input
                    type="hidden"
                    name="order_id"
                    value="<?= (int)$order['id'] ?>">

                <input
                    type="hidden"
                    name="action"
                    value="cancel">

                <button
                    type="submit"
                    class="jp-cancel-btn">

                    Cancel Order

                </button>

            </form>

        <?php endif ?>   

        <?php if (

            in_array(

                $order['status'],

                [
                    'completed',
                    'rejected',
                    'cancelled'
                ],

                true

            )

        ): ?>

        <form
            method="POST"
            action="order-action.php">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= Security::generateCsrfToken() ?>">

            <input
                type="hidden"
                name="order_id"
                value="<?= (int)$order['id'] ?>">

            <input
                type="hidden"
                name="action"
                value="delete">

            <button
                type="submit"
                class="jp-delete-btn">

                Delete Order

            </button>

        </form>

        <?php endif; ?>

    </div>

        

</div>

<?php endforeach; ?>

<?php endif; ?>

</div>

<?php

include
__DIR__ .
'/../includes/customer-footer.php';
?>