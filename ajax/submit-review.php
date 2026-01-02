<?php
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Please login to submit a review']);
    exit;
}

$product_id = intval($_POST['product_id'] ?? 0);
$rating = intval($_POST['rating'] ?? 0);
$review_title = clean_input($_POST['review_title'] ?? '');
$review_text = clean_input($_POST['review_text'] ?? '');
$user_id = get_user_id();

if ($product_id == 0 || $rating < 1 || $rating > 5 || empty($review_title) || empty($review_text)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
    exit;
}

$stmt = $conn->prepare("SELECT product_id FROM products WHERE product_id = ? AND is_active = 1");
$stmt->bind_param("i", $product_id);
$stmt->execute();
if ($stmt->get_result()->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

$stmt = $conn->prepare("SELECT review_id FROM product_reviews WHERE user_id = ? AND product_id = ?");
$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'You have already reviewed this product']);
    exit;
}

$stmt = $conn->prepare("SELECT o.order_id 
                       FROM orders o
                       JOIN order_items oi ON o.order_id = oi.order_id
                       WHERE o.user_id = ? AND oi.product_id = ? AND o.order_status = 'delivered'");
$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();
$is_verified_purchase = $stmt->get_result()->num_rows > 0;

$stmt = $conn->prepare("INSERT INTO product_reviews (product_id, user_id, rating, review_title, review_text, is_verified_purchase) 
                       VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("iiissi", $product_id, $user_id, $rating, $review_title, $review_text, $is_verified_purchase);

if ($stmt->execute()) {
    $conn->query("UPDATE users SET loyalty_points = loyalty_points + 10 WHERE user_id = $user_id");
    
    echo json_encode([
        'success' => true,
        'message' => 'Review submitted successfully! You earned 10 loyalty points.'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit review']);
}
?>