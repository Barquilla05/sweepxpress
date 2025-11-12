<?php
// /admin/customer_management.php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config.php'; // Ensure config.php with $pdo is included
require_once __DIR__ . '/../includes/log_helper.php'; // Needed for last login check

if (!is_admin()) {
    header("Location: /sweepxpress/login.php");
    exit;
}
require_once __DIR__ . '/../includes/header.php';

// --- PHP Functions for Styling and Status Logic ---

// Define badge styles for modern look (Role Badge)
function get_role_badge_class($role) {
    return match($role) {
        'admin' => 'bg-danger-subtle text-danger',
        'customer' => 'bg-primary-subtle text-primary',
        'business' => 'bg-success-subtle text-success',
        default => 'bg-secondary-subtle text-secondary'
    };
}

/**
 * Determines the status of a user based on suspension and last login time.
 * @param string|null $suspendedUntil The suspension expiration date from the users table.
 * @param string|null $lastLoginAt The last successful login time from user_logs.
 * @return array Contains 'status' string and 'class' for the Bootstrap badge.
 */
function get_user_status($suspendedUntil, $lastLoginAt) {
    $now = new DateTime();
    $status = 'Active';
    $badgeClass = 'bg-success'; // Default to Active

    // 1. Check for Suspension (Highest Priority)
    if (!empty($suspendedUntil)) {
        $suspensionExpiry = new DateTime($suspendedUntil);
        if ($now < $suspensionExpiry) {
            $status = 'Suspended';
            $badgeClass = 'bg-danger';
            return ['status' => $status, 'class' => $badgeClass];
        }
    }

    // 2. Check for Inactivity (30 days threshold)
    $inactivityThreshold = new DateTime('-30 days');

    if ($lastLoginAt) {
        $lastLogin = new DateTime($lastLoginAt);
        
        if ($lastLogin < $inactivityThreshold) {
            $status = 'Inactive';
            $badgeClass = 'bg-warning text-dark';
        }
        // Else: Active (last login is within 30 days)
    } else {
        // No successful login logs found
        $status = 'Inactive'; 
        $badgeClass = 'bg-warning text-dark'; 
    }
    
    return ['status' => $status, 'class' => $badgeClass];
}


// --- Data Fetching ---

// Fetch all users with role, suspension date, and last login date
try {
    $stmt = $pdo->query("
        SELECT 
            u.id, 
            u.name, 
            u.email, 
            u.role, 
            u.created_at, 
            u.suspended_until, 
            (
                SELECT MAX(ul.created_at)
                FROM user_logs ul
                WHERE ul.user_id = u.id AND ul.action_type = 'LOGIN_SUCCESS'
            ) AS last_login_at
        FROM users u
        ORDER BY u.created_at DESC
    ");
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("<div class='container p-5'><div class='alert alert-danger'>Database error: Could not fetch users. " . htmlspecialchars($e->getMessage()) . "</div></div>");
}
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<div class="container-fluid p-4 p-lg-5">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h1 class="fw-bolder text-dark">
            <i class="fas fa-users-cog text-primary me-2"></i> User Management
        </h1>
        <div class="col-md-4 col-lg-3">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                <input type="text" id="searchCustomer" class="form-control border-start-0" placeholder="Search users...">
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-lg">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0 fw-bold text-dark-emphasis">All Registered Users</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0" id="customerTable">
                    <thead class="table-light">
                        <tr>
                            <th class="text-muted">ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th> 
                            <th>Joined</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $c): ?>
                            <?php 
                                $statusData = get_user_status($c['suspended_until'], $c['last_login_at']); 
                            ?>
                            <tr>
                                <td class="text-muted fw-bold">#<?= $c['id']; ?></td>
                                <td><?= htmlspecialchars($c['name']); ?></td>
                                <td>
                                    <a href="mailto:<?= htmlspecialchars($c['email']); ?>" class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($c['email']); ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge rounded-pill <?= get_role_badge_class($c['role']) ?> px-3 py-2">
                                        <?= ucfirst($c['role']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge rounded-pill <?= $statusData['class'] ?> px-3 py-2">
                                        <?= $statusData['status'] ?>
                                    </span>
                                </td>
                                <td class="text-muted"><?= date("M d, Y", strtotime($c['created_at'])); ?></td>
                                <td>
                                    <a href="user_actions.php?id=<?= $c['id']; ?>" class="btn btn-sm btn-outline-primary shadow-sm">
                                        <i class="fas fa-tasks me-1"></i> Manage
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Live Search
document.getElementById('searchCustomer').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll("#customerTable tbody tr");
    rows.forEach(row => {
        // Search across all columns (Name, Email, Role, Status, Joined)
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? "" : "none";
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>