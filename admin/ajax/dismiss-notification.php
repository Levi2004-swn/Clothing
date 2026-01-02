<?php
require_once '../config.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!is_admin_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$type = isset($_POST['type']) ? trim((string)$_POST['type']) : '';
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($type === '' || $id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

if (!isset($_SESSION['admin_dismissed']) || !is_array($_SESSION['admin_dismissed'])) {
    $_SESSION['admin_dismissed'] = [];
}
$key = $type . ':' . $id;
$_SESSION['admin_dismissed'][$key] = true;

echo json_encode(['success' => true]);
