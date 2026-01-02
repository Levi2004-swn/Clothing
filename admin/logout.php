<?php
require_once 'config.php';

if (is_admin_logged_in()) {
    $admin_id = get_admin_id();
    $ip = $_SERVER['REMOTE_ADDR'];
    $conn->query("INSERT INTO admin_logs (admin_id, action, ip_address) VALUES ($admin_id, 'logout', '$ip')");
}

// Destroy session and clear cookie on the same path used by the app
if (session_status() === PHP_SESSION_ACTIVE) {
    // Get cookie params to unset cookie correctly
    $params = session_get_cookie_params();
    $cookiePath = $params['path'] ?? '/clothing';
    $cookieDomain = $params['domain'] ?? '';
    $secure = $params['secure'] ?? false;
    $httponly = $params['httponly'] ?? true;
    // Invalidate cookie
    setcookie(session_name(), '', time() - 3600, $cookiePath, $cookieDomain, $secure, $httponly);
    session_destroy();
}
header('Location: login.php');
exit;
?>