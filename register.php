<?php
require_once 'config.php';

// Manual PHPMailer include - Updated for YOUR folder structure
require_once 'PHPMailer-master/src/Exception.php';
require_once 'PHPMailer-master/src/PHPMailer.php';
require_once 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$page_title = "Register - " . SITE_NAME;
$error = '';
$success = '';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

// Handle registration
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = clean_input($_POST['first_name']);
    $last_name = clean_input($_POST['last_name']);
    $email = clean_input($_POST['email']);
    $phone = clean_input($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $captcha_input = isset($_POST['captcha']) ? trim($_POST['captcha']) : '';
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error = "All fields are required";
    } elseif (empty($captcha_input)) {
        $error = "Please complete the CAPTCHA";
    } elseif (!isset($_SESSION['captcha_text']) || strcasecmp($captcha_input, $_SESSION['captcha_text']) !== 0) {
        $error = "Incorrect CAPTCHA. Please try again.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif (!is_email_authentic($email)) {
        $error = 'Please input authentic email';
    } elseif (strlen($password) < 10) {
        $error = "Password must be at least 10 characters";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Email already registered";
        } else {
            // Generate OTP
            $otp = sprintf("%06d", mt_rand(0, 999999));
            
            // Store registration data in session
            $_SESSION['registration_data'] = [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'phone' => $phone,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'otp' => $otp,
                'otp_expiry' => time() + (3 * 60) // 3 minutes
            ];
            
            // Send OTP email
            $mail = new PHPMailer(true);
            
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'kaizoblues0@gmail.com';            // TODO: Change this to your email
                $mail->Password   = 'tgdc xacw isot fwvx';             // TODO: Change this to your app password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                
                // Recipients
                $mail->setFrom('noreply@yourdomain.com', 'Register OTP Code');   // TODO: Change this
                $mail->addAddress($email, $first_name . ' ' . $last_name);
                
                // Content
                $mail->isHTML(true);
                $mail->Subject = "Your OTP Code";
                $mail->Body    = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                        <h2 style='color: #f53d2d;'>Welcome to " . SITE_NAME . "!</h2>
                        <p>Your OTP code is:</p>
                        <div style='background: #f5f5f5; padding: 20px; text-align: center; border-radius: 8px; margin: 20px 0;'>
                            <h1 style='color: #f53d2d; font-size: 36px; margin: 0; letter-spacing: 5px;'>$otp</h1>
                        </div>
                        <p>This code is valid for <strong>3 minutes</strong>.</p>
                        <p style='color: #999; font-size: 12px; margin-top: 30px;'>If you didn't request this code, please ignore this email.</p>
                    </div>
                ";
                $mail->AltBody = "Your OTP code is: $otp. This code is valid for 3 minutes.";
                
                $mail->send();
                
                // Redirect to OTP validation page
                header("Location: validate_otp.php");
                exit;
                
            } catch (Exception $e) {
                $error = "Failed to send OTP. Please try again. Error: {$mail->ErrorInfo}";
            }
        }
    }
}

include 'header.php';
?>

<div class="container">
    <div class="auth-container" style="max-width: 500px; margin: 40px auto;">
        <div class="card">
            <h2 class="card-title" style="text-align: center;">Create Account</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="first_name" class="form-control" required 
                           value="<?php echo $_POST['first_name'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" class="form-control" required 
                           value="<?php echo $_POST['last_name'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Email Address *</label>
                    <input type="email" name="email" class="form-control" required 
                           value="<?php echo $_POST['email'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" class="form-control" 
                           value="<?php echo $_POST['phone'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Password * (minimum 10 characters)</label>
                    <input type="password" name="password" class="form-control" required>
                    <div class="pwd-strength"></div>
                </div>
                
                <div class="form-group">
                    <label>Confirm Password *</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                
                <!-- CAPTCHA Verification -->
                <div class="form-group">
                    <label style="display:block;">Verification *</label>
                    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                        <img id="captchaImage" src="<?php echo SITE_URL; ?>/captcha.php" alt="CAPTCHA" style="height:42px; border:1px solid #e5e7eb; border-radius:6px; background:#f8fafc;">
                        <button type="button" id="refreshCaptcha" class="btn btn-secondary" style="height:42px; display:inline-flex; align-items:center; gap:8px;">
                            <i class="fas fa-rotate-right"></i> Refresh
                        </button>
                    </div>
                    <input type="text" name="captcha" class="form-control" placeholder="Enter the text shown above" style="margin-top:8px;" autocomplete="off" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-full">Register</button>
            </form>
            
            <div style="margin-top: 20px; text-align: center;">
                <p>Already have an account? <a href="login.php" style="color: #f53d2d; font-weight: 600;">Login here</a></p>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
// Refresh CAPTCHA without reloading the page (scoped to this page)
(function(){
    var btn = document.getElementById('refreshCaptcha');
    var img = document.getElementById('captchaImage');
    if (btn && img) {
        btn.addEventListener('click', function(){
            var url = '<?php echo SITE_URL; ?>/captcha.php?ts=' + Date.now();
            img.setAttribute('src', url);
        });
    }
})();
</script>