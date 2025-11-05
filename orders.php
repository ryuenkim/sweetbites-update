<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php'; // for e()
require_login();
include '../includes/header.php';

$user_id = $_SESSION['user_id'];

// Fetch orders with payment info (LEFT JOIN)
$sql = "SELECT o.order_id, o.total_amount, o.delivery_date, o.order_status, o.order_date,
               p.payment_method, p.payment_status, p.transaction_date
        FROM orders o
        LEFT JOIN payments p ON o.order_id = p.order_id
        WHERE o.user_id = ?
        ORDER BY o.order_date DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<div class="orders-container">
  <h2 class="page-title">📦 My Orders</h2>

  <?php if (mysqli_num_rows($result) > 0): ?>
  <div class="table-wrapper">
    <table class="orders-table">
      <thead>
        <tr>
          <th>Order ID</th>
          <th>Total</th>
          <th>Delivery Date</th>
          <th>Order Status</th>
          <th>Payment Status</th>
          <th>Payment Method</th>
          <th>Placed On</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php while($row = mysqli_fetch_assoc($result)): ?>
          <?php
            $order_id = $row['order_id'];
            $payment_status = $row['payment_status'] ?? 'Unpaid';
            $payment_method = $row['payment_method'] ?? '-';

            // Auto-mark as paid if payment record exists
            if ($payment_status === 'Paid' && $row['order_status'] === 'Pending') {
                mysqli_query($conn, "UPDATE orders SET order_status = 'Preparing' WHERE order_id = $order_id");
                $row['order_status'] = 'Preparing';
            }
          ?>
          <tr>
            <td>#<?php echo e($order_id); ?></td>
            <td><strong>₱<?php echo number_format($row['total_amount'], 2); ?></strong></td>
            <td><?php echo e($row['delivery_date']); ?></td>
            <td>
              <span class="status <?php echo strtolower($row['order_status']); ?>">
                <?php echo e($row['order_status']); ?>
              </span>
            </td>
            <td>
              <span class="status <?php echo strtolower($payment_status); ?>">
                <?php echo e($payment_status); ?>
              </span>
            </td>
            <td><?php echo e($payment_method); ?></td>
            <td><?php echo date('M d, Y', strtotime($row['order_date'])); ?></td>
            <td>
              <?php if ($payment_status === 'Unpaid'): ?>
                <a href="payment.php?order_id=<?php echo $order_id; ?>" class="button success small">Pay Now</a>
              <?php elseif (strtolower($row['order_status']) === 'pending'): ?>
                <a href="delete_order.php?id=<?php echo $order_id; ?>" class="button danger small">Cancel</a>
              <?php else: ?>
                <span class="locked">Locked</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
    <p class="no-items">You haven’t placed any orders yet. <a href="../index.php">Start shopping now!</a></p>
  <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
