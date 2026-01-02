<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = get_user_id();

// We will compile two categories:
// 1. Return request status changes (approved/rejected/completed) in last 30 days
// 2. Order status/payment updates for this user's orders where either payment != paid OR order_status != delivered

$items = [];

// 1. Return request updates (existing logic)
$sql = "SELECT rr.return_id, rr.status, rr.updated_at, rr.order_id, o.order_number
                FROM return_requests rr
                JOIN orders o ON rr.order_id = o.order_id
                WHERE rr.user_id = ?
                    AND rr.status IN ('approved','rejected','completed')
                    AND rr.updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ORDER BY rr.updated_at DESC
                LIMIT 10";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $status = $row['status'];
    $order_number = $row['order_number'];
    $message = '';
    if ($status === 'approved') {
        $message = "Return request for Order #{$order_number} was approved";
    } elseif ($status === 'completed') {
        $message = "Return for Order #{$order_number} was completed";
    } else {
        $message = "Return request for Order #{$order_number} was rejected";
    }

    $items[] = [
        'id' => (int)$row['return_id'],
        'status' => $status,
        'order_id' => (int)$row['order_id'],
        'order_number' => $order_number,
        'updated_at' => date('M j, g:i A', strtotime($row['updated_at'])),
        'message' => $message,
        'href' => SITE_URL . '/order-details.php?id=' . (int)$row['order_id'],
        'type' => 'return'
    ];
}

// 2. Outstanding order notifications (orders not both paid and delivered)
$sql2 = "SELECT order_id, order_number, payment_status, order_status, updated_at, created_at
          FROM orders
          WHERE user_id = ? AND (payment_status <> 'paid' OR order_status <> 'delivered')
          ORDER BY updated_at DESC, created_at DESC
          LIMIT 10";
$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param('i', $user_id);
$stmt2->execute();
$res2 = $stmt2->get_result();
while ($row = $res2->fetch_assoc()) {
    $order_number = $row['order_number'];
    $ps = $row['payment_status'];
    $os = $row['order_status'];
    // Build a concise message describing what's pending
    $parts = [];
    if ($ps !== 'paid') $parts[] = 'payment pending';
    if ($os !== 'delivered') $parts[] = 'status: ' . ucfirst($os);
    $msg = 'Order #' . $order_number . ' ' . implode(', ', $parts);
    $items[] = [
        'id' => (int)$row['order_id'],
        'order_id' => (int)$row['order_id'],
        'order_number' => $order_number,
        'payment_status' => $ps,
        'order_status' => $os,
        'updated_at' => date('M j, g:i A', strtotime($row['updated_at'] ?? $row['created_at'])),
        'message' => $msg,
        'href' => SITE_URL . '/order-details.php?id=' . (int)$row['order_id'],
        'type' => 'order'
    ];
}

echo json_encode([
    'success' => true,
    'count' => count($items),
    'items'  => $items,
]);
