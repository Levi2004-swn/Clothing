<?php
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$cart_item_id = intval($_POST['cart_item_id'] ?? 0);

if ($cart_item_id == 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid item']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_item_id = ?");
$stmt->bind_param("i", $cart_item_id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Item removed from cart'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to remove item']);
}
?>