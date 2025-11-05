<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_admin();
include '../includes/header.php';

// Fetch orders with product info
$sql = "
SELECT 
  o.order_id,
  o.total_amount,
  o.delivery_date,
  o.order_status,
  o.order_date,
  c.full_name,
  u.email,
  i.item_name,
  i.img_path,
  oi.quantity
FROM orders o
JOIN users u ON o.user_id = u.user_id
LEFT JOIN customer_info c ON u.user_id = c.user_id
JOIN order_items oi ON o.order_id = oi.order_id
JOIN item i ON oi.item_id = i.item_id
ORDER BY o.order_date DESC, o.order_id DESC
";

$result = mysqli_query($conn, $sql);

// ✅ Group by order_id and combine same products
$orders = [];
while ($row = mysqli_fetch_assoc($result)) {
  $oid = $row['order_id'];
  $item_name = $row['item_name'];

  // Initialize order if not yet set
  if (!isset($orders[$oid])) {
    $orders[$oid] = [
      'order_id' => $oid,
      'total_amount' => $row['total_amount'],
      'delivery_date' => $row['delivery_date'],
      'order_status' => $row['order_status'],
      'order_date' => $row['order_date'],
      'full_name' => $row['full_name'],
      'email' => $row['email'],
      'items' => []
    ];
  }

  // If same product exists in this order, combine quantity
  if (isset($orders[$oid]['items'][$item_name])) {
    $orders[$oid]['items'][$item_name]['quantity'] += $row['quantity'];
  } else {
    $orders[$oid]['items'][$item_name] = [
      'name' => $item_name,
      'img' => $row['img_path'],
      'quantity' => $row['quantity']
    ];
  }
}
?>

<div class="orders-admin-container">
  <h2 class="page-title">🧾 Manage Orders</h2>

  <?php if (!empty($orders)): ?>
  <div class="table-wrapper">
    <table class="orders-table">
      <thead>
        <tr>
          <th>Order ID</th>
          <th>Products</th>
          <th>Customer</th>
          <th>Email</th>
          <th>Total</th>
          <th>Delivery Date</th>
          <th>Status</th>
          <th>Payment</th>
          <th>Placed On</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $order): ?>
        <tr>
          <td>#<?php echo e($order['order_id']); ?></td>
          <td>
            <?php foreach ($order['items'] as $item): ?>
              <div class="order-product" style="margin-bottom:5px;">
                <img src="../uploads/products/<?php echo e($item['img']); ?>" 
                     alt="<?php echo e($item['name']); ?>" 
                     width="50" height="50" 
                     style="border-radius:8px;">
                <div>
                  <strong><?php echo e($item['name']); ?></strong><br>
                  <small>Qty: <?php echo e($item['quantity']); ?></small>
                </div>
              </div>
            <?php endforeach; ?>
          </td>
          <td><?php echo e($order['full_name'] ?: 'N/A'); ?></td>
          <td><?php echo e($order['email']); ?></td>
          <td><strong>₱<?php echo number_format($order['total_amount'], 2); ?></strong></td>
          <td><?php echo e($order['delivery_date']); ?></td>
          <td>
            <span class="status <?php echo strtolower($order['order_status']); ?>">
              <?php echo e($order['order_status']); ?>
            </span>
          </td>
          <td><span class="payment-status pending">Pending</span></td>
          <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
          <td>
            <a href="order_update.php?id=<?php echo $order['order_id']; ?>" class="button small">✏️ Update</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
    <p class="no-items">No orders have been placed yet.</p>
  <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
