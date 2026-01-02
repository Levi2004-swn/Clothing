<?php
require_once '../config.php';
require_admin_login(); // Protect this endpoint

header('Content-Type: application/json');

// Only POST requests allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request method'
    ]);
    exit;
}

$user_id = intval($_POST['user_id'] ?? 0);
$status = intval($_POST['status'] ?? 0);

// Validate user ID
if (!$user_id) {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid user ID'
    ]);
    exit;
}

// Prevent blocking yourself
if (isset($_SESSION['admin_id']) && $user_id == $_SESSION['admin_id']) {
    echo json_encode([
        'success' => false, 
        'message' => 'You cannot block yourself'
    ]);
    exit;
}

// Update user status
$stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE user_id = ?");
$stmt->bind_param("ii", $status, $user_id);

if ($stmt->execute()) {
    $action = $status ? 'unblocked' : 'blocked';
    echo json_encode([
        'success' => true, 
        'message' => "User has been {$action} successfully"
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to update user status'
    ]);
}

$stmt->close();
$conn->close();
?>