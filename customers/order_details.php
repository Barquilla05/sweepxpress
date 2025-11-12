<?php
// E:\xampp\htdocs\sweepxpress\customers\order_details.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ====================================================================
// 1. CONFIGURATION, AUTHENTICATION, AT VARIABLE SETUP (WALANG OUTPUT)
// ====================================================================
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php'; // I assume is_logged_in() is here

// ====================================================================
// 2. HEADER/REDIRECTION LOGIC (DAPAT NASA ITAAS NG HTML HEADER INCLUDE)
// ====================================================================

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

// ✅ CLEANUP HANDLER - Must run before any HTML output
if (isset($_GET['cleanup']) && $_GET['cleanup'] == 1) {
    $pdo->prepare("
        UPDATE orders 
        SET admin_note = NULL
        WHERE id = ? AND user_id = ? AND status IN ('cancelled', 'pending')
    ")->execute([$order_id, $user_id]);
    header("Location: /sweepxpress/customers/order_details.php?id={$order_id}");
    exit();
}

// ====================================================================
// 3. DATABASE FETCHING (WALANG OUTPUT)
// ====================================================================
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

    // ✅ Fetch items (with added product image field)
    $stmt_items = $pdo->prepare("
        SELECT oi.*, p.name, p.price, p.image
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
// 4. STATUS MESSAGES (FOR SWEETALERT)
// ====================================================================
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'request_submitted':
            $swal_title = 'Request Submitted!';
            $swal_text = 'Your cancellation request has been submitted for review.';
            $swal_icon = 'success';
            break;
        case 'reason_error':
            $swal_title = 'Submission Failed';
            $swal_text = 'Please provide a detailed reason (at least 10 characters).';
            $swal_icon = 'warning';
            break;
        case 'request_denied':
            $swal_title = 'Action Not Allowed';
            $swal_text = 'Cannot submit cancellation request. Order is no longer pending.';
            $swal_icon = 'error';
            break;
        case 'error':
            $swal_title = 'System Error';
            $swal_text = 'An unexpected error occurred.';
            $swal_icon = 'error';
            break;
        case 'received_success':
            $swal_title = 'Order Completed! 🎉';
            $swal_text = 'Thank you for confirming that you received your order.';
            $swal_icon = 'success';
            break;
        case 'received_error':
            $swal_title = 'Order Status Updated';
            $swal_text = 'You indicated that you did not receive the order. The status has been set back to Preparing.';
            $swal_icon = 'info';
            break;
        case 'invalid_confirm':
            $swal_title = 'Invalid Action';
            $swal_text = 'This order cannot be confirmed as received.';
            $swal_icon = 'warning';
            break;
    }
}

// ✅ ADMIN DECISION POP-UPS
if (!empty($admin_note)) {
    if ($display_status === 'cancelled') {
        $swal_title = 'Cancellation Approved! ✅';
        // Removed json_encode here, will do it directly in JS for cleaner code
        $swal_text = "Your request to cancel Order #{$order_id} has been APPROVED.\n\nAdmin Note: " . $admin_note;
        $swal_icon = 'success';
        $is_admin_decision_swal = true;
    } elseif ($display_status === 'pending' && $order['cancellation_reason'] === NULL) {
        $swal_title = 'Cancellation Rejected ❌';
        $swal_text = "Your request to cancel Order #{$order_id} has been REJECTED.\n\nAdmin Note: " . $admin_note;
        $swal_icon = 'error';
        $is_admin_decision_swal = true;
    }
}

