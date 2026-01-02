<?php
require_once 'config.php';

if (!is_logged_in()) {
    header('Location: ' . SITE_URL . '/login.php');
    exit;
}

$page_title = "Order Confirmation - " . SITE_NAME;
$user_id = get_user_id();

if (!isset($_GET['order'])) {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$order_number = clean_input($_GET['order']);

$stmt = $conn->prepare("SELECT o.*, ua.full_name, ua.phone, ua.address_line1, ua.address_line2, 
                        ua.city, ua.state, ua.postal_code, ua.country
                        FROM orders o
                        LEFT JOIN user_addresses ua ON o.shipping_address_id = ua.address_id
                        WHERE o.order_number = ? AND o.user_id = ?");
$stmt->bind_param("si", $order_number, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$order_items = [];
$stmt = $conn->prepare("SELECT oi.*, pi.image_url 
                        FROM order_items oi
                        LEFT JOIN product_images pi ON oi.product_id = pi.product_id AND pi.is_primary = 1
                        WHERE oi.order_id = ?");
$stmt->bind_param("i", $order['order_id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $order_items[] = $row;
}

include 'header.php';
?>

<div class="container" style="margin-top: 40px; margin-bottom: 60px;">
    <div style="text-align: center; margin-bottom: 40px;">
        <div style="width: 100px; height: 100px; background: #4caf50; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
            <i class="fas fa-check" style="font-size: 50px; color: white;"></i>
        </div>
        <h1 style="font-size: 32px; margin-bottom: 10px; font-weight: 700;">Order Placed Successfully!</h1>
        <p style="font-size: 18px; color: #666; margin-bottom: 10px;">Thank you for your purchase</p>
        <p style="font-size: 16px; color: #666;">
            Order Number: <strong style="color: #f53d2d; font-size: 20px;"><?php echo htmlspecialchars($order['order_number']); ?></strong>
        </p>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 400px; gap: 30px;">
        <div>
            <div class="card">
                <h3 style="margin-bottom: 20px;">Order Items</h3>
                
                <?php foreach ($order_items as $item): ?>
                    <div style="display: flex; gap: 15px; padding: 15px 0; border-bottom: 1px solid #e5e5e5;">
                        <img src="<?php echo $item['image_url'] ?? 'assets/images/no-image.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                             style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px;">
                        <div style="flex: 1;">
                            <div style="font-weight: 600; margin-bottom: 5px;">
                                <?php echo htmlspecialchars($item['product_name']); ?>
                            </div>
                            <div style="font-size: 14px; color: #666;">
                                Quantity: <?php echo $item['quantity']; ?>
                            </div>
                            <div style="font-weight: 600; color: #f53d2d;">
                                <?php echo CURRENCY_SYMBOL . number_format($item['subtotal'], 2); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="card" style="margin-top: 20px;">
                <h3 style="margin-bottom: 15px;"><i class="fas fa-map-marker-alt" style="color: #f53d2d;"></i> Shipping Address</h3>
                <div style="color: #666; line-height: 1.8;">
                    <strong style="color: #333; display: block; margin-bottom: 8px;">
                        <?php echo htmlspecialchars($order['full_name']); ?>
                    </strong>
                    <?php echo htmlspecialchars($order['address_line1']); ?><br>
                    <?php if ($order['address_line2']): echo htmlspecialchars($order['address_line2']) . '<br>'; endif; ?>
                    <?php echo htmlspecialchars($order['city']); ?>, <?php echo htmlspecialchars($order['state']); ?> <?php echo htmlspecialchars($order['postal_code']); ?><br>
                    <?php echo htmlspecialchars($order['country']); ?><br>
                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($order['phone']); ?>
                </div>
            </div>
        </div>

        <div>
            <div class="card" style="position: sticky; top: 20px;">
                <h3 style="margin-bottom: 20px;">Order Summary</h3>

                <div style="padding-bottom: 20px; margin-bottom: 20px; border-bottom: 1px solid #e5e5e5;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                        <span style="color: #666;">Subtotal:</span>
                        <span style="font-weight: 600;"><?php echo CURRENCY_SYMBOL . number_format($order['subtotal'], 2); ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                        <span style="color: #666;">Tax:</span>
                        <span style="font-weight: 600;"><?php echo CURRENCY_SYMBOL . number_format($order['tax_amount'], 2); ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                        <span style="color: #666;">Shipping:</span>
                        <span style="font-weight: 600;"><?php echo $order['shipping_amount'] == 0 ? 'FREE' : CURRENCY_SYMBOL . number_format($order['shipping_amount'], 2); ?></span>
                    </div>
                    <div style="border-top: 2px solid #e5e5e5; margin-top: 15px; padding-top: 15px; display: flex; justify-content: space-between; font-size: 20px; font-weight: 700;">
                        <span>Total Paid:</span>
                        <span style="color: #f53d2d;"><?php echo CURRENCY_SYMBOL . number_format($order['total_amount'], 2); ?></span>
                    </div>
                </div>

                <a href="orders.php" class="btn btn-primary btn-full" style="margin-bottom: 10px;">
                    View My Orders
                </a>
                <a href="index.php" class="btn btn-secondary btn-full">
                    Continue Shopping
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>