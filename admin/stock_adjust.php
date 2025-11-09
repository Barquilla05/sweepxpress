<?php
require_once __DIR__ . '/../includes/auth.php';
if (!is_admin()) { header("Location: /sweepxpress/login.php"); exit; }
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/header.php';

$page_title = 'Inventory Adjustment';
$movement_type = 'ADJUST';

// Helper function
function send_alert_and_redirect($msg, $type = 'success') {
    $target_page = ($type === 'success') ? 'inventory.php' : 'stock_adjust.php';
    echo "<script>alert('" . addslashes($msg) . "'); window.location.href='{$target_page}';</script>";
    exit;
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $location_id = (int)($_POST['location_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);
    $adjustment_type = $_POST['adjustment_type'] ?? ''; // 'add' or 'deduct'
    $remark = trim($_POST['remark'] ?? '');

    if (!$product_id || !$location_id || $quantity <= 0 || !$adjustment_type) send_alert_and_redirect('Missing or invalid fields.', 'error');
    if (!$remark) send_alert_and_redirect('Remark is required for adjustment.', 'error');

    try {
        $pdo->beginTransaction();
        $user_name = $_SESSION['user_name'] ?? 'Admin';
        $delta = ($adjustment_type === 'add') ? $quantity : -$quantity; // Determine delta

        // 1. Check current stock and lock row
        $sel = $pdo->prepare("SELECT id, quantity FROM inventory WHERE product_id = :pid AND location_id = :lid FOR UPDATE");
        $sel->execute([':pid' => $product_id, ':lid' => $location_id]);
        $row = $sel->fetch(PDO::FETCH_ASSOC);

        $current_qty = $row ? (int)$row['quantity'] : 0;
        $newQty = $current_qty + $delta;

        if ($newQty < 0) {
            $pdo->rollBack();
            send_alert_and_redirect('Cannot deduct stock: results in negative inventory.', 'error');
        }

        // 2. Update/Insert inventory record
        if ($row) {
            $pdo->prepare("UPDATE inventory SET quantity=:q, updated_at=NOW() WHERE id=:id")
                ->execute([':q'=>$newQty, ':id'=>$row['id']]);
        } else {
            // Should only happen if $delta > 0
            if ($delta < 0) {
                 $pdo->rollBack();
                 send_alert_and_redirect('No stock to deduct for this new product/location combination.', 'error');
            }
            $pdo->prepare("INSERT INTO inventory (product_id, location_id, quantity, updated_at)
                           VALUES (:pid, :lid, :q, NOW())")
                ->execute([':pid'=>$product_id, ':lid'=>$location_id, ':q'=>$delta]);
        }

        // 3. Insert stock movement record (Always positive quantity in movements table, let type and remark explain)
        // Note: I will use the absolute value of the quantity in stock_movements, matching the IN/OUT records
        $movement_qty = abs($quantity);
        $remark_with_type = ($adjustment_type === 'add' ? '[ADD] ' : '[DEDUCT] ') . $remark;

        $ins = $pdo->prepare("INSERT INTO stock_movements (product_id, location_id, movement_type, quantity, remark, created_at, user_name)
                              VALUES (:pid, :lid, :mt, :qty, :remark, NOW(), :user)");
        $ins->execute([
            ':pid' => $product_id,
            ':lid' => $location_id,
            ':mt'  => $movement_type,
            ':qty' => $movement_qty,
            ':remark' => $remark_with_type,
            ':user' => $user_name
        ]);

        $pdo->commit();
        send_alert_and_redirect('Inventory adjustment successful. New Stock: '.$newQty, 'success');
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
            <form method="POST" action="stock_adjust.php">
                <input type="hidden" name="action" value="adjustment">

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
                    <label class="form-label">Adjustment Type</label>
                    <!-- Added me-4 (margin-right) to the label for the radio buttons to increase spacing from the left edge of the column -->
                    <div class="form-check mb-1">
                      <input class="form-check-input" type="radio" name="adjustment_type" id="adjust_add" value="add" required>
                      <label class="form-check-label me-4" for="adjust_add">Add to Stock (e.g., Found stock)</label>
                    </div>
                    <div class="form-check mb-1">
                      <input class="form-check-input" type="radio" name="adjustment_type" id="adjust_deduct" value="deduct">
                      <label class="form-check-label me-4" for="adjust_deduct">Deduct from Stock (e.g., Damaged/Lost)</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="quantity" class="form-label">Adjustment Quantity</label>
                    <input type="number" name="quantity" id="quantity" class="form-control" min="1" required>
                </div>

                <div class="mb-3">
                    <label for="remark" class="form-label">Remark (REQUIRED for Adjustment)</label>
                    <textarea name="remark" id="remark" class="form-control" rows="2" placeholder="Explain the reason for adjustment (e.g., Inventory count variance, Item damaged)" required></textarea>
                </div>

                <button type="submit" id="confirm_button" class="btn btn-danger btn-lg">Confirm Adjustment</button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addButton = document.getElementById('adjust_add');
    const deductButton = document.getElementById('adjust_deduct');
    const confirmButton = document.getElementById('confirm_button');

    function updateConfirmButton() {
        if (addButton.checked) {
            confirmButton.textContent = 'Confirm Stock Addition';
            confirmButton.classList.remove('btn-danger', 'btn-warning', 'btn-secondary');
            confirmButton.classList.add('btn-success');
        } else if (deductButton.checked) {
            confirmButton.textContent = 'Confirm Stock Deduction';
            confirmButton.classList.remove('btn-success', 'btn-warning', 'btn-secondary');
            confirmButton.classList.add('btn-danger');
        } else {
            confirmButton.textContent = 'Confirm Adjustment';
            confirmButton.classList.remove('btn-success', 'btn-danger', 'btn-warning');
            confirmButton.classList.add('btn-secondary'); // Default color if neither is selected
        }
    }

    // Attach listeners to radio buttons
    addButton.addEventListener('change', updateConfirmButton);
    deductButton.addEventListener('change', updateConfirmButton);

    // Initial call (since 'add' is required, it will likely be the default, but we check anyway)
    updateConfirmButton();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
