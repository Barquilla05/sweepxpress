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
require_once __DIR__ . '/../includes/header.php';
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php

// ====================================================================
// 1. Handle delivery status and date update 
//    (CANCELLATION ACTION LOGIC REMOVED)
// ====================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $orderId = $_POST['order_id'];
    $status = $_POST['status'];
    $deliveryDate = $_POST['delivery_date'] ?? null;

    // First, check the current order status to prevent updating a cancelled order
    $currentOrderStmt = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
    $currentOrderStmt->execute([$orderId]);
    $currentStatus = $currentOrderStmt->fetchColumn();

    // Do NOT update status if the order is already cancelled or cancellation requested
    if ($currentStatus === 'cancelled' || $currentStatus === 'cancellation_requested') {
         echo '<div class="alert alert-warning mt-3">⚠️ Cannot update status: Order is currently ' . h(ucfirst(str_replace('_', ' ', $currentStatus))) . '.</div>';
    } else {
        try {
            // Logic to update delivery or insert new delivery record
            $stmt = $pdo->prepare("SELECT id FROM deliveries WHERE order_id = ?");
            $stmt->execute([$orderId]);
            $deliveryExists = $stmt->fetch();
            
            if ($deliveryExists) {
                $updateStmt = $pdo->prepare("UPDATE deliveries SET status=?, delivery_date=? WHERE order_id=?");
                $updateStmt->execute([$status, $deliveryDate, $orderId]);
            } else {
                $infoStmt = $pdo->prepare("SELECT o.address, u.name AS customer_name FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
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

            // --- Update the main orders table status ---
            $updateOrderStmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $updateOrderStmt->execute([$status, $orderId]);

            // Simple success message: The page refreshes naturally on POST submission, 
            // but since there's no WHERE clause, the order remains visible.
            echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Delivery status updated!',
                showConfirmButton: false,
                timer: 1500
            });
            </script>";

        } catch (PDOException $e) {
            error_log("Delivery update failed: " . $e->getMessage());
            echo '<div class="alert alert-danger mt-3">❌ Error updating delivery status.</div>';
        }
    }
}

// ====================================================================
// 2. FETCH DATA (All Orders - NO WHERE CLAUSE)
// ====================================================================

// Fetch ALL orders and their delivery details 
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
    ORDER BY o.created_at DESC
";
$orders = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"> Orders Management: All Orders</h1>
        <a href="allorders.php" class="btn btn-secondary">⬅ Back to Order Dashboard</a>
    </div>
    
    <h2 class="h4 m-0 mb-3">📦 All Orders List</h2>
    <?php if ($orders): ?>
        <div class="table-responsive shadow-sm rounded">
            <table class="table table-striped table-hover align-middle table-bordered">
                <thead class="table-dark text-center">
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Order Date</th>
                        <th>Status</th>
                        <th>Delivery Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <?php
                            // Determine the status and disable flag based on the order_status
                            $currentStatus = $o['order_status']; 
                                
                                $statusClass = match($currentStatus) {
                                'pending'   => 'bg-warning text-dark',
                                'shipped'   => 'bg-info text-dark',
                                'delivered' => 'bg-success text-white',
                                'completed' => 'bg-primary text-white', // ✅ Blue for completed
                                'cancellation_requested', 'cancelled' => 'bg-danger text-white',
                                default     => 'bg-secondary text-white'
                            };

                            
                            // Status update disabled if cancellation is requested or approved/rejected
                            $disableStatus = in_array($currentStatus, [
                                'cancellation_requested',
                                'cancelled',
                                'completed' // ✅ lock completed orders
                            ]);

                        ?>
                        <tr>
                            <td class="fw-bold text-center">#<?php echo (int)$o['order_id']; ?></td>
                            <td><?php echo h($o['customer_name'] ?? 'Guest'); ?></td>
                            <td class="fw-semibold text-end">₱<?php echo number_format($o['total'], 2); ?></td>
                            <td class="text-muted"><?php echo h($o['created_at']); ?></td>
                            <td class="text-center">
                                <?php if ($disableStatus): ?>
                                    <span class="badge <?php echo $statusClass; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $currentStatus)); ?>
                                    </span>
                                <?php else: ?>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="order_id" value="<?php echo (int)$o['order_id']; ?>">
                                        <select name="status" class="form-select form-select-sm <?php echo $statusClass; ?>" 
                                                onchange="this.form.submit()">
                                            <?php 
                                             $statusOptions = [
                                                'pending'   => 'Pending',
                                                'shipped'   => 'Preparing', // Display as Preparing, Submit as Shipped
                                                'delivered' => 'Delivered',
                                                'completed' => 'Completed' // ✅ Add this
                                            ];
                                            ?>
                                            <?php foreach ($statusOptions as $value => $label): ?>

                                                <option value="<?php echo $value; ?>" <?php if($currentStatus === $value) echo 'selected'; ?>>
                                                    <?php echo $label; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="order_id" value="<?php echo (int)$o['order_id']; ?>">
                                    <input type="hidden" name="status" value="<?php echo h($o['delivery_status'] ?? 'pending'); ?>">
                                    <input type="date" name="delivery_date" class="form-control form-control-sm" 
                                           value="<?php echo h($o['delivery_date'] ?? ''); ?>" onchange="this.form.submit()"
                                           <?php echo $disableStatus ? 'disabled' : ''; ?>>
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
        <div class="alert alert-info">📭 No orders found.</div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>