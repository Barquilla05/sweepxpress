<?php
// new_password.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/header_public.php'; // light header, walang auth check

// --- 1. Kunin ang token mula sa URL ---
$token = $_GET['token'] ?? '';
$show_form = false;
$message = '';

if ($token) {
    // --- 2. Hanapin ang user gamit ang password_reset_token at valid expiry ---
    $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE password_reset_token = ? AND reset_token_expiry > NOW() LIMIT 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $show_form = true;
        $user_id = $user['id'];

        // --- 3. Kapag nag-submit ng bagong password ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $new_password = trim($_POST['new_password'] ?? '');
            $confirm_password = trim($_POST['confirm_password'] ?? '');

            if (empty($new_password) || empty($confirm_password)) {
                $message = '<div class="alert alert-danger">Please fill in all fields.</div>';
            } elseif ($new_password !== $confirm_password) {
                $message = '<div class="alert alert-danger">Passwords do not match.</div>';
            } elseif (strlen($new_password) < 6) {
                $message = '<div class="alert alert-danger">Password must be at least 6 characters long.</div>';
            } else {
                // --- 4. I-hash ang password at i-update sa database ---
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt_update = $pdo->prepare("UPDATE users SET password = ?, password_reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");
                $stmt_update->execute([$hashed_password, $user_id]);

                $message = '<div class="alert alert-success text-center">
                                Your password has been updated successfully! 
                                <a href="login.php" class="fw-bold text-success">Login here</a>.
                            </div>';
                $show_form = false;
            }
        }
    } else {
        $message = '<div class="alert alert-danger">Invalid or expired token. Please request a new password reset.</div>';
    }
} else {
    $message = '<div class="alert alert-danger">Invalid request. No token provided.</div>';
}
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-header bg-primary text-white text-center rounded-top-4">
                    <h3 class="mb-0">Set New Password</h3>
                </div>
                <div class="card-body">
                    <?= $message; ?>

                    <?php if ($show_form): ?>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success">Update Password</button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <div class="text-center mt-3">
                        <a href="login.php" class="text-secondary">Back to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
