<?php
require_once 'config.php';

$page_title = "Reset Password - " . SITE_NAME;
$error = '';
$success = '';
$valid_token = false;

// Check if token is provided and valid
if (isset($_GET['token'])) {
    $token = clean_input($_GET['token']);
    
    $stmt = $conn->prepare("SELECT user_id FROM password_resets WHERE token = ? AND expires_at > NOW() AND used = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $valid_token = true;
        $reset_data = $result->fetch_assoc();
    } else {
        $error = "Invalid or expired reset token. Please request a new password reset link.";
    }
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $valid_token) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($new_password)) {
        $error = "Please enter a new password";
    } elseif (strlen($new_password) < 10) {
        $error = "Password must be at least 10 characters";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password using user_id
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->bind_param("si", $hashed_password, $reset_data['user_id']);
        
        if ($stmt->execute()) {
            // Mark token as used
            $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();

            // Redirect directly to login page after successful reset
            header('Location: ' . SITE_URL . '/login.php?reset=1');
            exit;
        } else {
            $error = "Failed to reset password. Please try again.";
        }
    }
}

include 'header.php';
?>

<div class="container">
    <div class="auth-container" style="max-width: 450px; margin: 40px auto;">
        <div class="card">
            <h2 class="card-title" style="text-align: center;">Reset Password</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($valid_token && !$success): ?>
                <p style="text-align: center; color: #666; margin-bottom: 30px;">
                    Enter your new password below.
                </p>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label>New Password (minimum 10 characters)</label>
                        <input type="password" name="new_password" class="form-control" required autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                        <div class="pwd-strength" data-placement="below-confirm"></div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-full">
                        Reset Password
                    </button>
                </form>
            <?php endif; ?>
            
            <?php if (!$valid_token && !$success): ?>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="forgot-password.php" class="btn btn-primary">
                        Request New Reset Link
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>