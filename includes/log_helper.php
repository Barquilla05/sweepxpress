<?php
// /includes/log_helper.php
// Requires $pdo connection to be available in the calling script.

/**
 * Logs an action performed by or on a user.
 *
 * @param PDO $pdo The database connection object.
 * @param int $userId The ID of the user the action is about (the subject).
 * @param string $actionType A constant/string defining the action (e.g., 'LOGIN_SUCCESS').
 * @param string $description Detailed text describing the log event.
 * @param int|null $actionById The ID of the user who performed the action (e.g., admin). Null if the subject performed it.
 * @param string|null $ip The IP address associated with the action.
 * @return bool True on success, false on failure.
 */
function log_user_action($pdo, $userId, $actionType, $description, $actionById = null, $ip = null) {
    
    // Fallback to get IP from server variables
    if ($ip === null) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_logs 
            (user_id, action_by_id, action_type, description, ip_address) 
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $userId, 
            $actionById, 
            $actionType, 
            $description, 
            $ip
        ]);
    } catch (PDOException $e) {
        // In a real application, you might log this error to a file instead of silencing it.
        // For development: echo "Logging error: " . $e->getMessage();
        return false;
    }
}
?>