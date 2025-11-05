<?php
require_once '../includes/session.php';
require_login();
require_once '../includes/config.php';
require_once '../includes/functions.php';

$order_id = to_int($_POST['order_id'] ?? 0);
$method   = e($_POST['payment_method'] ?? 'Cash on Delivery');
$user_id  = $_SESSION['user_id'] ?? 0;

if (!$order_id || !$user_id) {
    redirect('orders.php');
    exit();
}

// Simulate payment verification delay
include '../includes/header.php';
echo "<div class='card' style='text-align:center; padding:30px;'>
        <h2>⏳ Processing Payment...</h2>
        <p>Please wait while we confirm your payment.</p>
      </div>";

// Add a small delay for UX (optional)
flush();
sleep(2);

// Check if payment already exists
$check = mysqli_query($conn, "SELECT * FROM payments WHERE order_id = $order_id");
if (mysqli_num_rows($check) > 0) {
    redirect('payment_success.php?order_id=' . $order_id);
    exit();
}

// Insert payment
$sql = "INSERT INTO payments (order_id, payment_method, payment_status)
        VALUES ($order_id, '$method', 'Paid')";
mysqli_query($conn, $sql);

// Update order status
mysqli_query($conn, "UPDATE orders SET order_status = 'Preparing' WHERE order_id = $order_id");

// Small wait before redirect (for realism)
sleep(1);

redirect('payment_success.php?order_id=' . $order_id);
?>
