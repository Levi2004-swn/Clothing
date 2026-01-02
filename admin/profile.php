<?php
require_once 'config.php';
require_admin_login();

$page_title = 'My Profile';
$success = '';
$error = '';

$admin_id = $_SESSION['admin_id'] ?? 0;
if ($admin_id <= 0) {
    header('Location: login.php');
    exit;
}

// Fetch current admin
$stmt = $conn->prepare('SELECT admin_id, username, email, password FROM admins WHERE admin_id = ?');
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

if (!$admin) {
    $error = 'Admin account not found';
}

// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update display name (username)
    if (isset($_POST['update_profile'])) {
        $username = trim($_POST['username'] ?? '');
        if ($username === '') {
            $error = 'Name cannot be empty';
        } else {
            $stmt = $conn->prepare('UPDATE admins SET username = ?, updated_at = NOW() WHERE admin_id = ?');
            $stmt->bind_param('si', $username, $admin_id);
            if ($stmt->execute()) {
                $_SESSION['admin_username'] = $username;
                $success = 'Profile updated successfully';
                $admin['username'] = $username;
            } else {
                $error = 'Failed to update profile name';
            }
        }
    }

    // Update password
    if (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($new_password === '' || $confirm_password === '') {
            $error = 'Please enter a new password and confirm it';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New password and confirmation do not match';
        } elseif (strlen($new_password) < 10) {
            $error = 'New password must be at least 10 characters';
        } else {
            // NOTE: Existing system appears to use plaintext passwords (from sample data). In production, hash passwords.
            if ($current_password !== $admin['password']) {
                $error = 'Current password is incorrect';
            } else {
                $stmt = $conn->prepare('UPDATE admins SET password = ?, updated_at = NOW() WHERE admin_id = ?');
                $stmt->bind_param('si', $new_password, $admin_id);
                if ($stmt->execute()) {
                    $success = 'Password updated successfully';
                    $admin['password'] = $new_password;
                } else {
                    $error = 'Failed to update password';
                }
            }
        }
    }

    // Update avatar
    if (isset($_POST['update_avatar']) && isset($_FILES['avatar']) && is_uploaded_file($_FILES['avatar']['tmp_name'])) {
        $file = $_FILES['avatar'];
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = mime_content_type($file['tmp_name']);
        if (!isset($allowed[$mime])) {
            $error = 'Invalid image type. Allowed: JPG, PNG, WEBP';
        } else {
            $ext = $allowed[$mime];
            $avatarDir = __DIR__ . '/assets/images/avatars';
            if (!is_dir($avatarDir)) {
                @mkdir($avatarDir, 0777, true);
            }
            // Remove old variants
            foreach (['jpg', 'jpeg', 'png', 'webp'] as $oldExt) {
                $old = $avatarDir . '/' . $admin_id . '.' . $oldExt;
                if (file_exists($old)) { @unlink($old); }
            }
            $target = $avatarDir . '/' . $admin_id . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $target)) {
                // Success; nothing to store in DB since header computes path by admin_id
                $success = 'Profile picture updated successfully';
            } else {
                $error = 'Failed to upload profile picture';
            }
        }
    }
}

