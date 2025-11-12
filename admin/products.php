<?php 
// 1. CRITICAL: Output Buffering - Kailangan sa pinaka-umpisa
ob_start(); 

session_start(); // Dapat nandoon ang session_start() para gumana ang auth at alerts
require_once __DIR__ . '/../config.php'; 
require_once __DIR__ . '/../includes/auth.php';

// Helper function for basic HTML escaping
if (!function_exists('h')) {
    function h($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

// Tiyakin na ang user ay admin
if (!is_logged_in() || $_SESSION['user']['role'] !== 'admin') {
    header("Location: /sweepxpress/login.php");
    exit();
}

$message = ''; // Para sa errors na hindi SweetAlerts (e.g., upload/database errors)

// --- Pagination Setup ---
$limit = 10; // Items per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;


// ==========================================================
// 2. HANDLE DELETE (Uses PRG/SweetAlert pattern)
// ==========================================================
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $page_redirect = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Ibalik sa tamang page
    $deleted_name = 'Product';

    if ($id > 0) {
        try {
            $pdo->beginTransaction();

            // 1. I-delete ang mga konektadong entries (Foreign Key Fix)
            $stmt_order = $pdo->prepare("DELETE FROM order_items WHERE product_id = ?");
            $stmt_order->execute([$id]);

            // 2. I-delete ang image file at kunin ang pangalan
            $stmt = $pdo->prepare("SELECT name, image_path FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $product = $stmt->fetch();
            $deleted_name = $product['name'] ?? 'Product';

            if ($product && !empty($product['image_path'])) {
                // Tiyakin na tama ang path (alisin ang /sweepxpress/ sa image path)
                $relativePath = str_replace('/sweepxpress', '', $product['image_path']); 
                $imageFilePath = __DIR__ . '/..' . $relativePath; 
                
                if (file_exists($imageFilePath) && is_file($imageFilePath)) {
                    @unlink($imageFilePath); 
                }
            }

            // 3. I-DELETE ang Product sa main table
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            
            $pdo->commit();

            // PRG Redirect on SUCCESS - Gumamit ng SweetAlert parameters
            header('Location: products.php?page=' . $page_redirect . '&success=Product_Deleted&name=' . urlencode($deleted_name));
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            // Store error in session before redirect
            $_SESSION['alert_message'] = '<div class="alert alert-danger">❌ Database error during deletion: ' . h($e->getMessage()) . '</div>';
        }
    }
    // If an error occurred or the ID was invalid, redirect to clear the GET param
    header('Location: products.php?page=' . $page_redirect);
    exit;
}


// ==========================================================
// 3. HANDLE CREATE (POST)
// ==========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $sku = trim($_POST['sku'] ?? ''); 
    $name = trim($_POST['name'] ?? '');
    // Gamit ang filter_var para sa strict non-negative check
    $price = filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0]]);
    $desc = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    
    $errors = [];

    // Check for required fields
    if ($name === '' || $price === false || $desc === '' || $category === '') {
        $errors[] = "All fields are required, including category.";
    }

    if (empty($errors)) {
        // --- SKU LOGIC AT VALIDATION ---
        $sku_is_unique = true;

        if ($sku === '') {
            // AUTOMATIC SKU GENERATION LOGIC
            $words = explode(' ', $name);
            $initials = '';
            foreach ($words as $word) {
                if (!empty($word)) {
                    $initials .= strtoupper($word[0]);
                }
            }
            $prefix = substr($initials, 0, 4); 
            
            $length = 4;
            $digits = '0123456789';
            
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE sku = ?");
            
            // Loop hanggang maging unique ang SKU
            $attempts = 0;
            while (!$sku_is_unique && $attempts < 10) { // Limit attempts just in case
                $random_part = '';
                for ($i = 0; $i < $length; $i++) {
                    $random_part .= $digits[random_int(0, strlen($digits) - 1)];
                }
                
                $sku = $prefix . '-' . $random_part;
                
                $stmt->execute([$sku]);
                if ($stmt->fetchColumn() == 0) {
                    $sku_is_unique = true;
                }
                $attempts++;
            }
            if (!$sku_is_unique) {
                $errors[] = 'Failed to generate a unique SKU after several attempts. Please try again or provide a manual SKU.';
            }
            
        } else {
            // Manual SKU Validation: Check uniqueness
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE sku = ?");
            $stmt->execute([$sku]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'SKU **' . h($sku) . '** already exists. Please use a unique SKU.';
                $sku_is_unique = false;
            }
        }
    }


    if (empty($errors)) { 
        // --- Image Upload Logic... ---
        $imgPath = null;
        if (!empty($_FILES['image']['name'])) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectedType = finfo_file($fileInfo, $_FILES['image']['tmp_name']);
            finfo_close($fileInfo);

            if (in_array($detectedType, $allowedTypes) && $_FILES['image']['size'] < 2000000) {
                $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '', basename($_FILES['image']['name']));
                $uploadDir = realpath(__DIR__ . '/../uploads/');
                if ($uploadDir !== false) {
                    $destination = $uploadDir . DIRECTORY_SEPARATOR . $filename;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                        $imgPath = '/sweepxpress/uploads/' . $filename;
                    } else {
                        $errors[] = 'Error uploading image. Check permissions of the uploads folder.';
                    }
                } else {
                    $errors[] = 'Upload directory does not exist or is not accessible.';
                }
            } else {
                $errors[] = 'Invalid file type or size. Only JPG, PNG, or GIF under 2MB allowed.';
            }
        }

        if ($action === 'create' && empty($errors)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO products (sku, name, description, price, category, image_path) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$sku, $name, $desc, $price, $category, $imgPath]);
                
                // PRG Redirect on SUCCESS - uses SweetAlert pattern
                header('Location: products.php?success=Product_Added&name=' . urlencode($name));
                exit();
                
            } catch (PDOException $e) {
                $errors[] = 'Database error: ' . h($e->getMessage());
            }
        }
    }

    // Store persistent errors in session to be displayed after including header.
    if (!empty($errors)) {
        // I-set ang post data para hindi mawala ang input sa form
        $_SESSION['form_data'] = $_POST; 
        $_SESSION['alert_message'] = '<div class="alert alert-danger">❌ ' . implode('<br>❌ ', $errors) . '</div>';
    }
}

