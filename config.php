<?php
// Start session only if not already started, and ensure cookie path covers the whole app (including /clothing/admin/ajax)
if (session_status() === PHP_SESSION_NONE) {
    // Derive an app base path; default to '/clothing' when site runs under that folder, else fallback to '/'
    $requestPath = $_SERVER['REQUEST_URI'] ?? '';
    $appPath = (strpos($requestPath, '/clothing/') !== false || rtrim($requestPath, '/') === '/clothing') ? '/clothing' : '/';

    $cookieParams = session_get_cookie_params();
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 70300) {
        // Modern signature supports SameSite
        session_set_cookie_params([
            'lifetime' => $cookieParams['lifetime'] ?? 0,
            'path' => $appPath,
            'domain' => $cookieParams['domain'] ?? '',
            'secure' => $secure,
            'httponly' => $cookieParams['httponly'] ?? true,
            'samesite' => 'Lax',
        ]);
    } else {
        // Legacy signature
        session_set_cookie_params(
            $cookieParams['lifetime'] ?? 0,
            $appPath,
            $cookieParams['domain'] ?? '',
            $secure,
            $cookieParams['httponly'] ?? true
        );
    }
    session_start();
}

// ============================================
// DATABASE CONFIGURATION
// ============================================
define('DB_HOST', 'localhost');
define('DB_PORT', '3308');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'clothing');

// Create database connection
// Allow either DB_HOST containing a host:port or a separate DB_PORT value.
$mysqli_host = DB_HOST;
$mysqli_port = null;
if (strpos(DB_HOST, ':') !== false) {
    list($host_part, $host_port) = explode(':', DB_HOST, 2);
    $mysqli_host = $host_part;
    if (is_numeric($host_port)) {
        $mysqli_port = (int) $host_port;
    }
}
if ($mysqli_port === null && defined('DB_PORT') && DB_PORT !== '') {
    $mysqli_port = (int) DB_PORT;
}

// Use mysqli constructor with port parameter when available
if ($mysqli_port !== null) {
    $conn = new mysqli($mysqli_host, DB_USER, DB_PASS, DB_NAME, $mysqli_port);
} else {
    $conn = new mysqli($mysqli_host, DB_USER, DB_PASS, DB_NAME);
}

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// ============================================
// SITE CONFIGURATION
// ============================================
define('SITE_NAME', 'Clothing Store');
// SITE_URL is built dynamically so asset URLs use the same host/port the browser requested.
// This avoids issues when Apache is serving on a non-default port (e.g., :8080).
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('SITE_URL', $protocol . '://' . $host . '/clothing');
// Currency configuration: switched to USD per requirement (display only; no workflow changes)
define('CURRENCY_CODE', 'USD');
define('CURRENCY_SYMBOL', '$');
define('TAX_RATE', 10); // Percentage
define('SHIPPING_FEE', 5.99);
define('FREE_SHIPPING_THRESHOLD', 50);

// Google reCAPTCHA (Customer login)
// Provided by user; used conditionally by pages like login.php
if (!defined('RECAPTCHA_SITE_KEY')) {
    define('RECAPTCHA_SITE_KEY', '6LfxhAQsAAAAAHkQaJ-hfrbplPonereW2-Cu2L4L');
}
if (!defined('RECAPTCHA_SECRET')) {
    define('RECAPTCHA_SECRET', '6LfxhAQsAAAAACTb4V67NVfzMNtS11IYjwyA2OIv');
}

// ============================================
// HELPER FUNCTIONS
// ============================================

// Get session ID
function get_session_id() {
    return session_id();
}

