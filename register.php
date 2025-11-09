<?php
// Enable error display (good for development, but consider disabling in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // New fields: first_name, last_name, and role
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    // --- New: Capture the selected role. Default to 'customer' if not set for safety.
    $role = trim($_POST['role'] ?? 'customer');

    // Combine names for storage in the existing 'name' column
    $name = trim($first_name . ' ' . $last_name);
    
    // Validate the role against allowed values
    $allowed_roles = ['customer', 'business'];
    if (!in_array($role, $allowed_roles)) {
        // If someone tries to submit an invalid role, default it to customer
        $role = 'customer';
    }


    // Check that all required fields are present
    if ($first_name && $last_name && $email && $pass) {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $msg = "Email already registered.";
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            
            // --- MODIFICATION: Use the captured $role variable in the INSERT statement
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hash, $role]);

            // Automatically log in the user upon successful registration
            $_SESSION['user'] = [
                'id' => $pdo->lastInsertId(),
                'name' => $name, // Stored as "First Last"
                'email' => $email,
                'role' => $role // Store the new role in the session
            ];

            header("Location: /sweepxpress/index.php");
            exit;
        }
    } else {
        $msg = "All fields are required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up - SweepXpress</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    /* Custom Modern Styling */
    body {
        /* Subtle, inviting gradient background */
        background: linear-gradient(135deg, #f0f4f8 0%, #d9e2ec 100%);
    }
    .login-container {
        /* Center vertically and horizontally */
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .card-login {
        /* MADE SMALLER AGAIN: Reduced max-width from 400px to 350px */
        max-width: 350px; 
        /* Enhanced, deeper shadow for a 'lifted' look */
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1), 0 5px 10px rgba(0, 0, 0, 0.05);
        border-radius: 1rem; /* Rounded corners */
        border: none;
        /* MADE SMALLER AGAIN: Reduced padding from 2rem to 1.5rem */
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
        /* MADE SMALLER: Reduced margin from 1rem 0 to 0.75rem 0 */
        margin: 0.75rem 0;
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
          <div class="text-center mb-3"> <h1 class="h2 fw-bold text-primary">Sign Up</h1>
            <p class="text-secondary">Create your SweepXpress account</p>
          </div>
          
          <?php if ($msg): ?>
            <div class="alert alert-danger mb-3" role="alert"><?= htmlspecialchars($msg) ?></div> <?php endif; ?>

          <form action="" method="POST">
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="form-floating mb-3 mb-md-0">
                        <input type="text" class="form-control" id="first_name" name="first_name" placeholder="First Name" required>
                        <label for="first_name">First Name</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Last Name" required>
                        <label for="last_name">Last Name</label>
                    </div>
                </div>
            </div>
            
            <div class="form-floating mb-3">
              <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required>
              <label for="email">Email address</label>
            </div>
            
            <div class="form-floating mb-3"> <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
              <label for="password">Password</label>
            </div>

            <div class="mb-3"> <label class="form-label fw-bold text-primary mb-2">I am signing up as...</label>
              <div class="d-flex justify-content-between gap-3">
                  <div class="form-check form-check-inline flex-fill border p-2 rounded-3">
                      <input class="form-check-input" type="radio" name="role" id="roleCustomer" value="customer" checked>
                      <label class="form-check-label fw-bold" for="roleCustomer">
                          <i class="bi bi-person-fill me-1 text-primary"></i> Customer
                      </label>
                      <p class="small text-muted mb-0 mt-1">Book services for my home.</p>
                  </div>
                  <div class="form-check form-check-inline flex-fill border p-2 rounded-3">
                      <input class="form-check-input" type="radio" name="role" id="roleBusiness" value="business">
                      <label class="form-check-label fw-bold" for="roleBusiness">
                          <i class="bi bi-briefcase-fill me-1 text-primary"></i> Business
                      </label>
                      <p class="small text-muted mb-0 mt-1">Provide services to others.</p>
                  </div>
              </div>
            </div>
            
            <button type="submit" class="btn btn-success btn-lg w-100 mb-3 fw-bold">Create Account</button> </form>

          <div class="separator">or sign up with</div>

          <div class="d-flex gap-3 mb-3"> <a href="google_login.php" class="btn btn-google flex-fill d-flex align-items-center justify-content-center py-2 rounded-pill">
              <i class="bi bi-google me-2 fs-5"></i> <span class="d-none d-sm-inline">Google</span>
            </a>
            <a href="facebook_login.php" class="btn btn-facebook flex-fill d-flex align-items-center justify-content-center py-2 rounded-pill">
              <i class="bi bi-facebook me-2 fs-5"></i> <span class="d-none d-sm-inline">Facebook</span>
            </a>
          </div>

          <div class="text-center mt-3">
            <a href="/sweepxpress/login.php" class="text-primary">Already have an account? <strong>Login</strong></a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>