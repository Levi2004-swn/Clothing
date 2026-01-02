<?php
require_once '../config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'auth' => false, 'message' => 'Login required']);
    exit;
}

$code = strtoupper(trim($_POST['code'] ?? ''));
if ($code === '') {
    echo json_encode(['success' => false, 'message' => 'Coupon code required']);
    exit;
}

$user_id = get_user_id();

// Pull cart summary from session (authoritative for totals prior to discount)
$cart_summary = $_SESSION['cart_summary'] ?? [];
$subtotal = (float)($cart_summary['subtotal'] ?? 0);
$tax_amount = (float)($cart_summary['tax_amount'] ?? 0);
$shipping_amount = (float)($cart_summary['shipping_amount'] ?? 0);
$total = (float)($cart_summary['total'] ?? 0);

if ($total <= 0) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty']);
    exit;
}

// Detect if orders has coupon tracking columns to avoid SQL errors in environments without schema updates
$hasDiscountCol = false; $hasCouponCol = false; $canTrackUsage = false;
if ($conn) {
    $res1 = $conn->query("SHOW COLUMNS FROM orders LIKE 'discount_amount'");
    if ($res1 && $res1->num_rows > 0) { $hasDiscountCol = true; }
    $res2 = $conn->query("SHOW COLUMNS FROM orders LIKE 'coupon_id'");
    if ($res2 && $res2->num_rows > 0) { $hasCouponCol = true; }
    $canTrackUsage = ($hasDiscountCol && $hasCouponCol);
}

// Fetch coupon (with usage_count only when tracking columns exist)
if ($canTrackUsage) {
    $stmt = $conn->prepare("SELECT c.*, 
        COALESCE((SELECT COUNT(*) FROM orders WHERE coupon_id = c.coupon_id AND discount_amount > 0),0) AS usage_count
        FROM coupons c WHERE c.code = ? LIMIT 1");
    $stmt->bind_param('s', $code);
    $stmt->execute();
    $coupon = $stmt->get_result()->fetch_assoc();
} else {
    $stmt = $conn->prepare("SELECT c.* FROM coupons c WHERE c.code = ? LIMIT 1");
    $stmt->bind_param('s', $code);
    $stmt->execute();
    $coupon = $stmt->get_result()->fetch_assoc();
    if ($coupon) { $coupon['usage_count'] = 0; }
}

if (!$coupon) {
    echo json_encode(['success' => false, 'message' => 'Coupon not found']);
    exit;
}

// Enforce per-customer, per-code one-time usage only when tracking columns exist
if ($canTrackUsage) {
    $usedStmt = $conn->prepare("SELECT COUNT(*) AS c FROM orders WHERE user_id = ? AND coupon_id = ? AND discount_amount > 0");
    $cid = (int)$coupon['coupon_id'];
    $usedStmt->bind_param('ii', $user_id, $cid);
    $usedStmt->execute();
    $usedRow = $usedStmt->get_result()->fetch_assoc();
    if ((int)($usedRow['c'] ?? 0) > 0) {
        echo json_encode(['success' => false, 'message' => 'You\'ve already used this coupon code. Watch your email for a new code.']);
        exit;
    }
}

// Validation checks
$isExpired = strtotime($coupon['expires_at']) < time();
$limitReached = ($coupon['usage_limit'] > 0 && ($coupon['usage_count'] ?? 0) >= $coupon['usage_limit']);
$isActive = (int)$coupon['is_active'] === 1;
$minPurchase = (float)$coupon['min_purchase_amount'];
$maxDiscountCap = (float)$coupon['max_discount_amount'];
$discountType = $coupon['discount_type'];
$discountValue = (float)$coupon['discount_value'];

if (!$isActive) {
    echo json_encode(['success' => false, 'message' => 'Coupon inactive']);
    exit;
}
if ($isExpired) {
    echo json_encode(['success' => false, 'message' => 'Coupon expired']);
    exit;
}
if ($limitReached) {
    echo json_encode(['success' => false, 'message' => 'Usage limit reached']);
    exit;
}
if ($minPurchase > 0 && $subtotal < $minPurchase) {
    echo json_encode(['success' => false, 'message' => 'Minimum purchase not met']);
    exit;
}

// Calculate discount
$rawDiscount = 0.0;
if ($discountType === 'percentage') {
    $rawDiscount = ($subtotal * ($discountValue / 100.0));
    if ($maxDiscountCap > 0 && $rawDiscount > $maxDiscountCap) {
        $rawDiscount = $maxDiscountCap; // cap percentage discount
    }
} else { // fixed
    $rawDiscount = $discountValue;
}

$discount_amount = max(0, min($rawDiscount, $total));
$new_total = max(0, $total - $discount_amount);

// Persist in session for checkout/order placement usage
$_SESSION['applied_coupon'] = [
    'coupon_id' => (int)$coupon['coupon_id'],
    'code' => $coupon['code'],
    'discount_amount' => $discount_amount,
    'discount_type' => $discountType,
    'discount_value' => $discountValue,
];

echo json_encode([
    'success' => true,
    'message' => 'Coupon applied',
    'code' => $coupon['code'],
    'discount_amount' => number_format($discount_amount, 2, '.', ''),
    'new_total' => number_format($new_total, 2, '.', ''),
    'notice' => 'One-time use per customer. Not available for Cash on Delivery.',
]);
