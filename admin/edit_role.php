<?php
// Include necessary files and establish database connection
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config.php';


// Check if the user is an admin
if (!is_admin()) {
    http_response_code(403);
    die("Access denied. Admin privileges required.");
}

// Check if the request is a POST request and contains the necessary data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id']) && isset($_POST['role'])) {
    $userId = $_POST['user_id'];
    $newRole = $_POST['role'];

    // Validate the new role against a list of allowed roles
    // --- MODIFICATION: Added 'business' to the array of valid roles. ---
    $validRoles = ['customer', 'admin', 'business'];
    
    if (!in_array($newRole, $validRoles)) {
        http_response_code(400);
        die("Invalid role specified.");
    }

    try {
        // Prepare the SQL statement to update the user's role
        $stmt = $pdo->prepare("UPDATE users SET role = :role WHERE id = :id");

        // Bind the parameters to the prepared statement
        $stmt->bindParam(':role', $newRole, PDO::PARAM_STR);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);

        // Execute the statement
        $stmt->execute();

        // Check if the update was successful (if any rows were affected)
        if ($stmt->rowCount() > 0) {
            echo "Role updated successfully!";
        } else {
            echo "No changes made. The user ID might not exist or the role is already the same.";
        }
    } catch (PDOException $e) {
        // Handle database-related errors
        http_response_code(500);
        die("Database error: " . $e->getMessage());
    }
} else {
    // Return a bad request error if the request method or data is invalid
    http_response_code(400);
    die("Invalid request. User ID or role not provided.");
}
?>