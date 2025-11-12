<?php
// E:\xampp\htdocs\sweepxpress\resetpassword.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. CONFIGURATION AT LIBRARIES
require_once __DIR__ . '/config.php'; // I-assume na nandito ang PDO $pdo connection
require_once __DIR__ . '/includes/auth.php'; // Kung may helper functions
require_once __DIR__ . '/includes/email_config.php'; // Dito naka-define ang SMTP constants

// I-assume na naka-install ang PHPMailer via Composer at nandito ang autoload
// Kung hindi, palitan ang path sa iyong PHPMailer setup file.
require_once __DIR__ . '/vendor/autoload.php'; 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $error_message = "Please enter your email address.";
    } else {
        try {
            // A. CHECK USER
            $stmt = $pdo->prepare("SELECT id, email, name FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $user_id = $user['id'];
                $token = bin2hex(random_bytes(32));
                // Token is valid for 1 hour
                $expires_at = date('Y-m-d H:i:s', time() + 3600); 

                // B. SAVE TOKEN TO DATABASE (Password Resets Table)
                // Delete any existing tokens for this user first
                $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$user_id]);
                
                // Insert the new token
                $stmt_insert = $pdo->prepare("
                    INSERT INTO password_resets (user_id, token, expires_at) 
                    VALUES (?, ?, ?)
                ");
                $stmt_insert->execute([$user_id, $token, $expires_at]);

                // C. SEND EMAIL
                $mail = new PHPMailer(true);

                // --- 🚩 DEBUG MODE ACTIVATED (Para makita ang "Could not connect" error) ---
                $mail->SMTPDebug = 2; // Ipakita ang debug output
                $mail->Debugoutput = 'html'; 
                // --- 🚩 END DEBUG MODE ---
                
                $mail->isSMTP();
                $mail->Host       = SMTP_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USER;
                $mail->Password   = SMTP_PASS;
                $mail->SMTPSecure = SMTP_SECURE;
                $mail->Port       = SMTP_PORT;

                // Recipients
                $mail->setFrom(SMTP_USER, 'SweepXpress Support');
                $mail->addAddress($email, $user['name']);

                // Content
                $reset_link = "http://localhost/sweepxpress/new_password.php?token=" . urlencode($token);


                
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request';
                $mail->Body    = "
                    Hello {$user['name']},<br><br>
                    You requested a password reset. Click the link below to reset your password. This link will expire in 1 hour.<br><br>
                    <a href='{$reset_link}'>Reset My Password</a><br><br>
                    If you did not request this, please ignore this email.
                ";
                $mail->AltBody = "Hello {$user['name']}, Copy this link to reset your password: {$reset_link}";

                $mail->send();
                $success_message = "A password reset link has been sent to your email address: **{$email}**. Please check your inbox and spam folder.";

            } else {
                // To prevent email enumeration, we still show a success-like message
                $success_message = "If an account with that email exists, a password reset link has been sent.";
            }

        } catch (Exception $e) {
            // I-capture ang error, lalo na ang "Could not connect"
            $error_message = "An error occurred: " . $e->getMessage();
            // Kung may debug output, lalabas ito sa screen kasama ng error message.
        }
    }
}
// 2. HTML HEADER INCLUDE (DAPAT NAKALAGAY SA HULI BAGO ANG HTML OUTPUT)
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white text-center">
                    <h3 class="mb-0">Reset Password</h3>
                </div>
                <div class="card-body">
                    <?php if ($success_message): ?>
                        <div class="alert alert-success" role="alert">
                            <?= h($success_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger" role="alert">
                            <?= h($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!$success_message): ?>
                        <p class="text-center mb-4">Enter your email to receive a password reset link.</p>
                        <form method="POST" action="resetpassword.php">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Send Reset Link</button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <div class="text-center mt-3">
                        <a href="/sweepxpress/login.php" class="text-secondary">Back to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
// 3. FOOTER INCLUDE
require_once __DIR__ . '/includes/footer.php'; 
?>