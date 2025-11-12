<?php
// /admin/view_logs.php
// Displays the activity logs for a specific user.

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config.php'; // Includes $pdo connection

if (!is_admin()) {
    header("Location: /sweepxpress/login.php");
    exit;
}
require_once __DIR__ . '/../includes/header.php'; // Assuming this includes Bootstrap/CSS

// --- Input Validation and User Fetching ---
$userId = $_GET['user_id'] ?? null;

if (!$userId || !is_numeric($userId)) {
    // If ID is invalid, redirect back to the customer list
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'Invalid user ID provided for viewing logs.'];
    header("Location: customer_management.php");
    exit;
}

// Fetch the name of the user whose logs we are viewing
try {
    $userStmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'User not found.'];
        header("Location: customer_management.php");
        exit;
    }
    $userName = htmlspecialchars($user['name']);

    // Fetch all logs for this user, ordered by most recent
    $logsStmt = $pdo->prepare("
        SELECT 
            ul.action_type, ul.description, ul.created_at, ul.ip_address,
            a.name AS admin_name
        FROM user_logs ul
        LEFT JOIN users a ON ul.action_by_id = a.id
        WHERE ul.user_id = ?
        ORDER BY ul.created_at DESC
    ");
    $logsStmt->execute([$userId]);
    $logs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("<div class='container p-5'><div class='alert alert-danger'>Database error: Could not fetch logs. " . htmlspecialchars($e->getMessage()) . "</div></div>");
}

// --- Helper for styling log types ---
function get_log_badge_class($type) {
    return match($type) {
        'ACCOUNT_SUSPENDED', 'ACCOUNT_DELETED' => 'bg-danger',
        'LOGIN_FAILURE', 'PASSWORD_RESET' => 'bg-warning text-dark',
        'ROLE_CHANGE', 'UNSUSPENDED' => 'bg-success',
        'LOGIN_SUCCESS', 'PROFILE_UPDATE' => 'bg-info',
        default => 'bg-secondary'
    };
}
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<div class="container p-4 p-lg-5">
    
    <a href="user_actions.php?id=<?= $userId; ?>" class="btn btn-outline-secondary mb-4">
        <i class="fas fa-arrow-left me-2"></i> Back to <?= $userName; ?>
    </a>

    <h1 class="fw-bolder text-dark mb-4">
        <i class="fas fa-clipboard-list text-info me-2"></i> User Activity Logs for: <?= $userName; ?>
    </h1>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Found <?= count($logs); ?> Log Entries</h5>
        </div>
        <div class="card-body p-0">
            <?php if (count($logs) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Timestamp</th>
                                <th>Action Type</th>
                                <th>Description</th>
                                <th>Performer</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= date("M d, Y h:i A", strtotime($log['created_at'])); ?></td>
                                    <td>
                                        <span class="badge <?= get_log_badge_class($log['action_type']); ?>">
                                            <?= str_replace('_', ' ', $log['action_type']); ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($log['description']); ?></td>
                                    <td>
                                        <?php 
                                            // Action taken by an admin
                                            if ($log['admin_name']) {
                                                echo '<i class="fas fa-user-shield me-1 text-danger"></i> ' . $log['admin_name'];
                                            } else {
                                                // Action taken by the user themselves (e.g., login)
                                                echo '<i class="fas fa-user me-1 text-primary"></i> ' . $userName;
                                            }
                                        ?>
                                    </td>
                                    <td><?= htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-search-minus fa-3x mb-3"></i>
                    <p class="mb-0">No activity logs found for this user.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>