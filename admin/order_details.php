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
// Implements the new status colors and mapping.
function get_status_badge_class($status) {
    // Normalize status to lowercase for comparison
    $status = strtolower($status);
    
    return match($status) {
        'delivered', 'completed' => 'bg-success text-white', // Green for delivered/completed
        'preparing', 'shipped' => 'bg-info text-white',      // Blue for preparing (now includes shipped)
        'pending' => 'bg-warning text-dark',                 // Yellow for pending (needs dark text)
        'cancelled' => 'bg-danger text-white',               // Red for cancelled
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
// 1. HANDLE DELIVERY UPDATE 
// =======================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $status = $_POST['status'] ?? null;
    $date = $_POST['delivery_date'] ?? null;

    if ($status) {
        // Map 'shipped' to 'preparing' if it somehow comes through
        $finalStatus = ($status === 'shipped') ? 'preparing' : $status;

        $stmt = $pdo->prepare("SELECT id FROM deliveries WHERE order_id = ?");
        $stmt->execute([$orderId]);
        $deliveryExists = $stmt->fetch();

        try {
            // NOTE: Kung ang order status sa main table ay 'completed', dapat hindi na mag-e-execute
            // ang UPDATE/INSERT na ito. Pero para masigurado, hindi mo na ma-a-access ang form.

            if ($deliveryExists) {
                // UPDATE existing delivery record
                $updateStmt = $pdo->prepare("UPDATE deliveries SET status=?, delivery_date=? WHERE order_id=?");
                $updateStmt->execute([$finalStatus, $date, $orderId]);
            } else {
                // INSERT new delivery record
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

            // Update main orders table status
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
// 2. FETCH ALL ORDER DATA
// =======================================================================
try {
    // Fetch order, joining with users for customer data (handles guest orders with LEFT JOIN)
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

    // Set fallback name/email for guest orders
    $order['user_name'] = $order['user_name'] ?? h($order['customer_name']) . ' (Guest)';
    $order['user_email'] = $order['user_email'] ?? 'N/A';
    
    // Fetch order items
    $stmtItems = $pdo->prepare("
        SELECT oi.*, p.name 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ");
    $stmtItems->execute([$orderId]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    // Fetch delivery details
    $stmtDelivery = $pdo->prepare("SELECT * FROM deliveries WHERE order_id = ?");
    $stmtDelivery->execute([$orderId]);
    $delivery = $stmtDelivery->fetch(PDO::FETCH_ASSOC);

    // 💡 NEW CHECK: Check if the order is completed
    $isCompleted = (strtolower($order['status']) === 'completed');

} catch (PDOException $e) {
    die("<div class='container p-5'><div class='alert alert-danger'>Database error: " . h($e->getMessage()) . "</div></div>");
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<div class="container p-4 p-lg-5">
    <a href="allorders.php" class="btn btn-outline-secondary mb-4">
        <i class="fas fa-arrow-left me-2"></i> Back to All Orders
    </a>

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
                    
                    <hr>
                    
                    <form method="post" class="row g-3">
                        <div class="col-md-6">
                            <label for="status" class="form-label fw-bold">Update Status</label>
                            <select name="status" id="status" class="form-select" required <?= $isCompleted ? 'disabled' : ''; ?>>
                                <?php
                                // --- UPDATED STATUS LIST FOR THE DROPDOWN ---
                                $statuses = ['pending','preparing','delivered','cancelled','completed']; // Added completed
                                $currentStatus = $delivery['status'] ?? 'pending';
                                
                                $displayStatus = (strtolower($currentStatus) === 'shipped') ? 'preparing' : $currentStatus;

                                foreach ($statuses as $s) {
                                    // Only allow 'completed' to be selected if the current status is completed
                                    if ($isCompleted && $s !== 'completed') {
                                        $sel = (strtolower($displayStatus) === $s) ? 'selected' : '';
                                        // Skip other statuses if order is completed
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
                            <input type="date" name="delivery_date" id="delivery_date" 
                                       value="<?= $delivery['delivery_date'] ?? ''; ?>" 
                                       class="form-control"
                                       <?= $isCompleted ? 'disabled' : ''; ?>> </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-success mt-2 w-100 fw-bold" <?= $isCompleted ? 'disabled' : ''; ?>>
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