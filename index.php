<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/header.php';

// ✅ Get selected category & search term
$selectedCategory = $_GET['category'] ?? '';
$searchTerm = $_GET['search'] ?? '';

// ✅ Fetch unique categories from products
try {
    $catStmt = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category <> '' ORDER BY category ASC");
    $categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $categories = [];
}

// ✅ Build SQL query with filters (search + category)
if (!empty($searchTerm) && !empty($selectedCategory)) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE category = ? AND (name LIKE ? OR description LIKE ?) ORDER BY created_at DESC");
    $stmt->execute([$selectedCategory, "%$searchTerm%", "%$searchTerm%"]);
} elseif (!empty($searchTerm)) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE ? OR description LIKE ? ORDER BY created_at DESC");
    $stmt->execute(["%$searchTerm%", "%$searchTerm%"]);
} elseif (!empty($selectedCategory)) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE category = ? ORDER BY created_at DESC");
    $stmt->execute([$selectedCategory]);
} else {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC");
}

$products = $stmt->fetchAll();

// ✅ Check login status
$is_user_logged_in = is_logged_in();

// ✅ Banner configuration
$banner_image_path = '/sweepxpress/assets/Banner1.jpg';
?>

<?php if (!$is_user_logged_in): ?>
<!-- 🏞️ Hero Banner -->
<div class="hero-section text-white d-flex align-items-center justify-content-center"
     style="background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('<?php echo $banner_image_path; ?>') no-repeat center center;
            background-size: cover;
            height: 400px;
            margin-bottom: 3rem;">
    <div class="text-center">
        <h1 class="display-4 fw-bold mb-3">CLEAN STARTS HERE</h1>
        <p class="lead mb-4">Discover high-quality tools for a spotless home and workplace.</p>
        <a href="/sweepxpress/login.php" class="btn btn-lg btn-light border-0"
           style="background-color: #f7b733; color: #388e3c; font-weight: bold;">
            LOG IN TO SHOP
        </a>
    </div>
</div>
<?php endif; ?>

<div class="container my-5">
    <h1 class="mb-4 text-center">🧹 Shop Cleaning Supplies & Tools</h1>

    <!-- 🔍 Search Bar -->
    <form action="" method="get" class="d-flex mb-3">
        <input type="text"
              name="search"
              class="form-control me-2"
              placeholder="Search for products..."
              value="<?php echo htmlspecialchars($searchTerm); ?>">
        <button type="submit" class="btn btn-success">Search</button>
    </form>

    <!-- 🏷️ Category Filter Buttons -->
    <?php if (!empty($categories)): ?>
        <div class="d-flex flex-wrap justify-content-center gap-2 mb-4">
            <a href="index.php" 
               class="btn btn-outline-success <?php echo empty($selectedCategory) ? 'active' : ''; ?>">
                All
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="?category=<?php echo urlencode($cat); ?>" 
                   class="btn btn-outline-success <?php echo ($selectedCategory === $cat) ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($cat); ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- 🛒 Product Grid -->
    <div class="row g-4" id="product-list">
        <?php if (count($products) > 0): ?>
            <?php foreach($products as $p): ?>
                <div class="col-md-4 col-lg-3">
                    <div class="card h-100 shadow-sm">
                        <img src="<?php echo h($p['image_path'] ?: '/sweepxpress/assets/placeholder.jpg'); ?>"
                             class="card-img-top"
                             alt="<?php echo h($p['name']); ?>">

                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo h($p['name']); ?></h5>
                            <h6 class="text-success mb-2">₱<?php echo number_format($p['price'], 2); ?></h6>
                            <p class="card-text small text-muted">
                                <?php echo h(mb_strimwidth($p['description'], 0, 80, '…')); ?>
                            </p>

                            <div class="mt-auto">
                                <?php if ($is_user_logged_in): ?>
                                    <button class="btn btn-product-green w-100 mb-2" onclick="addToCart(<?php echo (int)$p['id']; ?>)">
                                        Add to Cart
                                    </button>
                                    <a href="/sweepxpress/product.php?id=<?php echo (int)$p['id']; ?>" class="btn btn-outline-product-green w-100">
                                        Details
                                    </a>
                                <?php else: ?>
                                    <a href="/sweepxpress/login.php" class="btn btn-product-green w-100 mb-2">
                                        Add to Cart
                                    </a>
                                    <a href="/sweepxpress/login.php" class="btn btn-outline-product-green w-100">
                                        View Details
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-center text-muted mt-5">No products found for this category/search.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
