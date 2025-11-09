<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/header.php';

// Check if a search term exists
$searchTerm = $_GET['search'] ?? '';

if (!empty($searchTerm)) {
    // SECURITY FIX: Using prepared statements to prevent SQL Injection
    $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE ? OR description LIKE ? ORDER BY created_at DESC");
    $stmt->execute(["%$searchTerm%", "%$searchTerm%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC");
}

$products = $stmt->fetchAll();

// Determine if the user is logged in
$is_user_logged_in = is_logged_in();

// --- CONFIGURATION FOR BANNER IMAGE ---
// **Using the path for the image you provided**
// Make sure this image is correctly placed in your web server's assets directory.
$banner_image_path = '/sweepxpress/assets/Banner1.jpg';
// -------------------------------------

?>

<?php if (!$is_user_logged_in): ?>
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

    <form action="" method="get" class="d-flex mb-4">
        <input type="text"
              name="search"
              class="form-control me-2"
              placeholder="Search for products..."
              value="<?php echo htmlspecialchars($searchTerm); ?>">
        <button type="submit" class="btn btn-success">Search</button>
    </form>


    <div class="row g-4" id="product-list">
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
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>