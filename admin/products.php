<?php 
// 1. CRITICAL: Output Buffering - Kailangan sa pinaka-umpisa
ob_start(); 

require_once __DIR__ . '/../config.php'; 
require_once __DIR__ . '/../includes/auth.php';

$message = '';

// Tiyakin na ang user ay admin
if (!is_logged_in() || $_SESSION['user']['role'] !== 'admin') {
    header("Location: /sweepxpress/login.php");
    exit();
}

require_once __DIR__ . '/../includes/header.php';


// Category Prefix Mapping (Hindi na ginagamit sa SKU generation)
$category_prefixes = [
    'Cleaning Agents' => 'CA',
    'Cleaning Tools' => 'CT',
    'Adhesives & Tapes' => 'AT',
    'Floor & Surface Care' => 'FS',
    'Equipment' => 'EQ',
];


// --- HANDLE DELETE (GET) ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id > 0) {
        try {
            $pdo->beginTransaction();

            // I-DELETE ang mga konektadong entries (Foreign Key Fix)
            $stmt_order = $pdo->prepare("DELETE FROM order_items WHERE product_id = ?");
            $stmt_order->execute([$id]);

            // I-delete ang image file
            $stmt = $pdo->prepare("SELECT name, image_path FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $product = $stmt->fetch();
            $deleted_name = $product['name'] ?? 'Product';

            if ($product && !empty($product['image_path'])) {
                $relativePath = str_replace('/sweepxpress', '', $product['image_path']); 
                $imageFilePath = __DIR__ . '/..' . $relativePath; 
                
                if (file_exists($imageFilePath) && is_file($imageFilePath)) {
                    @unlink($imageFilePath); 
                }
            }

            // I-DELETE ang Product sa main table
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            
            $pdo->commit();

            header("Location: products.php?msg=deleted&name=" . urlencode($deleted_name));
            exit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = '<div class="alert alert-danger">Database error: Could not delete product. Check for remaining database constraints: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}


// --- HANDLE CREATE (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $sku = trim($_POST['sku'] ?? ''); 
    $name = trim($_POST['name'] ?? '');
    $price = filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0]]);
    $desc = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');

    $sku_ok = true;
    
    // Check for required fields first
    if ($name === '' || $price === false || $desc === '' || $category === '') {
        $message = '<div class="alert alert-danger">All fields are required, including category.</div>';
        $sku_ok = false;
    }

    if ($sku_ok) {
        // SKU LOGIC AT VALIDATION
        if ($sku === '') {
            // -----------------------------------------------------------------------------------
            // AUTOMATIC SKU GENERATION LOGIC (Initial-Based: Initials - 4 digits)

            // 1. KUNIN ANG INITIALS MULA SA PRODUCT NAME
            $words = explode(' ', $name);
            $initials = '';
            
            // Loop sa bawat salita para kuhanin ang unang letra
            foreach ($words as $word) {
                if (!empty($word)) {
                    // Kinukuha ang unang character at ginagawang uppercase
                    $initials .= strtoupper($word[0]);
                }
            }
            
            // Limitahan ang initials sa 4 na letra lang (hal. INPB)
            $prefix = substr($initials, 0, 4); 
            
            // 2. GUMAWA NG 4 NA RANDOM DIGITS
            $length = 4;
            $digits = '0123456789';
            $sku_is_unique = false;
            
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE sku = ?");
            
            // Loop hanggang maging unique ang SKU
            while (!$sku_is_unique) {
                $random_part = '';
                for ($i = 0; $i < $length; $i++) {
                    $random_part .= $digits[random_int(0, strlen($digits) - 1)];
                }
                
                // FINAL SKU FORMAT: [INITIALS] - [RANDOM_NUMBER] (e.g., INPB-1234)
                $sku = $prefix . '-' . $random_part; // <--- DITO IDINAGDAG ANG HYPHEN
                
                // I-check sa database
                $stmt->execute([$sku]);
                if ($stmt->fetchColumn() == 0) {
                    $sku_is_unique = true; // Unique, tapos na ang loop
                }
            }
            // -----------------------------------------------------------------------------------
            
        } else {
            // Manual SKU Validation: Check uniqueness
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE sku = ?");
            $stmt->execute([$sku]);
            if ($stmt->fetchColumn() > 0) {
                $message = '<div class="alert alert-danger">SKU **' . htmlspecialchars($sku) . '** already exists. Please use a unique SKU.</div>';
                $sku_ok = false;
            }
        }
    }


    if ($sku_ok && empty($message)) { 
        // Image Upload Logic...
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
                        $message = '<div class="alert alert-danger">Error uploading image. Check permissions of the uploads folder.</div>';
                    }
                }
            } else {
                $message = '<div class="alert alert-warning">Invalid file type or size. Only JPG, PNG, or GIF under 2MB allowed.</div>';
            }
        }

        if ($action === 'create' && empty($message)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO products (sku, name, description, price, category, image_path) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$sku, $name, $desc, $price, $category, $imgPath]);
                
                header("Location: products.php?msg=added&sku=" . urlencode($sku));
                exit();
                
            } catch (PDOException $e) {
                $message = '<div class="alert alert-danger">Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    }
}


