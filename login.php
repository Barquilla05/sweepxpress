<?php
// Enable error display (good for development, but consider disabling in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include DB config
// NOTE: Make sure 'config.php' is correctly configured for $pdo
require_once __DIR__ . '/config.php';

// Start session if not active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$msg = '';

// Handle Login Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ... (PHP login logic remains the same) ...
    $email = trim($_POST['email'] ?? '');
    $pass = trim($_POST['password'] ?? '');

    if ($email && $pass) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($u && password_verify($pass, $u['password_hash'])) {
            
            if ($u['role'] === 'customer' || $u['role'] === 'business') {
                $_SESSION['user'] = $u;
                header("Location: /sweepxpress/index.php");
                exit;
            } else {
                $msg = "Invalid credentials.";
            }
            
        } else {
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
          
          <?php if ($msg): ?>
            <div class="alert alert-danger mb-3" role="alert"><?= htmlspecialchars($msg) ?></div>
          <?php endif; ?>

          <form action="" method="POST" id="loginForm">
            
            <div class="form-floating mb-3">
              <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required>
              <label for="email">Email address</label>
            </div>
            
            <div class="form-floating mb-3">
              <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
              <label for="password">Password</label>
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
    document.getElementById('loginForm').addEventListener('submit', function(event) {
        // Prevent default form submission initially
        // event.preventDefault(); 
        
        const button = document.getElementById('loginButton');
        
        // 1. Disable the button
        button.disabled = true;
        
        // 2. Add the spinner HTML and change the text
        button.innerHTML = `
            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            Logging in...
        `;
        
        // 3. Since we didn't preventDefault() in the final version, the form will submit normally after the button is modified.
        // If you had complex JS validation, you would use event.preventDefault() and then manually submit using form.submit() here.
    });

    // NOTE: This check ensures that if the server returns an error ($msg is shown), 
    // the button state is reset when the page reloads.
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        // Assuming your PHP logic doesn't clear the error, the page reload is the key indicator.
        // You might consider adding a URL parameter (e.g., ?error=1) on failure to handle this more gracefully.
        
        // For simplicity, if the error message is visible, the button will be in its default state
        // because the page was reloaded by the server and we didn't proceed to a new page.
    });

  </script>
</body>
</html>