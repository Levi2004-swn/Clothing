<?php
require_once 'config.php';

// Require login
if (!is_logged_in()) {
    header('Location: ' . SITE_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$page_title = 'Request Return - ' . SITE_NAME;
$user_id = get_user_id();
$error = '';
$success = '';

// Get order id from query or post
$order_id = isset($_GET['order']) ? intval($_GET['order']) : intval($_POST['order_id'] ?? 0);
if ($order_id <= 0) {
    header('Location: ' . SITE_URL . '/orders.php');
    exit;
}

// Fetch order, ensure it belongs to user and is paid + delivered
$stmt = $conn->prepare("SELECT order_id, user_id, total_amount, payment_status, order_status, created_at, updated_at FROM orders WHERE order_id = ? AND user_id = ?");
$stmt->bind_param('ii', $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: ' . SITE_URL . '/orders.php');
    exit;
}

// Basic eligibility checks
if ($order['payment_status'] !== 'paid' || $order['order_status'] !== 'delivered') {
    $error = 'Only delivered and paid orders are eligible for return requests.';
}

// Compute days since payment; we lack a dedicated paid_at column, so use updated_at as best-effort fallback
$updatedAt = new DateTime($order['updated_at'] ?? $order['created_at']);
$now = new DateTime();
$daysSincePayment = (int)$updatedAt->diff($now)->format('%a');
if (!$error && $daysSincePayment > 30) {
    $error = 'Return window has expired. Returns are allowed within 30 days of payment.';
}

// Prevent duplicate pending requests
if (!$error) {
    $stmt = $conn->prepare('SELECT return_id, status FROM return_requests WHERE order_id = ? AND user_id = ? AND status IN (\'pending\', \'approved\')');
    $stmt->bind_param('ii', $order_id, $user_id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    if ($existing) {
        $error = 'A return/exchange request already exists for this order.';
    }
}

// Handle submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $request_type = in_array(($_POST['request_type'] ?? 'return'), ['return','exchange'], true) ? $_POST['request_type'] : 'return';
    $reason = trim($_POST['reason'] ?? '');
    if ($reason === '') {
        $error = 'Please provide a reason for your request.';
    } else {
        $stmt = $conn->prepare('INSERT INTO return_requests (order_id, user_id, request_type, reason, status) VALUES (?, ?, ?, ?, \'pending\')');
        $stmt->bind_param('iiss', $order_id, $user_id, $request_type, $reason);
        if ($stmt->execute()) {
            // Also append a note on the order for admin visibility (non-breaking)
            $note = '[' . date('Y-m-d H:i:s') . "] Return request by user #{$user_id}: {$request_type} - " . $reason;
            $stmt2 = $conn->prepare('UPDATE orders SET notes = CONCAT(COALESCE(notes, \'\'), ?) WHERE order_id = ?');
            $stmt2->bind_param('si', $note, $order_id);
            $stmt2->execute();

            $success = 'Your request has been submitted. Our team will review it shortly.';
        } else {
            $error = 'Failed to submit request. Please try again later.';
        }
    }
}

include 'header.php';
?>

<div class="container" style="margin-top:20px; max-width: 720px;">
    <div class="card" style="padding: 20px;">
        <h2 style="margin-bottom: 10px;">Request a Return</h2>
        <p style="color:#666; margin-top:0;">Order #<?php echo htmlspecialchars((string)$order_id); ?> • Total: <?php echo htmlspecialchars(number_format((float)$order['total_amount'], 2)); ?></p>

        <?php if ($error): ?>
            <div class="alert alert-error" style="margin: 10px 0;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success" style="margin: 10px 0;"><?php echo htmlspecialchars($success); ?></div>
            <div style="margin-top:15px;">
                <a class="btn btn-primary" href="<?php echo SITE_URL; ?>/order-details.php?id=<?php echo (int)$order_id; ?>">Back to Order</a>
                <a class="btn" href="<?php echo SITE_URL; ?>/orders.php" style="margin-left:8px;">My Orders</a>
            </div>
        <?php else: ?>
            <form method="post">
                <input type="hidden" name="order_id" value="<?php echo (int)$order_id; ?>" />
                <div class="form-group" style="margin-bottom:12px;">
                    <label for="request_type">Request type</label>
                    <select id="request_type" name="request_type" class="form-control" required>
                        <option value="return">Return</option>
                        <option value="exchange">Exchange</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:12px;">
                    <label for="reason">Reason</label>
                    <textarea id="reason" name="reason" class="form-control" rows="4" placeholder="Tell us what went wrong" required></textarea>
                </div>
                <p style="font-size: 0.9rem; color:#666;">Note: Returns are allowed only on specific conditions with valid reason within 30 days of payment for delivered and paid orders.</p>
                <button type="submit" class="btn btn-primary">Submit Request</button>
                <a class="btn" href="<?php echo SITE_URL; ?>/order-details.php?id=<?php echo (int)$order_id; ?>" style="margin-left:8px;">Cancel</a>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
