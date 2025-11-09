<?php
require_once __DIR__ . '/../config.php';

// ✅ Redirect kung hindi logged in
if (!isset($_SESSION['user'])) {
    header("Location: /sweepxpress/login.php");
    exit;
}

$user = $_SESSION['user'];

// 🎯 FIX for "Undefined array key profile_image" warning.
// Ensure the 'profile_image' key exists in the $user array, setting it to null if missing from the session.
$user['profile_image'] = $user['profile_image'] ?? null;

// Initialize $success for display
$success = null;

// ✅ Handle Profile Update
if (isset($_POST['update_account'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password']; // New password input

    // Use current image as default
    $profileImage = $user['profile_image']; 
    $newProfileImageUploaded = false; 

    // ✅ Handle profile image upload
    if (!empty($_FILES['profile_image']['name'])) {
        $uploadDir = __DIR__ . '/../uploads/profiles/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        // Basic sanitation and unique filename generation
        $originalFilename = basename($_FILES['profile_image']['name']);
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $filename = time() . '_' . md5(uniqid()) . '.' . $extension; // Added uniqid for extra uniqueness
        $targetFile = $uploadDir . $filename;

        // Check if file is an actual image and move it
        $check = getimagesize($_FILES['profile_image']['tmp_name']);
        if ($check !== false) {
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetFile)) {
                $profileImage = '/sweepxpress/uploads/profiles/' . $filename;
                $newProfileImageUploaded = true;
            } else {
                 // You might want to add error handling here for file move failure
            }
        } else {
             // You might want to add error handling here if the file is not an image
        }
    }

    // Prepare update query parts (dynamic update)
    $updates = ['name = ?', 'email = ?'];
    $params = [$name, $email];

    // Add profile_image update if a new image was uploaded
    if ($newProfileImageUploaded) {
        $updates[] = 'profile_image = ?';
        $params[] = $profileImage;
    }
    
    // Add password update if a new password was provided
    if (!empty($password)) {
        // ⚠️ SECURITY: Hashing the new password before storing it 
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $updates[] = 'password_hash = ?'; 
        $params[] = $hashedPassword;
    }

    $params[] = $user['id']; // Add user ID for the WHERE clause

    // Combine updates into a single query
    // 💡 FIX for Fatal error: Unknown column 'profile_image' (It's now conditionally included)
    $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
    
    // ✅ Execute DB update
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // ✅ Update session with new values
    $_SESSION['user']['name'] = $name;
    $_SESSION['user']['email'] = $email;
    
    // Update session image if changed
    if ($newProfileImageUploaded) {
        $_SESSION['user']['profile_image'] = $profileImage;
    }
    
    $user = $_SESSION['user']; // refresh the $user array
    $success = "Account updated successfully!"; // Set flag to trigger modal
}

// Ensure the image path used for display is safe (uses default image if $user['profile_image'] is null)
$displayImagePath = htmlspecialchars($user['profile_image'] ?? '/sweepxpress/assets/default-avatar.png');


require_once __DIR__ . '/../includes/header.php';
?>

<div class="container my-4">
    <h1 class="mb-4"><i class="bi bi-person-circle"></i> My Settings</h1>

    <div class="card mb-4 shadow-sm">
        <div class="card-header">
            <h2 class="h5 mb-0"><i class="bi bi-person-lines-fill"></i> My Account</h2>
        </div>
        <div class="card-body">

            <div class="text-center mb-3">
                <img src="<?php echo $displayImagePath; ?>" 
                    alt="Profile" class="rounded-circle" width="100" height="100">
            </div>

            <form method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Profile Image</label>
                    <input type="file" name="profile_image" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" 
                            value="<?php echo htmlspecialchars($user['name']); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" 
                            value="<?php echo htmlspecialchars($user['email']); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" class="form-control" 
                            placeholder="Leave blank to keep current">
                </div>
                <button type="submit" name="update_account" class="btn btn-primary">
                    Update Account
                </button>
            </form>
        </div>
    </div>
</div>

<?php 
    // Variables for the modal content
    $modal_title = "✅ Profile Updated";
    $modal_message = "Your account details have been successfully updated!";
    $modal_name = htmlspecialchars($user['name'] ?? '');
    $modal_email = htmlspecialchars($user['email'] ?? '');
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
        // Use an immediately invoked function to ensure the code runs after DOM is ready
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