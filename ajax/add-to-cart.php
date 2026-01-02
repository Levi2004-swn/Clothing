<?php
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$loginRequiredResponse = function() {
    echo json_encode([
        'success' => false,
        'auth_required' => true,
        'message' => 'Please log in or register to continue',
        'login_url' => SITE_URL . '/login.php',
        'register_url' => SITE_URL . '/register.php'
    ]);
    exit;
};

$isLogged = is_logged_in();

$product_id = intval($_POST['product_id'] ?? 0);
$variant_id = isset($_POST['variant_id']) ? intval($_POST['variant_id']) : null;
$quantity = intval($_POST['quantity'] ?? 1);
// Optional size/color from product page when using selects instead of explicit variant radios
$size = isset($_POST['size']) ? trim($_POST['size']) : null;
$color = isset($_POST['color']) ? trim($_POST['color']) : null;

if ($product_id == 0 || $quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

$stmt = $conn->prepare("SELECT final_price FROM products WHERE product_id = ? AND is_active = 1");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

// If product has variants, require a variant to be selected (or derive from size/color)
$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM product_variants WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$variantsInfo = $stmt->get_result()->fetch_assoc();
if (($variantsInfo['cnt'] ?? 0) > 0 && !$variant_id) {
    // Try to derive variant from provided size/color
    if (!empty($size) || !empty($color)) {
        // Require both size and color when both dimensions exist in catalog; try exact match
        $query = "SELECT variant_id, stock_quantity, size, color
                  FROM product_variants
                  WHERE product_id = ?";
        $types = "i";
        $binds = [$product_id];
        if (!empty($size)) { $query .= " AND size = ?"; $types .= "s"; $binds[] = $size; }
        if (!empty($color)) { $query .= " AND color = ?"; $types .= "s"; $binds[] = $color; }
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$binds);
        $stmt->execute();
        $found = $stmt->get_result()->fetch_assoc();
        if ($found) {
            $variant_id = intval($found['variant_id']);
            // Now validate stock via the standard path below
        } else {
            $msg = 'no variant';
            if (!empty($size)) { $msg .= ' for ' . $size . ' size'; }
            if (!empty($color)) { $msg .= ' for ' . $color . ' color'; }
            echo json_encode(['success' => false, 'message' => $msg]);
            exit;
        }
    } else {
        echo json_encode([
            'success' => false,
            'variant_required' => true,
            'message' => 'No stock available for selected options'
        ]);
        exit;
    }
}

if ($variant_id) {
    // Validate variant belongs to product and check stock with detailed message
    $stmt = $conn->prepare("SELECT stock_quantity, size, color FROM product_variants WHERE variant_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $variant_id, $product_id);
    $stmt->execute();
    $variant = $stmt->get_result()->fetch_assoc();

    if (!$variant) {
        echo json_encode(['success' => false, 'message' => 'Variant not found']);
        exit;
    }

    $stockQty = intval($variant['stock_quantity'] ?? 0);
    $vSize = trim((string)($variant['size'] ?? ''));
    $vColor = trim((string)($variant['color'] ?? ''));

    if ($stockQty <= 0) {
        // Exact phrasing requested: "no stock for x size for y color"
        $msg = 'no stock';
        if ($vSize !== '') { $msg .= ' for ' . $vSize . ' size'; }
        if ($vColor !== '') { $msg .= ' for ' . $vColor . ' color'; }
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }

    if ($stockQty < $quantity) {
        // More helpful when requested qty exceeds available
        $msg = 'Only ' . $stockQty . ' in stock';
        if ($vSize !== '') { $msg .= ' for ' . $vSize . ' size'; }
        if ($vColor !== '') { $msg .= ' for ' . $vColor . ' color'; }
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }
}

// Require login for add-to-cart per requirement
if ($isLogged) {
    $user_id = get_user_id();
    $stmt = $conn->prepare("SELECT cart_id FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart = $stmt->get_result()->fetch_assoc();
    
    if (!$cart) {
        $stmt = $conn->prepare("INSERT INTO cart (user_id) VALUES (?)");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $cart_id = $stmt->insert_id;
    } else {
        $cart_id = $cart['cart_id'];
    }
} else {
    $loginRequiredResponse();
}

$stmt = $conn->prepare("SELECT cart_item_id, quantity FROM cart_items 
                       WHERE cart_id = ? AND product_id = ? AND (variant_id = ? OR (variant_id IS NULL AND ? IS NULL))");
$stmt->bind_param("iiii", $cart_id, $product_id, $variant_id, $variant_id);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();

if ($existing) {
    $new_quantity = $existing['quantity'] + $quantity;
    $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?");
    $stmt->bind_param("ii", $new_quantity, $existing['cart_item_id']);
    $stmt->execute();
} else {
    $price = $product['final_price'];
    $stmt = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, variant_id, quantity, price) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiid", $cart_id, $product_id, $variant_id, $quantity, $price);
    $stmt->execute();
}

$stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart_items WHERE cart_id = ?");
$stmt->bind_param("i", $cart_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$cart_count = $result['count'] ?? 0;

echo json_encode([
    'success' => true,
    'message' => 'Added to cart',
    'cart_count' => $cart_count
]);
?>