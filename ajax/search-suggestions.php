<?php
require_once '../config.php';

header('Content-Type: application/json');

$query = clean_input($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$search_term = "%" . $query . "%";

$stmt = $conn->prepare("SELECT p.product_id, p.product_name, p.final_price, pi.image_url
                       FROM products p
                       LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                       WHERE (p.product_name LIKE ? OR p.description LIKE ?) AND p.is_active = 1
                       ORDER BY p.product_name
                       LIMIT 8");
$stmt->bind_param("ss", $search_term, $search_term);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

echo json_encode($products);
?>