// --- MESSAGE DISPLAY LOGIC ---
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'added') {
        echo '<div class="alert alert-success">✅ Product added successfully. SKU: **' . htmlspecialchars($_GET['sku'] ?? '') . '**</div>';
    } else if ($_GET['msg'] === 'deleted') {
        echo '<div class="alert alert-success">🗑 Product **' . htmlspecialchars($_GET['name'] ?? 'deleted') . '** successfully deleted.</div>';
    }
}

// Show any error message from POST/DELETE processing
if (!empty($message)) echo $message;

// Fetch products
$items = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
?>

<div class="container my-5">
    <h1 class="mb-4">🧾 Product Management</h1>
    
    <div class="card shadow-sm mb-5">
        <div class="card-header bg-primary text-white">Add New Product</div>
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">

                <div class="mb-3">
                    <label class="form-label">SKU (Stock Keeping Unit)</label>
                    <input type="text" name="sku" class="form-control" placeholder="Optional: Leave blank for auto-generate (Template: [Initials]-####)">
                    <small class="text-muted">Enter a unique code or leave blank to automatically generate an initial-based SKU (e.g., **INPB-1234**).</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Price (PHP)</label>
                    <input type="number" name="price" class="form-control" step="0.01" min="0" required value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select" required>
                        <option value="">-- Select Category --</option>
                        <option value="Cleaning Agents" <?php echo ($_POST['category'] ?? '') === 'Cleaning Agents' ? 'selected' : ''; ?>>Cleaning Agents</option>
                        <option value="Cleaning Tools" <?php echo ($_POST['category'] ?? '') === 'Cleaning Tools' ? 'selected' : ''; ?>>Cleaning Tools</option>
                        <option value="Adhesives & Tapes" <?php echo ($_POST['category'] ?? '') === 'Adhesives & Tapes' ? 'selected' : ''; ?>>Adhesives & Tapes</option>
                        <option value="Floor & Surface Care" <?php echo ($_POST['category'] ?? '') === 'Floor & Surface Care' ? 'selected' : ''; ?>>Floor & Surface Care</option>
                        <option value="Equipment" <?php echo ($_POST['category'] ?? '') === 'Equipment' ? 'selected' : ''; ?>>Equipment</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Product Image</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                </div>

                <button type="submit" class="btn btn-success">➕ Add Product</button>
            </form>
        </div>
    </div>

    <h3 class="mb-3">All Products</h3>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>SKU</th> 
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $product): ?>
                <tr>
                    <td><?php echo htmlspecialchars((string)$product['id']); ?></td>
                    <td><?php echo htmlspecialchars($product['sku'] ?? 'N/A'); ?></td> 
                    <td>
                        <?php if ($product['image_path']): ?>
                            <img src="<?php echo htmlspecialchars($product['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 class="img-thumbnail" style="max-width: 60px;">
                        <?php else: ?>
                            <span class="text-muted">No image</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td><?php echo htmlspecialchars($product['category'] ?? '—'); ?></td>
                    <td>₱<?php echo htmlspecialchars(number_format($product['price'], 2)); ?></td>
                    <td>
                        <a href="edit_product.php?id=<?php echo htmlspecialchars((string)$product['id']); ?>" 
                           class="btn btn-sm btn-primary">✏ Edit</a>
                        <a href="?delete=<?php echo htmlspecialchars((string)$product['id']); ?>" 
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('WARNING: Are you sure you want to delete product ID <?php echo htmlspecialchars((string)$product['id']); ?>? This will attempt to remove all associated order/cart items.')">🗑 Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>