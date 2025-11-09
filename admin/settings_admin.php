<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config.php';

if (!is_logged_in()) {
    header("Location: /sweepxpress/login.php");
    exit;
}
if (!is_admin()) {
    header("Location: /sweepxpress/index.php");
    exit;
}

$adminId = $_SESSION['user']['id'];

// Kunin current user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$adminId]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Initialize $success for display
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_account'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Default image path kung walang upload
    // 💡 Note: Use null coalescing for safety even here, though fetching from DB might make it safe.
    $profileImagePath = $admin['profile_image'] ?? null; 

    // Handle profile image upload
    if (!empty($_FILES['profile_image']['name'])) {
        $uploadDir = __DIR__ . '/../uploads/'; // General upload directory
        $profileUploadDir = $uploadDir . 'profiles/'; // Use a specific sub-directory if needed, or keep it general as per your original code
        
        // Ensure the directory exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Generate a unique filename
        $filename = "profile_" . $adminId . "_" . time() . "." . pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $targetFile = $uploadDir . $filename;

        // Ensure the uploaded file is an image before moving (basic check)
        if (getimagesize($_FILES['profile_image']['tmp_name']) !== false) {
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetFile)) {
                $profileImagePath = "/sweepxpress/uploads/" . $filename;
            }
        }
    }

    // --- Dynamic DB Update Logic ---
    $updates = ['name = ?', 'email = ?'];
    $params = [$name, $email];
    
    // Add image update if the path was changed (uploaded or set from DB default)
    if ($profileImagePath !== null) {
         $updates[] = 'profile_image = ?';
         $params[] = $profileImagePath;
    }
    
    // Handle Password
    if (!empty($password)) {
        // 🔑 Security: Always hash the password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT); // Use PASSWORD_DEFAULT instead of BCRYPT
        $updates[] = 'password_hash = ?';
        $params[] = $passwordHash;
    }

    $params[] = $adminId; // Add admin ID for the WHERE clause

    $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
    
    // Execute DB Update
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // --- Modal Trigger ---
    $success = "Account updated successfully!"; // Set flag to trigger the modal

    // Refresh data after update (using $admin)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    // 🔑 Update session
    $_SESSION['user']['name'] = $admin['name'];
    $_SESSION['user']['email'] = $admin['email'];
    $_SESSION['user']['profile_image'] = $admin['profile_image'] ?? null;
}

// Ensure the image path used for display is safe
$displayImagePath = htmlspecialchars($admin['profile_image'] ?? '/sweepxpress/assets/default-avatar.png');


require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <h1 class="mb-4"> Admin Settings</h1>

    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-primary text-white">👤 My Account</div>
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <div class="mb-3 text-center">
                    <img src="<?= $displayImagePath ?>" 
                            alt="Profile" class="rounded-circle" width="100" height="100">
                </div>

                <div class="mb-3">
                    <label class="form-label">Profile Image</label>
                    <input type="file" class="form-control" name="profile_image" accept="image/*">
                </div>

                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($admin['name']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($admin['email']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" class="form-control" name="password" placeholder="Leave blank to keep current">
                </div>

                <button type="submit" name="update_account" class="btn btn-success">Update Account</button>
            </form>
        </div>
    </div>
</div>

<?php 
    // Variables for the modal content
    $modal_title = "✅ Admin Profile Updated";
    $modal_message = "Your administrator account details have been successfully updated!";
    $modal_name = htmlspecialchars($admin['name'] ?? 'Admin');
    $modal_email = htmlspecialchars($admin['email'] ?? '');
?>

<?php if (!empty($success)): // Check if the PHP success message is set ?>

    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="successModalLabel"><?= $modal_title ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <p>Great job, <strong><?= $modal_name ?></strong>!</p>
                    <p><?= $modal_message ?></p>
                    <p class="small text-muted">New Email: *<?= $modal_email ?>*</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-success px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const successModalElement = document.getElementById('successModal');
            if (successModalElement) {
                // Initialize a new Bootstrap Modal object
                const successModal = new bootstrap.Modal(successModalElement);
                // Show the modal
                successModal.show();
            }
        });
    </script>

<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>