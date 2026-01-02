<?php
require_once 'config.php';

$page_title = "Login - " . SITE_NAME;
$error = '';
$lockRemaining = 0; // seconds remaining for lockout UI
$recaptcha_site_key = defined('RECAPTCHA_SITE_KEY') ? constant('RECAPTCHA_SITE_KEY') : '';
$recaptcha_secret   = defined('RECAPTCHA_SECRET_KEY') ? constant('RECAPTCHA_SECRET_KEY') : (defined('RECAPTCHA_SECRET') ? constant('RECAPTCHA_SECRET') : '');

// Dedicated helper for security alert emails with a simple fallback + logging
function send_security_alert_email(string $to, string $subject, string $htmlMessage): bool {
    // Prefer global helper if available
    if (function_exists('send_email')) {
        $ok = send_email($to, $subject, $htmlMessage);
        if ($ok) return true;
    }
    // Minimal plain-text fallback via mail()
    $headers = "From: " . SITE_NAME . " <noreply@clothingstore.com>\r\n" .
               "Reply-To: noreply@clothingstore.com\r\n" .
               "Content-Type: text/plain; charset=UTF-8\r\n";
    $plain = strip_tags(str_replace(['<br>','<br/>','<br />'], "\n", $htmlMessage));
    $ok = @mail($to, $subject, $plain, $headers);
    if ($ok) return true;

    // Log failure for diagnostics (no user-visible output)
    $logDir = __DIR__ . '/storage';
    if (!is_dir($logDir)) { @mkdir($logDir, 0777, true); }
    $logFile = $logDir . '/login_email.log';
    $line = date('Y-m-d H:i:s') . " | ALERT EMAIL FAILED | to={$to} | subject=" . str_replace(["\r","\n"], ' ', $subject) . "\n";
    @file_put_contents($logFile, $line, FILE_APPEND);
    return false;
}

// Small helper to verify Google reCAPTCHA v2 checkbox (only when configured)
function verify_recaptcha_response(?string $token, string $secret, ?string $remoteIp = null): bool {
    if (!$token || !$secret) return false;
    $postData = http_build_query([
        'secret'   => $secret,
        'response' => $token,
        'remoteip' => $remoteIp ?? ($_SERVER['REMOTE_ADDR'] ?? null),
    ]);
    $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
    $responseBody = false;
    if (function_exists('curl_init')) {
        $ch = curl_init($verifyUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $responseBody = curl_exec($ch);
        curl_close($ch);
    }
    if ($responseBody === false && ini_get('allow_url_fopen')) {
        $opts = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => $postData,
                'timeout' => 5,
            ]
        ];
        $context = stream_context_create($opts);
        $responseBody = @file_get_contents($verifyUrl, false, $context);
    }
    if ($responseBody === false) return false;
    $json = json_decode($responseBody, true);
    return is_array($json) && !empty($json['success']);
}

// ============================================
// Lightweight login throttle (no DB changes)
// - Tracks attempts and lock window per email in a JSON file under /storage
// - Locks for 60 seconds after 5 failed attempts (bad email or bad password)
// - Sends alert email only when a real account email hits the threshold with wrong password
// ============================================
function throttle_store_path(): string {
    $base = __DIR__ . '/storage';
    if (!is_dir($base)) {
        @mkdir($base, 0777, true);
    }
    return $base . '/login_throttle.json';
}

