<?php
// Start the session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include PDO database config
// NOTE: Adjust the path if necessary. Using /../ assumes config.php is one level up.
require_once __DIR__ . '/../config.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email && $password) {
        // Only look for the 'admin' user role
        // This is a crucial security step for separating admin access.
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Successful Admin Login
            $_SESSION['user'] = $user;

            // Redirect to the admin dashboard
            header("Location: /sweepxpress/admin/dashboard.php");
            exit;
        } else {
            // Generic security message (Invalid credentials)
            $msg = "Invalid admin credentials.";
        }
    } else {
        $msg = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | SweepXpress</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* Consistent Modern Background */
        body {
            /* Subtle, inviting gradient background consistent with public pages */
            background: linear-gradient(135deg, #f0f4f8 0%, #d9e2ec 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        /* Admin Card Styling */
        .login-card {
            /* MADE SMALLER: Reduced max-width from 400px to 350px */
            max-width: 350px;
            /* Enhanced, deeper shadow for a 'lifted' look */
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1), 0 5px 10px rgba(0, 0, 0, 0.05);
            border-radius: 1rem; /* Rounded corners */
            border: none;
            /* MADE SMALLER: Reduced padding from 2.5rem to 1.5rem */
            padding: 1.5rem;
        }
        /* Admin Button - Using a darker/secondary color for distinction */
        .btn-admin {
            background-color: #343a40; /* Dark gray/black */
            border-color: #343a40;
            color: white;
            font-weight: bold;
            transition: background-color 0.2s ease;
        }
        .btn-admin:hover {
            background-color: #23272b;
            border-color: #23272b;
            color: white;
        }
        .text-admin {
            color: #343a40 !important;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="text-center mb-4"> <i class="bi bi-shield-lock-fill fs-1 text-admin mb-2"></i>
        <h1 class="h3 fw-bold text-admin">Admin Portal</h1>
        <p class="text-muted small">SweepXpress Management Login</p>
    </div>
    
    <?php if (!empty($msg)): ?>
        <div class="alert alert-danger mb-3" role="alert"><?= htmlspecialchars($msg) ?></div> <?php endif; ?>

    <form method="POST" id="adminLoginForm">
        
        <div class="form-floating mb-3">
            <input type="email" name="email" class="form-control" id="adminEmail" required placeholder="Admin email">
            <label for="adminEmail">Email address</label>
        </div>
        
        <div class="form-floating mb-4">
            <input type="password" name="password" class="form-control" id="adminPassword" required placeholder="Password">
            <label for="adminPassword">Password</label>
        </div>
        
        <button type="submit" id="adminLoginButton" class="btn btn-admin btn-lg w-100">
            Access Dashboard
        </button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.getElementById('adminLoginForm').addEventListener('submit', function(event) {
        const button = document.getElementById('adminLoginButton');
        
        // Disable the button
        button.disabled = true;
        
        // Add the spinner HTML and change the text
        button.innerHTML = `
            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            Accessing...
        `;
        
        // The form will submit automatically, showing the spinner while PHP processes the request.
    });
</script>

</body>
</html>