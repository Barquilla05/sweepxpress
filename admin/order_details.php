<?php
// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
if (!is_admin()) {
    header("Location: /sweepxpress/login.php");
    exit;
}
require_once __DIR__ . '/../includes/header.php';

// Helper function for basic HTML escaping
if (!function_exists('h')) {
    function h($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

// --- UPDATED HELPER FUNCTION ---
function get_status_badge_class($status) {
    $status = strtolower($status);
    
    return match($status) {
        'delivered', 'completed' => 'bg-success text-white',
        'preparing', 'shipped' => 'bg-info text-white',
        'pending' => 'bg-warning text-dark',
        'cancelled' => 'bg-danger text-white',
        default => 'bg-secondary text-white'
    };
}
// --- END HELPER FUNCTION ---


$orderId = intval($_GET['id'] ?? 0);
if (!$orderId) {
    echo "<div class='container p-5'><div class='alert alert-danger'>❌ Order not found or ID is missing.</div></div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$message = '';

// =======================================================================
// 1. HANDLE DELIVERY UPDATE (PHP Logic)
// =======================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $status = $_POST['status'] ?? null;
    $date = $_POST['delivery_date'] ?? null;

    if ($status) {
        $finalStatus = ($status === 'shipped') ? 'preparing' : $status;

        $stmt = $pdo->prepare("SELECT id FROM deliveries WHERE order_id = ?");
        $stmt->execute([$orderId]);
        $deliveryExists = $stmt->fetch();

        try {
            if ($deliveryExists) {
                $updateStmt = $pdo->prepare("UPDATE deliveries SET status=?, delivery_date=? WHERE order_id=?");
                $updateStmt->execute([$finalStatus, $date, $orderId]);
            } else {
                $infoStmt = $pdo->prepare("SELECT o.address, o.customer_name FROM orders o WHERE o.id = ?");
                $infoStmt->execute([$orderId]);
                $customer_info = $infoStmt->fetch();

                $insertStmt = $pdo->prepare("INSERT INTO deliveries 
                    (order_id, customer_name, address, status, delivery_date) 
                    VALUES (?, ?, ?, ?, ?)");
                $insertStmt->execute([
                    $orderId,
                    $customer_info['customer_name'] ?? 'Unknown',
                    $customer_info['address'] ?? 'Unknown',
                    $finalStatus,
                    $date
                ]);
            }

            $updateOrderStmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $updateOrderStmt->execute([$finalStatus, $orderId]);
            
            $message = "<div class='alert alert-success mt-3' role='alert'>✅ Delivery status updated to **" . ucfirst($finalStatus) . "** successfully!</div>";
        } catch (PDOException $e) {
            error_log("Delivery update failed: " . $e->getMessage());
            $message = '<div class="alert alert-danger mt-3" role="alert">❌ Error updating delivery status.</div>';
        }
    }
}

// =======================================================================
// 2. FETCH ALL ORDER DATA (PHP Logic)
// =======================================================================
try {
    $stmtOrder = $pdo->prepare("
        SELECT o.*, u.name AS user_name, u.email AS user_email 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?
    ");
    $stmtOrder->execute([$orderId]);
    $order = $stmtOrder->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo "<div class='container p-5'><div class='alert alert-danger'>Order not found.</div></div>";
        require_once __DIR__ . '/../includes/footer.php';
        exit;
    }

    $order['user_name'] = $order['user_name'] ?? h($order['customer_name']) . ' (Guest)';
    $order['user_email'] = $order['user_email'] ?? 'N/A';
    
    $stmtItems = $pdo->prepare("
        SELECT oi.*, p.name 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ");
    $stmtItems->execute([$orderId]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    $stmtDelivery = $pdo->prepare("SELECT * FROM deliveries WHERE order_id = ?");
    $stmtDelivery->execute([$orderId]);
    $delivery = $stmtDelivery->fetch(PDO::FETCH_ASSOC);

    $isCompleted = (strtolower($order['status']) === 'completed');

} catch (PDOException $e) {
    die("<div class='container p-5'><div class='alert alert-danger'>Database error: " . h($e->getMessage()) . "</div></div>");
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
    /* Custom style for the PO Due Date box */
    .po-due-date {
        background-color: #e0f7fa; /* Light cyan/blue background */
        border-radius: 0.5rem;
        padding: 0.75rem;
        border: 1px solid #b2ebf2;
        font-weight: 600;
        margin-top: 1rem;
    }
</style>

<div class="container p-4 p-lg-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="allorders.php" class="btn btn-primary shadow-sm">
            <i class="fas fa-arrow-left me-2"></i> Back to All Orders
        </a>
        <a href="generate_order_pdf.php?id=<?= $orderId; ?>" target="_blank" class="btn btn-info text-white shadow-sm">
            <i class="fas fa-file-pdf me-2"></i> Download Invoice PDF
        </a>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-5">
        <h1 class="fw-bolder text-dark">
            <i class="fas fa-receipt text-primary me-2"></i> Order #<?= $orderId; ?>
        </h1>
        <span class="badge rounded-pill <?= get_status_badge_class($order['status']); ?> fs-5 py-2 px-4">
            Order Status: <?= ucfirst(h($order['status'])); ?>
        </span>
    </div>
    <?= $message; ?>

    <div class="row g-4">
        
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-file-invoice-dollar me-2"></i> Order Summary</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Date Placed:</strong> <?= date("F j, Y h:i A", strtotime($order['created_at'])); ?></p>
                    <h4 class="fw-bold text-success mb-4">Total: ₱<?= number_format($order['total'], 2); ?></h4>
                    
                    <hr>

                    <p class="mb-1"><strong>Customer:</strong> 
                        <?php if ($order['user_id']): ?>
                            <a href="user_actions.php?id=<?= $order['user_id']; ?>" class="text-decoration-none"><?= $order['user_name']; ?></a>
                        <?php else: ?>
                            <?= $order['user_name']; ?>
                        <?php endif; ?>
                    </p>
                    <p class="mb-1"><strong>Email:</strong> <?= $order['user_email']; ?></p>
                    <p class="mb-1"><strong>Name on Order:</strong> <?= h($order['customer_name']); ?></p>
                    <p class="mb-0"><strong>Notes:</strong> <?= nl2br(h($order['notes'] ?? 'N/A')); ?></p>
                    
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-secondary text-white">
                    <h5>🚚 Manage Delivery</h5>
                </div>
                <div class="card-body">
                    
                    <?php if ($isCompleted): ?>
                        <div class="alert alert-success fw-bold" role="alert">
                            <i class="fas fa-lock me-2"></i> **Order Completed!** This record is locked as the customer has confirmed receipt.
                        </div>
                    <?php endif; ?>

                    <h6 class="fw-bold mb-2">Shipping Information</h6>
                    <p class="mb-3"><strong>Address:</strong> <?= nl2br(h($order['address'])); ?></p>
                    <p class="mb-4">
                        <strong>Payment:</strong> <?= h($order['payment_method']); ?> 
                        (<span class="badge bg-dark text-white"><?= h($order['payment_status']); ?></span>)
                    </p>
                    
                    <div class="po-due-date mb-4">
                        **PO Due Date (Net 60)** **2026-01-08**
                    </div>
                    
                    <hr>
                    
                    <form method="post" class="row g-3">
                        <div class="col-md-6">
                            <label for="status" class="form-label fw-bold">Update Status</label>
                            <select name="status" id="status" class="form-select" required <?= $isCompleted ? 'disabled' : ''; ?>>
                                <?php
                                $statuses = ['pending','preparing','delivered','cancelled','completed'];
                                $currentStatus = $delivery['status'] ?? 'pending';
                                
                                $displayStatus = (strtolower($currentStatus) === 'shipped') ? 'preparing' : $currentStatus;

                                foreach ($statuses as $s) {
                                    if ($isCompleted && $s !== 'completed') {
                                        $sel = (strtolower($displayStatus) === $s) ? 'selected' : '';
                                        if (strtolower($s) !== 'completed') continue; 
                                    } else {
                                        $sel = (strtolower($displayStatus) === $s) ? 'selected' : '';
                                    }

                                    echo "<option value='$s' $sel>".ucfirst(h($s))."</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="delivery_date" class="form-label fw-bold">Delivery Date (Optional)</label>
                            <div class="input-group">
                                <input type="date" name="delivery_date" id="delivery_date" 
                                                value="<?= $delivery['delivery_date'] ?? ''; ?>" 
                                                class="form-control"
                                                <?= $isCompleted ? 'disabled' : ''; ?>>
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary mt-2 w-100 fw-bold" <?= $isCompleted ? 'disabled' : ''; ?>>
                                <i class="fas fa-truck-moving me-1"></i> Update Delivery
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-boxes me-2"></i> Items Ordered</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (count($items) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-end">Unit Price</th>
                                        <th class="text-center">Quantity</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $itemTotal = 0;
                                    foreach ($items as $item): 
                                        $subtotal = $item['price'] * $item['quantity'];
                                        $itemTotal += $subtotal;
                                    ?>
                                        <tr>
                                            <td><?= h($item['name']); ?></td>
                                            <td class="text-end">₱<?= number_format($item['price'], 2); ?></td>
                                            <td class="text-center"><?= $item['quantity']; ?></td>
                                            <td class="text-end fw-bold">₱<?= number_format($subtotal, 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold">Total (Order Items)</td>
                                        <td class="text-end fw-bolder text-primary">₱<?= number_format($itemTotal, 2); ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="p-4 text-center text-muted">
                            <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                            <p class="mb-0">No items found for this order.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>