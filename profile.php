<?php
require_once 'config.php';

// Allow profile page to load in logged-out state. If the visitor is not logged in,
// show a prompt to login/register rather than redirecting — this matches typical
// e‑commerce behavior where pages can be opened directly.
$is_logged = is_logged_in();
$page_title = "My Profile - " . SITE_NAME;
$error = '';
$success = '';

// Get user data only if logged in; otherwise provide safe defaults to avoid warnings
if ($is_logged) {
    $user_id = get_user_id();
    // Get user data
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
} else {
    $user = [
        'first_name' => '',
        'last_name' => '',
        'email' => '',
        'phone' => '',
        'date_of_birth' => '',
        'gender' => '',
        'loyalty_points' => 0,
        'wallet_balance' => 0,
        'password' => ''
    ];
}

// Handle profile update — only when logged in
if ($is_logged && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $first_name = clean_input($_POST['first_name']);
    $last_name = clean_input($_POST['last_name']);
    $phone = clean_input($_POST['phone']);
    $date_of_birth = clean_input($_POST['date_of_birth']);
    $gender = clean_input($_POST['gender']);
    
    if (empty($first_name) || empty($last_name)) {
        $error = "Name fields are required";
    } else {
        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ?, date_of_birth = ?, gender = ? WHERE user_id = ?");
        $stmt->bind_param("sssssi", $first_name, $last_name, $phone, $date_of_birth, $gender, $user_id);
        
        if ($stmt->execute()) {
            $success = "Profile updated successfully!";
            $_SESSION['user_name'] = $first_name . ' ' . $last_name;
            // Refresh user data
            $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
        } else {
            $error = "Failed to update profile";
        }
    }
} elseif (!$is_logged && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    // Prevent unauthorized profile updates
    $error = 'Please log in to update your profile.';
}

// Handle password change — only when logged in
if ($is_logged && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password)) {
        $error = "All password fields are required";
    } elseif (strlen($new_password) < 10) {
        $error = "New password must be at least 10 characters";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match";
    } elseif (!password_verify($current_password, $user['password'])) {
        $error = "Current password is incorrect";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($stmt->execute()) {
            $success = "Password changed successfully!";
        } else {
            $error = "Failed to change password";
        }
    }
} elseif (!$is_logged && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $error = 'Please log in to change your password.';
}

include 'header.php';
?>

