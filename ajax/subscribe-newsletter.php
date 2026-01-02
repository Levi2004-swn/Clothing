<?php
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$email = clean_input($_POST['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
    exit;
}

$stmt = $conn->prepare("SELECT subscriber_id FROM newsletter_subscribers WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();

if ($existing) {
    echo json_encode(['success' => false, 'message' => 'This email is already subscribed']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?)");
$stmt->bind_param("s", $email);

if ($stmt->execute()) {
    // Notify admin(s) about new subscriber (non-intrusive: best-effort)
    try {
        // Attempt to get all admin emails (super/admin roles) for broadcast
        $adminEmails = [];
        if (isset($conn) && $conn instanceof mysqli) {
            $resAdmins = $conn->query("SELECT email FROM admins WHERE is_active = 1");
            if ($resAdmins) {
                while ($r = $resAdmins->fetch_assoc()) {
                    if (!empty($r['email']) && filter_var($r['email'], FILTER_VALIDATE_EMAIL)) {
                        $adminEmails[] = $r['email'];
                    }
                }
            }
        }
        // Fallback single address if none collected (keeps current workflow intact)
        if (empty($adminEmails)) {
            $adminEmails[] = 'admin@clothingstore.com'; // existing test/fallback
        }
        $subject = 'New Newsletter Subscription';
        $body    = '<p>A new email has subscribed to the newsletter:</p>'
                 . '<p><strong>' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</strong></p>'
                 . '<p>Subscription Time: ' . date('Y-m-d H:i:s') . '</p>'
                 . '<p>— ' . SITE_NAME . ' System</p>';
        foreach ($adminEmails as $adminEmail) {
            // Suppress errors to avoid breaking JSON response
            @send_email($adminEmail, $subject, $body);
        }
    } catch (Throwable $e) {
        // Silent fail; do not alter workflow
    }

    // Send confirmation email to subscriber (primary requirement)
    try {
        $userSubject = 'Subscription Confirmed - ' . SITE_NAME;
        $userBody = "<p>You've subscribed. You'll get the latest updates on new products and upcoming sales.</p>"
                  . '<p>Thank you for joining our community!</p>'
                  . '<p>— ' . SITE_NAME . ' Team</p>';
        @send_email($email, $userSubject, $userBody);
    } catch (Throwable $e) {
        // Silent fail to keep JSON workflow unchanged
    }

    echo json_encode([
        'success' => true,
        'message' => 'Successfully subscribed to newsletter!'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to subscribe. Please try again.']);
}
?>