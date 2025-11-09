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

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /sweepxpress/customers/my_orders.php");
    exit();
}

$order_id = (int)$_GET['id'];
$user_id  = $_SESSION['user']['id'];

// ====================================================================
// ✅ CLEANUP HANDLER (Runs first to clear the notification flag)
// ====================================================================
if (isset($_GET['cleanup']) && $_GET['cleanup'] == 1) {
    // Clear the admin_note only if the order status is cancelled or back to pending
    $pdo->prepare("
        UPDATE orders 
        SET admin_note = NULL
        WHERE id = ? AND user_id = ? AND status IN ('cancelled', 'pending')
    ")->execute([$order_id, $user_id]);

    // Redirect to the clean URL without the cleanup parameter to prevent loops
    header("Location: /sweepxpress/customers/order_details.php?id={$order_id}");
    exit();
}
// ====================================================================


// ✅ Fetch the order (MUST include cancellation_reason and admin_note)
try {
    $stmt = $pdo->prepare("
        SELECT 
            o.*, 
            d.delivery_date, 
            d.status AS delivery_status
        FROM orders o
        LEFT JOIN deliveries d ON o.id = d.order_id
        WHERE o.id = ? AND o.user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        header("Location: /sweepxpress/customers/my_orders.php?status=not_found");
        exit();
    }

    // ✅ Fetch items in the order
    $stmt_items = $pdo->prepare("
        SELECT oi.*, p.name, p.price 
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt_items->execute([$order_id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$display_status = $order['status']; 
$admin_note = trim($order['admin_note'] ?? '');

$swal_title = '';
$swal_text = '';
$swal_icon = '';
$is_admin_decision_swal = false; 

// ====================================================================
// A. Handle URL status messages (Cancellation Submission Success/Error)
// This handles the pop-up AFTER the submission is successful.
// ====================================================================
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'request_submitted':
            $swal_title = 'Request Submitted!';
            // 💡 This is the "Cancellation is successful" part (request submitted for review)
            $swal_text = 'Your cancellation request has been submitted for review. Please check back later for an update.';
            $swal_icon = 'success';
            break;
        case 'reason_error':
            $swal_title = 'Submission Failed';
            $swal_text = 'Please provide a detailed reason of at least 10 characters to submit a cancellation request.';
            $swal_icon = 'warning';
            break;
        case 'request_denied':
            $swal_title = 'Action Not Allowed';
            $swal_text = 'Cannot submit cancellation request. Your order is no longer pending or is already being processed.';
            $swal_icon = 'error';
            break;
        case 'error':
            $swal_title = 'System Error';
            $swal_text = 'An unexpected error occurred while processing your request.';
            $swal_icon = 'error';
            break;
    }
}

// ====================================================================
// B. Handle Admin decision notifications (Accepted/Rejected Pop-up)
// ====================================================================
if (!empty($admin_note)) {
    if ($display_status === 'cancelled') {
        $swal_title = 'Cancellation Approved! ✅';
        $swal_text = json_encode("Your request to cancel Order #{$order_id} has been APPROVED.\n\nAdmin Note: " . $admin_note);
        $swal_icon = 'success';
        $is_admin_decision_swal = true;
    } 
    elseif ($display_status === 'pending' && $order['cancellation_reason'] === NULL) {
        $swal_title = 'Cancellation Rejected ❌';
        $swal_text = json_encode("Your request to cancel Order #{$order_id} has been REJECTED.\n\nAdmin Note: " . $admin_note);
        $swal_icon = 'error';
        $is_admin_decision_swal = true;
    }
} else {
    if (!isset($_GET['status'])) {
        $swal_title = '';
        $swal_icon = '';
        $swal_text = '';
    }
}

?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="container my-5">
    <div class="mb-4">
        <h1 class="h3">📄 Order #<?php echo h($order['id']); ?> Details</h1>
        <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($order['created_at'])); ?></p>
        <p><strong>Status:</strong> 
    <span class="badge 
        <?php 
            echo match($display_status) {
                'delivered' => 'bg-success text-light',
                'preparing' => 'bg-info text-dark', // NEW STATUS
                'pending'   => 'bg-warning text-dark',
                'cancellation_requested' => 'bg-danger text-light',
                'cancelled' => 'bg-danger text-light',
                default     => 'bg-secondary text-light'
            };
        ?>">
        <?php 
            echo h(ucfirst(str_replace('_', ' ', $display_status)));
        ?>
    </span>
</p>
        <p><strong>Delivery Date:</strong> 
            <?php echo !empty($order['delivery_date']) 
                ? date('F j, Y', strtotime($order['delivery_date'])) 
                : "<span class='text-muted'>Not yet scheduled</span>"; ?>
        </p>
        <p><strong>Total:</strong> ₱<?php echo number_format($order['total'], 2); ?></p>
    </div>

    <?php if ($order['status'] === 'pending'): ?>
    <div class="card bg-light p-3 mb-4 border-danger">
        <h5 class="text-danger"><i class="bi bi-exclamation-triangle-fill"></i> Request Cancellation</h5>
        <p>You can request to cancel this order only while its status is **Pending**.</p>
        
        <form id="cancellationForm" action="/sweepxpress/customers/cancel_order.php" method="POST">
            <input type="hidden" name="order_id" value="<?php echo h($order_id); ?>">
            <div class="mb-3">
                <label for="cancellationReason" class="form-label">Reason for Cancellation (Min. 10 characters):</label>
                <textarea class="form-control" id="cancellationReason" name="reason" rows="3" required></textarea>
            </div>
            <button type="submit" class="btn btn-danger">Submit Cancellation Request</button> 
        </form>
    </div>
    <?php elseif ($order['status'] === 'cancellation_requested'): ?>
    <div class="alert alert-danger mb-4">
        <i class="bi bi-info-circle-fill"></i> **Cancellation Request Pending:** Your request for cancellation is currently being reviewed by the administration.
        <?php if ($order['cancellation_reason']): ?>
            <br>Reason submitted: *<?php echo h($order['cancellation_reason']); ?>*
        <?php endif; ?>
    </div>
    <?php elseif ($order['status'] === 'cancelled'): ?>
    <div class="alert alert-warning mb-4">
        <i class="bi bi-x-circle-fill"></i> **Order Cancelled.**
        <?php if ($order['admin_note']): ?>
            <br>Admin Note: *<?php echo h($order['admin_note']); ?>*
        <?php endif; ?>
    </div>
    <?php endif; ?>


    <h4>🛒 Items in this Order</h4>
    <?php if ($items): ?>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo h($item['name']); ?></td>
                            <td><?php echo h($item['quantity']); ?></td>
                            <td>₱<?php echo number_format($item['price'], 2); ?></td>
                            <td>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">⚠️ No items found for this order.</div>
    <?php endif; ?>

    <a href="/sweepxpress/customers/my_orders.php" class="btn btn-secondary mt-3">← Back to My Orders</a>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- 1. Handle Admin Decision / Submission Success Pop-ups (Existing Logic) ---
        const swalTitle = '<?php echo h($swal_title); ?>';
        const swalTextRaw = '<?php echo h($swal_text); ?>';
        let swalText = swalTextRaw ? JSON.parse(swalTextRaw) : ''; 
        const swalIcon = '<?php echo h($swal_icon); ?>';
        const orderId = '<?php echo $order_id; ?>';
        const isAdminDecision = '<?php echo $is_admin_decision_swal ? 'true' : 'false'; ?>';

        if (swalTitle && swalIcon) {
            Swal.fire({
                title: swalTitle,
                text: swalText,
                icon: swalIcon,
                confirmButtonText: 'Got it'
            }).then(() => {
                if (isAdminDecision === 'true') {
                    const cleanupUrl = window.location.href.split('?')[0] + '?id=' + orderId + '&cleanup=1';
                    window.location.href = cleanupUrl;
                } else {
                    if (window.history.replaceState) {
                        const cleanUrl = window.location.href.split('?')[0];
                        window.history.replaceState({path: cleanUrl}, '', cleanUrl + '?id=' + orderId);
                    }
                }
            });
        }

        // --- 2. Add Pre-Submission Confirmation Pop-up (NEW LOGIC: "Are you sure?") ---
        const form = document.getElementById('cancellationForm');
        const reasonInput = document.getElementById('cancellationReason');

        if (form) {
            form.addEventListener('submit', function(e) {
                // Prevent the default form submission
                e.preventDefault();

                // Check for reason length manually as we are intercepting the submit
                if (reasonInput.value.trim().length < 10) {
                    // Re-enable form submission for default HTML5 validation messages to show
                    // If the form fields have the 'required' attribute, the browser will block submission
                    // and show the default message. We only proceed with Swal if validation passes.
                    if (reasonInput.checkValidity()) {
                        // This case should not be hit if HTML5 validation works, but acts as a safeguard.
                        // We proceed to show the confirmation pop-up.
                    } else {
                        // Manually trigger validation display if needed
                        reasonInput.reportValidity();
                        return;
                    }
                }


                // 💡 This is the "Are you sure you want to submit?" pop-up
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You are about to submit a request to cancel Order #<?php echo $order_id; ?>. This action will send it for admin review.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, Submit Request!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // If user confirms, manually submit the form
                        // This sends the request to cancel_order.php
                        form.submit();
                    }
                });
            });
        }
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>