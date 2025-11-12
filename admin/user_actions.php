<?php
// Core System Includes
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config.php'; // Ensure config.php with $pdo is included

if (!is_admin()) {
    header("Location: /sweepxpress/login.php");
    exit;
}
require_once __DIR__ . '/../includes/header.php';

// --- Input Validation and User Fetching ---
$userId = $_GET['id'] ?? null;

if (!$userId || !is_numeric($userId)) {
    // Redirect if ID is missing or invalid
    header("Location: customer_management.php");
    exit;
}

// Fetch main user data
try {
    // IMPORTANT: Ensure 'suspended_until' is selected to potentially display status
    $stmt = $pdo->prepare("
        SELECT id, name, email, role, created_at, profile_image, suspended_until
        FROM users
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Redirect if user not found
        header("Location: customer_management.php");
        exit;
    }

    // --- Fetch Correlated Order History ---
    $orders = $pdo->prepare("
        SELECT id, status, total, created_at
        FROM orders
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    $orders->execute([$userId]);
    $orderHistory = $orders->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("<div class='container p-5'><div class='alert alert-danger'>Database error: " . htmlspecialchars($e->getMessage()) . "</div></div>");
}


// --- HELPER FUNCTIONS ---

// Function for Gravatar (Profile Image)
function get_gravatar_url($email, $s = 150, $d = 'identicon', $r = 'g') {
    $hash = md5(strtolower(trim($email)));
    return "https://www.gravatar.com/avatar/$hash?s=$s&d=$d&r=$r";
}

// Function for Role Badge Colors
function get_role_badge_class_actions($role) {
    return match($role) {
        'admin' => 'bg-danger',
        'customer' => 'bg-primary',
        'business' => 'bg-success',
        default => 'bg-secondary'
    };
}

// Function for Order Status Badge Colors
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

// APPLYING THE LOGIC: Prioritize uploaded image path, fall back to Gravatar
$uploadedImagePath = $user['profile_image'] ?? null; 
$gravatarUrl = get_gravatar_url($user['email'], 120); 
$displayImagePath = htmlspecialchars($uploadedImagePath ?: $gravatarUrl);

// Determine suspension status for display and button logic
$isSuspended = false;
$suspensionExpiry = null;
if (!empty($user['suspended_until'])) {
    $suspensionExpiry = new DateTime($user['suspended_until']);
    if ($suspensionExpiry > new DateTime()) {
        $isSuspended = true;
    }
}
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<div class="container p-4 p-lg-5">
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= htmlspecialchars($_SESSION['message']['type']) ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['message']['text']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message']); // Clear the message after displaying ?>
    <?php endif; ?>
    <a href="customer_management.php" class="btn btn-outline-secondary mb-4">
        <i class="fas fa-arrow-left me-2"></i> Back to User List
    </a>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="fw-bolder text-dark">
            <i class="fas fa-user-circle text-primary me-2"></i> Manage User: <?= htmlspecialchars($user['name']); ?>
        </h1>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i> User Details</h5>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <img 
                            src="<?= $displayImagePath ?>" 
                            alt="<?= htmlspecialchars($user['name']); ?>'s Profile Image" 
                            class="rounded-circle border border-5 border-light-subtle shadow-sm"
                            style="width: 120px; height: 120px; object-fit: cover;"
                        >
                        <h3 class="mt-3 mb-0 fw-bold"><?= htmlspecialchars($user['name']); ?></h3>
                        <p class="text-muted"><small>User ID: #<?= $user['id']; ?></small></p>
                    </div>

                    <hr>

                    <?php if ($isSuspended): ?>
                        <div class="alert alert-danger fw-bold text-center" role="alert">
                            <i class="fas fa-lock me-2"></i> ACCOUNT SUSPENDED
                            <br><small>Until: <?= $suspensionExpiry->format('M d, Y h:i A'); ?></small>
                        </div>
                    <?php endif; ?>

                    <p class="mb-2 text-start">
                        <strong>Email:</strong> 
                        <a href="mailto:<?= htmlspecialchars($user['email']); ?>">
                            <?= htmlspecialchars($user['email']); ?>
                        </a>
                    </p>
                    <p class="mb-2 text-start">
                        <strong>Current Role:</strong>
                        <span id="currentRoleBadge" class="badge <?= get_role_badge_class_actions($user['role']) ?> px-3 py-1">
                            <?= ucfirst($user['role']) ?>
                        </span>
                    </p>
                    <p class="mb-0 text-muted text-start">Joined: <?= date("M d, Y", strtotime($user['created_at'])); ?></p>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i> Update User Role</h5>
                </div>
                <div class="card-body">
                    <form id="roleUpdateForm" class="row g-3">
                        <input type="hidden" name="user_id" value="<?= $user['id']; ?>">
                        <div class="col-sm-8">
                            <label for="userRole" class="form-label fw-bold">Select New Role</label>
                            <select id="userRole" name="role" class="form-select form-select-lg">
                                <option value="customer" <?= ($user['role'] == 'customer') ? 'selected' : ''; ?>>Customer</option>
                                <option value="business" <?= ($user['role'] == 'business') ? 'selected' : ''; ?>>Business</option>
                                <option value="admin" <?= ($user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        <div class="col-sm-4 d-flex align-items-end">
                            <button type="submit" id="saveRoleBtn" class="btn btn-success btn-lg w-100 fw-bold">
                                <i class="fas fa-save me-1"></i> Save
                            </button>
                        </div>
                    </form>
                    <div id="roleMessage" class="mt-3 alert d-none" role="alert"></div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i> Order History</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (count($orderHistory) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orderHistory as $order): ?>
                                        <tr>
                                            <td><span class="badge bg-dark">#<?= $order['id']; ?></span></td>
                                            <td><?= date("M d, Y", strtotime($order['created_at'])); ?></td>
                                            <td>₱<?= number_format($order['total'], 2); ?></td> 
                                            <td>
                                                <span class="badge <?= get_status_badge_class($order['status']); ?>">
                                                    <?= ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="order_details.php?id=<?= $order['id']; ?>" class="btn btn-sm btn-outline-info">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="p-4 text-center text-muted">
                            <i class="fas fa-box-open fa-3x mb-3"></i>
                            <p class="mb-0">This user has not placed any orders yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-cogs me-2"></i> Other Account Actions (Admin Control)</h5>
                </div>
                <div class="card-body">
                    <div class="row row-cols-2 row-cols-md-3 g-3 text-center">
                        
                        <div class="col">
                            <a href="delete.php?user_id=<?= $user['id']; ?>" class="btn btn-danger w-100 py-3 text-white fw-bold" onclick="return confirm('WARNING: Are you sure you want to permanently DELETE this user?')">
                                <i class="fas fa-trash-alt fa-2x d-block mb-1"></i>
                                Delete
                            </a>
                        </div>
                        <div class="col">
                            <?php if ($isSuspended): ?>
                                <a href="unsuspend.php?user_id=<?= $user['id']; ?>" class="btn btn-success w-100 py-3 text-white fw-bold">
                                    <i class="fas fa-undo fa-2x d-block mb-1"></i>
                                    Unsuspend
                                </a>
                            <?php else: ?>
                                <a href="suspend.php?user_id=<?= $user['id']; ?>" class="btn btn-warning w-100 py-3 text-dark fw-bold" onclick="return confirm('Confirm suspending this account for 30 days?')">
                                    <i class="fas fa-hand-paper fa-2x d-block mb-1"></i>
                                    Suspend
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="col">
                            <a href="view_logs.php?user_id=<?= $user['id']; ?>" class="btn btn-info w-100 py-3 text-white fw-bold">
                                <i class="fas fa-clipboard-list fa-2x d-block mb-1"></i>
                                View Logs
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('roleUpdateForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    const formData = new URLSearchParams(new FormData(form)).toString();
    const messageBox = document.getElementById('roleMessage');
    const saveBtn = document.getElementById('saveRoleBtn');
    const currentRoleBadge = document.getElementById('currentRoleBadge');
    const newRole = document.getElementById('userRole').value;

    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

    // AJAX request to edit_role.php 
    fetch('edit_role.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData
    })
    .then(res => res.text())
    .then(data => {
        messageBox.textContent = data;
        messageBox.classList.remove('d-none', 'alert-danger', 'alert-success');
        
        if (data.includes('Success')) {
            messageBox.classList.add('alert-success');

            // Update the current role badge dynamically on success
            const roleClasses = {
                'admin': 'bg-danger',
                'customer': 'bg-primary',
                'business': 'bg-success'
            };
            currentRoleBadge.className = `badge ${roleClasses[newRole]} px-3 py-1`;
            currentRoleBadge.textContent = newRole.charAt(0).toUpperCase() + newRole.slice(1);
        } else {
            messageBox.classList.add('alert-danger');
        }
    })
    .catch(error => {
        messageBox.textContent = 'An unknown network error occurred during the update.';
        messageBox.classList.remove('d-none', 'alert-success');
        messageBox.classList.add('alert-danger');
        console.error('Error:', error);
    })
    .finally(() => {
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="fas fa-save me-1"></i> Save';
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>