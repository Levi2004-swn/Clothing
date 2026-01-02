<?php
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!is_logged_in()) {
    echo json_encode([
        'success' => false,
        'auth_required' => true,
        'message' => 'Please log in or register to continue',
        'login_url' => SITE_URL . '/login.php',
        'register_url' => SITE_URL . '/register.php'
    ]);
    exit;
}

$product_id = intval($_POST['product_id'] ?? 0);
$user_id = get_user_id();

if ($product_id == 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product']);
    exit;
}

$stmt = $conn->prepare("SELECT product_id FROM products WHERE product_id = ? AND is_active = 1");
$stmt->bind_param("i", $product_id);
$stmt->execute();
if ($stmt->get_result()->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

$stmt = $conn->prepare("SELECT wishlist_id FROM wishlist WHERE user_id = ? AND product_id = ?");
$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();

if ($existing) {
    echo json_encode(['success' => false, 'message' => 'Product already in wishlist']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
$stmt->bind_param("ii", $user_id, $product_id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Added to wishlist'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add to wishlist']);
}
?>