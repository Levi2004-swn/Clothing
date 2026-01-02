<?php
// Simple admin test - NO config.php yet
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Admin Login Test</h1>";
echo "<hr>";

// Database connection
$conn = new mysqli('localhost', 'root', '', 'clothing_store');

if ($conn->connect_error) {
    die("❌ Database connection failed: " . $conn->connect_error);
}

echo "✅ Connected to database<br><br>";

// Check admins table
$result = $conn->query("SHOW TABLES LIKE 'admins'");
if ($result->num_rows == 0) {
    echo "❌ Admins table doesn't exist!<br>";
    echo "<br><strong>Creating admins table...</strong><br>";
    
    $sql = "CREATE TABLE IF NOT EXISTS `admins` (
      `admin_id` INT(11) NOT NULL AUTO_INCREMENT,
      `username` VARCHAR(50) NOT NULL,
      `email` VARCHAR(100) NOT NULL,
      `password` VARCHAR(255) NOT NULL,
      `role` ENUM('super_admin', 'inventory_manager', 'staff') DEFAULT 'staff',
      `is_active` TINYINT(1) DEFAULT 1,
      `last_login` DATETIME DEFAULT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`admin_id`),
      UNIQUE KEY `email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($sql)) {
        echo "✅ Admins table created!<br>";
    } else {
        die("❌ Failed to create table: " . $conn->error);
    }
}

echo "✅ Admins table exists<br><br>";

// Check for admin account
$result = $conn->query("SELECT * FROM admins WHERE email = 'admin@clothingstore.com'");

if ($result->num_rows == 0) {
    echo "⚠️ No admin account found. Creating one...<br>";
    
    $email = 'admin@clothingstore.com';
    $username = 'admin';
    $password = 'admin123';
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO admins (username, email, password, role, is_active) VALUES (?, ?, ?, 'super_admin', 1)");
    $stmt->bind_param("sss", $username, $email, $hashed);
    
    if ($stmt->execute()) {
        echo "✅ Admin account created successfully!<br>";
        echo "<strong>Email:</strong> $email<br>";
        echo "<strong>Password:</strong> $password<br><br>";
    } else {
        die("❌ Failed to create admin: " . $stmt->error);
    }
} else {
    echo "✅ Admin account exists<br><br>";
}

// Test password
echo "<h3>Testing Login Credentials</h3>";
$email = 'admin@clothingstore.com';
$password = 'admin123';

$stmt = $conn->prepare("SELECT admin_id, username, email, password, role FROM admins WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 1) {
    $admin = $result->fetch_assoc();
    
    echo "Admin found: <strong>" . htmlspecialchars($admin['username']) . "</strong><br>";
    echo "Email: " . htmlspecialchars($admin['email']) . "<br>";
    echo "Role: " . htmlspecialchars($admin['role']) . "<br><br>";
    
    if (password_verify($password, $admin['password'])) {
        echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; border-left: 4px solid #28a745;'>";
        echo "<h3 style='color: #155724; margin: 0;'>✅ SUCCESS!</h3>";
        echo "<p style='color: #155724;'>Password verification works! Login should work now.</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px; border-left: 4px solid #dc3545;'>";
        echo "<h3 style='color: #721c24; margin: 0;'>❌ FAILED!</h3>";
        echo "<p style='color: #721c24;'>Password verification failed. Let me fix it...</p>";
        echo "</div>";
        
        // Fix the password
        $new_hash = password_hash($password, PASSWORD_DEFAULT);
        $fix_stmt = $conn->prepare("UPDATE admins SET password = ? WHERE email = ?");
        $fix_stmt->bind_param("ss", $new_hash, $email);
        
        if ($fix_stmt->execute()) {
            echo "<br><div style='background: #d4edda; padding: 20px; border-radius: 5px;'>";
            echo "✅ Password fixed! Try the test again by refreshing this page.";
            echo "</div>";
        }
    }
} else {
    echo "❌ Admin not found in database!<br>";
}

echo "<br><hr>";
echo "<h3>Try Logging In:</h3>";
echo "<a href='admin/login.php' style='display: inline-block; background: #ee4d2d; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;'>GO TO ADMIN LOGIN →</a>";
echo "<br><br>";
echo "<p><strong>Credentials:</strong></p>";
echo "<ul>";
echo "<li>Email: <strong>admin@clothingstore.com</strong></li>";
echo "<li>Password: <strong>admin123</strong></li>";
echo "</ul>";
echo "<br><span style='color: red;'>⚠️ DELETE THIS FILE AFTER LOGIN WORKS!</span>";
?>
```

**Run this file:**
```
http://localhost/clothing/admin-test.php