// Sanitize user input
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Check whether an email looks "authentic" (not disposable and has DNS records)
// Returns true when email appears authentic, false when it's likely disposable/invalid.
function is_email_authentic($email) {
    // Basic format check
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $domain = strtolower(substr(strrchr($email, "@"), 1));

    // Short disposable domain list (expand as needed)
    $disposable_domains = [
        'mailinator.com', '10minutemail.com', 'temp-mail.org', 'yopmail.com', 'guerrillamail.com',
        'maildrop.cc', 'trashmail.com', 'tempmail.net', 'fakeinbox.com', 'dispostable.com'
    ];

    if (in_array($domain, $disposable_domains, true)) {
        return false;
    }

    // Prefer MX records
    $has_mx = false;
    if (function_exists('getmxrr')) {
        $mxhosts = [];
        $has_mx = @getmxrr($domain, $mxhosts);
        if ($has_mx) return true;
    }

    // Fallback to A/AAAA DNS records
    if (function_exists('checkdnsrr')) {
        if (checkdnsrr($domain, 'A') || checkdnsrr($domain, 'AAAA')) {
            return true;
        }
    }

    // As a last resort try dns_get_record if available
    if (function_exists('dns_get_record')) {
        $records = @dns_get_record($domain, DNS_MX | DNS_A | DNS_AAAA);
        if (!empty($records)) return true;
    }

    return false;
}

// Check if user is logged in
function is_logged_in() {
    // Allow pages to force a guest view without destroying the session
    if (defined('FORCE_GUEST') && constant('FORCE_GUEST') === true) {
        return false;
    }
        if (!isset($_SESSION['user_id'])) return false;
        // Guard: if the user has been soft-deleted, treat as logged out
        try {
            global $conn;
            $uid = (int)($_SESSION['user_id'] ?? 0);
            if ($uid <= 0) return false;
            $stmt = $conn->prepare("SELECT deleted_at FROM users WHERE user_id = ? LIMIT 1");
            $stmt->bind_param("i", $uid);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                if (!empty($row['deleted_at'])) {
                    // Invalidate session for deleted users
                    unset($_SESSION['user_id'], $_SESSION['user_name']);
                    return false;
                }
            } else {
                // User record not found
                unset($_SESSION['user_id'], $_SESSION['user_name']);
                return false;
            }
        } catch (Throwable $e) {
            // On any DB error, fall back to session flag
        }
        return true;
}

// Get current user ID
function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

