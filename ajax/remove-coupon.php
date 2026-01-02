<?php
require_once '../config.php';
header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Clear session coupon
if (isset($_SESSION['applied_coupon'])) {
    unset($_SESSION['applied_coupon']);
}

$cart_summary = $_SESSION['cart_summary'] ?? [];
$total = (float)($cart_summary['total'] ?? 0);

echo json_encode([
    'success' => true,
    'message' => 'Coupon removed',
    'new_total' => number_format($total, 2, '.', ''),
]);
