<?php
require_once '../config.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = "Email and password are required";
    } else {
        $stmt = $conn->prepare("SELECT admin_id, username, email, password, role, is_active FROM admins WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            
            if (!$admin['is_active']) {
                $error = "Your account has been deactivated";
            } elseif ($password === $admin['password']) {  // DIRECT COMPARISON - NO HASHING
                // Login successful
                // Regenerate session ID to mint cookie with current config params/path
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_regenerate_id(true);
                }
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_role'] = $admin['role'];
                
                // Update last login
                $update_stmt = $conn->prepare("UPDATE admins SET last_login = NOW() WHERE admin_id = ?");
                $update_stmt->bind_param("i", $admin['admin_id']);
                $update_stmt->execute();
                
                // Log login activity
                $ip = $_SERVER['REMOTE_ADDR'];
                $log_stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, ip_address) VALUES (?, 'login', ?)");
                $log_stmt->bind_param("is", $admin['admin_id'], $ip);
                $log_stmt->execute();
                
                // Redirect to dashboard
                header('Location: index.php');
                exit;
            } else {
                $error = "Invalid email or password";
            }
        } else {
            $error = "Invalid email or password";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #ee4d2d 0%, #ff6b35 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .admin-login-container {
            width: 100%;
            max-width: 450px;
        }

        .admin-login-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            padding: 40px;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .admin-login-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .admin-login-header i {
            font-size: 60px;
            color: #ee4d2d;
            margin-bottom: 20px;
            display: block;
        }

        .admin-login-header h2 {
            font-size: 28px;
            margin-bottom: 10px;
            color: #333;
            font-weight: 700;
        }

        .admin-login-header p {
            color: #666;
            font-size: 14px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            animation: shake 0.5s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        .alert-error {
            background: #fee;
            color: #c00;
            border: 1px solid #fcc;
            border-left: 4px solid #c00;
        }

        .alert i {
            font-size: 18px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
            font-size: 14px;
        }

        .form-group label i {
            margin-right: 8px;
            color: #ee4d2d;
            width: 18px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #ee4d2d;
            box-shadow: 0 0 0 3px rgba(238, 77, 45, 0.1);
        }

        .form-control::placeholder {
            color: #999;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 20px;
            border: none;
            border-radius: 4px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-primary {
            background: #ee4d2d;
            color: white;
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary:hover {
            background: #d73211;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(238, 77, 45, 0.3);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .admin-login-footer {
            margin-top: 25px;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #e5e5e5;
        }

        .admin-login-footer a {
            color: #ee4d2d;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .admin-login-footer a:hover {
            color: #d73211;
            gap: 12px;
        }

        .login-help {
            text-align: center;
            margin-top: 15px;
            font-size: 13px;
            color: #666;
        }

        @media (max-width: 480px) {
            .admin-login-card {
                padding: 30px 20px;
            }

            .admin-login-header h2 {
                font-size: 24px;
            }

            .admin-login-header i {
                font-size: 50px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <div class="admin-login-card">
            <div class="admin-login-header">
                <i class="fas fa-shield-alt"></i>
                <h2>Admin Panel</h2>
                <p>Login to manage your clothing store</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>
                        <i class="fas fa-envelope"></i>
                        Email Address
                    </label>
                    <input 
                        type="email" 
                        name="email" 
                        class="form-control" 
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        required 
                        autofocus 
                        placeholder="admin@clothingstore.com"
                    >
                </div>
                
                <div class="form-group">
                    <label>
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <input 
                        type="password" 
                        name="password" 
                        class="form-control" 
                        required 
                        placeholder="Enter your password"
                    >
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i>
                    Login to Dashboard
                </button>
            </form>
            
            <div class="admin-login-footer">
                <a href="../index.php">
                    <i class="fas fa-home"></i>
                    <span>Back to Store</span>
                </a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.querySelector('input[name="email"]');
            if (emailInput && !emailInput.value) {
                emailInput.focus();
            }
        });
    </script>
</body>
</html>