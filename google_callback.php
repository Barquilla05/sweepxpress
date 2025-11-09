<?php
require_once 'config.php';

use League\OAuth2\Client\Provider\Google;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$provider = new Google(options: [
    'clientId'      => GOOGLE_CLIENT_ID,
    'clientSecret'  => GOOGLE_CLIENT_SECRET,
    'redirectUri'   => GOOGLE_REDIRECT_URI,
]);

if (!isset($_GET['code'])) {
    // If we don't have an authorization code, get one.
    $authUrl = $provider->getAuthorizationUrl([
        'scope' => ['profile', 'email'],
    ]);
    header('Location: ' . $authUrl);
    exit;
} else {
    try {
        // Try to get an access token using the authorization code.
        $token = $provider->getAccessToken('authorization_code', [
            'code' => $_GET['code']
        ]);

        $user = $provider->getResourceOwner($token);
        $userData = $user->toArray();

        $email      = $userData['email'] ?? null;
        $google_id  = $userData['id'] ?? $userData['sub'] ?? null;
        $name       = $userData['name'] ?? '';

        if (!$google_id && !$email) {
            die('Google did not return a valid user ID or email.');
        }

        // Check if user already exists in your database
        // Assuming $pdo is available from 'config.php'
        $stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = ? OR email = ?");
        $stmt->execute([$google_id, $email]);
        $existingUser = $stmt->fetch();

        $redirectUrl = "/sweepxpress/index.php"; // Default redirect for regular users

        if ($existingUser) {
            $_SESSION['user'] = $existingUser;
            // Check if the existing user is an admin
            if (isset($existingUser['role']) && $existingUser['role'] === 'admin') {
                $redirectUrl = "/sweepxpress/admin/dashboard.php";
            }
        } else {
            // New user, insert into database with default 'user' role
            $role = 'user';
            $stmt = $pdo->prepare("INSERT INTO users (name, email, google_id, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $google_id, $role]);
            $newUserId = $pdo->lastInsertId();

            $_SESSION['user'] = [
                'id'        => $newUserId,
                'name'      => $name,
                'email'     => $email,
                'google_id' => $google_id,
                'role'      => $role
            ];
            // No need to check for admin role here as new users default to 'user'
        }

        // Redirect based on the determined URL
        header("Location: " . $redirectUrl);
        exit;
    } catch (Exception $e) {
        die('Failed to authenticate with Google: ' . $e->getMessage());
    }
}