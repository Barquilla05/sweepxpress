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

// ====================================================================
// 1. Logic (NONE REQUIRED - NO UPDATES ALLOWED FOR COMPLETED ORDERS)
// ====================================================================

// No POST handling is included here as completed orders should not be editable.

// ====================================================================
// 2. FETCH DATA (Completed Orders Only)
// ====================================================================

// Fetch ONLY DELIVERED (Completed) orders and their details 
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
    WHERE o.status = 'delivered' /* <--- FILTERED FOR COMPLETED ORDERS */
    ORDER BY o.created_at DESC
";
$orders = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-success">✅ Completed Orders List</h1>
        <a href="allorders.php" class="btn btn-secondary">⬅ Back to Orders Dashboard</a>
    </div>
    
    <h2 class="h4 m-0 mb-3">Successfully Delivered Orders (<?= count($orders) ?>)</h2>
    <?php if ($orders): ?>
        <div class="table-responsive shadow-sm rounded">
            <table class="table table-striped table-hover align-middle table-bordered">
                <thead class="table-success text-white text-center">
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
                            // Status is fixed as 'delivered' for this list
                            $currentStatus = $o['order_status']; 
                            $statusClass = 'bg-success text-white';
                            $deliveryDateDisplay = $o['delivery_date'] ? date('M j, Y', strtotime($o['delivery_date'])) : 'N/A';
                        ?>
                        <tr>
                            <td class="fw-bold text-center">#<?php echo (int)$o['order_id']; ?></td>
                            <td><?php echo h($o['customer_name'] ?? 'Guest'); ?></td>
                            <td class="fw-semibold text-end">₱<?php echo number_format($o['total'], 2); ?></td>
                            <td class="text-muted"><?php echo h($o['created_at']); ?></td>
                            <td class="text-center">
                                <span class="badge <?php echo $statusClass; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $currentStatus)); ?>
                                </span>
                            </td>
                            <td class="text-center fw-semibold">
                                <?php echo h($deliveryDateDisplay); ?>
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
        <div class="alert alert-info">📭 No completed orders found yet.</div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>