function throttle_read(): array {
    $path = throttle_store_path();
    if (!is_file($path)) return [];
    $json = @file_get_contents($path);
    if ($json === false) return [];
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function throttle_write(array $data): void {
    $path = throttle_store_path();
    @file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
}

function norm_email(string $email): string {
    return strtolower(trim($email));
}

function throttle_get_lock_remaining(string $email): int {
    $email = norm_email($email);
    if ($email === '') return 0;
    $data = throttle_read();
    $row = $data[$email] ?? null;
    if (!$row || empty($row['lock_until'])) return 0;
    $remaining = (int)$row['lock_until'] - time();
    return $remaining > 0 ? $remaining : 0;
}

function throttle_reset(string $email): void {
    $email = norm_email($email);
    if ($email === '') return;
    $data = throttle_read();
    if (isset($data[$email])) {
        unset($data[$email]);
        throttle_write($data);
    }
}

// Returns ['locked' => bool, 'remaining' => int, 'attempts' => int, 'locked_now' => bool]
function throttle_on_failure(string $email, bool $isExistingUser): array {
    $email = norm_email($email);
    $data = throttle_read();
    $row = $data[$email] ?? ['attempts' => 0, 'lock_until' => 0];

    // If currently locked, keep it
    $now = time();
    if (!empty($row['lock_until']) && $row['lock_until'] > $now) {
        return ['locked' => true, 'remaining' => $row['lock_until'] - $now, 'attempts' => (int)$row['attempts'], 'locked_now' => false];
    }

    // Not locked: increment attempts
    $row['attempts'] = (int)($row['attempts'] ?? 0) + 1;
    $locked_now = false;
    if ($row['attempts'] >= 5) {
        $row['lock_until'] = $now + 60; // 1 minute lock
        // Reset attempts after locking to count fresh for next window
        $row['attempts'] = 0;
        $locked_now = true;
    }
    $data[$email] = $row;
    throttle_write($data);

    return [
        'locked' => $locked_now,
        'remaining' => $locked_now ? 60 : 0,
        'attempts' => (int)$row['attempts'],
        'locked_now' => $locked_now,
    ];
}

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    $normalizedEmail = norm_email($email);

    // Short-circuit if this email is currently locked
    $existingLock = throttle_get_lock_remaining($normalizedEmail);
    if ($existingLock > 0) {
        $lockRemaining = $existingLock;
        $_SESSION['login_lock_email'] = $normalizedEmail;
        $error = 'Too many failed attempts. Please try again in ' . $lockRemaining . ' second' . ($lockRemaining !== 1 ? 's' : '') . '.';
    } else {
    
    // Enforce reCAPTCHA only when secret is configured; otherwise skip (no workflow change)
    if (!empty($recaptcha_secret)) {
        $recaptcha_token = $_POST['g-recaptcha-response'] ?? '';
        if (empty($recaptcha_token)) {
            $error = 'Please complete the reCAPTCHA';
        } else {
            $ok = verify_recaptcha_response($recaptcha_token, $recaptcha_secret, $_SERVER['REMOTE_ADDR'] ?? null);
            if (!$ok) {
                $error = 'reCAPTCHA verification failed. Please try again.';
            }
        }
    }
    
    if (!$error && (empty($email) || empty($password))) {
        $error = "Email and password are required";
    } else {
        if ($error) {
            // Skip DB work if we already have a reCAPTCHA or field error
        } else {
        $stmt = $conn->prepare("SELECT user_id, first_name, last_name, email, password, is_active FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (!$user['is_active']) {
                $error = "Your account has been deactivated. Please contact support.";
            } elseif (password_verify($password, $user['password'])) {
                // Successful login
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_email'] = $user['email'];

                // Clear throttle on success for this email
                throttle_reset($normalizedEmail);
                unset($_SESSION['login_lock_email']);
                
                // Set remember me cookie if checked
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + (86400 * 30), "/");
                }
                
                // Redirect to intended page or homepage
                $redirect = $_GET['redirect'] ?? SITE_URL . '/index.php';
                header('Location: ' . $redirect);
                exit;
            } else {
                // Wrong password for a real account: count attempt and possibly lock + email alert
                $th = throttle_on_failure($normalizedEmail, true);
                if ($th['locked']) {
                    $lockRemaining = $th['remaining'] ?? 60;
                    $_SESSION['login_lock_email'] = $normalizedEmail;
                    // Send alert email only at the moment the lock is first applied
                    if (!empty($th['locked_now'])) {
                        $to = $user['email'];
                        $subject = 'Security alert: Multiple failed login attempts';
                        $message = '<p>Hello ' . htmlspecialchars($user['first_name'] ?? '', ENT_QUOTES, 'UTF-8') . ',</p>' .
                                   '<p>We detected multiple failed login attempts to your account. For your security, your login is temporarily locked for 1 minute.</p>' .
                                   '<p>If this was not you, please reset your password immediately.</p>' .
                                   '<p>— ' . SITE_NAME . '</p>';
                        send_security_alert_email($to, $subject, $message);
                    }
                    $error = 'Too many failed attempts. Please try again in ' . $lockRemaining . ' second' . ($lockRemaining !== 1 ? 's' : '') . '.';
                } else {
                    $error = "Invalid email or password";
                }
            }
        } else {
            // Email not found: still count attempts against the typed email
            $th = throttle_on_failure($normalizedEmail, false);
            if ($th['locked']) {
                $lockRemaining = $th['remaining'] ?? 60;
                $_SESSION['login_lock_email'] = $normalizedEmail;
                $error = 'Too many failed attempts. Please try again in ' . $lockRemaining . ' second' . ($lockRemaining !== 1 ? 's' : '') . '.';
            } else {
                $error = "Invalid email or password";
            }
        }
        }
    }
    }
}