<div class="container" style="margin-top: 20px;">
    <h2 style="margin-bottom: 30px;">My Profile</h2>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 250px 1fr; gap: 30px;">
        <!-- Sidebar -->
        <div>
            <?php if ($is_logged): ?>
            <div class="card">
                <div style="text-align: center; padding: 20px; border-bottom: 1px solid #e5e5e5;">
                    <div style="width: 80px; height: 80px; background: #f53d2d; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; color: white; font-size: 32px; font-weight: 600;">
                        <?php echo strtoupper(substr($user['first_name'] ?? '', 0, 1)); ?>
                    </div>
                    <div style="font-weight: 600; margin-bottom: 5px;">
                        <?php echo htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'My Account'); ?>
                    </div>
                    <div style="font-size: 13px; color: #666;">
                        <?php echo htmlspecialchars($user['email'] ?? ''); ?>
                    </div>
                </div>
                <div style="padding: 15px 0;">
                    <a href="#profile" class="profile-menu-item active">
                        <i class="fas fa-user"></i> Profile Information
                    </a>
                    <a href="#password" class="profile-menu-item">
                        <i class="fas fa-lock"></i> Change Password
                    </a>
                    <a href="<?php echo SITE_URL; ?>/addresses.php" class="profile-menu-item">
                        <i class="fas fa-map-marker-alt"></i> My Addresses
                    </a>
                    <a href="<?php echo SITE_URL; ?>/orders.php" class="profile-menu-item">
                        <i class="fas fa-shopping-bag"></i> My Orders
                    </a>
                    <a href="<?php echo SITE_URL; ?>/wishlist.php" class="profile-menu-item">
                        <i class="fas fa-heart"></i> My Wishlist
                    </a>
                </div>
            </div>
            
            <div class="card" style="margin-top: 15px; text-align: center;">
                <div style="font-size: 14px; color: #666; margin-bottom: 10px;">Loyalty Points</div>
                <div style="font-size: 32px; font-weight: 700; color: #f53d2d; margin-bottom: 10px;">
                    <?php echo number_format($user['loyalty_points'] ?? 0); ?>
                </div>
                <div style="font-size: 13px; color: #666; margin-bottom: 15px;">Wallet Balance</div>
                <div style="font-size: 24px; font-weight: 600; color: #4caf50;">
                    <?php echo CURRENCY_SYMBOL . number_format($user['wallet_balance'] ?? 0, 2); ?>
                </div>
                <div style="margin-top:15px;">
                    <a href="<?php echo SITE_URL; ?>/wallet.php" class="btn btn-primary" style="padding:8px 14px; font-size:13px;">
                        <i class="fas fa-plus-circle"></i> Top-up Wallet
                    </a>
                </div>
            </div>
            <?php else: ?>
            <div class="card" style="text-align:center; padding:30px;">
                <div style="width: 80px; height: 80px; background: #f0f0f0; border-radius: 50%; display:inline-flex; align-items:center; justify-content:center; font-size:28px; color:#666; margin-bottom:15px;">👤</div>
                <h4>Please sign in</h4>
                <p style="color:#666; font-size:14px;">To access your profile, orders and wishlist, please log in or create an account.</p>
                <div style="margin-top:15px; display:flex; gap:10px; justify-content:center;">
                    <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-primary">Login</a>
                    <a href="<?php echo SITE_URL; ?>/register.php" class="btn" style="background:#f5f5f5;">Sign Up</a>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Main Content -->
        <div>
            <?php if ($is_logged): ?>
            <!-- Profile Information -->
            <div class="card" id="profile">
                <h3 style="margin-bottom: 20px; font-size: 20px; font-weight: 600;">Profile Information</h3>
                
                <form method="POST" action="">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>First Name *</label>
                            <input type="text" name="first_name" class="form-control" required 
                                   value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Last Name *</label>
                            <input type="text" name="last_name" class="form-control" required 
                                   value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Email Address (cannot be changed)</label>
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['date_of_birth'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Gender</label>
                            <select name="gender" class="form-control">
                                <option value="">Select Gender</option>
                                <option value="male" <?php echo (($user['gender'] ?? '') == 'male') ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo (($user['gender'] ?? '') == 'female') ? 'selected' : ''; ?>>Female</option>
                                <option value="other" <?php echo (($user['gender'] ?? '') == 'other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </form>
            </div>

            <!-- Change Password -->
            <div class="card" id="password" style="margin-top: 30px;">
                <h3 style="margin-bottom: 20px; font-size: 20px; font-weight: 600;">Change Password</h3>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Current Password *</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>New Password * (minimum 10 characters)</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Confirm New Password *</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                        <div class="pwd-strength" data-placement="below-confirm"></div>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn btn-primary">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </form>
            </div>
            <?php else: ?>
            <div class="card">
                <h3 style="margin-bottom: 10px;">Please sign in to view your profile</h3>
                <p style="color:#666;">You must be logged in to access and edit your profile information. If you already have an account, please log in. Otherwise, create a new account.</p>
                <div style="margin-top:15px;">
                    <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-primary">Login</a>
                    <a href="<?php echo SITE_URL; ?>/register.php" class="btn" style="margin-left:10px;">Create Account</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.profile-menu-item {
    display: block;
    padding: 12px 20px;
    color: #333;
    text-decoration: none;
    transition: all 0.3s;
}
.profile-menu-item:hover, .profile-menu-item.active {
    background: #fff5f3;
    color: #f53d2d;
    border-left: 3px solid #f53d2d;
}
.profile-menu-item i {
    width: 20px;
    margin-right: 10px;
}
</style>

<?php include 'footer.php'; ?>