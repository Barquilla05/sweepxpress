<?php
session_start();
require_once __DIR__ . '/../config.php';

// Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: /sweepxpress/login.php");
    exit;
}

// Merge user data with defaults
$user = array_merge([
    'id' => '',
    'username' => '',
    'name' => '',
    'email' => '',
    'phone_number' => '',
    'gender' => '',
    'birth_date' => '',
    'profile_image' => '/sweepxpress/assets/default-avatar.png',
    'street_address' => '',
    'city' => '',
    'zip_code' => ''
], $_SESSION['user']);

$success = null;
$error = null;

// Determine which section to display (default is profile)
$current_section = 'profile-section';
if (isset($_POST['update_account']) && !empty($_POST['new_password'])) {
    $current_section = 'password-section';
}

// ✅ Handle form submit
if (isset($_POST['update_account'])) {
    $username = trim($_POST['username'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone_number'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $birth_date = $_POST['birth_date'] ?? '';
    
    $streetAddress = trim($_POST['street_address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $zipCode = trim($_POST['zip_code'] ?? '');
    
    $currentPassword = $_POST['current_password'] ?? ''; 
    $newPassword = $_POST['new_password'] ?? '';       
    
    $profileImage = $user['profile_image']; 

    // --- Image Upload Logic ---
    if (!empty($_FILES['profile_image']['name'])) {
        $uploadDir = __DIR__ . '/../uploads/profiles/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $originalFilename = basename($_FILES['profile_image']['name']);
        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];
        
        if ($_FILES['profile_image']['size'] > 1048576) {
            $error = "Image file is too large (max 1MB).";
        } elseif (in_array($extension, $allowed)) {
            $filename = time() . '_' . md5(uniqid()) . '.' . $extension;
            $targetFile = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetFile)) {
                $profileImage = '/sweepxpress/uploads/profiles/' . $filename;
            } else {
                $error = "Failed to upload image.";
            }
        } else {
            $error = "Only JPG, JPEG, and PNG files are allowed.";
        }
    }
    
    // --- Password Change Logic ---
    if (!$error && !empty($newPassword)) {
        if (empty($currentPassword)) {
            $error = "You must enter your Current Password to change your password.";
        } else {
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$user['id']]);
            $dbUser = $stmt->fetch();
            
            if (!$dbUser || !password_verify($currentPassword, $dbUser['password_hash'])) {
                $error = "The Current Password you entered is incorrect.";
            }
        }
    }
    
    // --- Database Update ---
    if (!$error) {
        $updates = [
            'username = ?',
            'name = ?',
            'phone_number = ?',
            'gender = ?',
            'birth_date = ?',
            'profile_image = ?',
            'street_address = ?',
            'city = ?',
            'zip_code = ?'
        ];
        $params = [
            $username, $name, $phone, $gender, $birth_date, $profileImage, 
            $streetAddress, $city, $zipCode 
        ];

        if (!empty($newPassword)) { 
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updates[] = 'password_hash = ?';
            $params[] = $hashedPassword;
        }

        $params[] = $user['id'];
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        
        try {
            $stmt->execute($params);

            $_SESSION['user'] = array_merge($user, [
                'username' => $username,
                'name' => $name,
                'phone_number' => $phone,
                'gender' => $gender,
                'birth_date' => $birth_date,
                'profile_image' => $profileImage,
                'street_address' => $streetAddress,
                'city' => $city,
                'zip_code' => $zipCode
            ]);

            $user = $_SESSION['user'];
            $success = "Your profile has been updated successfully!";
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                $error = "Username is already taken. Please choose another.";
            } else {
                $error = "An error occurred while updating your profile.";
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
body {
    background-color: #fafafa;
    font-family: 'Inter', sans-serif;
}
.container-profile {
    padding-top: 30px;
}
.profile-wrapper {
    max-width: 1300px;
    width: 100%;
    margin: 0 auto;
}

/* Sidebar */
.profile-nav { 
    background: #fff;
    border: 1px solid #eee;
    border-radius: 8px;
    padding: 1.5rem;
    height: 100%;
}
.profile-nav h6 {
    font-weight: 700;
    margin-bottom: 1rem;
    color: #222;
}
.profile-nav a {
    display: block;
    color: #555;
    text-decoration: none;
    margin: 0.4rem 0;
    transition: 0.2s;
    cursor: pointer;
}
.profile-nav a:hover,
.profile-nav a.active {
    color: #ee4d2d;
    font-weight: 600;
}

/* Profile Card */
.profile-card {
    background: #fff;
    border: 1px solid #eee;
    border-radius: 8px;
    padding: 2rem;
}
.profile-card h5 {
    font-weight: 600;
    color: #222;
}
.form-control {
    border-radius: 6px;
    border: 1px solid #ccc;
}
.btn-save {
    background-color: #ee4d2d;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 0.6rem 1.2rem;
    font-weight: 600;
    transition: 0.3s;
}
.btn-save:hover {
    background-color: #d74424;
}
.avatar-preview {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #eee;
}
.alert-success {
    background: #f0f9f4;
    color: #2e7d32;
    border-left: 4px solid #2e7d32;
    padding: 0.75rem 1rem;
    border-radius: 6px;
    margin-bottom: 1rem;
}
.alert-danger {
    background: #fef2f2;
    color: #cc3333;
    border-left: 4px solid #cc3333;
    padding: 0.75rem 1rem;
    border-radius: 6px;
    margin-bottom: 1rem;
}

/* ---------------------- RESPONSIVE SECTION ---------------------- */
@media (max-width: 992px) {
    .profile-wrapper {
        padding: 0 15px;
    }
    .profile-nav {
        margin-bottom: 20px;
        text-align: center;
    }
    .profile-nav .d-flex {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    .profile-nav img {
        margin-bottom: 10px;
    }
    .profile-nav h6 {
        text-align: center;
        margin-top: 10px;
    }
}

@media (max-width: 768px) {
    .profile-card {
        padding: 1.5rem;
    }
    .avatar-preview {
        width: 90px;
        height: 90px;
    }
    .btn-save {
        width: 100%;
        margin-top: 10px;
    }
}

@media (max-width: 576px) {
    .profile-card {
        padding: 1rem;
    }
    .form-label {
        font-size: 0.9rem;
    }
    .form-control {
        font-size: 0.9rem;
    }
}
</style>

<div class="container py-5 container-profile">
    <div class="profile-wrapper">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="profile-nav">
                    <div class="d-flex align-items-center mb-3">
                        <img src="<?= htmlspecialchars($user['profile_image']); ?>" class="avatar-preview me-2" alt="Profile">
                        <div>
                            <strong><?= htmlspecialchars($user['username']); ?></strong>
                            <div class="text-muted small"><?= htmlspecialchars($user['email']); ?></div>
                        </div>
                    </div>
                    <h6>My Account</h6>
                    <a href="#" class="active" data-target="profile-section">Profile</a>
                    <a href="#" data-target="banks-section">Banks & Cards</a>
                    <a href="#" data-target="addresses-section">Addresses</a>
                    <a href="#" data-target="password-section">Change Password</a>
                    <a href="#" data-target="privacy-section">Privacy Settings</a>
                    <a href="#" data-target="notifications-section">Notification Settings</a>
                    <hr>
                    <h6>My Purchase</h6>
                    <a href="#" data-target="purchase-notifications-section">Notifications</a>
                    <a href="#" data-target="vouchers-section">My Vouchers</a>
                    <a href="#" data-target="coins-section">My Sweep Coins</a>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="profile-card settings-section" id="profile-section">
                    <h5 class="mb-4 text-center">My Profile</h5>
                    <p class="text-muted mb-4 text-center">Manage and protect your account</p>

                    <?php if ($success): ?>
                        <div class="alert-success"><?= htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert-danger"><?= htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <form method="post" enctype="multipart/form-data">
                        <div class="row g-3">
                            <div class="col-md-12 text-center">
                                <img src="<?= htmlspecialchars($user['profile_image']); ?>" id="profilePreview" class="avatar-preview mb-3" alt="Profile Picture">
                                <input type="file" name="profile_image" id="profileImgInput" class="form-control" style="max-width:300px;margin:auto;">
                                <small class="text-muted">File size: max 1 MB (JPEG, PNG)</small>
                                <hr>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']); ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']); ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']); ?>" readonly>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="phone_number" class="form-control" value="<?= htmlspecialchars($user['phone_number'] ?? ''); ?>">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Street Address</label>
                                <input type="text" name="street_address" class="form-control" value="<?= htmlspecialchars($user['street_address'] ?? ''); ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">City / Municipality</label>
                                <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($user['city'] ?? ''); ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Zip Code</label>
                                <input type="text" name="zip_code" class="form-control" value="<?= htmlspecialchars($user['zip_code'] ?? ''); ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Gender</label><br>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="gender" value="Male" <?= ($user['gender'] ?? '') == 'Male' ? 'checked' : '' ?>>
                                    <label class="form-check-label">Male</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="gender" value="Female" <?= ($user['gender'] ?? '') == 'Female' ? 'checked' : '' ?>>
                                    <label class="form-check-label">Female</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="gender" value="Other" <?= ($user['gender'] ?? '') == 'Other' ? 'checked' : '' ?>>
                                    <label class="form-check-label">Other</label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" name="birth_date" class="form-control" value="<?= htmlspecialchars($user['birth_date'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <button type="submit" name="update_account" class="btn btn-save">Save Profile Changes</button>
                        </div>
                    </form>
                </div>
                
                <div class="profile-card settings-section" id="password-section" style="display:none;">
                    <h5 class="mb-4 text-center">Change Password</h5>
                    <p class="text-muted mb-4 text-center">Secure your account by changing your password regularly.</p>
                    
                    <form method="post">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control" placeholder="Enter current password">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control" placeholder="Enter new password">
                            </div>
                        </div>
                        <div class="text-center mt-4">
                            <button type="submit" name="update_account" class="btn btn-save">Save Password</button>
                        </div>
                    </form>
                </div>

                <div class="profile-card settings-section" id="banks-section" style="display:none;">
                    <h5 class="mb-3">Banks & Cards</h5>
                    <p class="text-muted">This is where you would manage your saved payment methods.</p>
                </div>

                <div class="profile-card settings-section" id="addresses-section" style="display:none;">
                    <h5 class="mb-3">Addresses</h5>
                    <p class="text-muted">This is where you would manage your shipping addresses.</p>
                </div>
                
                <div class="profile-card settings-section" id="privacy-section" style="display:none;">
                    <h5 class="mb-3">Privacy Settings</h5>
                    <p class="text-muted">Manage your data and privacy preferences here.</p>
                </div>

                <div class="profile-card settings-section" id="notifications-section" style="display:none;">
                    <h5 class="mb-3">Notification Settings</h5>
                    <p class="text-muted">Toggle your email and push notifications.</p>
                </div>

                <div class="profile-card settings-section" id="purchase-notifications-section" style="display:none;">
                    <h5 class="mb-3">My Purchase Notifications</h5>
                    <p class="text-muted">View updates about your orders.</p>
                </div>

                <div class="profile-card settings-section" id="vouchers-section" style="display:none;">
                    <h5 class="mb-3">My Vouchers</h5>
                    <p class="text-muted">View and manage your promotional vouchers.</p>
                </div>

                <div class="profile-card settings-section" id="coins-section" style="display:none;">
                    <h5 class="mb-3">My Sweep Coins</h5>
                    <p class="text-muted">Check your coin balance and usage history.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Image preview
document.getElementById("profileImgInput").addEventListener("change", function(event) {
    const img = document.getElementById("profilePreview");
    img.src = URL.createObjectURL(event.target.files[0]);
});

// Section switching
document.addEventListener('DOMContentLoaded', () => {
    const navLinks = document.querySelectorAll('.profile-nav a');
    const sections = document.querySelectorAll('.settings-section');

    const showSection = (targetId) => {
        sections.forEach(section => section.style.display = 'none');
        const targetSection = document.getElementById(targetId);
        if (targetSection) targetSection.style.display = 'block';
    };

    navLinks.forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            navLinks.forEach(l => l.classList.remove('active'));
            link.classList.add('active');
            showSection(link.getAttribute('data-target'));
        });
    });

    // Default visible section
    showSection('<?= $current_section ?>');
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
