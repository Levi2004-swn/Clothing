<?php
require_once 'config.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
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
        $mail->Username   = 'soewana586@gmail.com';
        $mail->Password   = 'dnig cglb ropt ybpz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Recipients
        $mail->setFrom('noreply@yourdomain.com', 'Register OTP Code');
        $mail->addAddress($registration_data['email'], $registration_data['first_name'] . ' ' . $registration_data['last_name']);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "Your New OTP Code";
        $mail->Body    = "
            <h2>Welcome to " . SITE_NAME . "!</h2>
            <p>Your new OTP code is: <strong style='font-size: 24px; color: #f53d2d;'>$new_otp</strong></p>
            <p>This code is valid for 3 minutes.</p>
            <p>If you didn't request this code, please ignore this email.</p>
        ";
        $mail->AltBody = "Your new OTP code is: $new_otp. This code is valid for 3 minutes.";
        
        $mail->send();
        $success = "New OTP has been sent to your email!";
        
    } catch (Exception $e) {
        $error = "Failed to resend OTP. Please try again.";
    }
}

// Calculate remaining time
$remaining_seconds = $registration_data['otp_expiry'] - time();
$remaining_minutes = floor($remaining_seconds / 60);
$remaining_seconds = $remaining_seconds % 60;

include 'header.php';
?>

<style>
/* Enhanced OTP Page Styles */
.otp-input-container {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin: 25px 0;
}

.otp-input-single {
    width: 50px;
    height: 60px;
    text-align: center;
    font-size: 28px;
    font-weight: 700;
    border: 2px solid #ddd;
    border-radius: 8px;
    transition: all 0.3s;
    background: #f9f9f9;
}

.otp-input-single:focus {
    border-color: #f53d2d;
    outline: none;
    box-shadow: 0 0 0 3px rgba(245, 61, 45, 0.1);
    transform: scale(1.05);
    background: white;
}

.otp-timer {
    text-align: center;
    margin: 20px 0;
    padding: 12px;
    background: #fff3f3;
    border-radius: 8px;
    color: #f53d2d;
    font-weight: 600;
    font-size: 16px;
}

