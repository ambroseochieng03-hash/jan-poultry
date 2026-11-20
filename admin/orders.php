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
'/../app/helpers/Flash.php';

Auth::requireAdmin();

$pageCss = 'admin-orders.css';

include
__DIR__ .
'/../includes/admin-header.php';

$appResponse =
$_SESSION['app_response']
?? null;

unset(
    $_SESSION['app_response']
);

$controller =
new OrderController();


$orders =
$controller->getAllOrders();

$flash =
Flash::get();

?>

<div class="jp-admin-orders-page">


<div class="jp-admin-orders-header">

    <h1>
        Orders Management
    </h1>

    <p>
        Monitor customer orders and delivery progress.
    </p>

</div>



<?php if(empty($orders)): ?>


<div class="jp-admin-orders-empty">

    No orders available.

</div>


<?php else: ?>


<?php foreach($orders as $order): ?>


<?php

$statusClass = match(
    $order['status']
){

    'pending'
    => 'jp-admin-status-pending',

    'accepted'
    => 'jp-admin-status-accepted',

    'in_transit'
    => 'jp-admin-status-transit',

    'completed'
    => 'jp-admin-status-completed',

    'rejected'
    => 'jp-admin-status-rejected',

    'cancelled'
    => 'jp-admin-status-cancelled',

    default
    => 'jp-admin-status-pending'

};


$customerName =
$order['name']
??
$order['customer_name']
??
'Guest Customer';


$email =
$order['email']
??
$order['customer_email']
??
'No email';


$phone =
$order['phone']
??
$order['customer_phone']
??
'No phone';

?>


<div class="jp-admin-order-card">


<div class="jp-admin-order-top">


<h2>

Order #<?= (int)$order['id'] ?>

</h2>


<span class="jp-admin-status <?= $statusClass ?>">

<?= ucfirst(
str_replace(
'_',
' ',
$order['status']
)
) ?>

</span>


</div>



<div class="jp-admin-order-info">


<div>

<label>
Customer
</label>

<strong>
<?= htmlspecialchars($customerName) ?>
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



<div>

<label>
Amount
</label>

<strong>

KSh
<?= number_format(
$order['total_price'],
2
) ?>

</strong>

</div>



<div>

<label>
Ordered
</label>

<strong>

<?= htmlspecialchars(
$order['created_at']
) ?>

</strong>

</div>


</div>



<div class="jp-admin-order-actions">


<a
href="order-view?id=<?= (int)$order['id'] ?>"
class="jp-admin-view-btn">

View Order

</a>



<?php if($order['status']==='pending'): ?>


<form method="POST" action="order-status">

<input
type="hidden"
name="order_id"
value="<?= $order['id'] ?>">


<input
type="hidden"
name="status"
value="rejected">


<button
class="jp-admin-reject-btn">

Reject

</button>

</form>



<form method="POST" action="order-status">

<input
type="hidden"
name="order_id"
value="<?= $order['id'] ?>">


<input
type="hidden"
name="status"
value="accepted">


<button
class="jp-admin-accept-btn">

Accept Order

</button>

</form>


<?php elseif($order['status']==='accepted'): ?>


<form method="POST" action="order-status">


<input
type="hidden"
name="order_id"
value="<?= $order['id'] ?>">


<input
type="hidden"
name="status"
value="in_transit">


<button
class="jp-admin-transit-btn">

Begin Delivery

</button>


</form>



<?php elseif($order['status']==='in_transit'): ?>


<form method="POST" action="order-status">


<input
type="hidden"
name="order_id"
value="<?= $order['id'] ?>">


<input
type="hidden"
name="status"
value="completed">


<button
class="jp-admin-complete-btn">

Mark Delivered

</button>


</form>



<?php endif; ?>


</div>


</div>


<?php endforeach; ?>


<?php endif; ?>


</div>

<?php if ($appResponse): ?>

<script>

document.addEventListener(

    "DOMContentLoaded",

    function () {

        showAppResponseModal({

            icon:
                <?= $appResponse['success']
                    ? json_encode('✅')
                    : json_encode('⚠️') ?>,

            title:
                <?= json_encode(
                    $appResponse['title']
                ) ?>,

            message:
                <?= json_encode(
                    $appResponse['message']
                ) ?>,

            buttonText: "OK",

            autoClose: 2000

        });

    }

);

</script>

<?php endif; ?>

<?php

include
__DIR__ .
'/../includes/app-response-modal.php';

include
__DIR__ .
'/../includes/admin-footer.php';

?>