<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) { echo '<p class="alert error">Product not found.</p>'; require 'includes/footer.php'; exit; }
?>
<div class="row">
  <div>
    <img src="<?php echo h($p['image_path'] ?: '/sweepxpress/assets/placeholder.jpg'); ?>" alt="<?php echo h($p['name']); ?>" style="width:100%;max-height:400px;object-fit:cover;border-radius:16px;background:#fff">
  </div>
  <div>
    <h1><?php echo h($p['name']); ?></h1>
    <div class="price">₱<?php echo number_format($p['price'], 2); ?></div>
    <p><?php echo nl2br(h($p['description'])); ?></p>
    <button class="btn" onclick="addToCart(<?php echo (int)$p['id']; ?>)">Add to cart</button>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
