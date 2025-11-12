<?php  
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch main product info
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();

if (!$p) {
  echo '<p class="alert alert-danger text-center mt-5">Product not found.</p>';
  require 'includes/footer.php';
  exit;
}

// Fetch product gallery images
$stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY id ASC");
$stmt->execute([$id]);
$images = $stmt->fetchAll();

// 🔧 Helper function for image path
function getImagePath($filename) {
    $placeholder = '/sweepxpress/assets/placeholder.jpg';
    
    if (empty($filename)) return $placeholder;

    $filename = basename($filename);

    $paths = [
        __DIR__ . '/uploads/' . $filename,
        __DIR__ . '/assets/' . $filename
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            return str_replace(__DIR__, '/sweepxpress', $path);
        }
    }

    return $placeholder;
}

$mainImage = getImagePath($p['image_path'] ?? ($images[0]['image_path'] ?? ''));
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

body {
  background: #f5f7ff;
  font-family: 'Poppins', sans-serif;
  color: #222;
  margin: 0;
}

.product-container {
  display: flex;
  flex-wrap: wrap;
  gap: 50px;
  justify-content: center;
  background: #ffffff;
  border-radius: 24px;
  padding: 60px 50px;
  box-shadow: 0 6px 25px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
}
.product-container:hover {
  box-shadow: 0 10px 35px rgba(0, 0, 0, 0.12);
  transform: translateY(-3px);
}

.product-images { flex: 1; min-width: 350px; }
.main-image img {
  width: 100%;
  max-height: 450px;
  object-fit: cover;
  border-radius: 20px;
  background: #f2f5ff;
  box-shadow: 0 3px 15px rgba(0,0,0,0.1);
}

.thumb-gallery {
  display: flex;
  gap: 12px;
  margin-top: 12px;
}
.thumb-gallery img {
  width: 75px;
  height: 75px;
  border-radius: 12px;
  cursor: pointer;
  border: 2px solid transparent;
  transition: all 0.25s;
}
.thumb-gallery img:hover {
  border-color: #3366ff;
  transform: scale(1.05);
}

.product-details {
  flex: 1;
  min-width: 350px;
}
.product-details h2 {
  font-size: 1.8rem;
  color: #2b2b2b;
  font-weight: 600;
  margin-bottom: 15px;
}

.price {
  font-size: 1rem;
  color: #3366ff;
  font-weight: 700;
  margin-right: 10px;
}
.old-price {
  text-decoration: line-through;
  color: #aaa;
  margin-left: 8px;
  font-weight: 400;
}
.discount {
  color: #fff;
  background: #ffb300;
  padding: 3px 8px;
  border-radius: 6px;
  font-size: 0.9rem;
  margin-left: 8px;
}

.product-details p {
  font-size: 1rem;
  color: #444;
  line-height: 1.6;
  margin-top: 15px;
}

