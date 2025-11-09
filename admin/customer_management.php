<?php
require_once __DIR__ . '/../includes/auth.php';
if (!is_admin()) {
    header("Location: /sweepxpress/login.php");
    exit;
}
require_once __DIR__ . '/../includes/header.php';

// Fetch all users with role
$customers = $pdo->query("
    SELECT id, name, email, role, created_at
    FROM users
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Define badge styles for modern look
function get_role_badge_class($role) {
    return match($role) {
        'admin' => 'bg-danger-subtle text-danger',
        'customer' => 'bg-primary-subtle text-primary',
        'business' => 'bg-success-subtle text-success',
        default => 'bg-secondary-subtle text-secondary'
    };
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
                            <th>Joined</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $c): ?>
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
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? "" : "none";
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>