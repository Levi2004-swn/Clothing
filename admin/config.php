<?php
// Include main config
require_once '../config.php';

// ============================================
// ADMIN AUTHENTICATION FUNCTIONS
// ============================================

// Check if admin is logged in
function is_admin_logged_in() {
    return isset($_SESSION['admin_id']);
}

// Get admin ID
function get_admin_id() {
    return $_SESSION['admin_id'] ?? null;
}

// Get admin role
function get_admin_role() {
    return $_SESSION['admin_role'] ?? null;
}

// Get admin username
function get_admin_username() {
    return $_SESSION['admin_username'] ?? 'Admin';
}

// Get admin email
function get_admin_email() {
    return $_SESSION['admin_email'] ?? '';
}

// Redirect if not logged in
function require_admin_login() {
    if (!is_admin_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

// Check admin permissions
function has_permission($required_role) {
    $role = get_admin_role();
    $role_hierarchy = [
        'staff' => 1,
        'inventory_manager' => 2,
        'super_admin' => 3
    ];
    
    $current_level = $role_hierarchy[$role] ?? 0;
    $required_level = $role_hierarchy[$required_role] ?? 999;
    
    return $current_level >= $required_level;
}

// Admin activity log
function log_admin_activity($action, $details = '') {
    global $conn;
    $admin_id = get_admin_id();
    
    if (!$admin_id) return false;
    
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $admin_id, $action, $details, $ip_address);
    return $stmt->execute();
}

// Get admin data
function get_admin_data($conn, $admin_id) {
    $stmt = $conn->prepare("SELECT admin_id, username, email, role FROM admins WHERE admin_id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Get all admins
function get_all_admins($conn) {
    $admins = [];
    $result = $conn->query("SELECT admin_id, username, email, role, is_active, last_login, created_at FROM admins ORDER BY created_at DESC");
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
    return $admins;
}

// Get recent activity logs
function get_recent_logs($conn, $limit = 10) {
    $logs = [];
    $query = "SELECT al.*, a.username 
              FROM admin_logs al 
              JOIN admins a ON al.admin_id = a.admin_id 
              ORDER BY al.created_at DESC 
              LIMIT $limit";
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    return $logs;
}

// Dashboard statistics
function get_dashboard_stats($conn) {
    $stats = [];
    
    // Total revenue
    $result = $conn->query("SELECT SUM(total_amount) as revenue FROM orders WHERE order_status != 'cancelled'");
    $stats['total_revenue'] = $result->fetch_assoc()['revenue'] ?? 0;
    
    // Total orders
    $result = $conn->query("SELECT COUNT(*) as count FROM orders");
    $stats['total_orders'] = $result->fetch_assoc()['count'];
    
    // Total users
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_active = 1");
    $stats['total_users'] = $result->fetch_assoc()['count'];
    
    // Total products
    $result = $conn->query("SELECT COUNT(*) as count FROM products WHERE is_active = 1");
    $stats['total_products'] = $result->fetch_assoc()['count'];
    
    // Today's revenue
    $result = $conn->query("SELECT SUM(total_amount) as revenue FROM orders WHERE DATE(created_at) = CURDATE() AND order_status != 'cancelled'");
    $stats['today_revenue'] = $result->fetch_assoc()['revenue'] ?? 0;
    
    // Today's orders
    $result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()");
    $stats['today_orders'] = $result->fetch_assoc()['count'];
    
    // Pending orders
    $result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE order_status = 'pending'");
    $stats['pending_orders'] = $result->fetch_assoc()['count'];
    
    // Low stock products
    $result = $conn->query("SELECT COUNT(*) as count FROM product_variants WHERE stock_quantity < 10");
    $stats['low_stock'] = $result->fetch_assoc()['count'];
    
    return $stats;
}
?>