.otp-timer.expiring {
    background: #ffebee;
    animation: pulse 1s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.email-icon-container {
    text-align: center;
    margin-bottom: 20px;
    animation: fadeInDown 0.6s ease;
}

@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.success-animation {
    display: none;
    text-align: center;
    margin: 20px 0;
}

.success-animation.show {
    display: block;
    animation: scaleIn 0.5s ease;
}

.success-animation i {
    font-size: 80px;
    color: #4caf50;
}

@keyframes scaleIn {
    from {
        transform: scale(0);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}

@media (max-width: 768px) {
    .otp-input-single {
        width: 45px;
        height: 55px;
        font-size: 24px;
    }
}
</style>

<div class="container">
    <div class="auth-container" style="max-width: 550px; margin: 40px auto;">
        <div class="card">
            <div class="email-icon-container">
                <i class="fas fa-envelope-open-text" style="font-size: 64px; color: #f53d2d;"></i>
            </div>
            
            <h2 class="card-title" style="text-align: center;">Verify Your Email</h2>
            
            <p style="text-align: center; color: #666; margin-bottom: 10px;">
                We've sent a 6-digit verification code to
            </p>
            <p style="text-align: center; margin-bottom: 25px;">
                <strong style="color: #333; font-size: 16px;"><?php echo htmlspecialchars($registration_data['email']); ?></strong>
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
            
            <div class="success-animation" id="successAnimation">
                <i class="fas fa-check-circle"></i>
                <p style="color: #4caf50; font-weight: 600; margin-top: 10px;">Verification Successful!</p>
            </div>
            
            <form method="POST" action="" id="otpForm">
                <div class="form-group">
                    <label style="text-align: center; display: block; margin-bottom: 15px; font-size: 15px;">
                        Enter Verification Code
                    </label>
                    
                    <!-- Individual OTP Inputs -->
                    <div class="otp-input-container">
                        <input type="text" class="otp-input-single" maxlength="1" data-index="0" />
                        <input type="text" class="otp-input-single" maxlength="1" data-index="1" />
                        <input type="text" class="otp-input-single" maxlength="1" data-index="2" />
                        <input type="text" class="otp-input-single" maxlength="1" data-index="3" />
                        <input type="text" class="otp-input-single" maxlength="1" data-index="4" />
                        <input type="text" class="otp-input-single" maxlength="1" data-index="5" />
                    </div>
                    
                    <!-- Hidden input to submit full OTP -->
                    <input type="hidden" name="otp" id="otpValue" />
                    
                    <!-- Timer Display -->
                    <div class="otp-timer" id="otpTimer">
                        <i class="fas fa-clock"></i>
                        Code expires in: <span id="timeRemaining"><?php echo sprintf("%d:%02d", $remaining_minutes, $remaining_seconds); ?></span>
                    </div>
                </div>
                
                <button type="submit" name="verify_otp" class="btn btn-primary btn-full" id="verifyBtn">
                    <i class="fas fa-check"></i> Verify & Create Account
                </button>
            </form>
            
            <div style="margin-top: 25px; text-align: center;">
                <p style="color: #666; margin-bottom: 12px;">Didn't receive the code?</p>
                <form method="POST" action="" style="display: inline;" id="resendForm">
                    <button type="submit" name="resend_otp" class="btn btn-secondary" id="resendBtn" style="padding: 10px 24px;">
                        <i class="fas fa-redo"></i> <span id="resendText">Resend OTP</span>
                    </button>
                </form>
            </div>
            
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e5e5; text-align: center;">
                <a href="register.php" style="color: #f53d2d; font-weight: 600;">
                    <i class="fas fa-arrow-left"></i> Back to Registration
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const otpInputs = document.querySelectorAll('.otp-input-single');
    const otpForm = document.getElementById('otpForm');
    const otpValue = document.getElementById('otpValue');
    const verifyBtn = document.getElementById('verifyBtn');
    const resendBtn = document.getElementById('resendBtn');
    const resendText = document.getElementById('resendText');
    
    // Focus first input
    otpInputs[0].focus();
    
    // Handle OTP input
    otpInputs.forEach((input, index) => {
        input.addEventListener('input', function(e) {
            // Only allow numbers
            this.value = this.value.replace(/[^0-9]/g, '');
            
            if (this.value.length === 1 && index < otpInputs.length - 1) {
                // Move to next input
                otpInputs[index + 1].focus();
            }
            
            // Update hidden input with full OTP
            updateOTPValue();
        });
        
        input.addEventListener('keydown', function(e) {
            // Handle backspace
            if (e.key === 'Backspace' && this.value === '' && index > 0) {
                otpInputs[index - 1].focus();
            }
            
            // Handle paste
            if (e.key === 'v' && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                navigator.clipboard.readText().then(text => {
                    const numbers = text.replace(/[^0-9]/g, '').slice(0, 6);
                    numbers.split('').forEach((num, i) => {
                        if (otpInputs[i]) {
                            otpInputs[i].value = num;
                        }
                    });
                    updateOTPValue();
                    if (numbers.length === 6) {
                        otpInputs[5].focus();
                    }
                });
            }
        });
        
        // Handle paste event
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedData = e.clipboardData.getData('text');
            const numbers = pastedData.replace(/[^0-9]/g, '').slice(0, 6);
            
            numbers.split('').forEach((num, i) => {
                if (otpInputs[i]) {
                    otpInputs[i].value = num;
                }
            });
            updateOTPValue();
            if (numbers.length === 6) {
                otpInputs[5].focus();
            }
        });
    });
    
    function updateOTPValue() {
        const otp = Array.from(otpInputs).map(input => input.value).join('');
        otpValue.value = otp;
        
        // Auto-submit when all 6 digits are entered (optional)
        // if (otp.length === 6) {
        //     otpForm.submit();
        // }
    }
    
    // Countdown timer
    let expiryTime = <?php echo $registration_data['otp_expiry']; ?>;
    let timerElement = document.getElementById('timeRemaining');
    let timerContainer = document.getElementById('otpTimer');
    
    let countdownInterval = setInterval(function() {
        let now = Math.floor(Date.now() / 1000);
        let remaining = expiryTime - now;
        
        if (remaining <= 0) {
            clearInterval(countdownInterval);
            timerElement.textContent = 'Expired';
            timerContainer.style.background = '#ffebee';
            verifyBtn.disabled = true;
            verifyBtn.style.opacity = '0.5';
            verifyBtn.style.cursor = 'not-allowed';
            
            // Show alert
            alert('OTP has expired. Please request a new code.');
        } else {
            let minutes = Math.floor(remaining / 60);
            let seconds = remaining % 60;
            timerElement.textContent = minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
            
            // Add expiring class when less than 30 seconds
            if (remaining <= 30) {
                timerContainer.classList.add('expiring');
            }
        }
    }, 1000);
    
    // Form submission with loading state
    otpForm.addEventListener('submit', function(e) {
        if (otpValue.value.length !== 6) {
            e.preventDefault();
            alert('Please enter all 6 digits');
            return;
        }
        
        verifyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
        verifyBtn.disabled = true;
    });
    
    // Resend button with countdown
    let resendCooldown = 0;
    
    document.getElementById('resendForm').addEventListener('submit', function(e) {
        if (resendCooldown > 0) {
            e.preventDefault();
            return;
        }
        
        resendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        resendBtn.disabled = true;
        
        // Start cooldown after form submits
        setTimeout(function() {
            startResendCooldown();
        }, 1000);
    });
    
    function startResendCooldown() {
        resendCooldown = 60; // 60 seconds cooldown
        resendBtn.disabled = true;
        
        let cooldownInterval = setInterval(function() {
            resendCooldown--;
            resendText.textContent = 'Resend in ' + resendCooldown + 's';
            
            if (resendCooldown <= 0) {
                clearInterval(cooldownInterval);
                resendBtn.disabled = false;
                resendBtn.innerHTML = '<i class="fas fa-redo"></i> <span id="resendText">Resend OTP</span>';
            }
        }, 1000);
    }
});
</script>

<?php include 'footer.php'; ?>