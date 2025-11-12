<?php
// /admin/suspend.php
// Suspends a user account for 30 days.

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config.php'; // Includes $pdo connection

if (!is_admin()) {
    header("Location: /sweepxpress/login.php");
    exit;
}

$userId = $_GET['user_id'] ?? null;

if (!$userId || !is_numeric($userId)) {
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'Invalid user ID provided.'];
    header("Location: customer_management.php");
    exit;
}

$suspensionDays = 30; 

try {
    $pdo->beginTransaction();

    // The core action: Use MySQL's DATE_ADD to calculate the expiry date
    $stmt = $pdo->prepare("
        UPDATE users 
        SET 
            suspended_until = DATE_ADD(NOW(), INTERVAL ? DAY)
        WHERE id = ?
    ");
    $stmt->execute([$suspensionDays, $userId]);
    
    // 2. Fetch user name and new expiry date for the success message
    $userStmt = $pdo->prepare("SELECT name, suspended_until FROM users WHERE id = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    $userName = htmlspecialchars($user['name'] ?? 'User');
    
    $expiry_date = new DateTime($user['suspended_until']);
    $expiry_formatted = $expiry_date->format('M d, Y h:i A');

    $pdo->commit();

    $_SESSION['message'] = [
        'type' => 'success',
        'text' => $userName . " has been successfully suspended. Access will be blocked until: " . $expiry_formatted . "."
    ];

} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['message'] = [
        'type' => 'danger',
        'text' => 'Database Error: Could not suspend user. ' . htmlspecialchars($e->getMessage())
    ];
}

// *** CORRECTED REDIRECTION ***
// Redirect back to your actual user detail page: user_actions.php
header("Location: user_actions.php?id=" . $userId); 
exit;