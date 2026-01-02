<?php
require_once '../config.php';
require_admin_login();

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in JSON response

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $status = isset($_POST['status']) ? clean_input($_POST['status']) : '';

    $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'returned'];

    if (!$order_id) {
        throw new Exception('Invalid order ID');
    }

    if (!in_array($status, $valid_statuses)) {
        throw new Exception('Invalid status');
    }

    // Update order status (with delivered hook for loyalty coupon email)
    $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
    $stmt->bind_param("si", $status, $order_id);
    if (!$stmt->execute()) {
        throw new Exception('Database error: ' . $stmt->error);
    }

    // If delivered, evaluate loyalty for user and optionally email a random valid coupon
    if ($status === 'delivered') {
        // Fetch order's user
        $ou = $conn->prepare("SELECT user_id FROM orders WHERE order_id = ? LIMIT 1");
        $ou->bind_param("i", $order_id);
        if ($ou->execute()) {
            $resU = $ou->get_result()->fetch_assoc();
            if ($resU) {
                $orderUserId = (int)$resU['user_id'];
                // Count successful delivered & paid orders
                $cntStmt = $conn->prepare("SELECT COUNT(*) AS c FROM orders WHERE user_id = ? AND order_status = 'delivered' AND payment_status = 'paid'");
                $cntStmt->bind_param("i", $orderUserId);
                if ($cntStmt->execute()) {
                    $rowCnt = $cntStmt->get_result()->fetch_assoc();
                    $deliveredPaidCount = (int)($rowCnt['c'] ?? 0);
                    if ($deliveredPaidCount >= 3) {
                        // Select a random valid active coupon not expired and not over usage limit
                        $couponSql = "SELECT c.* FROM coupons c LEFT JOIN (
                            SELECT coupon_id, COUNT(*) AS usage_count FROM orders WHERE discount_amount > 0 GROUP BY coupon_id
                        ) u ON c.coupon_id = u.coupon_id
                        WHERE c.is_active = 1 
                          AND c.expires_at > NOW()
                          AND (c.usage_limit = 0 OR COALESCE(u.usage_count,0) < c.usage_limit)
                        ORDER BY RAND() LIMIT 1";
                        $couponRes = $conn->query($couponSql);
                        if ($couponRes && ($cp = $couponRes->fetch_assoc())) {
                            // Prepare email content
                            $emailStmt = $conn->prepare("SELECT email, full_name FROM users WHERE user_id = ?");
                            $emailStmt->bind_param("i", $orderUserId);
                            if ($emailStmt->execute()) {
                                $uinfo = $emailStmt->get_result()->fetch_assoc();
                                if ($uinfo && !empty($uinfo['email'])) {
                                    // Use existing mail helper if available (simplified inline mail send fallback)
                                    $to = $uinfo['email'];
                                    $subject = 'Thank you! Enjoy a coupon: ' . $cp['code'];
                                    $customerName = isset($uinfo['full_name']) && $uinfo['full_name'] !== '' ? $uinfo['full_name'] : 'Customer';
                                    $discountLine = ($cp['discount_type'] === 'percentage')
                                        ? ($cp['discount_value'] . '%')
                                        : (CURRENCY_SYMBOL . number_format($cp['discount_value'],2));
                                    $minPurchaseVal = isset($cp['min_purchase_amount']) ? (float)$cp['min_purchase_amount'] : 0.0;
                                    $minPurchaseLine = $minPurchaseVal > 0
                                        ? (CURRENCY_SYMBOL . number_format($minPurchaseVal, 2))
                                        : 'None';

                                    $body  = "Hi " . $customerName . "\r\n\r\n";
                                    $body .= "As a thank you for your loyalty, here's a coupon you can use on your next order: " . $cp['code'] . "\r\n\r\n";
                                    $body .= "Coupon details:\r\n";
                                    $body .= "- Type: " . ucfirst($cp['discount_type']) . "\r\n";
                                    $body .= "- Discount: " . $discountLine . "\r\n";
                                    $body .= "- Minimum purchase: " . $minPurchaseLine . "\r\n";
                                    $body .= "- Expiry date: " . $cp['expires_at'] . "\r\n\r\n";
                                    $body .= "Conditions: One-time use per customer. Not valid for Cash on Delivery.\r\n\r\n";
                                    $body .= "Happy shopping!";
                                    // Basic mail(); in production use PHPMailer already present
                                    // Build simple headers (avoid malformed quotes)
                                    $fromDomain = preg_replace("/[^A-Za-z0-9.-]/", '', $_SERVER['SERVER_NAME']);
                                    $fromHeader = 'From: ' . SITE_NAME . ' <no-reply@' . $fromDomain . '>' . "\r\n" . 'Content-Type: text/plain; charset=UTF-8';
                                    @mail($to, $subject, $body, $fromHeader);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    // Log activity
    log_admin_activity('update_order_status', "Updated order #$order_id status to $status");
    
    echo json_encode([
        'success' => true, 
        'message' => 'Order status updated successfully',
        'status' => $status
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>