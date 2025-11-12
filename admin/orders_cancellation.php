<?php
// orders_cancellation.php

// 1. Core Includes and Setup
// Assuming these files exist in the parent directory (sweepxpress/)
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config.php'; 

// --- CRITICAL FIX: The Composer Autoloader ---
require_once __DIR__ . '/../vendor/autoload.php'; 

// 2. PHPMailer Class Use Statements
use PHPMailer\PHPMailer\PHPMailer; 
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// FIX: Define the helper function 'h' if it's not in the includes
if (!function_exists('h')) {
    function h($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

// Check admin access
if (!is_admin()) {
    header("Location: /sweepxpress/login.php");
    exit;
}

// 3. PHPMailer Configuration Constants (MUST BE UPDATED)
// ====================================================================
// *** IMPORTANT: REPLACE THESE PLACEHOLDERS WITH YOUR ACTUAL CREDENTIALS ***
// For Gmail, this must be a 16-character App Password, NOT your regular password.
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USERNAME', 'sweepxpress@gmail.com'); 
define('SMTP_PASSWORD', 'qzstxtuoqdokbffa'); 
define('SMTP_PORT', 587); 
define('SMTP_SECURE', PHPMailer::ENCRYPTION_STARTTLS);
define('EMAIL_FROM', 'sweepxpress@gmail.com');
define('EMAIL_FROM_NAME', 'SweepXpress Admin Team');
define('ADMIN_NOTIFICATION_EMAIL', 'sweepxpress@gmail.com'); // Internal notification recipient
// ====================================================================


// 4. Email Helper Function
// ====================================================================
/**
 * Sends a cancellation notification email to the user AND the admin.
 */
function send_cancellation_email($pdo, $recipientEmail, $recipientName, $orderId, $action, $adminNote) {
    // This is the line that required PHPMailer to be installed
    $mail = new PHPMailer(true); 

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        
        // Sender
        $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
        
        // Recipients (Customer and Admin)
        // 1. Customer (Primary recipient)
        $mail->addAddress($recipientEmail, $recipientName); 
        
        // 2. Admin (Internal notification - using BCC for privacy)
        if (!empty(ADMIN_NOTIFICATION_EMAIL)) {
            $mail->addBCC(ADMIN_NOTIFICATION_EMAIL, 'Admin Team'); 
        }

        // Content Setup
        $mail->isHTML(true);
        $orderLink = "https://www.your-live-domain.com/sweepxpress/order_details.php?id=" . $orderId;
        
        if ($action === 'approved') {
            $mail->Subject = 'Order Cancellation Confirmed (Order #' . $orderId . ')';
            $body = "
                <h2>✅ Cancellation Approved for Order #{$orderId}</h2>
                <p>Dear **" . h($recipientName) . "**,</p>
                <p>This is to confirm that your cancellation request for Order #{$orderId} has been **APPROVED** by the administration.</p>
                <p>The order status is now **Cancelled**.</p>
                <p><strong>Admin Note:</strong> " . (empty($adminNote) ? 'N/A' : h($adminNote)) . "</p>
                <p>You can view your order details here: <a href='" . h($orderLink) . "'>View Order #{$orderId}</a></p>
                <p>Thank you for choosing SweepXpress.</p>
            ";
        } else { // rejected
            $mail->Subject = 'Update on Cancellation Request (Order #' . $orderId . ')';
            $body = "
                <h2>❌ Cancellation Request Rejected for Order #{$orderId}</h2>
                <p>Dear **" . h($recipientName) . "**,</p>
                <p>We regret to inform you that your cancellation request for Order #{$orderId} has been **REJECTED** by the administration.</p>
                <p>The order status has been reset to **Pending**.</p>
                <p><strong>Admin Note:</strong> " . (empty($adminNote) ? 'No note provided.' : h($adminNote)) . "</p>
                <p>If you have any questions, please reply to this email.</p>
                <p>You can view your order details here: <a href='" . h($orderLink) . "'>View Order #{$orderId}</a></p>
            ";
        }

        $mail->Body = $body;
        $mail->AltBody = strip_tags($body); 

        $mail->send();
        return true;

    } catch (Exception $e) {
        // Log the error but return true so the user is not confused by an error message.
        error_log("Email Error for Order #{$orderId} ({$action}): {$mail->ErrorInfo}");
        return true; 
    }
}
// ====================================================================


require_once __DIR__ . '/../includes/header.php';
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php

// 5. CANCELLATION REQUEST ACTIONS 
// ====================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancellation_action'])) {
    $order_id = intval($_POST['order_id']);
    $action = $_POST['cancellation_action']; 
    $admin_note = trim($_POST['admin_note'] ?? '');
    $success_message = '';
    $is_error = false;
    
    // --- STEP 1: Fetch User Info for Email ---
    $stmt_user = $pdo->prepare("
        SELECT u.name, u.email 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?
    ");
    $stmt_user->execute([$order_id]);
    $user_info = $stmt_user->fetch(PDO::FETCH_ASSOC);

    $recipient_email = $user_info['email'] ?? '';
    $recipient_name = $user_info['name'] ?? 'Customer';
    // ------------------------------------------

    
    if ($action === 'approve') {
        $new_status = 'cancelled';
        $success_message = 'Cancellation Approved! Order #' . $order_id . ' is now cancelled.';
        
        $pdo->beginTransaction();
        try {
            // 1. Update order status
            $stmt_order = $pdo->prepare("
                UPDATE orders 
                SET status = ?, 
                    admin_note = ?,
                    cancellation_reason = NULL  
                WHERE id = ? AND status = 'cancellation_requested'
            ");
            $stmt_order->execute([$new_status, $admin_note, $order_id]);

            if ($stmt_order->rowCount() === 0) {
                 throw new Exception("Order status was not 'cancellation_requested'. Status remains unchanged.");
            }

            // 2. Update deliveries status 
            $stmt_delivery = $pdo->prepare("
                UPDATE deliveries 
                SET status = ? 
                WHERE order_id = ?
            ");
            $stmt_delivery->execute([$new_status, $order_id]);

            $pdo->commit();
            
            // --- STEP 2: Send Email on Successful Approval ---
            if (!empty($recipient_email)) {
                send_cancellation_email($pdo, $recipient_email, $recipient_name, $order_id, 'approved', $admin_note);
            }
            // -------------------------------------------------

        } catch (Exception $e) {
            $pdo->rollBack();
            $is_error = true;
            error_log("Cancellation Approval Error for Order #{$order_id}: " . $e->getMessage());
            $success_message = 'Database Error on Approval. Details: ' . h($e->getMessage());
        }

    } elseif ($action === 'reject') {
        $new_status = 'pending';
        // Default success message 
        $success_message = 'Cancellation Rejected! Order #' . $order_id . ' status reset to pending.';
        $is_error = false;
        
        try {
            // Update order status back to pending, but ONLY if it's currently 'cancellation_requested'
            $stmt_order = $pdo->prepare("
                UPDATE orders 
                SET status = ?, 
                    admin_note = ?,
                    cancellation_reason = NULL 
                WHERE id = ? AND status = 'cancellation_requested'
            ");
            $stmt_order->execute([$new_status, $admin_note, $order_id]);
            
            // --- CRUCIAL CHECK ADDED HERE ---
            // If rowCount is 0, it means the WHERE clause failed (status was not 'cancellation_requested')
            if ($stmt_order->rowCount() === 0) {
                 // Throw an exception to jump to the catch block and show a failure message
                 throw new Exception("Order status was not 'cancellation_requested'. Status remains unchanged.");
            }
            // --------------------------------
            
            // --- STEP 2: Send Email on Successful Rejection ---
            if (!empty($recipient_email)) {
                send_cancellation_email($pdo, $recipient_email, $recipient_name, $order_id, 'rejected', $admin_note);
            }
            // --------------------------------------------------
            
        } catch (PDOException $e) {
             // Catches actual database connection/query errors
             $is_error = true;
             error_log("Cancellation Rejection Error for Order #{$order_id}: " . $e->getMessage());
             $success_message = 'Database Error on Rejection. Details: ' . h($e->getMessage());
        } catch (Exception $e) { 
             // Catches the custom exception thrown if no row was updated
             $is_error = true;
             $success_message = 'Rejection Failed. Order status was already changed (e.g., manually or already pending).';
        }
    }
    
    // Display SweetAlert message and redirect
    $icon = $is_error ? 'error' : 'success';

    echo "<script>
    document.addEventListener('DOMContentLoaded', () => {
      Swal.fire({
        icon: '{$icon}',
        title: 'Action Complete!',
        text: '" . h($success_message) . "',
        confirmButtonColor: '#3085d6'
      }).then(() => {
        window.location.href = 'orders_cancellation.php'; 
      });
    });
    </script>";
    exit; 
}

// ====================================================================
// 6. FETCH CANCELLATION DATA 
// ====================================================================

// Fetch Cancellation Requests 
$stmt_cancellations = $pdo->query("
    SELECT 
        o.id, 
        o.total, 
        o.created_at, 
        o.cancellation_reason, 
        o.user_id, 
        u.name AS full_name, 
        u.email 
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.status = 'cancellation_requested' 
    ORDER BY o.created_at ASC
");
$cancellation_requests = $stmt_cancellations->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">🚨 Cancellation Request Review</h1>
        <a href="allorders.php" class="btn btn-secondary">⬅ Back to Orders Dashboard</a>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3 mt-5">
        <h2 class="h4 m-0">Pending Cancellation Requests</h2>
        <span class="badge bg-danger fs-6 text-white"><?= count($cancellation_requests) ?> Pending</span>
    </div>

    <div class="card shadow-sm border-0 mb-5">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-danger">
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Requested On</th>
                            <th>Reason (Snippet)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($cancellation_requests): ?>
                            <?php foreach ($cancellation_requests as $request): ?>
                                <tr class="cancellation-row" 
                                    data-id="<?= h($request['id']) ?>"
                                    data-name="<?= h($request['full_name']) ?>"
                                    data-email="<?= h($request['email']) ?>"
                                    data-total="₱<?= number_format($request['total'], 2) ?>"
                                    data-reason="<?= h($request['cancellation_reason']) ?>">
                                    
                                    <td>#<?= h($request['id']) ?></td>
                                    <td><?= h($request['full_name']) ?></td>
                                    <td>₱<?= number_format($request['total'], 2) ?></td>
                                    <td><?= date('M j, Y', strtotime($request['created_at'])) ?></td>
                                    <td style="max-width: 300px;" class="text-truncate">
                                        <?= h($request['cancellation_reason']) ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-review-cancellation">
                                            Review
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-muted py-4 text-center">No pending cancellation requests. 🎉</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="alert alert-info">
        Return to the <a href="allorders.php">Orders Dashboard</a> to manage all other order statuses.
    </div>

</div>

<div class="modal fade" id="cancellationModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Review Cancellation Request: Order #<span id="cancellationModalOrderId"></span></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p><strong>Customer:</strong> <span id="cancellationModalName"></span> (<span id="cancellationModalEmail"></span>)</p>
        <p><strong>Order Total:</strong> <span id="cancellationModalTotal"></span></p>
        <hr>
        <h6>Reason for Cancellation:</h6>
        <p id="cancellationModalReason" class="alert alert-warning text-break"></p>
        <hr>
        
        <form method="POST" id="cancellationActionForm">
          <input type="hidden" name="order_id" id="cancellationModalOrderIdInput">
          <input type="hidden" name="cancellation_action" id="cancellationModalActionInput">

          <div class="mb-3">
            <label for="adminNote" class="form-label">Admin Note (Optional):</label>
            <textarea name="admin_note" id="cancellationAdminNote" class="form-control" rows="3" placeholder="e.g., 'Refund processed', 'Customer booked wrong service.'"></textarea>
          </div>
          
          <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-success" id="btnApprove">
                <i class="bi bi-check-circle"></i> Approve & Cancel Order
            </button>
            <button type="button" class="btn btn-secondary" id="btnReject">
                <i class="bi bi-x-circle"></i> Reject & Keep Pending
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<style>
  .cancellation-row:hover { cursor: pointer; }
  #cancellationModalReason { white-space: pre-wrap; }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    
  if (typeof bootstrap === 'undefined' || !bootstrap.Modal) {
    console.error("Bootstrap 5 is not loaded or the Modal component is missing.");
    return; 
  }

  const cancellationModal = new bootstrap.Modal(document.getElementById('cancellationModal'));
  const cModalOrderId = document.getElementById('cancellationModalOrderId');
  const cModalName = document.getElementById('cancellationModalName');
  const cModalEmail = document.getElementById('cancellationModalEmail');
  const cModalTotal = document.getElementById('cancellationModalTotal');
  const cModalReason = document.getElementById('cancellationModalReason');
  const cModalOrderIdInput = document.getElementById('cancellationModalOrderIdInput');
  const cModalActionInput = document.getElementById('cancellationModalActionInput');
  const cBtnApprove = document.getElementById('btnApprove');
  const cBtnReject = document.getElementById('btnReject');
  const cForm = document.getElementById('cancellationActionForm');

  // Handle request click
  document.querySelectorAll('.btn-review-cancellation').forEach(button => {
    button.addEventListener('click', e => {
      e.stopPropagation(); 
      const row = button.closest('.cancellation-row');

      cModalOrderId.textContent = row.dataset.id;
      cModalName.textContent = row.dataset.name;
      cModalEmail.textContent = row.dataset.email;
      cModalTotal.textContent = row.dataset.total;
      cModalReason.textContent = row.dataset.reason;
      cModalOrderIdInput.value = row.dataset.id;
      
      cancellationModal.show();
    });
  });
  
  // Approve button handler
  cBtnApprove.addEventListener('click', () => {
    Swal.fire({
      title: 'Confirm Approval?',
      text: "This will set the order and delivery status to 'Cancelled' and notify the customer.",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#28a745',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Yes, Approve Cancellation!'
    }).then(result => {
      if (result.isConfirmed) {
        cModalActionInput.value = 'approve';
        cForm.submit();
      }
    });
  });

  // Reject button handler
  cBtnReject.addEventListener('click', () => {
    Swal.fire({
      title: 'Confirm Rejection?',
      text: "This will set the order status back to 'Pending' and notify the customer.",
      icon: 'info',
      showCancelButton: true,
      confirmButtonColor: '#ffc107',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Yes, Reject Request!'
    }).then(result => {
      if (result.isConfirmed) {
        cModalActionInput.value = 'reject';
        cForm.submit();
      }
    });
  });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> = 'reject';
        cForm.submit();
      }
    });
  });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>