// Determine avatar path for current admin (reused in multiple sections)
$avatarRel = 'assets/images/user_avatar_def.jpg';
$__avatarDir = __DIR__ . '/assets/images/avatars/';
foreach (['jpg','jpeg','png','webp'] as $__ext) {
    $__p = $__avatarDir . $admin_id . '.' . $__ext;
    if (file_exists($__p)) { $avatarRel = 'assets/images/avatars/' . $admin_id . '.' . $__ext . '?t=' . @filemtime($__p); break; }
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="admin-profile-page">
    <div class="admin-header" style="margin-bottom: 0;">
        <h1><i class="fas fa-user-circle"></i> My Profile</h1>
    </div>

    <!-- Profile hero -->
    <div class="profile-hero">
        <div class="hero-inner">
            <div class="hero-avatar">
            </div>
            <div class="hero-meta">
                <div class="hero-name"><?php echo htmlspecialchars($admin['username'] ?? ''); ?></div>
                <div class="hero-email"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($admin['email'] ?? ''); ?></div>
            </div>
        </div>
    </div>

<?php if ($success): ?>
<div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

    <div class="admin-grid-2" style="gap: 20px; margin-top: 20px;">
    <div class="admin-card">
        <h3><i class="fas fa-id-card-clip"></i> Profile Information</h3>
        <form method="post">
            <div class="form-group">
                <label><i class="fas fa-user"></i> Name</label>
                <div class="input-wrap with-icon">
                    <input type="text" name="username" value="<?php echo htmlspecialchars($admin['username'] ?? ''); ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email</label>
                <div class="input-wrap with-icon disabled">
                    <input type="email" value="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>" disabled>
                </div>
                <small>Contact the system admin to change the email.</small>
            </div>
            <div class="form-actions">
                <button type="submit" name="update_profile" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>

    <div class="admin-card">
        <h3><i class="fas fa-image"></i> Profile Picture</h3>
        <form method="post" enctype="multipart/form-data">
            <div class="form-group" style="display:flex; align-items:center; gap:15px; flex-wrap: wrap;">
                <label class="file-input-label">
                    <i class="fas fa-upload"></i>
                    <span>Choose new image</span>
                    <input type="file" name="avatar" accept="image/*" required>
                </label>
                <div class="new-preview-wrap" aria-hidden="true" style="display:none;">
                    <img id="newAvatarPreview" alt="New preview" class="avatar-preview new">
                    <div class="new-preview-hint">Preview</div>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" name="update_avatar" class="btn btn-secondary"><i class="fas fa-cloud-arrow-up"></i> Upload</button>
            </div>
        </form>
    </div>
</div>

<div class="admin-card" style="margin-top: 20px;">
    <h3><i class="fas fa-key"></i> Change Password</h3>
    <form method="post">
        <div class="form-group">
            <label><i class="fas fa-lock"></i> Current Password</label>
            <div class="input-wrap with-icon">
                <input type="password" name="current_password" required>
            </div>
        </div>
        <div class="form-group">
            <label><i class="fas fa-shield-alt"></i> New Password (minimum 10 characters)</label>
            <div class="input-wrap with-icon">
                <input type="password" name="new_password" required>
            </div>
        </div>
        <div class="form-group">
            <label><i class="fas fa-check-circle"></i> Confirm New Password</label>
            <div class="input-wrap with-icon">
                <input type="password" name="confirm_password" required>
            </div>
            <div class="pwd-strength" data-placement="below-confirm"></div>
        </div>
        <div class="form-actions">
            <button type="submit" name="update_password" class="btn btn-primary"><i class="fas fa-key"></i> Update Password</button>
        </div>
    </form>
</div>

<style>
/* Scoped styles for profile page */
.admin-profile-page .profile-hero { 
    margin: 10px 0 20px; 
    background: linear-gradient(135deg, #1e293b, #0ea5e9);
    border-radius: 12px; 
    padding: 22px; 
    color: #e2e8f0; 
    box-shadow: 0 6px 18px rgba(2, 6, 23, 0.25);
}
.admin-profile-page .profile-hero .hero-inner { display:flex; align-items:center; gap:16px; }
.admin-profile-page .profile-hero .hero-avatar img { width:72px; height:72px; border-radius:50%; object-fit:cover; border:3px solid rgba(255,255,255,0.25); box-shadow: 0 4px 14px rgba(0,0,0,0.25); }
.admin-profile-page .profile-hero .hero-meta .hero-name { font-size:20px; font-weight:700; letter-spacing: 0.2px; color:#fff; }
.admin-profile-page .profile-hero .hero-meta .hero-email { font-size:13px; opacity:0.9; margin-top:4px; }
.admin-profile-page .profile-hero .hero-email i { margin-right:6px; }

.admin-profile-page .admin-card h3 { display:flex; align-items:center; gap:10px; margin-bottom: 14px; }
.admin-profile-page .admin-card h3 i { color:#0ea5e9; }

.admin-profile-page .input-wrap { position: relative; }
.admin-profile-page .input-wrap.with-icon input { padding-left: 38px; }
.admin-profile-page .input-wrap i { position:absolute; top:50%; left:12px; transform: translateY(-50%); color:#64748b; }
.admin-profile-page .input-wrap.disabled { opacity: 0.85; }

.admin-profile-page .avatar-preview { width:64px; height:64px; border-radius:50%; object-fit:cover; box-shadow: 0 2px 8px rgba(0,0,0,0.12); border: 2px solid #e2e8f0; }
.admin-profile-page .file-input-label { display:inline-flex; align-items:center; gap:10px; background:#f1f5f9; border:1px dashed #cbd5e1; color:#0f172a; padding:10px 14px; border-radius:8px; cursor:pointer; }
.admin-profile-page .file-input-label input[type=file] { display:none; }
.admin-profile-page .file-input-label:hover { background:#e2e8f0; }

/* Enlarge inputs to blend with settings page feel */
.admin-profile-page .admin-card input[type="text"],
.admin-profile-page .admin-card input[type="email"],
.admin-profile-page .admin-card input[type="password"] {
    height: 48px;
    padding: 12px 14px;
    font-size: 15.5px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    background: #fff;
    transition: box-shadow .15s ease, border-color .15s ease;
}
.admin-profile-page .admin-card input[type="email"][disabled] { background: #f8fafc; color: #64748b; }
.admin-profile-page .admin-card input:focus {
    outline: none;
    border-color: #0ea5e9;
    box-shadow: 0 0 0 4px rgba(14,165,233,0.15);
}
.admin-profile-page .input-wrap.with-icon input { height: 48px; }
.admin-profile-page .input-wrap i { font-size: 14px; }

/* New image live preview beside the button */
.admin-profile-page .new-preview-wrap { display: inline-flex; align-items: center; gap:10px; background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:8px 10px; }
.admin-profile-page .avatar-preview.new { width: 72px; height: 72px; border-radius: 50%; border: 2px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.08); object-fit: cover; }
.admin-profile-page .new-preview-hint { font-size: 12px; color:#64748b; }

/* Ensure comfortable inner spacing so content isn't close to card edges */
.admin-profile-page .admin-card { padding: 24px; }
.admin-profile-page .admin-card .form-group { margin-left: 2px; margin-right: 2px; }
.admin-profile-page .admin-card .form-actions { margin-top: 12px; }
</style>

<script>
// Live preview for newly chosen avatar image (shown next to the button)
(function(){
    var fileInput = document.querySelector('.admin-profile-page input[name="avatar"]');
    if (!fileInput) return;
    var wrap = document.querySelector('.admin-profile-page .new-preview-wrap');
    var img = document.getElementById('newAvatarPreview');
    fileInput.addEventListener('change', function(){
        var f = fileInput.files && fileInput.files[0];
        if (!f) { if (wrap) wrap.style.display = 'none'; return; }
        var reader = new FileReader();
        reader.onload = function(e){
            if (img) { img.src = e.target.result; }
            if (wrap) { wrap.style.display = 'inline-flex'; wrap.setAttribute('aria-hidden', 'false'); }
        };
        reader.readAsDataURL(f);
    });
})();
</script>

</div>

<?php include 'includes/footer.php'; ?>
