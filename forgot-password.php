<?php
require_once 'config.php';

// PHPMailer (use same approach as registration)
require_once 'PHPMailer-master/src/Exception.php';
require_once 'PHPMailer-master/src/PHPMailer.php';
require_once 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$page_title = "Forgot Password - " . SITE_NAME;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = clean_input($_POST['email']);
    
    if (empty($email)) {
        $error = "Please enter your email address";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif (!is_email_authentic($email)) {
        $error = 'Email not registered';
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT user_id, first_name FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $user_id = $user['user_id'];
            $first_name = $user['first_name'] ?? '';

            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Delete old tokens for this user
            $stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            // Insert new token
            $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user_id, $token, $expires);
            $stmt->execute();

            // Build reset link
            $reset_link = SITE_URL . "/reset-password.php?token=" . $token;

            // Send email to the genuine registered email (same SMTP setup as registration)
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                // Use the same sender creds as registration
                $mail->Username   = 'kaizoblues0@gmail.com';
                $mail->Password   = 'tgdc xacw isot fwvx';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('noreply@yourdomain.com', SITE_NAME . ' Password Reset');
                $mail->addAddress($email, $first_name);

                $mail->isHTML(true);
                $mail->Subject = 'Reset your password';
                $mail->Body    = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                        <h2 style='color:#111827; margin:0 0 12px;'>Password reset request</h2>
                        <p>Hi " . htmlspecialchars($first_name ?: 'there') . ",</p>
                        <p>We received a request to reset your password for <strong>" . htmlspecialchars(SITE_NAME) . "</strong>.</p>
                        <p>Click the button below to set a new password. This link will expire in 1 hour.</p>
                        <p style='margin:20px 0;'>
                            <a href='" . $reset_link . "' style='display:inline-block; background:#f53d2d; color:#fff; text-decoration:none; padding:12px 18px; border-radius:8px;'>Reset Password</a>
                        </p>
                        <p>If you didn't request this, you can safely ignore this email.</p>
                        <p style='color:#6b7280; font-size:12px;'>If the button doesn't work, paste this link into your browser:<br>" . htmlspecialchars($reset_link) . "</p>
                    </div>
                ";
                $mail->AltBody = "Use this link to reset your password (valid for 5 minutes): " . $reset_link;
                $mail->send();
            } catch (Exception $e) {
                // Silent fail to avoid email enumeration or leaking SMTP errors
            }

            // Always show a generic success message
            $success = "If an account exists with that email, a password reset link has been sent.";
        } else {
            // Don't reveal if email exists or not for security
            $success = "If an account exists with that email, a password reset link has been sent.";
        }
    }
}

include 'header.php';
?>

<div class="container">
    <div class="auth-container" style="max-width: 450px; margin: 40px auto;">
        <div class="card">
            <h2 class="card-title" style="text-align: center;">Forgot Password</h2>
            <p style="text-align: center; color: #666; margin-bottom: 30px;">
                Enter your email address and we'll send you a link to reset your password.
            </p>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" required 
                           value="<?php echo $_POST['email'] ?? ''; ?>" autofocus>
                </div>
                
                <button type="submit" class="btn btn-primary btn-full">
                    Send Reset Link
                </button>
            </form>
            
            <div style="margin-top: 20px; text-align: center;">
                <a href="login.php" style="color: #f53d2d; font-weight: 600;">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>