include 'header.php';
?>

<div class="container">
    <div class="auth-container" style="max-width: 450px; margin: 40px auto;">
        <div class="card">
            <h2 class="card-title" style="text-align: center;">Login</h2>
            <!-- Live lockout countdown toast placed directly under title -->
            <div id="lockout-timer" class="alert alert-warning" style="display:none; margin:10px auto 16px; max-width:400px;"></div>
            
            <?php if ($error && $lockRemaining <= 0 && empty($_SESSION['login_lock_email'])): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['registered'])): ?>
                <div class="alert alert-success">Registration successful! Please login.</div>
            <?php endif; ?>
            
            <?php if (isset($_GET['logout'])): ?>
                <div class="alert alert-success">You have been logged out successfully.</div>
            <?php endif; ?>
            
            <form method="POST" action="" id="login-form">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" required 
                           value="<?php echo $_POST['email'] ?? ''; ?>" autofocus>
                </div>
                
                <div class="form-group" data-no-pw-strength>
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required data-no-strength="1">
                </div>
                
                <?php if (!empty($recaptcha_site_key)): ?>
                <div class="form-group" style="margin-top:10px;">
                    <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($recaptcha_site_key, ENT_QUOTES); ?>"></div>
                </div>
                <?php endif; ?>
                
                <div class="form-group" style="display: flex; justify-content: space-between; align-items: center;">
                    <label style="margin: 0; display: flex; align-items: center; cursor: pointer;">
                        <input type="checkbox" name="remember" style="margin-right: 8px;">
                        Remember me
                    </label>
                    <a href="forgot-password.php" style="color: #f53d2d; font-size: 14px;">Forgot Password?</a>
                </div>
                
                <button type="submit" class="btn btn-primary btn-full" id="login-btn">Login</button>
            </form>
            
            <div style="margin-top: 20px; text-align: center;">
                <p>Don't have an account? <a href="register.php" style="color: #f53d2d; font-weight: 600;">Register here</a></p>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($recaptcha_site_key)): ?>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>

<script>
(function(){
    // Remaining seconds from backend, derived either from the current POST email or from the last locked email in session
    var lockSeconds = <?php
        // Derive a lock to show on GET: if there's a session-stored email with an active lock, compute remaining
        $uiLockRemaining = 0;
        if (!empty($_SESSION['login_lock_email'])) {
                $uiLockRemaining = throttle_get_lock_remaining($_SESSION['login_lock_email']);
                if ($uiLockRemaining <= 0) {
                        unset($_SESSION['login_lock_email']);
                }
        }
        // Prefer the server-side value from this request (if any)
        $initialLock = max((int)$lockRemaining, (int)$uiLockRemaining);
        echo (int)$initialLock;
    ?>;
    var form = document.getElementById('login-form');
    var btn  = document.getElementById('login-btn');
    var box  = document.getElementById('lockout-timer');
    function startCountdown(sec){
        if (!btn || !box) return;
        var remaining = parseInt(sec, 10) || 0;
        if (remaining <= 0) return;
        btn.disabled = true;
        function render(){
          box.style.display = 'block';
          var s = remaining === 1 ? 'second' : 'seconds';
          box.textContent = 'Too many failed attempts. Please try again in ' + remaining + ' ' + s + '.';
        }
        render();
        var iv = setInterval(function(){
            remaining--;
            if (remaining <= 0){
                clearInterval(iv);
                box.style.display = 'none';
                btn.disabled = false;
                return;
            }
            render();
        }, 1000);
    }
    if (lockSeconds > 0) startCountdown(lockSeconds);
})();
</script>

<?php include 'footer.php'; ?>