<?php
require_once '../includes/session.php';
require_login();
require_once '../includes/config.php';
require_once '../includes/functions.php';

$order_id = to_int($_GET['order_id'] ?? 0);
$user_id  = $_SESSION['user_id'];

// Fetch order details
$sql = "SELECT * FROM orders WHERE order_id = $order_id AND user_id = $user_id";
$result = mysqli_query($conn, $sql);
$order = mysqli_fetch_assoc($result);

if (!$order) {
    redirect('/sweetbites/user/orders.php');
}
?>
<?php include '../includes/header.php'; ?>
<h2>Proceed to Payment</h2>

<section class="card">
    <p><strong>Order ID:</strong> <?= e($order['order_id']) ?></p>
    <p><strong>Total Amount:</strong> ₱<?= number_format($order['total_amount'], 2) ?></p>
    <p><strong>Delivery Date:</strong> <?= e($order['delivery_date']) ?></p>

    <form action="payment_process.php" method="POST">
        <input type="hidden" name="order_id" value="<?= e($order['order_id']) ?>">

        <label for="payment_method"><strong>Select Payment Method:</strong></label>
        <select name="payment_method" id="payment_method" required>
            <option value="Cash on Delivery">Cash on Delivery</option>
            <option value="Gcash">Gcash</option>
            <option value="Credit Card">Credit Card</option>
        </select>

        <br><br>
        <button type="submit" class="button primary">Pay Now</button>
        <a href="/sweetbites/user/orders.php" class="button secondary">Cancel</a>
    </form>
</section>

<?php include '../includes/footer.php'; ?>
