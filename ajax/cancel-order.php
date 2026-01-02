<?php
require_once '../config.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Please login']);
    exit;
}

$order_id = intval($_POST['order_id'] ?? 0);
$user_id = get_user_id();

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order']);
    exit;
}

// Verify order belongs to user and can be cancelled
$stmt = $conn->prepare("SELECT order_status, payment_status FROM orders WHERE order_id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

// Disallow cancellation once paid
if ($order['payment_status'] === 'paid') {
    echo json_encode(['success' => false, 'message' => 'Paid orders cannot be cancelled. Please request a return when the order arrives.']);
    exit;
}

if ($order['order_status'] !== 'pending') {
    echo json_encode(['success' => false, 'message' => 'Order cannot be cancelled at this stage']);
    exit;
}

// Cancel the order (reverted flow; do not touch cancel_reason to avoid schema dependency)
$stmt = $conn->prepare("UPDATE orders SET order_status = 'cancelled', updated_at = NOW() WHERE order_id = ?");
$stmt->bind_param("i", $order_id);

if ($stmt && $stmt->execute()) {
    // Restore stock for cancelled items using prepared statements and safe checks
    $stmtItems = $conn->prepare("SELECT variant_id, quantity FROM order_items WHERE order_id = ?");
    if ($stmtItems) {
        $stmtItems->bind_param("i", $order_id);
        if ($stmtItems->execute()) {
            $itemsRes = $stmtItems->get_result();
            if ($itemsRes) {
                $stmtUpd = $conn->prepare("UPDATE product_variants SET stock_quantity = stock_quantity + ? WHERE variant_id = ?");
                if ($stmtUpd) {
                    while ($row = $itemsRes->fetch_assoc()) {
                        $variantId = (int)($row['variant_id'] ?? 0);
                        $qty = (int)($row['quantity'] ?? 0);
                        if ($variantId > 0 && $qty > 0) {
                            $stmtUpd->bind_param("ii", $qty, $variantId);
                            $stmtUpd->execute(); // ignore per-item failures
                        }
                    }
                    $stmtUpd->close();
                }
            }
        }
        $stmtItems->close();
    }

    echo json_encode(['success' => true, 'message' => 'Order cancelled successfully']);
} else {
    $err = '';
    if ($stmt && isset($stmt->error) && $stmt->error) { $err = $stmt->error; }
    if (!$err && isset($conn->error) && $conn->error) { $err = $conn->error; }
    echo json_encode(['success' => false, 'message' => 'Failed to cancel order', 'debug' => $err]);
}
?>