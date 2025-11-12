<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/header.php';

$message = '';
$id = 0;
$product = null;
$images = [];

// Determine product id (GET first, POST may override)
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $id = (int)$_GET['id'];
}

// SKU generator function (can also move to config.php if preferred)
function generateSKU($productName) {
    $prefix = strtoupper(substr(preg_replace('/\s+/', '', $productName), 0, 3));
    $randomNumber = rand(1000, 9999);
    return $prefix . $randomNumber;
}

// POST handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    // prefer id from POST when present (forms include it)
    if (isset($_POST['id']) && filter_var($_POST['id'], FILTER_VALIDATE_INT)) {
        $id = (int)$_POST['id'];
    }

    if ($id <= 0) {
        $message = '<div class="alert alert-danger">Invalid product ID.</div>';
    } else {
        // --- DELETE IMAGE (Secondary) ---
        if ($action === 'delete_image' && isset($_POST['image_id']) && filter_var($_POST['image_id'], FILTER_VALIDATE_INT)) {
            $imageId = (int)$_POST['image_id'];
            try {
                $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE id = ? AND product_id = ?");
                $stmt->execute([$imageId, $id]);
                $image = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($image) {
                    // compute server file path
                    $relative = ltrim(str_replace('/sweepxpress/', '', $image['image_path']), '/');
                    $filePath = realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR . $relative;

                    if ($filePath && file_exists($filePath)) {
                        @unlink($filePath);
                    }
                    $pdo->prepare("DELETE FROM product_images WHERE id = ?")->execute([$imageId]);
                    $message = '<div class="alert alert-success">Image deleted successfully.</div>';
                } else {
                    $message = '<div class="alert alert-warning">Image not found.</div>';
                }
            } catch (PDOException $e) {
                error_log("Image deletion error: " . $e->getMessage());
                $message = '<div class="alert alert-danger">Database error while deleting image.</div>';
            }
        }

        // --- ADD IMAGE(S) (Secondary) ---
        if ($action === 'add_image' && !empty($_FILES['new_image']['name'][0])) {
            $allowedTypes = ['image/jpeg','image/png','image/gif'];
            $uploadDir = realpath(__DIR__ . '/../') . '/uploads/';

            // Check folder existence
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    die('<div class="alert alert-danger">❌ Failed to create upload directory: ' . htmlspecialchars($uploadDir) . '</div>');
                }
            }

            // Check folder writable
            if (!is_writable($uploadDir)) {
                die('<div class="alert alert-danger">❌ Upload directory is not writable: ' . htmlspecialchars($uploadDir) . '</div>');
            }

            $errors = [];
            foreach ($_FILES['new_image']['tmp_name'] as $key => $tmpName) {
                $filename = $_FILES['new_image']['name'][$key];
                $fileType = $_FILES['new_image']['type'][$key] ?? mime_content_type($tmpName);
                $fileSize = $_FILES['new_image']['size'][$key];

                if (in_array($fileType, $allowedTypes) && $fileSize < 2000000) {
                    $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '', $filename);
                    $destination = $uploadDir . $safeName;
                    if (move_uploaded_file($tmpName, $destination)) {
                        $imgPath = '/sweepxpress/uploads/' . $safeName;
                        try {
                            $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path) VALUES (?, ?)");
                            $stmt->execute([$id, $imgPath]);
                        } catch (PDOException $e) {
                            error_log("Image insert error: " . $e->getMessage());
                            $errors[] = "DB error saving $filename";
                            if (file_exists($destination)) @unlink($destination);
                        }
                    } else {
                        $errors[] = "Failed to move $filename";
                    }
                } else {
                    $errors[] = "$filename: invalid type or file too large";
                }
            }

            if (empty($errors)) {
                $message = '<div class="alert alert-success">✅ Image(s) added successfully.</div>';
            } else {
                $message = '<div class="alert alert-warning">⚠ Some images were not uploaded: ' . htmlspecialchars(implode(', ', $errors)) . '</div>';
            }
        }

        // --- UPDATE DETAILS ---
        if ($action === 'update_details') {
            $name = trim($_POST['name'] ?? '');
            $skuInput = trim($_POST['sku'] ?? '');
            $price = filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT);
            $desc = trim($_POST['description'] ?? '');
            $stock = filter_var($_POST['stock'] ?? 0, FILTER_VALIDATE_INT);

            if ($name === '' || $price === false || $desc === '' || $stock === false) {
                $message = '<div class="alert alert-danger">Invalid form data submitted for product details.</div>';
            } else {
                // Use provided SKU or generate if empty
                $sku = $skuInput === '' ? generateSKU($name) : $skuInput;

                try {
                    $stmt = $pdo->prepare("UPDATE products SET name = ?, sku = ?, description = ?, price = ?, stock = ? WHERE id = ?");
                    $stmt->execute([$name, $sku, $desc, $price, $stock, $id]);
                    $message = '<div class="alert alert-success">✅ Product details updated successfully. SKU: ' . htmlspecialchars($sku) . '</div>';
                } catch (PDOException $e) {
                    error_log("Product update error: " . $e->getMessage());
                    $message = '<div class="alert alert-danger">Database error while updating product details.</div>';
                }
            }
        }
        
        // --- UPDATE PRIMARY IMAGE ---
        if ($action === 'update_primary_image' && isset($_FILES['primary_image']) && $_FILES['primary_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['primary_image'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $maxSize = 2000000; // 2MB
            $uploadDir = realpath(__DIR__ . '/../') . '/uploads/';
            
            if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
                 $message = '<div class="alert alert-danger">❌ Upload directory error.</div>';
            } else if (in_array(mime_content_type($file['tmp_name']), $allowedTypes) && $file['size'] <= $maxSize) {
                
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $safeName = time() . '_' . uniqid() . '.' . $extension; 
                $destination = $uploadDir . $safeName;
                $newImagePath = '/sweepxpress/uploads/' . $safeName;

                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    try {
                        // 1. Fetch current image path to delete the old file
                        $stmt = $pdo->prepare("SELECT image_path FROM products WHERE id = ?");
                        $stmt->execute([$id]);
                        $oldProduct = $stmt->fetch(PDO::FETCH_ASSOC);

                        // 2. Update the database with the new path
                        $stmt = $pdo->prepare("UPDATE products SET image_path = ? WHERE id = ?");
                        $stmt->execute([$newImagePath, $id]);

                        // 3. Delete the old file if it exists and is not empty
                        if ($oldProduct && !empty($oldProduct['image_path'])) {
                            $relative = ltrim(str_replace('/sweepxpress/', '', $oldProduct['image_path']), '/');
                            $filePath = realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR . $relative;
                            if ($filePath && file_exists($filePath) && is_file($filePath)) {
                                @unlink($filePath);
                            }
                        }
                        
                        $message = '<div class="alert alert-success">✅ Primary image updated successfully.</div>';

                    } catch (PDOException $e) {
                        error_log("Primary image update error: " . $e->getMessage());
                        // Rollback file upload if DB fails
                        if (file_exists($destination)) @unlink($destination); 
                        $message = '<div class="alert alert-danger">Database error while updating primary image.</div>';
                    }
                } else {
                    $message = '<div class="alert alert-danger">Failed to move uploaded file.</div>';
                }
            } else {
                $message = '<div class="alert alert-danger">Invalid file type or file too large for primary image.</div>';
            }
        }
    }
}

