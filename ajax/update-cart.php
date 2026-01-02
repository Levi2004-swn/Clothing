<?php
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$cart_item_id = intval($_POST['cart_item_id'] ?? 0);
$quantity = intval($_POST['quantity'] ?? 0);

if ($cart_item_id == 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid item']);
    exit;
}

if ($quantity < 1) {
    $stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_item_id = ?");
    $stmt->bind_param("i", $cart_item_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Item removed from cart'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
    }
    exit;
}

$stmt = $conn->prepare("SELECT ci.variant_id, pv.stock_quantity 
                       FROM cart_items ci
                       LEFT JOIN product_variants pv ON ci.variant_id = pv.variant_id
                       WHERE ci.cart_item_id = ?");
$stmt->bind_param("i", $cart_item_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if ($item && $item['variant_id'] && $item['stock_quantity'] < $quantity) {
    echo json_encode([
        'success' => false,
        'message' => 'Only ' . $item['stock_quantity'] . ' items available in stock'
    ]);
    exit;
}

$stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?");
$stmt->bind_param("ii", $quantity, $cart_item_id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Cart updated'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
}
?>