// Get user data
function get_user_data($conn, $user_id) {
    if (!$user_id) return null;
    
    $stmt = $conn->prepare("SELECT user_id, first_name, last_name, email, phone FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Redirect to login if not logged in
function require_login() {
    if (!is_logged_in()) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}

// Get cart count
function get_cart_count($conn, $user_id = null) {
    if (!$user_id) {
        $user_id = get_user_id();
    }
    
    if (!$user_id) return 0;
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'] ?? 0;
}

// Get cart items
function get_cart_items($conn, $user_id = null) {
    if (!$user_id) {
        $user_id = get_user_id();
    }
    
    if (!$user_id) return [];
    
    $items = [];
    $query = "SELECT c.*, p.product_name, p.price, pi.image_url, pv.size, pv.color 
              FROM cart c
              JOIN products p ON c.product_id = p.product_id
              LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
              LEFT JOIN product_variants pv ON c.variant_id = pv.variant_id
              WHERE c.user_id = ?
              ORDER BY c.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    return $items;
}

// Get cart total
function get_cart_total($conn, $user_id = null) {
    if (!$user_id) {
        $user_id = get_user_id();
    }
    
    if (!$user_id) return 0;
    
    $stmt = $conn->prepare("SELECT SUM(c.quantity * p.price) as total 
                           FROM cart c 
                           JOIN products p ON c.product_id = p.product_id 
                           WHERE c.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}

// Format price
// Unified currency formatter (presentation only)
function format_currency($amount, $with_code = false) {
    // Ensure numeric; fallback to 0 if not
    if (!is_numeric($amount)) {
        $amount = 0;
    }
    $formatted = number_format((float)$amount, 2);
    $value = CURRENCY_SYMBOL . $formatted; // e.g. $123.45
    if ($with_code) {
        $value .= ' ' . CURRENCY_CODE; // e.g. $123.45 USD
    }
    return $value;
}

// Backward compatibility wrapper (existing calls use format_price)
function format_price($price) {
    return format_currency($price);
}

// Generate random string
function generate_random_string($length = 10) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $result = '';
    for ($i = 0; $i < $length; $i++) {
        $result .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $result;
}

// Generate order number
function generate_order_number() {
    return 'ORD-' . date('Ymd') . '-' . generate_random_string(6);
}

// Send email (basic implementation)
function send_email($to, $subject, $message) {
    $headers = "From: " . SITE_NAME . " <noreply@clothingstore.com>\r\n";
    $headers .= "Reply-To: noreply@clothingstore.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}

// Get product by ID
function get_product($conn, $product_id) {
    $stmt = $conn->prepare("SELECT p.*, c.category_name 
                           FROM products p 
                           LEFT JOIN categories c ON p.category_id = c.category_id 
                           WHERE p.product_id = ? AND p.is_active = 1");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Get product images
function get_product_images($conn, $product_id) {
    $images = [];
    $result = $conn->query("SELECT * FROM product_images WHERE product_id = $product_id ORDER BY is_primary DESC, image_id");
    while ($row = $result->fetch_assoc()) {
        $images[] = $row;
    }
    return $images;
}

// Get product variants
function get_product_variants($conn, $product_id) {
    $variants = [];
    $result = $conn->query("SELECT * FROM product_variants WHERE product_id = $product_id ORDER BY variant_id");
    while ($row = $result->fetch_assoc()) {
        $variants[] = $row;
    }
    return $variants;
}

// Check if product is in wishlist
function is_in_wishlist($conn, $user_id, $product_id) {
    if (!$user_id) return false;
    
    $stmt = $conn->prepare("SELECT wishlist_id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

// Calculate final price with tax
function calculate_price_with_tax($price) {
    $tax = ($price * TAX_RATE) / 100;
    return $price + $tax;
}

// Calculate shipping fee
function calculate_shipping($subtotal) {
    if ($subtotal >= FREE_SHIPPING_THRESHOLD) {
        return 0;
    }
    return SHIPPING_FEE;
}

// Format date
function format_date($date) {
    return date('M d, Y', strtotime($date));
}

// Format datetime
function format_datetime($datetime) {
    return date('M d, Y - g:i A', strtotime($datetime));
}

// Get time ago
function time_ago($datetime) {
    $time = strtotime($datetime);
    $current = time();
    $seconds = $current - $time;
    
    if ($seconds < 60) {
        return "Just now";
    } elseif ($seconds < 3600) {
        $minutes = floor($seconds / 60);
        return $minutes . " minute" . ($minutes > 1 ? "s" : "") . " ago";
    } elseif ($seconds < 86400) {
        $hours = floor($seconds / 3600);
        return $hours . " hour" . ($hours > 1 ? "s" : "") . " ago";
    } elseif ($seconds < 604800) {
        $days = floor($seconds / 86400);
        return $days . " day" . ($days > 1 ? "s" : "") . " ago";
    } else {
        return date('M d, Y', $time);
    }
}

// Redirect function
function redirect($url) {
    header("Location: $url");
    exit;
}

// Set flash message
function set_flash($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

// Get and clear flash message
function get_flash() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

// Slugify text
function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    
    if (empty($text)) {
        return 'n-a';
    }
    
    return $text;
}

function get_image_url($path) {
    if (empty($path)) {
        return SITE_URL . '/assets/images/placeholder.png';
    }
    return SITE_URL . '/' . $path;
}

// Upload image
function upload_image($file, $destination = 'uploads/products/') {
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5242880; // 5MB
    
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.'];
    }
    
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File size must be less than 5MB.'];
    }
    
    // Create directory if not exists
    if (!file_exists($destination)) {
        mkdir($destination, 0777, true);
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $destination . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filepath' => $filepath, 'filename' => $filename];
    } else {
        return ['success' => false, 'message' => 'Failed to upload file.'];
    }
}

// Delete file
function delete_file($filepath) {
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

// Pagination
function paginate($total_items, $items_per_page, $current_page) {
    $total_pages = ceil($total_items / $items_per_page);
    $offset = ($current_page - 1) * $items_per_page;
    
    return [
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'offset' => $offset,
        'items_per_page' => $items_per_page
    ];
}

// ============================================
// TIMEZONE
// ============================================
date_default_timezone_set('Asia/Yangon');
?>