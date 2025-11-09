<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/header.php';

if (!is_logged_in()) {
    header("Location: /sweepxpress/login.php");
    exit();
}

$user_id = $_SESSION['user']['id'];

// Fetch the user's orders, joining with the deliveries table
try {
    $stmt = $pdo->prepare("
        SELECT 
            o.*, 
            d.delivery_date, 
            d.status AS delivery_status 
        FROM orders o
        LEFT JOIN deliveries d ON o.id = d.order_id
        WHERE o.user_id = ? 
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // In a production environment, you would log this error and show a generic message.
    die("Database error: " . $e->getMessage());
}
?>

<!-- Changed to min-vh-100 and added p-4 for general padding, removing the fixed 'container my-5' -->
<div class="min-vh-100 bg-light p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold text-primary">📦 My Orders</h1>
    </div>

    <?php if ($orders): ?>
        <!-- Wrapped table in a clean, white card-like block for presentation -->
        <div class="bg-white rounded shadow-sm p-4">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle table-bordered">
                    <thead class="table-dark text-center">
                        <tr>
                            <th>Order ID</th>
                            <th>Total</th>
                            <th>Order Date</th>
                            <th>Status</th>
                            <th>Delivery Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <?php 
                                // Check if the Admin has left a note (meaning a decision was made)
                                $has_notification = !empty(trim($order['admin_note'] ?? ''));
                                // Highlight the row if a notification is pending
                                $row_class = $has_notification ? 'table-warning border border-3 border-danger' : '';

                                // Use the primary order status (o.status) for display
                                $status_to_display = $order['status']; 
                                $status_class = match($status_to_display) {
                                    'delivered' => 'bg-success text-light',
                                    'shipped'   => 'bg-info text-dark', // 'shipped' still uses bg-info
                                    'pending'   => 'bg-warning text-dark',
                                    // Highlight cancellation states
                                    'cancellation_requested', 'cancelled' => 'bg-danger text-light', 
                                    default     => 'bg-secondary text-light'
                                };
                            ?>
                            <tr class="<?php echo $row_class; ?>">
                                <td class="fw-bold text-center">
                                    #<?php echo h($order['id']); ?>
                                    <?php if ($has_notification): // Add a visual cue to click ?>
                                        <span class="badge bg-danger ms-1" title="Important Update">🔔</span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-semibold text-end">₱<?php echo number_format($order['total'], 2); ?></td>
                                <td class="text-muted"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                
                                <td class="text-center">
                                    <span class="badge <?php echo $status_class; ?>">
                                        <?php 
                                            // Custom logic to display 'Preparing' instead of 'Shipped'
                                            if ($status_to_display === 'shipped') {
                                                echo h('Preparing');
                                            } else {
                                                echo h(ucfirst(str_replace('_', ' ', $status_to_display)));
                                            }
                                        ?>
                                    </span>
                                </td>

                                <td>
                                    <?php 
                                        if (!empty($order['delivery_date'])) {
                                            echo date('F j, Y', strtotime($order['delivery_date']));
                                        } else {
                                            echo "<span class='text-muted'>Not yet scheduled</span>";
                                        }
                                    ?>
                                </td>
                                <td class="text-center">
                                    <a href="/sweepxpress/customers/order_details.php?id=<?php echo h($order['id']); ?>" 
                                        class="btn btn-sm btn-outline-primary">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center">
            <p>You have not placed any orders yet. <a href="/sweepxpress/products.php" class="alert-link">Start shopping now!</a></p>
        </div>
    <?php endif; ?>
</div>

<?php 
require_once __DIR__ . '/../includes/footer.php'; 
?>
