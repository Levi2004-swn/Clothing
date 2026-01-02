<?php
require_once '../config.php';
require_admin_login();
if (!has_permission('super_admin')) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

function columnExists(mysqli $conn, string $table, string $column): bool {
    $stmt = $conn->prepare('SHOW COLUMNS FROM `' . $table . '` LIKE ?');
    $stmt->bind_param('s', $column);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res && $res->num_rows > 0;
}

$outputs = [];

// Add deleted_at
if (!columnExists($conn, 'users', 'deleted_at')) {
    if ($conn->query('ALTER TABLE `users` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL AFTER `updated_at`')) {
        $outputs[] = 'Added users.deleted_at';
    } else {
        $outputs[] = 'Failed adding users.deleted_at: ' . $conn->error;
    }
}

// Add deleted_by
if (!columnExists($conn, 'users', 'deleted_by')) {
    if ($conn->query('ALTER TABLE `users` ADD COLUMN `deleted_by` INT NULL DEFAULT NULL AFTER `deleted_at`')) {
        $outputs[] = 'Added users.deleted_by';
    } else {
        $outputs[] = 'Failed adding users.deleted_by: ' . $conn->error;
    }
}

// Add deleted_reason
if (!columnExists($conn, 'users', 'deleted_reason')) {
    if ($conn->query('ALTER TABLE `users` ADD COLUMN `deleted_reason` VARCHAR(255) NULL DEFAULT NULL AFTER `deleted_by`')) {
        $outputs[] = 'Added users.deleted_reason';
    } else {
        $outputs[] = 'Failed adding users.deleted_reason: ' . $conn->error;
    }
}

// Index on deleted_at for filtering
if ($conn->query("SHOW INDEX FROM `users` WHERE Key_name = 'idx_users_deleted_at'")->num_rows === 0) {
    if ($conn->query('CREATE INDEX idx_users_deleted_at ON `users` (`deleted_at`)')) {
        $outputs[] = 'Created index idx_users_deleted_at';
    } else {
        $outputs[] = 'Failed creating index idx_users_deleted_at: ' . $conn->error;
    }
}

echo '<pre>' . htmlspecialchars(implode("\n", $outputs) ?: 'No changes needed.') . '</pre>';
?>