// After any POST handling, (re)fetch the product and images so variables exist
if ($id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY id ASC");
            $stmt->execute([$id]);
            $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $message = $message ?: '<div class="alert alert-danger">Product not found.</div>';
            $product = null;
            $images = [];
        }
    } catch (PDOException $e) {
        error_log("Product fetch error: " . $e->getMessage());
        $message = '<div class="alert alert-danger">Database error while fetching product.</div>';
        $product = null;
        $images = [];
    }
} else {
    $message = $message ?: '<div class="alert alert-danger">Invalid product ID.</div>';
    $product = null;
    $images = [];
}

// Display any message
if (!empty($message)) {
    echo $message;
}
?>

<?php if ($product): ?>

<div class="container my-5">
    
    <div class="mb-3">
        <a href="products.php" class="btn btn-secondary">← Back to Products</a>
    </div>

    <h1>Edit Product: <?php echo htmlspecialchars($product['name']); ?></h1>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">Update Details</div>
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="action" value="update_details">
                <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
                
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">SKU (Optional, leave blank to auto-generate)</label>
                    <input type="text" name="sku" class="form-control" maxlength="20" value="<?php echo htmlspecialchars($product['sku'] ?? ''); ?>" placeholder="e.g. CLE1234">
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Price (PHP)</label>
                    <input type="number" name="price" class="form-control" step="0.01" min="0" value="<?php echo htmlspecialchars((string)$product['price']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Stock Quantity</label>
                    <input type="number" name="stock" class="form-control" min="0" value="<?php echo htmlspecialchars((string)$product['stock']); ?>" required>
                </div>

                <button type="submit" class="btn btn-success">💾 Save Details</button>
            </form>
        </div>
    </div>
    
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-danger text-white">Primary Product Image</div>
        <div class="card-body">
            
            <div class="mb-3">
                <label class="form-label">Current Primary Image</label>
                <?php if ($product['image_path']): ?>
                    <img src="<?php echo htmlspecialchars($product['image_path']); ?>" class="img-thumbnail d-block mb-3" style="max-width: 150px; height: auto;">
                <?php else: ?>
                    <p class="text-muted">No primary image set.</p>
                <?php endif; ?>
            </div>
            
            <hr>

            <h5>Replace Primary Image</h5>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_primary_image">
                <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
                
                <div class="mb-3">
                    <input type="file" name="primary_image" class="form-control" accept="image/*" required>
                    <small class="text-muted">Max 2MB. JPG, PNG, GIF only.</small>
                </div>
                
                <button type="submit" class="btn btn-danger">🔄 Replace Primary Image</button>
            </form>
            
        </div>
    </div>
    
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-secondary text-white">Product Images (<?php echo count($images); ?>)</div>
        <div class="card-body">
            
            <div class="d-flex flex-wrap gap-3 mb-4">
                <?php foreach ($images as $image): ?>
                <div class="image-wrapper text-center">
                    <img src="<?php echo htmlspecialchars($image['image_path']); ?>" class="img-thumbnail" style="max-width: 100px; height: auto;">
                    <form method="post" style="margin-top: 5px;">
                        <input type="hidden" name="action" value="delete_image">
                        <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
                        <input type="hidden" name="image_id" value="<?php echo (int)$image['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this image?')">🗑 Delete</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
            
            <hr>

            <h5>Add New Image(s)</h5>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_image">
                <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
                
                <div class="mb-3">
                    <input type="file" name="new_image[]" class="form-control" accept="image/*" multiple required>
                    <small class="text-muted">Max 2MB per file. JPG, PNG, GIF only.</small>
                </div>
                
                <button type="submit" class="btn btn-info text-white">➕ Upload Image(s)</button>
            </form>
            
        </div>
    </div>
</div>

<?php else: ?>
    <?php if (empty($message)): ?>
        <div class="container my-5">
            <div class="alert alert-danger text-center">Product ID not specified, not valid, or product not found in the database.</div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php // require_once __DIR__ . '/../includes/footer.php'; ?>