.buy-buttons {
  margin-top: 30px;
  display: flex;
  gap: 12px;
}
.btn-cart, .btn-buy {
  flex: 1;
  padding: 13px;
  border: none;
  border-radius: 14px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(51, 102, 255, 0.25);
  background: linear-gradient(135deg, #3366ff, #5588ff);
  color: #fff;
}
.btn-cart:hover, .btn-buy:hover {
  background: linear-gradient(135deg, #274bdb, #1a35b8);
  transform: translateY(-3px);
}

.shipping-info {
  margin-top: 25px;
  background: #eef3ff;
  padding: 18px;
  border-radius: 14px;
  font-size: 0.95rem;
  border: 1px solid #ccd8ff;
}
.shipping-info strong { color: #3366ff; }

.container h2 {
  font-size: 1.7rem;
  font-weight: 600;
  color: #2b2b2b;
  text-align: center;
  margin: 50px 0 20px;
}

.products-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 28px;
  padding: 20px 0 60px;
}

.product-card {
  background: #fff;
  border-radius: 18px;
  overflow: hidden;
  border: 2px solid transparent;
  box-shadow: 0 4px 16px rgba(0,0,0,0.08);
  transition: all 0.3s ease;
}
.product-card:hover {
  border-color: #3366ff;
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.12);
}

.product-image {
  width: 100%;
  height: 220px;
  object-fit: cover;
}

.product-info {
  padding: 18px;
  text-align: center;
}
.product-info h5 {
  font-size: 1.05rem;
  color: #2b2b2b;
  font-weight: 500;
  margin-bottom: 8px;
  text-overflow: ellipsis;
  white-space: nowrap;
  overflow: hidden;
}

.view-details-btn, .add-cart-btn {
  width: 100%;
  border: none;
  border-radius: 12px;
  padding: 10px;
  margin-top: 10px;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  color: #fff;
  background: linear-gradient(135deg, #3366ff, #5588ff);
  box-shadow: 0 3px 10px rgba(51, 102, 255, 0.25);
}
.view-details-btn:hover, .add-cart-btn:hover {
  background: linear-gradient(135deg, #274bdb, #1a35b8);
  transform: translateY(-2px);
}
</style>


<div class="container">
  <div class="product-container">
    <div class="product-images">
      <div class="main-image mb-3">
        <img id="mainProductImage" src="<?php echo htmlspecialchars($mainImage); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
      </div>

      <?php if (!empty($images)): ?>
      <div class="thumb-gallery">
        <?php foreach ($images as $img): ?>
          <img src="<?php echo htmlspecialchars(getImagePath($img['image_path'])); ?>" onclick="changeMainImage(this.src)">
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <div class="product-details">
      <h2><?php echo htmlspecialchars($p['name']); ?></h2>

      <div class="d-flex align-items-center mt-2">
        <span class="price">₱<?php echo number_format($p['price'], 2); ?></span>
        <?php 
          $oldPrice = isset($p['old_price']) ? (float)$p['old_price'] : 0;
          if ($oldPrice > $p['price'] && $oldPrice > 0): 
        ?>
          <span class="old-price">₱<?php echo number_format($oldPrice, 2); ?></span>
          <span class="discount">
            -<?php echo round(100 - ($p['price'] / $oldPrice * 100)); ?>%
          </span>
        <?php endif; ?>
      </div>

      <p class="mt-4"><?php echo nl2br(htmlspecialchars($p['description'])); ?></p>

      <div class="shipping-info mt-4">
        <p>🚚 <strong>Estimated</strong> 2–3 days</p>
        <p>💸 <strong>Free Returns</strong> within 7 days</p>
        <p>🛡️ <strong>Shopping Guarantee</strong> – Safe and Secure Checkout</p>
      </div>

      <div class="buy-buttons">
        <button class="btn-cart" onclick="addToCart(<?php echo (int)$p['id']; ?>)">Add to Cart</button>
        <button class="btn btn-success" onclick="buyNow(<?= $p['id'] ?>)">
        <i class="bi bi-lightning-charge"></i> Buy Now
      </button>

      </div>
    </div>
  </div>
</div>

<script>
function changeMainImage(src) {
  document.getElementById('mainProductImage').src = src;
}

function addToCart(productId) {
 fetch(window.location.origin + '/sweepxpress/add_to_cart.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/x-www-form-urlencoded'},
  body: 'product_id=' + encodeURIComponent(productId)
})

  .then(res => res.json())
  .then(data => {
    if (data.success) {
      updateCartCount();
      alert('✅ ' + data.message);
    } else {
      alert('⚠️ ' + data.message);
    }
  })
  .catch(err => console.error('Add to cart error:', err));
}

function updateCartCount() {
  fetch(window.location.origin + '/sweepxpress/add_to_cart.php', {
  fetch(window.location.origin + '/sweepxpress/buy_now.php', {

    .then(res => res.json())
    .then(data => {
      if (data.success) {
        document.getElementById('cart-count').innerText = data.count;
      }
    })
    .catch(err => console.error('Cart count error:', err));
}

function buyNow(productId) {
  fetch(window.location.origin + '/sweepxpress/buy_now.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'product_id=' + encodeURIComponent(productId)
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      window.location.href = window.location.origin + '/sweepxpress/checkout.php';
    } else {
      alert('⚠️ ' + data.message);
    }
  })
  .catch(err => console.error('Buy now error:', err));
}

</script>

<?php
$stmt = $pdo->query("SELECT * FROM products WHERE id != $id ORDER BY id DESC LIMIT 8");
$products = $stmt->fetchAll();
?>

<div class="container">
  <h2 class="text-center mb-4">Related Products</h2>
  <div class="products-container">
    <?php foreach ($products as $prod): ?>
      <?php 
        $imgPath = getImagePath($prod['image_path']);
        $oldPrice = isset($prod['old_price']) ? (float)$prod['old_price'] : 0;
        $discount = ($oldPrice > $prod['price'] && $oldPrice > 0) 
          ? round(100 - ($prod['price'] / $oldPrice * 100)) 
          : 0;
      ?>
      <div class="product-card">
        <img src="<?php echo htmlspecialchars($imgPath); ?>" 
             alt="<?php echo htmlspecialchars($prod['name'] ?: 'Product'); ?>" 
             class="product-image">
        <div class="product-info">
          <h5><?php echo htmlspecialchars($prod['name'] ?: 'Unnamed Product'); ?></h5>
          <div>
            <span class="price">₱<?php echo number_format($prod['price'], 2); ?></span>
            <?php if ($discount > 0): ?>
              <span class="old-price">₱<?php echo number_format($oldPrice, 2); ?></span>
              <span class="discount">-<?php echo $discount; ?>%</span>
            <?php endif; ?>
          </div>
          <button class="add-cart-btn" onclick="addToCart(<?php echo (int)$prod['id']; ?>)">Add to Cart</button>
          <button class="view-details-btn" onclick="window.location='product.php?id=<?php echo $prod['id']; ?>'">Details</button>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>