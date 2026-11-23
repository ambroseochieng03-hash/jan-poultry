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

$pageCss = 'order-view.css';

$orderId =
(int)(
    $_GET['id']
    ?? 0
);

$controller =
new OrderController();

$order =
$controller->getSingleOrder(

    $orderId,

    Auth::userId(),

    Auth::guestId()

);


include
__DIR__ .
'/../includes/customer-header.php';


?>

<div class="jp-order-view-page">


<?php if (!$order): ?>


<div class="jp-order-view-empty">

    Order not found.

</div>


<?php else: ?>


<?php

$items =
$controller->getOrderItems(
    $orderId
);


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


<div class="jp-order-view-card">


<div class="jp-order-view-header">


<h1>

Order #<?= (int)$order['id'] ?>

</h1>


<span class="jp-status-badge <?= $statusClass ?>">

<?= ucfirst(
    str_replace(
        '_',
        ' ',
        $order['status']
    )
) ?>

</span>


</div>



<div class="jp-order-view-section">


<h3>

Order Information

</h3>


<div class="jp-order-view-row">

<span>
Ordered On
</span>


<strong>

<?= htmlspecialchars(
    $order['created_at']
) ?>

</strong>


</div>



<div class="jp-order-view-row">

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


</div>




<div class="jp-order-view-section">


<h3>

Delivery Information

</h3>


<p>

<?= nl2br(
    htmlspecialchars(
        $order['delivery_address']
    )
) ?>

</p>


</div>




<div class="jp-order-view-section">


<h3>

Items Ordered

</h3>


<?php foreach ($items as $item): ?>


<div class="jp-order-item-card">


<?php if (!empty($item['image'])): ?>


<?php if (
    pathinfo(
        $item['image'],
        PATHINFO_EXTENSION
    ) === 'mp4'
): ?>


<video
controls
preload="metadata">

    <source
    src="<?= htmlspecialchars(Media::url($item['image'])) ?>"
    type="video/mp4">

</video>


<?php else: ?>


<img
src="<?= htmlspecialchars(Media::url($item['image'])) ?>"
alt="<?= htmlspecialchars(
    $item['name']
) ?>">


<?php endif; ?>


<?php else: ?>


<div class="jp-order-item-placeholder">

    <img src="/assets/img/no-image.svg" alt="" width="48" height="48">

</div>


<?php endif; ?>


<div>


<h4>

<?= htmlspecialchars(
    $item['name']
) ?>

</h4>


<p>

Quantity:

<?= (int)$item['quantity'] ?>

</p>


<p>

Price:

KSh
<?= number_format(
    $item['price_snapshot'],
    2
) ?>

</p>


</div>


</div>


<?php endforeach; ?>


</div>



<a
href="orders"
class="jp-order-view-back-btn">

← Back To Orders

</a>



</div>


<?php endif; ?>


</div>


<?php

include
__DIR__ .
'/../includes/customer-footer.php';

?>