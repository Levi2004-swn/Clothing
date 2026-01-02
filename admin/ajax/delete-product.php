<?php
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!is_admin_logged_in() || !has_permission('inventory_manager')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$product_id = intval($_POST['product_id'] ?? 0);

if ($product_id == 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product']);
    exit;
}

// Soft delete
$stmt = $conn->prepare("UPDATE products SET is_active = 0, updated_at = NOW() WHERE product_id = ?");
$stmt->bind_param("i", $product_id);

if ($stmt->execute()) {
    log_admin_activity('delete_product', "Product ID: $product_id");
    
    echo json_encode([
        'success' => true,
        'message' => 'Product deleted successfully'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete product']);
}
?>