<?php 
require_once __DIR__ . '/../includes/auth.php';
// Assuming $pdo (PDO connection) is available from a shared include, 
// or you need to establish it here if not in 'auth.php' or 'header.php'.
require_once __DIR__ . '/../includes/header.php';

$message = '';

// --- DELETE LOGIC ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id > 0) {
        try {
            // 1. Delete associated images (files and records) from `product_images`
            $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
            $stmt->execute([$id]);
            $extraImages = $stmt->fetchAll();

            foreach ($extraImages as $image) {
                // Compute server file path
                $relative = ltrim(str_replace('/sweepxpress/', '', $image['image_path']), '/');
                $filePath = realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR . $relative;
                if ($filePath && file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
            // Delete records from product_images table
            $pdo->prepare("DELETE FROM product_images WHERE product_id = ?")->execute([$id]);

            // 2. Delete primary image file (if one exists)
            $stmt = $pdo->prepare("SELECT image_path FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $product = $stmt->fetch();

            if ($product && !empty($product['image_path'])) {
                // Compute server file path from web path
                $relative = ltrim(str_replace('/sweepxpress/', '', $product['image_path']), '/');
                $filePath = realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR . $relative;
                if ($filePath && file_exists($filePath) && is_file($filePath)) {
                    @unlink($filePath);
                }
            }

            // 3. Delete the product record from the `products` table
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $message = '<div class="alert alert-success">🗑 Product and ' . count($extraImages) . ' image(s) deleted successfully.</div>';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Invalid product ID for deletion.</div>';
    }
}

// Display messages (success/error from deletion)
if (!empty($message)) echo $message;

// Fetch all products
try {
    $items = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
    $productCount = count($items);
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Error fetching products: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $items = [];
    $productCount = 0;
}
?>

<style>
    /* Edit Button (Yellow) */
    .btn-custom-edit {
        background-color: #ffc107 !important; /* Yellow */
        border-color: #ffc107 !important;
        color: #212529 !important; /* Dark text for contrast */
    }
    .btn-custom-edit:hover {
        background-color: #e0a800 !important;
        border-color: #e0a800 !important;
    }

    /* Delete Button (Red) */
    .btn-custom-delete {
        background-color: #dc3545 !important; /* Red */
        border-color: #dc3545 !important;
        color: #ffffff !important; /* White text for contrast */
    }
    .btn-custom-delete:hover {
        background-color: #c82333 !important;
        border-color: #c82333 !important;
    }
</style>

<div class="container-fluid min-vh-100 bg-light px-0 py-4"> 
    
    <div class="d-flex justify-content-between align-items-center mb-4 px-4 px-lg-5"> 
        <h1 class="display-6 fw-bold text-primary">
            Product Inventory
        </h1>
        <a href="add_product.php" class="btn btn-primary btn-lg shadow-sm">
            <i class="fas fa-plus me-2"></i> Add New Product
        </a>
    </div>
    
    <div class="card shadow-lg border-0 w-100"> 
        <div class="card-header bg-white border-bottom py-3 px-4 px-lg-5">
            <h3 class="mb-0 text-dark">
                All Products <span class="badge bg-primary text-white rounded-pill ms-2"><?php echo $productCount; ?></span>
            </h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th scope="col" style="width: 5%;" class="ps-4 ps-lg-5">ID</th>
                            <th scope="col" style="width: 10%;">Image</th>
                            <th scope="col" style="width: 45%;">Name</th>
                            <th scope="col" style="width: 15%;">Price</th>
                            <th scope="col" style="width: 25%;" class="pe-4 pe-lg-5">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <i class="fas fa-box-open fa-2x d-block mb-2"></i> 
                                    No products found in the inventory.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $product): ?>
                            <tr>
                                <td class="text-muted ps-4 ps-lg-5"><?php echo htmlspecialchars((string)$product['id']); ?></td>
                                <td>
                                    <?php if ($product['image_path']): ?>
                                        <img src="<?php echo htmlspecialchars($product['image_path']); ?>" 
                                            alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                            class="img-fluid rounded" style="max-width: 50px; height: auto;">
                                    <?php else: ?>
                                        <span class="text-muted small">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold"><?php echo htmlspecialchars($product['name']); ?></td>
                                <td>
                                    <span class="badge bg-success-subtle text-success py-2 px-3">
                                        ₱<?php echo htmlspecialchars(number_format($product['price'], 2)); ?>
                                    </span>
                                </td>
                                <td class="pe-4 pe-lg-5">
                                    <div class="d-flex flex-wrap gap-2"> 
                                        <a href="edit_product.php?id=<?php echo htmlspecialchars((string)$product['id']); ?>" 
                                            class="btn btn-sm btn-custom-edit">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="?delete=<?php echo htmlspecialchars((string)$product['id']); ?>" 
                                            class="btn btn-sm btn-custom-delete"
                                            onclick="return confirm('Confirm deletion of: <?php echo htmlspecialchars(addslashes($product['name'])); ?>?')">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>