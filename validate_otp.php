<?php
require_once 'config.php';

// Manual PHPMailer include - Updated for YOUR folder structure
require_once 'PHPMailer-master/src/Exception.php';
require_once 'PHPMailer-master/src/PHPMailer.php';
require_once 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$page_title = "Verify OTP - " . SITE_NAME;
$error = '';
$success = '';

// Redirect if not coming from registration
if (!isset($_SESSION['registration_data'])) {
    header('Location: register.php');
    exit;
}

$registration_data = $_SESSION['registration_data'];

// Check if OTP has expired
if (time() > $registration_data['otp_expiry']) {
    $error = "OTP has expired. Please register again.";
    unset($_SESSION['registration_data']);
}

// Handle OTP verification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_otp'])) {
    $entered_otp = clean_input($_POST['otp']);
    
    if (empty($entered_otp)) {
        $error = "Please enter the OTP code";
    } elseif (strlen($entered_otp) != 6) {
        $error = "OTP must be 6 digits";
    } elseif (time() > $registration_data['otp_expiry']) {
        $error = "OTP has expired. Please register again.";
        unset($_SESSION['registration_data']);
    } elseif ($entered_otp != $registration_data['otp']) {
        $error = "Invalid OTP code. Please try again.";
    } else {
        // OTP is correct, create the user account
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", 
            $registration_data['first_name'],
            $registration_data['last_name'],
            $registration_data['email'],
            $registration_data['phone'],
            $registration_data['password']
        );
        
        if ($stmt->execute()) {
            // Clear registration data (do not auto-login; require explicit login)
            unset($_SESSION['registration_data']);

            // Redirect to login page per requirement
            header('Location: ' . SITE_URL . '/login.php');
            exit;
        } else {
            $error = "Registration failed. Please try again.";
        }
    }
}

// Handle resend OTP
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['resend_otp'])) {
    // Generate new OTP
    $new_otp = sprintf("%06d", mt_rand(0, 999999));
    
    // Update session with new OTP and expiry
    $_SESSION['registration_data']['otp'] = $new_otp;
    $_SESSION['registration_data']['otp_expiry'] = time() + (3 * 60);
    $registration_data = $_SESSION['registration_data'];
    
    // Send new OTP email
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'soewana586@gmail.com';         // TODO: Change this
        $mail->Password   = 'dnig cglb ropt ybpz';          // TODO: Change this
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Recipients
        $mail->setFrom('noreply@yourdomain.com', 'Register OTP Code');
        $mail->addAddress($registration_data['email'], $registration_data['first_name'] . ' ' . $registration_data['last_name']);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "Your New OTP Code";
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #f53d2d;'>Welcome to " . SITE_NAME . "!</h2>
                <p>Your new OTP code is:</p>
                <div style='background: #f5f5f5; padding: 20px; text-align: center; border-radius: 8px; margin: 20px 0;'>
                    <h1 style='color: #f53d2d; font-size: 36px; margin: 0; letter-spacing: 5px;'>$new_otp</h1>
                </div>
                <p>This code is valid for <strong>3 minutes</strong>.</p>
                <p style='color: #999; font-size: 12px; margin-top: 30px;'>If you didn't request this code, please ignore this email.</p>
            </div>
        ";
        $mail->AltBody = "Your new OTP code is: $new_otp. This code is valid for 3 minutes.";
        
        $mail->send();
        $success = "New OTP has been sent to your email!";
        
    } catch (Exception $e) {
        $error = "Failed to resend OTP. Please try again.";
    }
}

include 'header.php';
?>

<div class="container">
    <div class="auth-container" style="max-width: 500px; margin: 40px auto;">
        <div class="card">
            <div style="text-align: center; margin-bottom: 20px;">
                <i class="fas fa-envelope-open-text" style="font-size: 64px; color: #f53d2d;"></i>
            </div>
            
            <h2 class="card-title" style="text-align: center;">Verify Your Email</h2>
            
            <p style="text-align: center; color: #666; margin-bottom: 25px;">
                We've sent a 6-digit verification code to<br>
                <strong style="color: #333;"><?php echo htmlspecialchars($registration_data['email']); ?></strong>
            </p>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label style="text-align: center; display: block;">Enter OTP Code</label>
                    <input type="text" 
                           name="otp" 
                           class="form-control" 
                           maxlength="6" 
                           pattern="[0-9]{6}"
                           placeholder="000000"
                           required 
                           autofocus
                           style="text-align: center; font-size: 24px; letter-spacing: 8px; font-weight: 600;">
                    <small style="display: block; text-align: center; color: #999; margin-top: 8px;">
                        Code expires in 3 minutes
                    </small>
                </div>
                
                <button type="submit" name="verify_otp" class="btn btn-primary btn-full">
                    <i class="fas fa-check"></i> Verify & Create Account
                </button>
            </form>
            
            <div style="margin-top: 20px; text-align: center;">
                <p style="color: #666; margin-bottom: 10px;">Didn't receive the code?</p>
                <form method="POST" action="" style="display: inline;">
                    <button type="submit" name="resend_otp" class="btn btn-secondary" style="padding: 8px 20px;">
                        <i class="fas fa-redo"></i> Resend OTP
                    </button>
                </form>
            </div>
            
            <div style="margin-top: 20px; text-align: center;">
                <a href="register.php" style="color: #f53d2d; font-weight: 600;">
                    <i class="fas fa-arrow-left"></i> Back to Registration
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-focus on OTP input
document.addEventListener('DOMContentLoaded', function() {
    const otpInput = document.querySelector('input[name="otp"]');
    if (otpInput) {
        otpInput.focus();
        
        // Only allow numbers
        otpInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }
});

// Countdown timer for OTP expiry
<?php if (!$error || $error != "OTP has expired. Please register again."): ?>
let expiryTime = <?php echo $registration_data['otp_expiry']; ?>;
let countdownInterval = setInterval(function() {
    let now = Math.floor(Date.now() / 1000);
    let remaining = expiryTime - now;
    
    if (remaining <= 0) {
        clearInterval(countdownInterval);
        location.reload();
    }
}, 1000);
<?php endif; ?>
</script>

<?php include 'footer.php'; ?>