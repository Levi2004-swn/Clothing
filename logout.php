<?php
require_once 'config.php';

// Destroy session
session_destroy();

// Clear remember me cookie if exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, "/");
}

// Redirect to login page with success message
header('Location: ' . SITE_URL . '/login.php?logout=1');
exit;
?>