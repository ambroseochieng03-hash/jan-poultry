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
'admin-order-view.css';



$orderId =
(int)(
    $_GET['id']
    ?? 0
);



$controller =
new OrderController();



$order =
$controller->getAdminOrder(
    $orderId
);



include
__DIR__ .
'/../includes/admin-header.php';



?>



<div class="jp-admin-order-view-page">



<?php if(!$order): ?>


<div class="jp-admin-order-view-empty">

Order not found.

</div>



<?php else: ?>



<?php


$items =
$controller->getOrderItems(
    $orderId
);



$statusClass = match(
    $order['status']
){

    'pending'
    =>
    'jp-admin-view-pending',


    'accepted'
    =>
    'jp-admin-view-accepted',


    'in_transit'
    =>
    'jp-admin-view-transit',


    'completed'
    =>
    'jp-admin-view-completed',


    'rejected'
    =>
    'jp-admin-view-rejected',


    default
    =>
    'jp-admin-view-pending'

};



$customerName =
$order['user_name']
??
$order['customer_name']
??
'Guest Customer';



$email =
$order['user_email']
??
$order['customer_email']
??
'No email';



$phone =
$order['user_phone']
??
$order['customer_phone']
??
'No phone';



?>



<div class="jp-admin-order-view-card">



<div class="jp-admin-order-view-header">


<h1>

Order #<?= $order['id'] ?>

</h1>


<span class="jp-admin-view-status <?= $statusClass ?>">

<?= ucfirst(
str_replace(
'_',
' ',
$order['status']
)
) ?>

</span>


</div>





<div class="jp-admin-order-view-section">


<h2>
Customer Information
</h2>



<div class="jp-admin-customer-grid">



<div>

<label>
Name
</label>

<strong>

<?= htmlspecialchars(
$customerName
) ?>

</strong>

</div>



<div>

<label>
Email
</label>

<a href="mailto:<?= htmlspecialchars($email) ?>">

<?= htmlspecialchars($email) ?>

</a>

</div>



<div>

<label>
Phone
</label>

<a href="tel:<?= htmlspecialchars($phone) ?>">

<?= htmlspecialchars($phone) ?>

</a>

</div>



</div>


</div>





<div class="jp-admin-order-view-section">


<h2>
Delivery Information
</h2>


<p>

<?= nl2br(
htmlspecialchars(
$order['delivery_address']
)
) ?>

</p>


</div>





<div class="jp-admin-order-view-section">


<h2>
Order Summary
</h2>



<div class="jp-admin-summary-row">

<span>
Ordered On
</span>


<strong>

<?= htmlspecialchars(
$order['created_at']
) ?>

</strong>

</div>



<div class="jp-admin-summary-row">

<span>
Grand Total
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





<div class="jp-admin-order-view-section">


<h2>
Items Ordered
</h2>



<?php foreach($items as $item): ?>


<div class="jp-admin-product-card">


<div class="jp-admin-product-media">


<?php if(!empty($item['image'])): ?>


<?php if(
pathinfo(
$item['image'],
PATHINFO_EXTENSION
)
===
'mp4'
): ?>


<video controls preload="metadata">

<source src="/jan-poultry/public/uploads/products/<?= htmlspecialchars($item['image']) ?>">

</video>


<?php else: ?>


<img
src="/jan-poultry/public/uploads/products/<?= htmlspecialchars($item['image']) ?>"
alt="<?= htmlspecialchars($item['name']) ?>">


<?php endif; ?>


<?php else: ?>


<div class="jp-admin-product-placeholder">

🐔

</div>


<?php endif; ?>


</div>



<div class="jp-admin-product-details">


<h3>

<?= htmlspecialchars(
$item['name']
) ?>

</h3>


<p>

Quantity:

<?= $item['quantity'] ?>

</p>


<p>

Price:

KSh
<?= number_format(
$item['price_snapshot'],
2
) ?>

</p>


<p>

Subtotal:

<strong>

KSh
<?= number_format(
$item['price_snapshot']
*
$item['quantity'],
2
) ?>

</strong>

</p>


</div>


</div>


<?php endforeach; ?>


</div>





<a
href="orders.php"
class="jp-admin-back-btn">

← Back To Orders

</a>




</div>



<?php endif; ?>


</div>




<?php

include
__DIR__ .
'/../includes/admin-footer.php';

?>