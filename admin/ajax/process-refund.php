<?php
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!is_admin_logged_in() || !has_permission('super_admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$order_id = intval($_POST['order_id'] ?? 0);
$amount = floatval($_POST['amount'] ?? 0);

if ($order_id == 0 || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Get order details (include timestamps for 30-day window check)
$stmt = $conn->prepare("SELECT total_amount, payment_status, order_status, created_at, updated_at FROM orders WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

if ($order['payment_status'] != 'paid') {
    echo json_encode(['success' => false, 'message' => 'Order is not paid']);
    exit;
}

// Enforce 30-day refund window from time of payment. We don't track a dedicated paid_at; use updated_at as best-effort.
try {
    $updatedAt = new DateTime($order['updated_at'] ?? $order['created_at']);
    $now = new DateTime();
    $days = (int)$updatedAt->diff($now)->format('%a');
    if ($days > 30) {
        echo json_encode(['success' => false, 'message' => 'Refund window expired (30 days after payment).']);
        exit;
    }
} catch (Throwable $e) {
    // If parsing fails, fail closed to avoid unintended refunds
    echo json_encode(['success' => false, 'message' => 'Unable to verify refund window']);
    exit;
}

if ($amount > $order['total_amount']) {
    echo json_encode(['success' => false, 'message' => 'Refund amount exceeds order total']);
    exit;
}

// Process refund (In production, integrate with payment gateway)
$stmt = $conn->prepare("UPDATE orders SET payment_status = 'refunded', order_status = 'cancelled', updated_at = NOW() WHERE order_id = ?");
$stmt->bind_param("i", $order_id);

if ($stmt->execute()) {
    // Log refund
    $stmt = $conn->prepare("INSERT INTO refunds (order_id, amount, reason, processed_by) VALUES (?, ?, 'Admin refund', ?)");
    $admin_id = get_admin_id();
    $reason = "Refund processed by admin";
    $stmt->bind_param("idi", $order_id, $amount, $admin_id);
    $stmt->execute();
    
    log_admin_activity('process_refund', "Order ID: $order_id, Amount: $amount");
    
    echo json_encode([
        'success' => true,
        'message' => 'Refund processed successfully'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to process refund']);
}
?>