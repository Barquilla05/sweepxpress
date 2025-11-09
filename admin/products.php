<?php 
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/header.php';

$message = '';

// --- form handling logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $price = filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0]]);
    $desc = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');

    if ($name === '' || $price === false || $desc === '' || $category === '') {
        $message = '<div class="alert alert-danger">All fields are required, including category.</div>';
    } else {
        $imgPath = null;
        if (!empty($_FILES['image']['name'])) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectedType = finfo_file($fileInfo, $_FILES['image']['tmp_name']);
            finfo_close($fileInfo);

            if (in_array($detectedType, $allowedTypes) && $_FILES['image']['size'] < 2000000) {
                $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '', $_FILES['image']['name']);
                $uploadDir = realpath(__DIR__ . '/../uploads/');
                if ($uploadDir === false) {
                    $message = '<div class="alert alert-danger">Upload directory does not exist or is not accessible.</div>';
                } else {
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
                $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category, image_path) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $desc, $price, $category, $imgPath]);
                $message = '<div class="alert alert-success">✅ Product added successfully.</div>';
            } catch (PDOException $e) {
                $message = '<div class="alert alert-danger">Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    }
}

// DELETE
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id > 0) {
        try {
            $stmt = $pdo->prepare("SELECT image_path FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $product = $stmt->fetch();

            if ($product && !empty($product['image_path'])) {
                $imageFilePath = __DIR__ . '/..' . str_replace('/sweepxpress', '', $product['image_path']);
                if (file_exists($imageFilePath)) unlink($imageFilePath);
            }

            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $message = '<div class="alert alert-success">🗑 Product deleted successfully.</div>';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

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
          <label class="form-label">Name</label>
          <input type="text" name="name" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="3" required></textarea>
        </div>

        <div class="mb-3">
          <label class="form-label">Price (PHP)</label>
          <input type="number" name="price" class="form-control" step="0.01" min="0" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Category</label>
          <select name="category" class="form-select" required>
            <option value="">-- Select Category --</option>
            <option value="Cleaning Agents">Cleaning Agents</option>
            <option value="Cleaning Tools">Cleaning Tools</option>
            <option value="Adhesives & Tapes">Adhesives & Tapes</option>
            <option value="Floor & Surface Care">Floor & Surface Care</option>
            <option value="Equipment">Equipment</option>
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
               onclick="return confirm('Are you sure you want to delete this product?')">🗑 Delete</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
