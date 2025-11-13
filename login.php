<?php
// Enable error display (good for development, but consider disabling in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include DB config
require_once __DIR__ . '/config.php';
// NEW: Include the Log Helper
require_once __DIR__ . '/includes/log_helper.php';

// Start session if not active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$msg = '';

// Handle Login Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass = trim($_POST['password'] ?? '');

    if ($email && $pass) {
        // IMPORTANT: Ensure suspended_until is fetched
        $stmt = $pdo->prepare("SELECT id, role, password_hash, suspended_until FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($u && password_verify($pass, $u['password_hash'])) {
            
            // =========================================================================
            //  !!! CRITICAL: SUSPENSION CHECK BLOCK !!!
            // =========================================================================
            if (!empty($u['suspended_until'])) {
                
                $suspension_expiry = new DateTime($u['suspended_until']);
                $now = new DateTime();

                // If the current time is BEFORE the suspension expiry time, block login.
                if ($now < $suspension_expiry) {
                    
                    $expiry_date_formatted = $suspension_expiry->format('F d, Y \a\t h:i A');
                    
                    // 1. DENY LOGIN: Clear session (just in case)
                    if (session_status() === PHP_SESSION_ACTIVE) {
                           session_unset(); 
                    }
                    
                    // NEW: Log the attempted login which was blocked by suspension
                    log_user_action($pdo, $u['id'], 'LOGIN_BLOCKED', 'Login attempt blocked due to active suspension.', null, $_SERVER['REMOTE_ADDR'] ?? null);

                    // 2. Set the message to be displayed on the login page
                    $_SESSION['suspension_message'] = "
                        <div class='alert alert-danger p-4 text-center'>
                            <h4 class='alert-heading'>
                                <i class='bi bi-shield-fill-exclamation me-2'></i> Account Suspended!
                            </h4>
                            <p>Your access is blocked due to an account suspension.</p>
                            <p class='mb-0'>Access will be automatically restored on: <strong>{$expiry_date_formatted}</strong></p>
                            <hr>
                            <p class='mb-0'><small>Please contact support for assistance.</small></p>
                        </div>
                    ";

                    header("Location: /sweepxpress/login.php"); 
                    exit; // <<< THIS 'exit;' STOPS THE LOGIN PROCESS
                }
            }
            // =========================================================================
            //  !!! END SUSPENSION CHECK !!!
            // =========================================================================
            
            // NEW: Log the successful login action AFTER all checks pass
            log_user_action($pdo, $u['id'], 'LOGIN_SUCCESS', 'User logged in successfully.'); 

            // User is not suspended or suspension expired. Continue with normal login:
            if ($u['role'] === 'customer' || $u['role'] === 'business') {
                $_SESSION['user'] = $u;
                header("Location: /sweepxpress/index.php");
                exit;
            } else {
                // This is for other roles that don't go to index.php (like admin)
                // You should probably check for 'admin' and redirect to admin/dashboard here.
                $msg = "Invalid credentials.";
            }
            
        } else {
            // NEW: Log login failure for an existing user (if $u is set, meaning email was found)
            if ($u) {
                log_user_action($pdo, $u['id'], 'LOGIN_FAILURE', 'Failed password attempt.');
            }
            $msg = "Invalid email or password.";
        }
    } else {
        $msg = "Please enter email and password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SweepXpress</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* Custom Modern Styling */
        body {
            background: linear-gradient(135deg, #f0f4f8 0%, #d9e2ec 100%);
        }
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card-login {
            max-width: 350px; 
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1), 0 5px 10px rgba(0, 0, 0, 0.05);
            border-radius: 1rem;
            border: none;
            padding: 1.5rem; 
        }
        /* Custom social button styles with hover effects */
        .btn-google {
            background-color: #db4437;
            color: white;
            transition: background-color 0.2s ease;
        }
        .btn-google:hover {
            background-color: #c2382c;
            color: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        .btn-facebook {
            background-color: #4267b2;
            color: white;
            transition: background-color 0.2s ease;
        }
        .btn-facebook:hover {
            background-color: #365899;
            color: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        /* Separator style */
        .separator {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 1rem 0;
            color: #adb5bd;
        }
        .separator::before,
        .separator::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #dee2e6;
        }
        .separator:not(:empty)::before {
            margin-right: .5em;
        }
        .separator:not(:empty)::after {
            margin-left: .5em;
        }
        /* Style for the icon button inside the form-floating/input group */
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            z-index: 10;
            color: #6c757d; /* Muted color */
            font-size: 1.2rem;
        }
    </style>
</head>
<body class="login-container">
  <div class="container">
    <div class="row w-100 justify-content-center">
      <div class="col-12 col-sm-10 col-md-8 col-lg-6">
        <div class="card card-login mx-auto">
          <div class="text-center mb-3"> 
            <h1 class="h2 fw-bold text-primary">SweepXpress</h1>
            <p class="text-secondary">Log in to your account</p>
          </div>
          
          <?php if (isset($_SESSION['suspension_message'])): ?>
            <?= $_SESSION['suspension_message'] ?>
            <?php unset($_SESSION['suspension_message']); // Clear the message after display ?>
          <?php endif; ?>
          <?php if ($msg): ?>
            <div class="alert alert-danger mb-3" role="alert"><?= htmlspecialchars($msg) ?></div>
          <?php endif; ?>

          <form action="" method="POST" id="loginForm">
            
            <div class="form-floating mb-3">
              <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required>
              <label for="email">Email address</label>
            </div>
            
            <div class="form-floating mb-3 position-relative">
              <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
              <label for="password">Password</label>
              <span class="password-toggle" id="togglePassword">
                  <i class="bi bi-eye-slash" aria-hidden="true"></i>
              </span>
            </div>
            <div class="d-flex justify-content-end mb-3">
              <a href="/sweepxpress/forgot_password.php" class="text-sm text-decoration-none">Forgot Password?</a>
            </div>

            <button type="submit" id="loginButton" class="btn btn-primary btn-lg w-100 mb-3 fw-bold">
                Login
            </button>
          </form>

          <div class="separator">or</div>
          
          <div class="d-flex gap-3 mb-3">
            <a href="google_login.php" class="btn btn-google flex-fill d-flex align-items-center justify-content-center py-2 rounded-pill">
              <i class="bi bi-google me-2 fs-5"></i> <span class="d-none d-sm-inline">Google</span>
            </a>
            <a href="facebook_login.php" class="btn btn-facebook flex-fill d-flex align-items-center justify-content-center py-2 rounded-pill">
              <i class="bi bi-facebook me-2 fs-5"></i> <span class="d-none d-sm-inline">Facebook</span>
            </a>
          </div>

          <div class="text-center mt-3">
            Don't have an account? <a href="/sweepxpress/register.php" class="text-primary fw-bold">Sign Up</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Form submission loading state
        document.getElementById('loginForm').addEventListener('submit', function(event) {
            const button = document.getElementById('loginButton');
            button.disabled = true;
            button.innerHTML = `
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                Logging in...
            `;
        });
        
        // Show/Hide Password Logic
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
            // Toggle the type attribute
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Toggle the icon (bi-eye-slash <-> bi-eye)
            const icon = this.querySelector('i');
            icon.classList.toggle('bi-eye');
            icon.classList.toggle('bi-eye-slash');
        });
    });
  </script>
</body>
</html>