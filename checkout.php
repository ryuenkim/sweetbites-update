<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_login();
include '../includes/header.php';

$user_id = (int)($_SESSION['user_id'] ?? 0);
$message = '';

// Fetch user addresses
$addr_stmt = $conn->prepare("SELECT address_id, address_line FROM addresses WHERE user_id = ?");
$addr_stmt->bind_param("i", $user_id);
$addr_stmt->execute();
$addr_result = $addr_stmt->get_result();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $address_id = isset($_POST['address_id']) ? (int)$_POST['address_id'] : 0;
    $delivery_date = $_POST['delivery_date'] ?? '';

    if (!$address_id || !$delivery_date) {
        $message = "⚠️ Please select a delivery address and date.";
    } else {
        // Begin transaction for safety
        $conn->begin_transaction();

        try {
            // Fetch cart items
            $cart_sql = "SELECT sc.item_id, sc.quantity AS cart_qty, i.item_name, i.sell_price, COALESCE(s.quantity, 0) AS stock_qty
                         FROM saved_cart sc
                         JOIN item i ON sc.item_id = i.item_id
                         LEFT JOIN stock s ON i.item_id = s.item_id
                         WHERE sc.user_id = ?";
            $cart_stmt = $conn->prepare($cart_sql);
            $cart_stmt->bind_param("i", $user_id);
            $cart_stmt->execute();
            $cart = $cart_stmt->get_result();

            if (!$cart || $cart->num_rows === 0) {
                throw new Exception("Your cart is empty.");
            }

            $total = 0.0;
            $items = [];

            while ($row = $cart->fetch_assoc()) {
                $cart_qty = (int)$row['cart_qty'];
                $stock_qty = (int)$row['stock_qty'];

                if ($cart_qty > $stock_qty) {
                    throw new Exception("Insufficient stock for " . htmlspecialchars($row['item_name']));
                }

                $price = (float)$row['sell_price'];
                $subtotal = $price * $cart_qty;
                $total += $subtotal;

                $items[] = [
                    'item_id' => (int)$row['item_id'],
                    'quantity' => $cart_qty,
                    'price' => $price
                ];
            }

            // Insert order
            $order_stmt = $conn->prepare("INSERT INTO orders (user_id, address_id, total_amount, delivery_date, order_status)
                                          VALUES (?, ?, ?, ?, 'Pending')");
            $order_stmt->bind_param("iids", $user_id, $address_id, $total, $delivery_date);
            $order_stmt->execute();
            $order_id = $order_stmt->insert_id;
            $order_stmt->close();

            // Insert order items + update stock
            $insert_item_stmt = $conn->prepare("INSERT INTO order_items (order_id, item_id, quantity, price) VALUES (?, ?, ?, ?)");
            $update_stock_stmt = $conn->prepare("UPDATE stock SET quantity = quantity - ? WHERE item_id = ?");

            foreach ($items as $item) {
                $insert_item_stmt->bind_param("iiid", $order_id, $item['item_id'], $item['quantity'], $item['price']);
                $insert_item_stmt->execute();

                $update_stock_stmt->bind_param("ii", $item['quantity'], $item['item_id']);
                $update_stock_stmt->execute();
            }

            // Clear cart
            $clear_stmt = $conn->prepare("DELETE FROM saved_cart WHERE user_id = ?");
            $clear_stmt->bind_param("i", $user_id);
            $clear_stmt->execute();

            // Commit transaction
            $conn->commit();

            // ✅ Redirect directly to payment page with order_id
            header("Location: payment.php?order_id=" . $order_id);
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $message = "❌ Checkout failed: " . $e->getMessage();
        }
    }
}
?>

<h2>Checkout</h2>
<?php if ($message): ?>
  <p class="alert"><?= htmlspecialchars($message, ENT_QUOTES); ?></p>
<?php endif; ?>

<form method="post" action="">
  <label>Delivery Address:</label>
  <select name="address_id" required>
    <option value="">-- Select Address --</option>
    <?php if ($addr_result && $addr_result->num_rows > 0): ?>
      <?php while ($a = $addr_result->fetch_assoc()): ?>
        <option value="<?= (int)$a['address_id']; ?>"><?= htmlspecialchars($a['address_line'], ENT_QUOTES); ?></option>
      <?php endwhile; ?>
    <?php else: ?>
      <option value="">No saved addresses found</option>
    <?php endif; ?>
  </select>

  <label>Delivery Date:</label>
  <input type="date" name="delivery_date" required min="<?= date('Y-m-d'); ?>">

  <button type="submit" name="checkout">Proceed to Payment</button>
</form>

<?php include '../includes/footer.php'; ?>
