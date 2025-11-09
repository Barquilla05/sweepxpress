<?php
require_once __DIR__ . '/../includes/auth.php';
if (!is_admin()) { header("Location: /sweepxpress/login.php"); exit; }
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/header.php';

$page_title = 'Stock-Out (Deduct Stock)';
$movement_type = 'OUT';

// Helper function
function send_alert_and_redirect($msg, $type = 'success') {
    $target_page = ($type === 'success') ? 'inventory.php' : 'stock_out.php';
    // Use an alert and redirect via JS
    echo "<script>alert('". addslashes($msg) ."'); window.location.href='{$target_page}';</script>";
    exit;
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $location_id = (int)($_POST['location_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);
    $remark = trim($_POST['remark'] ?? ''); // Optional, but useful for tracking

    if (!$product_id || !$location_id || $quantity <= 0) send_alert_and_redirect('Missing or invalid fields.', 'error');

    try {
        $pdo->beginTransaction();
        $user_name = $_SESSION['user_name'] ?? 'Admin';
        // Removed: $delta = -$quantity; // Not needed with direct subtraction

        // 1. Check current stock and lock row (Crucial for concurrency!)
        // 'FOR UPDATE' ensures no other transaction can modify this row until the current transaction commits or rolls back.
        $sel = $pdo->prepare("SELECT id, quantity FROM inventory WHERE product_id = :pid AND location_id = :lid FOR UPDATE");
        $sel->execute([':pid' => $product_id, ':lid' => $location_id]);
        $row = $sel->fetch(PDO::FETCH_ASSOC);

        if (!$row || (int)$row['quantity'] < $quantity) {
            $pdo->rollBack();
            send_alert_and_redirect('Not enough stock at this location.', 'error');
        }

        // 2. Update inventory record - THE DEDUCTION LOGIC IS NOW CLEARER
        // ------------------------------------------------------------------
        $currentQty = (int)$row['quantity'];
        // Use direct subtraction for clarity:
        $newQty = $currentQty - $quantity;
        // ------------------------------------------------------------------
        
        $pdo->prepare("UPDATE inventory SET quantity=:q, updated_at=NOW() WHERE id=:id")
            ->execute([':q'=>$newQty, ':id'=>$row['id']]);

        // 3. Insert stock movement record (Logs the Stock-Out transaction)
        $ins = $pdo->prepare("INSERT INTO stock_movements (product_id, location_id, movement_type, quantity, remark, created_at, user_name)
                             VALUES (:pid, :lid, :mt, :qty, :remark, NOW(), :user)");
        $ins->execute([
            ':pid' => $product_id,
            ':lid' => $location_id,
            ':mt'  => $movement_type, // 'OUT'
            ':qty' => $quantity, // The positive quantity that was moved out
            ':remark' => $remark,
            ':user' => $user_name
        ]);

        $pdo->commit();
        send_alert_and_redirect('Stock-Out successful. Inventory updated.', 'success');
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        send_alert_and_redirect('Error: '.$e->getMessage(), 'error');
    }
}

// Fetch products and locations for the form
$products = $pdo->query("SELECT id, name, sku, price FROM products ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$locations = $pdo->query("SELECT id, name FROM locations ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

?>
<div class="container-fluid min-vh-100 bg-light px-0 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 px-4 px-lg-5">
        <h1 class="display-6 fw-bold text-primary">
            <?php echo $page_title; ?>
        </h1>
        <a href="inventory.php" class="btn btn-secondary btn-lg shadow-sm">⬅ Back to Inventory</a>
    </div>

    <div class="card shadow-lg border-0 w-100 p-4 p-lg-5">
        <div class="card-body">
            <form method="POST" action="stock_out.php">
                <input type="hidden" name="action" value="stock_out">

                <div class="mb-3">
                    <label for="product_id" class="form-label">Product Name</label>
                    <select name="product_id" id="product_id" class="form-select" required>
                        <option value="">Select Product</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?php echo $p['id']; ?>" data-price="<?php echo $p['price']; ?>">
                                <?php echo htmlspecialchars($p['name']) . (empty($p['sku']) ? '' : ' (' . $p['sku'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="location_id" class="form-label">Location</label>
                    <select name="location_id" id="location_id" class="form-select" required>
                        <option value="">Select Location</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?php echo $loc['id']; ?>"><?php echo htmlspecialchars($loc['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="quantity" class="form-label">Quantity to Deduct</label>
                    <input type="number" name="quantity" id="quantity" class="form-control" min="1" required>
                </div>

                <div class="mb-3">
                    <label for="remark" class="form-label">Remark (e.g., Customer sale, Transfer)</label>
                    <textarea name="remark" id="remark" class="form-control" rows="2" placeholder="e.g., Customer sale order #123"></textarea>
                </div>

                <button type="submit" class="btn btn-warning btn-lg">Confirm Stock-Out</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>