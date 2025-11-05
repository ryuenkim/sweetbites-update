<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_admin();
include '../includes/header.php';

// Validate the ID parameter
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid or missing product ID.");
}
$id = (int)$_GET['id'];

// Prepare the query safely
$stmt = mysqli_prepare($conn, "
  SELECT i.item_id, i.item_name, i.description, i.img_path, i.sell_price, s.quantity
  FROM item i
  JOIN stock s ON i.item_id = s.item_id
  WHERE i.item_id = ?
");
if (!$stmt) {
    die("Database prepare failed: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Check if a product was found
$product = mysqli_fetch_assoc($result);
if (!$product) {
    die("Product not found or invalid ID.");
}
mysqli_stmt_close($stmt);

// Safe escape function (in case `e()` isn’t defined)
if (!function_exists('e')) {
    function e($str) {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}
?>

<h2>Edit Product</h2>
<form method="post" action="update.php" enctype="multipart/form-data">
  <input type="hidden" name="item_id" value="<?php echo e($product['item_id']); ?>">
  
  <label>Product Name:</label>
  <input type="text" name="item_name" value="<?php echo e($product['item_name']); ?>" required>

  <label>Description:</label>
  <textarea name="description"><?php echo e($product['description']); ?></textarea>

  <label>Price:</label>
  <input type="number" step="0.01" name="sell_price" value="<?php echo e($product['sell_price']); ?>" required>

  <label>Stock Quantity:</label>
  <input type="number" name="quantity" value="<?php echo e($product['quantity']); ?>" required>

  <label>Current Image:</label><br>
  <?php if (!empty($product['img_path'])): ?>
    <img src="../uploads/products/<?php echo e($product['img_path']); ?>" width="80"><br>
  <?php else: ?>
    <em>No image uploaded</em><br>
  <?php endif; ?>

  <label>Change Image (optional):</label>
  <input type="file" name="img_path" accept=".jpg,.jpeg,.png,.gif">

  <button type="submit">Update</button>
</form>

<?php include '../includes/footer.php'; ?>
