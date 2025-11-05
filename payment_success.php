<?php
require_once '../includes/session.php';
require_login();
require_once '../includes/config.php';
require_once '../includes/functions.php';

$order_id = to_int($_GET['order_id'] ?? 0);

$result = mysqli_query($conn, "
    SELECT o.order_id, o.total_amount, p.payment_method, p.payment_status, p.transaction_date
    FROM payments p
    JOIN orders o ON p.order_id = o.order_id
    WHERE o.order_id = $order_id
");

$payment = mysqli_fetch_assoc($result);
include '../includes/header.php';
?>

<section class="card">
    <?php if ($payment): ?>
        <h2>Payment Successful 🎉</h2>
        <p><strong>Order ID:</strong> <?= e($payment['order_id']) ?></p>
        <p><strong>Total Amount:</strong> ₱<?= number_format($payment['total_amount'], 2) ?></p>
        <p><strong>Method:</strong> <?= e($payment['payment_method']) ?></p>
        <p><strong>Status:</strong> <?= e($payment['payment_status']) ?></p>
        <p><strong>Date:</strong> <?= e($payment['transaction_date']) ?></p>
        <a href="/sweetbites/user/orders.php" class="button primary">Back to My Orders</a>
    <?php else: ?>
        <p>Payment record not found.</p>
    <?php endif; ?>
</section>

<?php include '../includes/footer.php'; ?>
