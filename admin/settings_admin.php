<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!is_logged_in()) {
    header("Location: /sweepxpress/login.php");
    exit;
}

if (!is_admin()) {
    header("Location: /sweepxpress/index.php");
    exit;
}

// ✅ Get Admin ID
$adminId = $_SESSION['user']['id'] ?? null;
if (!$adminId) { header("Location: /sweepxpress/login.php"); exit; }

// ✅ Fetch Admin Data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$adminId]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) { die("Admin record not found."); }

// ✅ Update Profile (username, email, phone, image)
if (isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    $profile_image = $admin['profile_image'];

    if (!empty($_FILES['profile_image']['name'])) {
        $uploadDir = __DIR__ . '/../uploads/admin/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $newFile = time() . '_' . basename($_FILES['profile_image']['name']);
        $uploadFile = $uploadDir . $newFile;

        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadFile)) {
            $profile_image = "/sweepxpress/uploads/admin/" . $newFile;
        }
    }

    // ✅ Final Update Query
    $sql = "UPDATE users SET name = ?, email = ?, phone = ?, profile_image = ? WHERE id = ?";
    $params = [$name, $email, $phone, $profile_image, $adminId];

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // ✅ Refresh session after update
    $newData = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $newData->execute([$adminId]);
    $_SESSION['user'] = $newData->fetch(PDO::FETCH_ASSOC);

    echo "<script>window.location.href='settings_admin.php?tab=general';</script>";
    exit;
}


// ✅ Change Password
if (isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $newpass = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (!password_verify($current, $admin['password'])) {
        echo "<script>alert('Incorrect current password.');</script>";
    } elseif ($newpass !== $confirm) {
        echo "<script>alert('Passwords do not match.');</script>";
    } else {
        $hash = password_hash($newpass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->execute([$hash, $adminId]);
        echo "<script>alert('Password updated successfully!');location.href='settings_admin.php?tab=general';</script>";
    }
}

$displayImagePath = htmlspecialchars($admin['profile_image'] ?? '/sweepxpress/assets/default-avatar.png');
$tab = $_GET['tab'] ?? 'general';
?>

<style>
.settings-wrapper{display:flex;min-height:90vh;font-family:Inter,sans-serif;}
.settings-sidebar{width:240px;background:#fafafa;border-right:1px solid #ddd;padding:25px 15px;}
.settings-sidebar h2{font-size:18px;font-weight:600;margin-bottom:15px;}
.settings-sidebar a{display:block;padding:12px 15px;margin-bottom:6px;color:#222;text-decoration:none;border-radius:7px;transition:.2s;}
.settings-sidebar a:hover,.settings-sidebar a.active{background:#e7f0ff;}
.settings-content{flex:1;padding:40px 45px;}
.profile-section{display:flex;align-items:center;gap:18px;margin-bottom:35px;}
.profile-section img{width:95px;height:95px;object-fit:cover;border-radius:50%;border:2px solid #ddd;}
.btn-small,.edit-btn{font-size:13px;border:1px solid #aaa;padding:6px 10px;border-radius:6px;background:#fff;cursor:pointer;}
.btn-small:hover,.edit-btn:hover{background:#eee;}
.info-row{padding:18px 0;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;}
.label{font-size:13px;color:#777;}
.value{font-size:15px;font-weight:500;}
.modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:#00000060;justify-content:center;align-items:center;}
.modal-content{background:white;padding:20px;width:350px;border-radius:10px;}
.modal-content input{width:100%;padding:8px;margin-bottom:10px;}
.btn-cancel{background:#ddd;border:none;padding:6px 12px;border-radius:6px;cursor:pointer;}
</style>

<div class="settings-wrapper">
    <div class="settings-sidebar">
        <h2>Settings</h2>
        <a href="?tab=general" class="<?= $tab=='general'?'active':'' ?>">General</a>
    </div>

    <div class="settings-content">

    <?php if($tab=='general'): ?>
    <div class="profile-section">
        <img src="<?= $displayImagePath ?>">
        <button class="btn-small" onclick="openProfileModal()">Change Profile</button>
    </div>

    <div class="info-row"><div><div class="label">Username</div><div class="value"><?= htmlspecialchars($admin['name']) ?></div></div><button class="edit-btn" onclick="openProfileModal()">Edit</button></div>
    <div class="info-row"><div><div class="label">Email</div><div class="value"><?= htmlspecialchars($admin['email']) ?></div></div><button class="edit-btn" onclick="openProfileModal()">Edit</button></div>
    <div class="info-row"><div><div class="label">Phone</div><div class="value"><?= htmlspecialchars($admin['phone'] ?? 'No number saved') ?></div></div><button class="edit-btn" onclick="openProfileModal()">Edit</button></div>
    <div class="info-row"><div><div class="label">Password</div><div class="value">********</div></div><button class="edit-btn" onclick="openPasswordModal()">Change</button></div>
    <?php endif; ?>

    </div>
</div>

<!-- Profile Modal -->
<div id="profileModal" class="modal">
  <div class="modal-content">
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="update_profile">
        <h3>Edit Profile</h3>

        <input type="text" name="name" value="<?= htmlspecialchars($admin['name']) ?>" placeholder="Username">
        <input type="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" placeholder="Email">
        <input type="text" name="phone" value="<?= htmlspecialchars($admin['phone'] ?? '') ?>" placeholder="Phone">
        <input type="file" name="profile_image">

        <button type="submit">Save</button>
        <button type="button" class="btn-cancel" onclick="closeProfileModal()">Cancel</button>
    </form>
  </div>
</div>

<!-- Password Modal -->
<div id="passwordModal" class="modal">
  <div class="modal-content">
    <form method="POST">
        <input type="hidden" name="change_password">
        <h3>Change Password</h3>

        <input type="password" name="current_password" placeholder="Current Password" required>
        <input type="password" name="new_password" placeholder="New Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>

        <button type="submit">Update Password</button>
        <button type="button" class="btn-cancel" onclick="closePasswordModal()">Cancel</button>
    </form>
  </div>
</div>

<script>
function openProfileModal(){ document.getElementById('profileModal').style.display='flex'; }
function closeProfileModal(){ document.getElementById('profileModal').style.display='none'; }
function openPasswordModal(){ document.getElementById('passwordModal').style.display='flex'; }
function closePasswordModal(){ document.getElementById('passwordModal').style.display='none'; }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
