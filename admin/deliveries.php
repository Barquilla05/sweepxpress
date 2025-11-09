<?php
// require_once __DIR__ . '/../includes/auth.php'; // Included in config.php or header.php usually
require_once __DIR__ . '/../config.php';
// Assuming is_admin() and $pdo are defined in config.php or another included file
if (!function_exists('is_admin') || !is_admin()) {
    header("Location: /sweepxpress/login.php");
    exit;
}
require_once __DIR__ . '/../includes/header.php';

// ====================================================================
// Handle status update with transaction to keep Orders table synced
// ====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delivery_id'], $_POST['status'])) {
    $delivery_id = $_POST['delivery_id'];
    $status = $_POST['status'];
    
    // --- CHECK: PREVENT UPDATE IF ORDER IS ALREADY FINAL ---
    $stmt_check = $pdo->prepare("
        SELECT o.status 
        FROM deliveries d 
        JOIN orders o ON d.order_id = o.id 
        WHERE d.id = ?
    ");
    $stmt_check->execute([$delivery_id]);
    $current_order_status = $stmt_check->fetchColumn();
    // Use the status from the database check for the warning message
    $display_order_status = $current_order_status ? htmlspecialchars($current_order_status) : 'N/A';

    if ($current_order_status === 'delivered' || $current_order_status === 'cancelled') {
        // Fetch Order ID for a better warning message
        $stmt_order_id = $pdo->prepare("SELECT order_id FROM deliveries WHERE id = ?");
        $stmt_order_id->execute([$delivery_id]);
        $order_id_for_msg = $stmt_order_id->fetchColumn();

        echo '<div class="alert alert-warning mt-3 px-4 px-lg-5">⚠️ Status for Order #' . htmlspecialchars($order_id_for_msg) . ' cannot be changed as it is already **' . ucfirst($display_order_status) . '**.</div>';
    } else {
        // --- ORIGINAL UPDATE LOGIC (Now inside the else block) ---
        $pdo->beginTransaction();
        try {
            // 1. Get order_id from delivery_id
            $stmt_fetch = $pdo->prepare("SELECT order_id FROM deliveries WHERE id = ?");
            $stmt_fetch->execute([$delivery_id]);
            $order_id = $stmt_fetch->fetchColumn();

            if ($order_id) {
                // 2. Update deliveries table
                $stmt_delivery = $pdo->prepare("UPDATE deliveries SET status = ? WHERE id = ?");
                $stmt_delivery->execute([$status, $delivery_id]);
                
                // 3. Update orders table to keep statuses aligned
                $stmt_order = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
                $stmt_order->execute([$status, $order_id]);

                // 4. Optionally update delivery_date if status is 'delivered'
                if ($status === 'delivered') {
                    $stmt_date = $pdo->prepare("UPDATE deliveries SET delivery_date = NOW() WHERE id = ? AND delivery_date IS NULL");
                    $stmt_date->execute([$delivery_id]);
                }

                $pdo->commit();
                echo '<div class="alert alert-success mt-3 px-4 px-lg-5">✅ Delivery and Order status updated.</div>';
            } else {
                $pdo->rollBack();
                echo '<div class="alert alert-danger mt-3 px-4 px-lg-5">❌ Error: Delivery not found.</div>';
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Delivery and Order status update failed: " . $e->getMessage());
            echo '<div class="alert alert-danger mt-3 px-4 px-lg-5">❌ Error updating delivery and order status.</div>';
        }
    }
}

// Fetch deliveries with order info
$stmt = $pdo->query("
    SELECT d.*, o.total, o.created_at, o.status AS order_status
    FROM deliveries d
    LEFT JOIN orders o ON d.order_id = o.id
    ORDER BY d.created_at DESC
");
$deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid min-vh-100 bg-light px-0 py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4 px-4 px-lg-5">
        <h1 class="display-6 fw-bold text-primary mb-0">Delivery Management</h1>
        <a href="dashboard.php" class="btn btn-secondary shadow-sm btn-lg">
            <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
        </a>
    </div>

    <?php if ($deliveries): ?>
        <div class="card shadow-lg border-0 w-100"> 
            <div class="card-header bg-white border-bottom py-3 px-4 px-lg-5">
                <h3 class="mb-0 text-dark">
                    All Deliveries <span class="badge bg-primary text-white rounded-pill ms-2"><?php echo count($deliveries); ?></span>
                </h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-dark text-center">
                            <tr>
                                <th class="ps-4 ps-lg-5">Order ID</th>
                                <th>Delivery ID</th>
                                <th>Customer</th>
                                <th>Order Total</th>
                                <th>Status</th>
                                <th>Delivery Date</th>
                                <th class="pe-4 pe-lg-5">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($deliveries as $d): ?>
                                <?php
                                    // Status color classes
                                    $statusClass = match($d['status']) {
                                        'pending'   => 'bg-warning text-dark',
                                        'shipped'   => 'bg-info text-dark',
                                        'delivered' => 'bg-success text-white',
                                        'cancelled' => 'bg-danger text-white',
                                        default     => 'bg-secondary text-white'
                                    };

                                    // Determine if status is final (immutable)
                                    $is_final = ($d['order_status'] === 'delivered' || $d['order_status'] === 'cancelled');
                                ?>
                                <tr>
                                    <td class="fw-bold text-center ps-4 ps-lg-5">#<?php echo htmlspecialchars($d['order_id']); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($d['id']); ?></td>
                                    <td><?php echo htmlspecialchars($d['customer_name']); ?></td>
                                    <td class="fw-semibold text-end">₱<?php echo number_format($d['total'], 2); ?></td>
                                    <td class="text-center">
                                        <?php if ($is_final): ?>
                                            <span class="badge <?php echo $statusClass; ?>">
                                                <?php echo ucfirst($d['status']); ?> (Final)
                                            </span>
                                        <?php else: ?>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="delivery_id" value="<?php echo htmlspecialchars($d['id']); ?>">
                                                <select name="status" class="form-select form-select-sm <?php echo $statusClass; ?>" onchange="this.form.submit()">
                                                    <?php foreach (['pending','shipped','delivered','cancelled'] as $s): ?>
                                                        <option value="<?php echo $s; ?>" <?php if($d['status'] === $s) echo 'selected'; ?>>
                                                            <?php echo ucfirst($s); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo $d['delivery_date'] ? htmlspecialchars(date('M j, Y', strtotime($d['delivery_date']))) : '<span class="text-muted">-</span>'; ?>
                                    </td>
                                    <td class="text-center pe-4 pe-lg-5">
                                        <a href="order_details.php?id=<?php echo $d['order_id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info mx-4 mx-lg-5"> No deliveries yet.</div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>