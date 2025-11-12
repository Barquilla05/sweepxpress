<?php
// /admin/unsuspend.php
// Lifts a user suspension by setting the 'suspended_until' field to NULL.

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config.php'; // Includes $pdo connection

if (!is_admin()) {
    header("Location: /sweepxpress/login.php");
    exit;
}

$userId = $_GET['user_id'] ?? null;

if (!$userId || !is_numeric($userId)) {
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'Invalid user ID provided for unsuspend action.'];
    header("Location: customer_management.php");
    exit;
}

try {
    // The core action: Setting suspended_until to NULL lifts the ban
    $stmt = $pdo->prepare("
        UPDATE users 
        SET 
            suspended_until = NULL
        WHERE id = ?
    ");
    
    $stmt->execute([$userId]);
    
    // 2. Fetch user name for the success message
    $userStmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    $userName = htmlspecialchars($user['name'] ?? 'User');

    $_SESSION['message'] = [
        'type' => 'success',
        'text' => $userName . " has been successfully unsuspended. The account is now fully active."
    ];

} catch (PDOException $e) {
    $_SESSION['message'] = [
        'type' => 'danger',
        'text' => 'Database Error: Could not unsuspend user. ' . htmlspecialchars($e->getMessage())
    ];
}

// *** CORRECTED REDIRECTION ***
// Redirect back to your actual user detail page: user_actions.php
header("Location: user_actions.php?id=" . $userId); 
exit;