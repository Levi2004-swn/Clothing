<?php
require_once '../config.php';
require_admin_login();
if (!has_permission('super_admin')) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$messages = [];

try {
    // Drop existing FK if present
    $conn->query("ALTER TABLE `orders` DROP FOREIGN KEY `orders_ibfk_2`");
    $messages[] = 'Dropped foreign key orders_ibfk_2 on orders.shipping_address_id';
} catch (Throwable $e) {
    $messages[] = 'Note: Could not drop orders_ibfk_2 (maybe not present): ' . $e->getMessage();
}

try {
    // Recreate FK with ON DELETE SET NULL
    $sql = "ALTER TABLE `orders` 
            ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`shipping_address_id`) 
            REFERENCES `user_addresses` (`address_id`) 
            ON DELETE SET NULL";
    if ($conn->query($sql)) {
        $messages[] = 'Recreated orders_ibfk_2 with ON DELETE SET NULL';
    } else {
        $messages[] = 'Failed to create FK with ON DELETE SET NULL: ' . $conn->error;
    }
} catch (Throwable $e) {
    $messages[] = 'Error creating FK: ' . $e->getMessage();
}

echo '<pre>' . htmlspecialchars(implode("\n", $messages)) . '</pre>';
