<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_login();
include '../includes/header.php';

// Redirect if no order ID
if (!isset($_GET['order_id'])) {
    header("Location: orders.php");
    exit();
}

$order_id = (int) $_GET['order_id'];
$user_id = $_SESSION['user_id'];

// Fetch order details
$sql = "SELECT order_id, total_amount, order_status 
        FROM orders 
        WHERE order_id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'ii', $order_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($result);

// If order not found
if (!$order) {
    echo "<p class='error'>Order not found.</p>";
    include '../includes/footer.php';
    exit();
}

// Check if already paid
$check = mysqli_query($conn, "SELECT * FROM payments WHERE order_id = $order_id AND payment_status = 'Paid'");
if (mysqli_num_rows($check) > 0) {
    header("Location: orders.php?msg=already_paid");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payment | SweetBites</title>
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: #f9f9f7;
      color: #333;
      margin: 0;
      padding: 0;
    }

    .payment-container {
      max-width: 600px;
      margin: 60px auto;
      background: white;
      border-radius: 16px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      padding: 30px 40px;
      text-align: center;
    }

    .page-title {
      font-size: 1.8rem;
      margin-bottom: 25px;
      color: #2b3a55;
      letter-spacing: 0.5px;
    }

    .payment-box p {
      margin: 10px 0;
      font-size: 1rem;
      color: #555;
    }

    .payment-form {
      margin-top: 25px;
      text-align: left;
    }

    label {
      font-weight: 600;
      display: block;
      margin-bottom: 8px;
      color: #2b3a55;
    }

    select {
      width: 100%;
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #ccc;
      font-size: 1rem;
      margin-bottom: 20px;
      outline: none;
      transition: border 0.3s ease;
    }

    select:focus {
      border-color: #4a90e2;
    }

    .button {
      display: inline-block;
      padding: 12px 20px;
      border-radius: 8px;
      text-decoration: none;
      font-size: 1rem;
      transition: 0.3s;
      margin-top: 10px;
    }

    .button.success {
      background-color: #4a90e2;
      color: white;
      border: none;
      cursor: pointer;
    }

    .button.success:hover {
      background-color: #4178c0;
    }

    .button.secondary {
      background-color: #f1f1f1;
      color: #555;
      border: none;
      margin-left: 10px;
    }

    .button.secondary:hover {
      background-color: #e0e0e0;
    }

    @media (max-width: 600px) {
      .payment-container {
        padding: 25px 20px;
      }
      .page-title {
        font-size: 1.4rem;
      }
    }
  </style>
</head>
<body>

<div class="payment-container">
  <h2 class="page-title">💳 Complete Your Payment</h2>

  <div class="payment-box">
    <p><strong>Order ID:</strong> #<?= htmlspecialchars($order['order_id']) ?></p>
    <p><strong>Total Amount:</strong> ₱<?= number_format($order['total_amount'], 2) ?></p>
    <p><strong>Order Status:</strong> <?= htmlspecialchars($order['order_status']) ?></p>

    <form action="../payment/payment_process.php" method="POST" class="payment-form">
      <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['order_id']) ?>">

      <label for="payment_method">Select Payment Method:</label>
      <select name="payment_method" id="payment_method" required>
        <option value="Cash on Delivery">Cash on Delivery</option>
        <option value="Gcash">Gcash</option>
        <option value="Credit Card">Credit Card</option>
      </select>

      <button type="submit" name="pay_now" class="button success">Confirm Payment</button>
      <a href="../user/orders.php" class="button secondary">Cancel</a>
    </form>
  </div>
</div>

</body>
</html>

<?php include '../includes/footer.php'; ?>