// ====================================================================
// 5. HTML HEADER INCLUDE (OUTPUT STARTS HERE - DITO ILIPAT!)
// ====================================================================
require_once __DIR__ . '/../includes/header.php'; 
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="container my-5">
    <div class="mb-4">
        <h1 class="h3">📄 Order #<?= h($order['id']); ?> Details</h1>
        <p><strong>Date:</strong> <?= date('F j, Y', strtotime($order['created_at'])); ?></p>
        <p><strong>Status:</strong> 
            <span class="badge 
                <?= match($display_status) {
                    'completed', 'delivered' => 'bg-success text-light',
                    'preparing' => 'bg-info text-dark',
                    'pending'   => 'bg-warning text-dark',
                    'cancellation_requested' => 'bg-danger text-light',
                    'cancelled' => 'bg-danger text-light',
                    default     => 'bg-secondary text-light'
                };
            ?>">
                <?= h(ucfirst(str_replace('_', ' ', $display_status))); ?>
            </span>
        </p>
        <p><strong>Delivery Date:</strong> 
            <?= !empty($order['delivery_date']) 
                ? date('F j, Y', strtotime($order['delivery_date'])) 
                : "<span class='text-muted'>Not yet scheduled</span>"; ?>
        </p>
        <p><strong>Total:</strong> ₱<?= number_format($order['total'], 2); ?></p>
    </div>

    <?php if ($order['status'] === 'pending'): ?>
        <div class="card bg-light p-3 mb-4 border-danger">
            <h5 class="text-danger"><i class="bi bi-exclamation-triangle-fill"></i> Request Cancellation</h5>
            <form id="cancellationForm" action="/sweepxpress/customers/cancel_order.php" method="POST">
                <input type="hidden" name="order_id" value="<?= h($order_id); ?>">
                <div class="mb-3">
                    <label for="cancellationReason" class="form-label">Reason for Cancellation:</label>
                    <textarea class="form-control" id="cancellationReason" name="reason" rows="3" required minlength="10"></textarea>
                </div>
                <button type="submit" class="btn btn-danger">Submit Cancellation Request</button> 
            </form>
        </div>
    <?php elseif ($order['status'] === 'cancellation_requested'): ?>
        <div class="alert alert-danger mb-4">
            <i class="bi bi-info-circle-fill"></i> Cancellation request pending.
            <?php if ($order['cancellation_reason']): ?>
                <br>Reason: <em><?= h($order['cancellation_reason']); ?></em>
            <?php endif; ?>
        </div>
    <?php elseif ($order['status'] === 'cancelled'): ?>
        <div class="alert alert-warning mb-4">
            <i class="bi bi-x-circle-fill"></i> Order Cancelled.
            <?php if ($order['admin_note']): ?>
                <br>Admin Note: <em><?= h($order['admin_note']); ?></em>
            <?php endif; ?>
        </div>
    <?php elseif ($order['status'] === 'delivered'): ?>
        <div class="card bg-light p-3 mb-4 border-success">
            <h5 class="text-success"><i class="bi bi-box-seam"></i> Confirm Delivery</h5>
            <p>Your order has been delivered. Please confirm that you received it.</p>
            <form id="confirmReceivedForm" action="/sweepxpress/customers/confirm_received.php" method="POST">
                <input type="hidden" name="order_id" value="<?= h($order_id); ?>">
                <button type="button" id="confirmReceivedBtn" class="btn btn-success">
                    <i class="bi bi-check2-circle"></i> Confirm Delivery
                </button>
            </form>
        </div>
    <?php elseif ($order['status'] === 'completed'): ?>
        <div class="alert alert-success mb-4">
            <i class="bi bi-check-circle-fill"></i> You confirmed that you received your order. Thank you!
        </div>
    <?php endif; ?>

    <h4>🛒 Items in this Order</h4>
    <?php if ($items): ?>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Image</th>
                        <th>Product</th>
                        <th class="text-center">Quantity</th>
                        <th class="text-end">Price</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
    <?php
    $defaultImage = '/sweepxpress/assets/img/no-image.png';
    ?>
    <?php foreach ($items as $item): ?>
        <?php
            // Security check for image path
            $safeImageName = basename($item['image'] ?? '');
            $imgFile = !empty($safeImageName) ? __DIR__ . '/../assets/uploads/' . $safeImageName : null;
            
            if (!$imgFile || !file_exists($imgFile)) {
                $imgPath = $defaultImage;
            } else {
                // Assuming images are stored in /assets/uploads/
                $imgPath = '/sweepxpress/assets/uploads/' . $safeImageName;
            }
        ?>
        <tr>
            <td class="text-center">
                <img src="<?= $imgPath; ?>" alt="<?= h($item['name']); ?>" 
                    class="rounded" width="70" height="70" 
                    style="object-fit: cover;">
            </td>
            <td><?= h($item['name']); ?></td>
            <td class="text-center"><?= h($item['quantity']); ?></td>
            <td class="text-end">₱<?= number_format($item['price'], 2); ?></td>
            <td class="text-end">₱<?= number_format($item['price'] * $item['quantity'], 2); ?></td>
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
    // PHP variables are correctly escaped using json_encode in the JS section
    const swalTitle = <?= json_encode($swal_title); ?>;
    // Check if the text contains newline for admin decision notes
    let swalText = <?= json_encode($swal_text); ?>;
    
    // Replace newline characters for better display in SweetAlert
    if (swalText.includes("\\n")) {
        swalText = swalText.replace(/\\n/g, "\n");
    }

    const swalIcon = <?= json_encode($swal_icon); ?>;
    const orderId = <?= json_encode($order_id); ?>;
    const isAdminDecision = <?= $is_admin_decision_swal ? 'true' : 'false'; ?>;

    // Show status SweetAlert
    if (swalTitle && swalIcon) {
        Swal.fire({
            title: swalTitle,
            // Use pre-formatted text that handles \n
            text: swalText,
            icon: swalIcon,
            confirmButtonText: 'Got it',
            // Pre-wrap handles the \n characters as line breaks
            customClass: {
                content: 'swal2-pre-wrap'
            }
        }).then(() => {
            if (isAdminDecision === true) {
                // Redirect to cleanup URL
                window.location.href = '?id=' + orderId + '&cleanup=1';
            } else {
                // Clean the URL without reloading (only for non-admin decision messages)
                if (window.history.replaceState) {
                    const cleanUrl = window.location.href.split('?')[0];
                    window.history.replaceState({}, '', cleanUrl + '?id=' + orderId);
                }
            }
        });
    }

    // --- Cancel order confirmation ---
    const cancelForm = document.getElementById('cancellationForm');
    const reasonInput = document.getElementById('cancellationReason');
    if (cancelForm) {
        cancelForm.addEventListener('submit', function(e) {
            e.preventDefault();
            // Using checkValidity() ensures both 'required' and 'minlength' are checked
            if (!reasonInput.checkValidity()) {
                reasonInput.reportValidity();
                return;
            }
            Swal.fire({
                title: 'Are you sure?',
                text: "You are about to request cancellation of this order.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, Submit Request!'
            }).then((result) => {
                if (result.isConfirmed) cancelForm.submit();
            });
        });
    }

    // --- Confirm Delivery Modal ---
    const confirmBtn = document.getElementById('confirmReceivedBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function(e) {
            Swal.fire({
                title: 'Did you receive your order?',
                text: "Please confirm whether you received your order in good condition.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, I received it',
                cancelButtonText: 'No, I did not receive it',
                reverseButtons: true
            }).then((result) => {
                const form = document.getElementById('confirmReceivedForm');
                const input = document.createElement('input');
                
                input.type = 'hidden';
                input.name = 'received';
                
                // If confirmed, send 'yes', otherwise send 'no'
                input.value = result.isConfirmed ? 'yes' : 'no';
                
                // Only submit if the user pressed one of the buttons (confirmed or cancelled in the modal)
                if (result.isConfirmed || result.dismiss === Swal.DismissReason.cancel) {
                    form.appendChild(input);
                    form.submit();
                }
            });
        });
    }
});
</script>

<style>
.swal2-pre-wrap {
    white-space: pre-wrap !important;
}
</style>

<?php 
// 6. FOOTER INCLUDE
require_once __DIR__ . '/../includes/footer.php'; 
?>