// ==========================================================
// 4. FETCH DATA AND INCLUDE HEADER
// ==========================================================
// Get total number of products
$totalResults = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalPages = ceil($totalResults / $limit);

// Ayusin ang page number if wala nang results sa kasalukuyang page
if ($page > $totalPages && $totalPages > 0) {
    $page = $totalPages;
    $offset = ($page - 1) * $limit;
}

// Fetch products for the current page
$stmt = $pdo->prepare("SELECT * FROM products ORDER BY id DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$items = $stmt->fetchAll();

// Kuhanin ang post data (if may error)
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']); // I-clear agad

// HTML output begins here!
require_once __DIR__ . '/../includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php 
// Display session errors from POST/DELETE handling
if (isset($_SESSION['alert_message'])) {
    echo $_SESSION['alert_message'];
    unset($_SESSION['alert_message']); 
}

// ====================================================================
// 5. SWEETALERT MESSAGE DISPLAY (Reads URL parameter)
// ====================================================================
if (isset($_GET['success'])) {
    $action = h($_GET['success']);
    $product_name = h(urldecode($_GET['name'] ?? 'Product'));
    $title = '';
    $text = '';

    switch($action) {
        case 'Product_Added':
            $title = 'Product Created!';
            $text = "The product **{$product_name}** has been successfully added to the inventory. ✅";
            break;
        case 'Product_Deleted':
            $title = 'Product Deleted!';
            $text = "The product **{$product_name}** was permanently removed. 🗑️";
            break;
        default:
            $title = 'Action Successful!';
            $text = 'The operation completed successfully.';
    }

    echo "<script>
    document.addEventListener('DOMContentLoaded', () => {
        Swal.fire({
            icon: 'success',
            title: '{$title}',
            html: '{$text}',
            confirmButtonColor: '#3085d6'
        }).then(() => {
            // Clear URL parameters without reloading again
            if (history.replaceState) {
                history.replaceState(null, null, window.location.pathname + window.location.search.replace(/[\?&]success=[^&]*(&name=[^&]*)?/, ''));
            }
        });
    });
    </script>";
}
?>

<div class="container my-5">
    <h1 class="mb-4 text-primary">🛍️ Product Inventory Manager</h1>

    <div class="card shadow-lg mb-5 border-0">
        <div class="card-header bg-success text-white">
            <h5 class="my-0">➕ Add New Product</h5>
        </div>
        <div class="card-body">
            <form id="addProductForm" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">

                <div class="mb-3">
                    <label class="form-label fw-bold">SKU (Stock Keeping Unit)</label>
                    <input type="text" name="sku" class="form-control" placeholder="Optional: Leave blank for auto-generate (Template: [Initials]-####)" value="<?php echo h($formData['sku'] ?? ''); ?>">
                    <small class="text-muted">Enter a unique code or leave blank to automatically generate an initial-based SKU (e.g., **INPB-1234**).</small>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Name</label>
                            <input type="text" name="name" class="form-control" required value="<?php echo h($formData['name'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Price (PHP)</label>
                            <input type="number" name="price" class="form-control" step="0.01" min="0" required value="<?php echo h($formData['price'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Description</label>
                    <textarea name="description" class="form-control" rows="2" required><?php echo h($formData['description'] ?? ''); ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Category</label>
                            <select name="category" class="form-select" required>
                                <option value="">-- Select Category --</option>
                                <option value="Cleaning Agents" <?php echo ($formData['category'] ?? '') === 'Cleaning Agents' ? 'selected' : ''; ?>>Cleaning Agents</option>
                                <option value="Cleaning Tools" <?php echo ($formData['category'] ?? '') === 'Cleaning Tools' ? 'selected' : ''; ?>>Cleaning Tools</option>
                                <option value="Adhesives & Tapes" <?php echo ($formData['category'] ?? '') === 'Adhesives & Tapes' ? 'selected' : ''; ?>>Adhesives & Tapes</option>
                                <option value="Floor & Surface Care" <?php echo ($formData['category'] ?? '') === 'Floor & Surface Care' ? 'selected' : ''; ?>>Floor & Surface Care</option>
                                <option value="Equipment" <?php echo ($formData['category'] ?? '') === 'Equipment' ? 'selected' : ''; ?>>Equipment</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Product Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                            <small class="text-muted">Max 2MB. JPG, PNG, GIF only.</small>
                        </div>
                    </div>
                </div>

                <button type="button" onclick="confirmAddProduct()" class="btn btn-success btn-lg mt-3 w-100">➕ Add Product</button>
            </form>
        </div>
    </div>

    <h3 class="mb-3 text-secondary">📦 Current Inventory (Page <?php echo $page; ?> of <?php echo $totalPages; ?>)</h3>
    
    <?php if ($totalResults > 0): ?>
    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">SKU</th>
                    <th scope="col">Image</th>
                    <th scope="col">Name</th>
                    <th scope="col">Category</th>
                    <th scope="col">Price</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $product): ?>
                <tr>
                    <td><?php echo h((string)$product['id']); ?></td>
                    <td><?php echo h($product['sku'] ?? 'N/A'); ?></td>
                    <td>
                        <?php if ($product['image_path']): ?>
                            <img src="<?php echo h($product['image_path']); ?>" 
                                alt="<?php echo h($product['name']); ?>" 
                                class="img-thumbnail" style="max-width: 60px; height: auto;">
                        <?php else: ?>
                            <span class="text-muted small">No image</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo h($product['name']); ?></td>
                    <td><?php echo h($product['category'] ?? '—'); ?></td>
                    <td><span class="fw-bold">₱<?php echo h(number_format($product['price'], 2)); ?></span></td>
                    <td>
                        <a href="edit_product.php?id=<?php echo h((string)$product['id']); ?>" 
                            class="btn btn-sm btn-outline-primary me-2">✏ Edit</a>
                        <a href="?delete=<?php echo h((string)$product['id']); ?>&page=<?php echo $page; ?>" 
                            class="btn btn-sm btn-outline-danger"
                            onclick="return confirm('WARNING: Are you sure you want to delete product ID <?php echo h((string)$product['id']); ?> (<?php echo addslashes($product['name']); ?>)? This will attempt to remove all associated order/cart items.')">🗑 Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <nav aria-label="Product list navigation">
        <ul class="pagination justify-content-center">
            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
            </li>
            
            <?php 
            // Display a limited set of page numbers around the current page
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);

            // Adjust start/end to always show 5 pages if possible
            if ($endPage - $startPage < 4) {
                if ($startPage > 1) {
                    $startPage = max(1, $endPage - 4);
                } else {
                    $endPage = min($totalPages, $startPage + 4);
                }
            }

            if ($startPage > 1): ?>
                <li class="page-item"><a class="page-link" href="?page=1">1</a></li>
                <?php if ($startPage > 2): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($endPage < $totalPages): ?>
                <?php if ($endPage < $totalPages - 1): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
                <li class="page-item"><a class="page-link" href="?page=<?php echo $totalPages; ?>"><?php echo $totalPages; ?></a></li>
            <?php endif; ?>

            <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
            </li>
        </ul>
    </nav>
    
    <?php else: ?>
    <div class="alert alert-info mt-4">
        No products found in the database. Start adding some products!
    </div>
    <?php endif; ?>

</div>

<script>
function confirmAddProduct() {
    const form = document.getElementById('addProductForm');
    
    // Simple client-side check to prevent Swal on empty fields
    if (!form.checkValidity()) {
        // Trigger browser's built-in validation messages
        form.reportValidity();
        return;
    }
    
    // Get the product name for the confirmation message
    const productName = form.elements['name'].value;

    Swal.fire({
        title: 'Confirm New Product?',
        html: `Are you sure you want to add **${productName}** to the inventory?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745', // Success green
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, Add Product!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Submit the form only if confirmed
            form.submit();
        }
    });
}
</script>

<?php 
// 6. Output Buffering End and Send Headers
require_once __DIR__ . '/../includes/footer.php'; 
ob_end_flush();
?>