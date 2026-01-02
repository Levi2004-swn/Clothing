<?php
require_once 'config.php';

// Only super admins can delete users
require_admin_login();
if (!has_permission('super_admin')) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Forbidden: You do not have permission to delete users.';
    exit;
}

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($user_id <= 0) {
    header('Location: users.php');
    exit;
}

// Verify user exists
$stmt = $conn->prepare('SELECT email FROM users WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user) {
    header('Location: users.php');
    exit;
}

// Optional reason
// Begin transaction for HARD delete + cascading cleanup
$conn->begin_transaction();
try {
    // 1) Wishlist
    $stmt = $conn->prepare('DELETE FROM wishlist WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();

    // 2) Password reset tokens
    $stmt = $conn->prepare('DELETE FROM password_resets WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();

    // 3) Cart items and carts (via JOIN to avoid IN lists)
    $stmt = $conn->prepare('DELETE ci FROM cart_items ci INNER JOIN cart c ON ci.cart_id = c.cart_id WHERE c.user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();

    $stmt = $conn->prepare('DELETE FROM cart WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();

    // 4) Order items and orders (remove business records as part of hard-delete)
    $stmt = $conn->prepare('DELETE oi FROM order_items oi INNER JOIN orders o ON oi.order_id = o.order_id WHERE o.user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();

    $stmt = $conn->prepare('DELETE FROM orders WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();

    // 5) User addresses (if table exists)
    $table_check = $conn->query("SHOW TABLES LIKE 'user_addresses'");
    if ($table_check && $table_check->num_rows > 0) {
        $stmt = $conn->prepare('DELETE FROM user_addresses WHERE user_id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
    }

    // 6) Finally delete the user
    $stmt = $conn->prepare('DELETE FROM users WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();

    // Log admin activity
    log_admin_activity('delete_user', 'Hard-deleted user: ' . ($user['email'] ?? ('ID ' . $user_id)));

    $conn->commit();
    header('Location: users.php?deleted=1');
    exit;
} catch (Throwable $e) {
    $conn->rollback();
    header('Location: users.php?deleted=0');
    exit;
}
?>
