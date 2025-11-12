<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config.php';

// Added a local helper function for basic HTML escaping
if (!function_exists('h')) {
    function h($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

// Only admin can view this page
if (!is_admin()) {
    header("Location: /sweepxpress/login.php");
    exit;
}

// ====================================================================
// ✅ NEW SEQUENCE: 1. Handle delivery status and date update (POST)
// ====================================================================
// Ginawa itong Section 1 para mauna sa anumang output.

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $orderId = $_POST['order_id'];
    $status = $_POST['status'];
    $deliveryDate = $_POST['delivery_date'] ?? null;

    // Fetch current status before updating
    $currentOrderStmt = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
    $currentOrderStmt->execute([$orderId]);
    $currentStatus = $currentOrderStmt->fetchColumn();

    if ($currentStatus === 'cancelled' || $currentStatus === 'cancellation_requested') {
        // Since we are moving the POST logic up, we need to redirect with a warning.
        // Iiwan ko muna itong error_log at ire-refresh ang page para hindi maipit.
        error_log('Attempt to update cancelled order: ' . $orderId);
        // Gumawa ng temporary variable para ipakita ang warning pagkatapos mag-redirect
        $post_warning = 'update_denied_cancelled';
    } else {
        try {
            // Logic to update delivery or insert new delivery record (deliveries table)
            $stmt = $pdo->prepare("SELECT id FROM deliveries WHERE order_id = ?");
            $stmt->execute([$orderId]);
            $deliveryExists = $stmt->fetch();
            
            if ($deliveryExists) {
                $updateStmt = $pdo->prepare("UPDATE deliveries SET status=?, delivery_date=? WHERE order_id=?");
                $updateStmt->execute([$status, $deliveryDate, $orderId]);
            } else {
                // Fetch customer info for new delivery record
                // NOTE: Use 'o.customer_name' instead of JOIN on 'users' in case of guest orders
                $infoStmt = $pdo->prepare("SELECT o.address, o.customer_name FROM orders o WHERE o.id = ?");
                $infoStmt->execute([$orderId]);
                $customer_info = $infoStmt->fetch();

                $insertStmt = $pdo->prepare("INSERT INTO deliveries (order_id, customer_name, address, status, delivery_date) VALUES (?, ?, ?, ?, ?)");
                $insertStmt->execute([
                    $orderId,
                    $customer_info['customer_name'] ?? 'Unknown',
                    $customer_info['address'] ?? 'Unknown',
                    $status,
                    $deliveryDate
                ]);
            }

            // Update the main orders table status
            $updateOrderStmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $updateOrderStmt->execute([$status, $orderId]);

            // ✅ FIX APPLIED HERE: PHP header redirect
            $redirectStatus = urlencode(ucfirst(str_replace('_', ' ', $status)));
            header("Location: orders_pending.php?success={$orderId}&status={$redirectStatus}");
            exit; 
            
        } catch (PDOException $e) {
            error_log("Delivery update failed: " . $e->getMessage());
            $post_warning = 'update_failed';
        }
    }

    // Kung hindi na-redirect dahil sa error o warning, i-redirect nang walang success message para ma-clear ang POST
    if (!isset($post_warning)) {
        header("Location: orders_pending.php");
        exit;
    }
}
// ====================================================================
// END POST HANDLING (Laging dapat mauna ito)
// ====================================================================

// Ngayon lang i-require ang header, dahil tapos na ang lahat ng header() calls.
require_once __DIR__ . '/../includes/header.php';
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php

// ====================================================================
// 2. DISPLAY SUCCESS/WARNING MESSAGE AFTER REDIRECT/POST
// ====================================================================

$swal_title = '';
$swal_text = '';
$swal_icon = '';

if (isset($_GET['success'], $_GET['status'])) {
    $orderId = h($_GET['success']);
    $newStatus = h($_GET['status']);

    $swal_title = 'Status Updated!';
    $swal_text = "Order #{$orderId} status changed to {$newStatus}. It has been moved off the Purchase Order list.";
    $swal_icon = 'success';

} elseif (isset($post_warning)) {
    if ($post_warning === 'update_denied_cancelled') {
        $swal_title = 'Action Denied';
        $swal_text = 'Cannot update status: Order is currently cancelled or cancellation requested.';
        $swal_icon = 'warning';
    } elseif ($post_warning === 'update_failed') {
        $swal_title = 'Update Failed';
        $swal_text = 'An error occurred while updating the delivery status. Please check logs.';
        $swal_icon = 'error';
    }
}

if (!empty($swal_title)) {
    // Display SweetAlert message and then remove the query parameters from the URL
    echo "<script>
    document.addEventListener('DOMContentLoaded', () => {
      Swal.fire({
        icon: '{$swal_icon}',
        title: '{$swal_title}',
        text: '{$swal_text}',
        confirmButtonColor: '#3085d6'
      }).then(() => {
        // Clear URL parameters without reloading again
        if (history.replaceState) {
            // Gumawa ng clean URL
            let cleanUrl = window.location.pathname;
            // Kung mayroong 'success' or 'status' sa URL, alisin ito
            if (window.location.search.includes('success') || window.location.search.includes('status')) {
                history.replaceState(null, null, cleanUrl);
            }
        }
      });
    });
    </script>";
}


// ====================================================================
// 3. FETCH DATA (Pending Orders Only - The "POs")
// ====================================================================

$sql = "
    SELECT 
        o.id AS order_id,
        u.name AS customer_name,
        o.total,
        o.created_at,
        o.status AS order_status, 
        d.id AS delivery_id,
        d.status AS delivery_status,
        d.delivery_date
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN deliveries d ON o.id = d.order_id
    WHERE o.status = 'pending' /* <--- ONLY PENDING ORDERS (POs) */
    ORDER BY o.created_at DESC
";
$orders = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-warning">⏳ Purchase Orders (PO) List</h1>
        <a href="allorders.php" class="btn btn-secondary">⬅ Back to Orders Dashboard</a>
    </div>
    
    <h2 class="h4 m-0 mb-3">POs Awaiting Processing (<?= count($orders) ?>)</h2>
    <?php if ($orders): ?>
        <div class="table-responsive shadow-sm rounded">
            <table class="table table-striped table-hover align-middle table-bordered">
                <thead class="table-warning text-dark text-center">
                    <tr>
                        <th>PO ID</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>PO Date</th>
                        <th>Status</th>
                        <th>Delivery Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <?php
                            $currentStatus = $o['order_status']; 
                            $statusClass = 'bg-warning text-dark';
                            $disableStatus = false;
                        ?>
                        <tr>
                            <td class="fw-bold text-center">#<?php echo (int)$o['order_id']; ?></td>
                            <td><?php echo h($o['customer_name'] ?? 'Guest'); ?></td>
                            <td class="fw-semibold text-end">₱<?php echo number_format($o['total'], 2); ?></td>
                            <td class="text-muted"><?php echo h($o['created_at']); ?></td>
                            <td class="text-center">
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="order_id" value="<?php echo (int)$o['order_id']; ?>">
                                    <select name="status" class="form-select form-select-sm <?php echo $statusClass; ?>" 
                                            onchange="this.form.submit()">
                                        <?php 
                                            // The status options for a Pending list
                                            $statusOptions = [
                                                'pending'   => 'Pending',
                                                // Note: 'shipped' maps to 'preparing' in delivery logic but we can use 'shipped' here as a distinct user action.
                                                'shipped'   => 'Preparing/Processing', 
                                                'delivered' => 'Delivered'
                                            ];
                                        ?>
                                        <?php foreach ($statusOptions as $value => $label): ?>
                                            <option value="<?php echo $value; ?>" <?php if($currentStatus === $value) echo 'selected'; ?>>
                                                <?php echo $label; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </td>
                            <td class="text-center">
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="order_id" value="<?php echo (int)$o['order_id']; ?>">
                                    <input type="hidden" name="status" value="<?php echo h($o['delivery_status'] ?? 'pending'); ?>">
                                    <input type="date" name="delivery_date" class="form-control form-control-sm" 
                                            value="<?php echo h($o['delivery_date'] ?? ''); ?>" onchange="this.form.submit()">
                                </form>
                            </td>
                            <td class="text-center">
                                <a class="btn btn-sm btn-outline-primary" href="order_details.php?id=<?php echo (int)$o['order_id']; ?>">
                                    View Details
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-success">🥳 All Purchase Orders have been processed!</div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>