<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_GET['token']) || !isset($_GET['email'])) {
    die("Invalid or missing token/email.");
}

$token = $_GET['token'];
$email = $_GET['email'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        try {
            // ✅ Check token + expiry from users table
            $stmt = $pdo->prepare("
                SELECT id, reset_token_expiry 
                FROM users 
                WHERE password_reset_token = :token AND email = :email 
                LIMIT 1
            ");
            $stmt->execute([':token' => $token, ':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $error = "Invalid token or email.";
            } elseif (strtotime($user['reset_token_expiry']) < time()) {
                $error = "Token has expired.";
            } else {
                // ✅ Update password and clear token
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update = $pdo->prepare("
                    UPDATE users 
                    SET password_hash = :password, password_reset_token = NULL, reset_token_expiry = NULL 
                    WHERE id = :id
                ");
                $update->execute([':password' => $hashed_password, ':id' => $user['id']]);

                $success = "Password reset successful. You can now <a href='login.php'>login</a>.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password | SweepXpress</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body {
        background: linear-gradient(135deg, #007bff 0%, #6610f2 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Poppins', sans-serif;
    }
    .card {
        border: none;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    }
    .btn-primary {
        background: linear-gradient(90deg, #007bff, #6610f2);
        border: none;
        border-radius: 10px;
        transition: 0.3s;
    }
    .btn-primary:hover {
        opacity: 0.9;
        transform: scale(1.02);
    }
    .form-control {
        border-radius: 10px;
        padding: 10px 12px;
    }
</style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card p-4 p-md-5 bg-white">
                <div class="text-center mb-4">
                    <img src="https://cdn-icons-png.flaticon.com/512/3064/3064197.png" width="70" alt="Lock Icon">
                    <h2 class="mt-3">Reset Password</h2>
                    <p class="text-muted">Enter your new password below.</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success text-center"><?= $success ?></div>
                <?php else: ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="new_password" class="form-label fw-semibold">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label fw-semibold">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2">Reset Password</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</body>
</html>
