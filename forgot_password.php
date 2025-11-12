<?php
// forgot_password.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/email_config.php'; // Ipalagay na dito mo inilagay ang PHPMailer setup
require_once __DIR__ . '/includes/log_helper.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$msg = '';
$msg_type = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $msg = "Please enter your email address.";
        $msg_type = 'danger';
    } else {
        try {
            // 1. Check if user exists
            $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $user_id = $user['id'];
                
                // 2. Generate token and expiry (e.g., expires in 1 hour)
                $token = bin2hex(random_bytes(32)); 
                $expiry_time = date("Y-m-d H:i:s", time() + 3600); // 1 hour validity

                // 3. Store token and expiry in the database
                $stmt = $pdo->prepare("UPDATE users SET password_reset_token = ?, reset_token_expiry = ? WHERE id = ?");
                $stmt->execute([$token, $expiry_time, $user_id]);

                // 4. Send email
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/sweepxpress/reset_password.php?token=" . $token . "&email=" . urlencode($email);

                $mail = new PHPMailer(true);
                // Configuration details from email_config.php should be here

                // --- PHPMailer SETUP (Example) ---
                $mail->isSMTP();
                $mail->Host = SMTP_HOST; // Assuming these are defined in email_config.php
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USER;
                $mail->Password = SMTP_PASS;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // or ENCRYPTION_STARTTLS
                $mail->Port = SMTP_PORT;
                
                $mail->setFrom(SMTP_USER, 'SweepXpress Support');
                $mail->addAddress($email, $user['username']);
                
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request';
                $mail->Body    = "
                    <p>Hello {$user['username']},</p>
                    <p>A password reset was requested for your account. Click the link below to reset your password. This link will expire in 1 hour.</p>
                    <p><a href='{$reset_link}'>Reset Password Link</a></p>
                    <p>If you did not request a password reset, please ignore this email.</p>
                ";
                
                if($mail->send()) {
                    $msg = "Password reset link sent to your email ({$email}). Check your inbox and spam folder.";
                    $msg_type = 'success';
                    log_user_action($pdo, $user_id, 'PASSWORD_RESET_REQUESTED', 'Password reset token generated and email sent.');
                } else {
                    $msg = "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
                    $msg_type = 'danger';
                    error_log("Password reset email failure: " . $mail->ErrorInfo);
                }

            } else {
                // To prevent enumeration, use a vague success message even if the user doesn't exist
                $msg = "If the email is registered, a password reset link has been sent.";
                $msg_type = 'info';
            }

        } catch (Exception $e) {
            $msg = "An error occurred: " . $e->getMessage();
            $msg_type = 'danger';
            error_log("Forgot password process error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - SweepXpress</title>
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
            <h1 class="h3 fw-bold text-primary">Reset Password</h1>
            <p class="text-secondary">Enter your email to receive a password reset link.</p>
          </div>
          
          <?php if ($msg): ?>
            <div class="alert alert-<?= $msg_type ?> mb-3" role="alert"><?= htmlspecialchars($msg) ?></div>
          <?php endif; ?>

          <form action="" method="POST">
            <div class="form-floating mb-3">
              <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required>
              <label for="email">Email address</label>
            </div>
            
            <button type="submit" class="btn btn-primary btn-lg w-100 mb-3 fw-bold">
                Send Reset Link
            </button>
          </form>

          <div class="text-center mt-3">
            Remember your password? <a href="/sweepxpress/login.php" class="text-primary fw-bold">Back to Login</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>