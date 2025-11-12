<?php
// reset_password.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/log_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$msg = '';
$msg_type = 'danger';
$show_form = false;

$token = $_GET['token'] ?? '';
$email = trim($_GET['email'] ?? '');

if (empty($token) || empty($email)) {
    $msg = "Invalid request. Please use the link provided in your email.";
} else {
    try {
        // 1. Find user by email and token, and check if the token is not expired
        $stmt = $pdo->prepare("
            SELECT id 
            FROM users 
            WHERE email = ? 
            AND password_reset_token = ? 
            AND reset_token_expiry > NOW()
        ");
        $stmt->execute([$email, $token]);
        $user_id = $stmt->fetchColumn();

        if ($user_id) {
            $show_form = true;
            $msg_type = 'info';
            
            // 2. Handle new password submission
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $new_pass = $_POST['password'] ?? '';
                $confirm_pass = $_POST['confirm_password'] ?? '';

                if (strlen($new_pass) < 6) {
                    $msg = "Password must be at least 6 characters long.";
                } elseif ($new_pass !== $confirm_pass) {
                    $msg = "Passwords do not match.";
                } else {
                    // Hash the new password
                    $password_hash = password_hash($new_pass, PASSWORD_DEFAULT);

                    // Update password and clear token
                    $update_stmt = $pdo->prepare("
                        UPDATE users 
                        SET password_hash = ?, password_reset_token = NULL, reset_token_expiry = NULL 
                        WHERE id = ?
                    ");
                    $update_stmt->execute([$password_hash, $user_id]);

                    $msg = "✅ Your password has been successfully reset. You can now log in.";
                    $msg_type = 'success';
                    $show_form = false; // Hide the form on success
                    log_user_action($pdo, $user_id, 'PASSWORD_RESET_SUCCESS', 'User successfully reset their password via token.');
                }
            }
        } else {
            $msg = "Invalid or expired reset link. Please try requesting a new one.";
        }

    } catch (Exception $e) {
        $msg = "A database error occurred: " . $e->getMessage();
        $msg_type = 'danger';
        error_log("Password reset error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - SweepXpress</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #f0f4f8 0%, #d9e2ec 100%); }
        .login-container { min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card-login { max-width: 400px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); border-radius: 1rem; border: none; padding: 1.5rem; }
    </style>
</head>
<body class="login-container">
  <div class="container">
    <div class="row w-100 justify-content-center">
      <div class="col-12 col-sm-10 col-md-8 col-lg-6">
        <div class="card card-login mx-auto">
          <div class="text-center mb-4"> 
            <h1 class="h3 fw-bold text-primary">New Password</h1>
            <p class="text-secondary">Set a new password for your account.</p>
          </div>
          
          <?php if ($msg): ?>
            <div class="alert alert-<?= $msg_type ?> mb-3" role="alert"><?= htmlspecialchars($msg) ?></div>
          <?php endif; ?>

          <?php if ($show_form): ?>
            <form action="" method="POST">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">

                <div class="form-floating mb-3">
                  <input type="password" class="form-control" id="password" name="password" placeholder="New Password" required minlength="6">
                  <label for="password">New Password</label>
                </div>
                
                <div class="form-floating mb-3">
                  <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required minlength="6">
                  <label for="confirm_password">Confirm Password</label>
                </div>
                
                <button type="submit" class="btn btn-success btn-lg w-100 mb-3 fw-bold">
                    Update Password
                </button>
            </form>
          <?php endif; ?>

          <div class="text-center mt-3">
            <a href="/sweepxpress/login.php" class="text-primary fw-bold">Back to Login</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>