<?php
require_once '../config.php';
// Ensure root config is loaded as well (defines $conn). If $conn is missing, include it directly.
if (!isset($conn) || !($conn instanceof mysqli)) {
    $rootConfig = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'config.php'; // admin/config.php
    if (file_exists($rootConfig)) {
        require_once $rootConfig;
    }
    if (!isset($conn) || !($conn instanceof mysqli)) {
        $siteRootConfig = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config.php'; // /clothing/config.php
        if (file_exists($siteRootConfig)) {
            require_once $siteRootConfig;
        }
    }
}

// Ensure session is active even if PHPSESSID cookie exists but session not started yet
if (session_status() === PHP_SESSION_NONE && isset($_COOKIE[session_name()])) {
    @session_start();
}
header('Content-Type: application/json; charset=UTF-8');
// Make sure PHP notices/warnings don't leak HTML into JSON
@ini_set('display_errors', '0');
@ini_set('log_errors', '1');
// Buffer output to strip any stray output before JSON
ob_start();

try {
    if (!is_admin_logged_in()) {
        // Debug payload (temporary). Remove once session issue resolved.
        $dbg = [
            'session_id' => session_id(),
            'session_keys' => array_keys($_SESSION ?? []),
            'cookie' => $_COOKIE['PHPSESSID'] ?? null,
            'path_info' => $_SERVER['REQUEST_URI'] ?? '',
        ];
        // Clear any stray output from included files
        if (ob_get_length()) { ob_clean(); }
        echo json_encode(['success' => false, 'message' => 'Unauthorized', 'debug' => $dbg]);
        exit;
    }

    // Aggregate notifications mirroring customer-side logic
    // Categories:
    // 1) Return status changes (approved/rejected/completed) in last 30 days
    // 2) Outstanding orders (payment != paid OR order_status != delivered)
    // Admin-specific extras kept: new users/subscribers (last hour), cancelled (last hour), paid (last hour)

    if (!isset($conn) || !($conn instanceof mysqli)) {
        // Graceful no-DB fallback: return empty notifications rather than error
        if (ob_get_length()) { ob_clean(); }
        echo json_encode([
            'success' => true,
            'count' => 0,
            'items' => []
        ]);
        exit;
    }

    $items = [];

    // 1) Return status changes (keep 30-day window)
    $sql = "SELECT rr.return_id, rr.status, rr.updated_at, rr.order_id, o.order_number, u.email
            FROM return_requests rr
            JOIN orders o ON rr.order_id = o.order_id
            JOIN users u ON rr.user_id = u.user_id
            WHERE rr.status IN ('approved','rejected','completed')
              AND rr.updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY rr.updated_at DESC
            LIMIT 10";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $status = $row['status'];
            $orderNo = $row['order_number'];
            $msg = $status === 'approved'
                ? "Return request for Order #{$orderNo} was approved"
                : ($status === 'completed'
                    ? "Return for Order #{$orderNo} was completed"
                    : "Return request for Order #{$orderNo} was rejected");
            $items[] = [
                'id' => (int)$row['return_id'],
                'type' => 'return_update',
                'email' => $row['email'],
                'message' => $msg,
                'created_at' => date('M j, g:i A', strtotime($row['updated_at'])),
                'ts' => strtotime($row['updated_at']),
                'href' => SITE_URL . '/admin/order-details.php?id=' . (int)$row['order_id'],
                'order_id' => (int)$row['order_id'],
                'order_number' => $orderNo,
                'status' => $status,
            ];
        }
    }

    // 2) Outstanding orders - restrict to last 24h
    $sql2 = "SELECT o.order_id, o.order_number, o.payment_status, o.order_status, o.created_at, o.updated_at, u.email
             FROM orders o
             JOIN users u ON o.user_id = u.user_id
             WHERE (o.payment_status <> 'paid' OR o.order_status <> 'delivered')
               AND (o.updated_at >= (NOW() - INTERVAL 1 DAY) OR o.created_at >= (NOW() - INTERVAL 1 DAY))
             ORDER BY o.updated_at DESC, o.created_at DESC
             LIMIT 10";
    $result2 = $conn->query($sql2);
    if ($result2) {
        while ($row = $result2->fetch_assoc()) {
            $parts = [];
            if ($row['payment_status'] !== 'paid') $parts[] = 'payment pending';
            if ($row['order_status'] !== 'delivered') $parts[] = 'status: ' . ucfirst($row['order_status']);
            $msg = 'Order #' . $row['order_number'] . ' ' . implode(', ', $parts);
            $items[] = [
                'id' => (int)$row['order_id'],
                'type' => 'order_outstanding',
                'email' => $row['email'],
                'message' => $msg,
                'created_at' => date('M j, g:i A', strtotime($row['updated_at'] ?: $row['created_at'])),
                'ts' => strtotime($row['updated_at'] ?: $row['created_at']),
                'href' => SITE_URL . '/admin/order-details.php?id=' . (int)$row['order_id'],
                'order_id' => (int)$row['order_id'],
                'order_number' => $row['order_number'],
                'order_status' => $row['order_status'],
                'payment_status' => $row['payment_status'],
            ];
        }
    }

    // Admin-specific extras (optional but useful)
    // New users in last hour
    $sql3 = "SELECT user_id, email, created_at
             FROM users
             WHERE created_at >= (NOW() - INTERVAL 1 HOUR)
             ORDER BY created_at DESC
             LIMIT 10";
    $result3 = $conn->query($sql3);
    if ($result3) {
        while ($row = $result3->fetch_assoc()) {
            $items[] = [
                'id' => (int)$row['user_id'],
                'type' => 'user_new',
                'email' => $row['email'],
                'message' => 'New registration: ' . $row['email'],
                'created_at' => date('M j, g:i A', strtotime($row['created_at'])),
                'ts' => strtotime($row['created_at']),
                'href' => SITE_URL . '/admin/users.php?search=' . urlencode($row['email']),
            ];
        }
    }

    // Newsletter subscribers in last hour
    $sql4 = "SELECT subscriber_id, email, subscribed_at FROM newsletter_subscribers
             WHERE subscribed_at >= (NOW() - INTERVAL 1 HOUR)
             ORDER BY subscribed_at DESC
             LIMIT 10";
    $result4 = $conn->query($sql4);
    if ($result4) {
        while ($row = $result4->fetch_assoc()) {
            $items[] = [
                'id' => (int)$row['subscriber_id'],
                'type' => 'subscriber_new',
                'email' => $row['email'],
                'message' => 'Joined newsletter: ' . $row['email'],
                'created_at' => date('M j, g:i A', strtotime($row['subscribed_at'])),
                'ts' => strtotime($row['subscribed_at']),
                'href' => SITE_URL . '/admin/content.php#newsletter',
            ];
        }
    }

    // Recently cancelled orders (last hour)
    $sql5 = "SELECT o.order_id, o.order_number, o.payment_status, o.order_status, o.created_at, o.updated_at, u.email
             FROM orders o
             JOIN users u ON o.user_id = u.user_id
             WHERE o.order_status = 'cancelled' AND (o.updated_at >= (NOW() - INTERVAL 1 HOUR) OR o.created_at >= (NOW() - INTERVAL 1 HOUR))
             ORDER BY o.updated_at DESC, o.created_at DESC
             LIMIT 10";
    $result5 = $conn->query($sql5);
    if ($result5) {
        while ($row = $result5->fetch_assoc()) {
            $items[] = [
                'id' => (int)$row['order_id'],
                'type' => 'order_cancel',
                'email' => $row['email'],
                'message' => 'Order #' . $row['order_number'] . ' was cancelled',
                'created_at' => date('M j, g:i A', strtotime($row['updated_at'] ?: $row['created_at'])),
                'ts' => strtotime($row['updated_at'] ?: $row['created_at']),
                'href' => SITE_URL . '/admin/order-details.php?id=' . (int)$row['order_id'],
                'order_id' => (int)$row['order_id'],
                'order_number' => $row['order_number'],
                'order_status' => $row['order_status'],
                'payment_status' => $row['payment_status'],
            ];
        }
    }

    // Recently successful orders (last hour): payment received
    $sql6 = "SELECT o.order_id, o.order_number, o.payment_status, o.order_status, o.created_at, o.updated_at, u.email
             FROM orders o
             JOIN users u ON o.user_id = u.user_id
             WHERE o.payment_status = 'paid' AND o.created_at >= (NOW() - INTERVAL 1 HOUR)
             ORDER BY o.created_at DESC
             LIMIT 10";
    $result6 = $conn->query($sql6);
    if ($result6) {
        while ($row = $result6->fetch_assoc()) {
            $items[] = [
                'id' => (int)$row['order_id'],
                'type' => 'order_success',
                'email' => $row['email'],
                'message' => 'Order #' . $row['order_number'] . ' payment received',
                'created_at' => date('M j, g:i A', strtotime($row['created_at'])),
                'ts' => strtotime($row['created_at']),
                'href' => SITE_URL . '/admin/order-details.php?id=' . (int)$row['order_id'],
                'order_id' => (int)$row['order_id'],
                'order_number' => $row['order_number'],
                'order_status' => $row['order_status'],
                'payment_status' => $row['payment_status'],
            ];
        }
    }

    // Sort newest first (by ts) for recent notifications
    usort($items, function($a,$b){ return ($b['ts'] ?? 0) <=> ($a['ts'] ?? 0); });

    // Clear any stray output and emit final JSON
    if (ob_get_length()) { ob_clean(); }
    echo json_encode([
        'success' => true,
        'count' => count($items),
        'items' => $items
    ]);
} catch (Throwable $e) {
    // Ensure JSON output even on fatal issues
    http_response_code(500);
    if (ob_get_length()) { ob_clean(); }
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error' => $e->getMessage()
    ]);
}
