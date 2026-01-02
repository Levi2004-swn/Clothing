<?php
require_once 'config.php';

if (!is_logged_in()) {
    header('Location: ' . SITE_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$page_title = "Order Details - " . SITE_NAME;
$user_id = get_user_id();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ' . SITE_URL . '/orders.php');
    exit;
}

$order_id = intval($_GET['id']);

// Get order details
$stmt = $conn->prepare("SELECT o.*, ua.full_name, ua.phone, ua.address_line1, ua.address_line2, 
                        ua.city, ua.state, ua.postal_code, ua.country
                        FROM orders o
                        LEFT JOIN user_addresses ua ON o.shipping_address_id = ua.address_id
                        WHERE o.order_id = ? AND o.user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: ' . SITE_URL . '/orders.php');
    exit;
}

// Get order items
$order_items = [];
$stmt = $conn->prepare("SELECT oi.*, pi.image_url 
                        FROM order_items oi
                        LEFT JOIN product_images pi ON oi.product_id = pi.product_id AND pi.is_primary = 1
                        WHERE oi.order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $order_items[] = $row;
}

// Determine if return button should be hidden (only when an existing return is approved or completed)
$hide_return_button = false;
$__return_completed = false;
$__has_noncompleted_return = false;
$rr_stmt = $conn->prepare("SELECT status FROM return_requests WHERE order_id = ? AND user_id = ? ORDER BY created_at DESC LIMIT 1");
$rr_stmt->bind_param("ii", $order_id, $user_id);
$rr_stmt->execute();
$rr_res = $rr_stmt->get_result();
if ($rr = $rr_res->fetch_assoc()) {
    $hide_return_button = in_array($rr['status'], ['approved','completed'], true);
    $__return_completed = ($rr['status'] === 'completed');
    $__has_noncompleted_return = in_array($rr['status'], ['pending','approved','rejected'], true);
}

// Order status tracking steps
$status_steps = [
    'pending' => ['label' => 'Order Placed', 'icon' => 'fa-shopping-bag', 'color' => '#ff9800'],
    'processing' => ['label' => 'Processing', 'icon' => 'fa-cog', 'color' => '#2196f3'],
    'shipped' => ['label' => 'Shipped', 'icon' => 'fa-truck', 'color' => '#00bcd4'],
    'delivered' => ['label' => 'Delivered', 'icon' => 'fa-check-circle', 'color' => '#4caf50'],
    'cancelled' => ['label' => 'Cancelled', 'icon' => 'fa-times-circle', 'color' => '#dc3545'],
    // UI-only display state when a return_request is completed
    'returned' => ['label' => 'Returned', 'icon' => 'fa-undo', 'color' => '#f59e0b']
];

$current_status = $order['order_status'];
// UI behavior: if return is completed -> show "returned"; otherwise if DB says returned or a non-completed return exists -> treat as delivered
if ($__return_completed) {
    $display_status = 'returned';
} else {
    if ($current_status === 'returned' || $__has_noncompleted_return) {
        $current_status = 'delivered';
    }
    $display_status = $current_status;
}
$status_order = ['pending', 'processing', 'shipped', 'delivered'];
$current_step = array_search($current_status, $status_order);

include 'header.php';
?>

<div class="container" style="margin-top: 20px;">
    <div style="margin-bottom: 30px;">
        <a href="orders.php" style="color: #f53d2d; font-weight: 600;">
            <i class="fas fa-arrow-left"></i> Back to Orders
        </a>
    </div>

    <!-- Order Header -->
    <div class="card" style="margin-bottom: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px;">
            <div>
                <h2 style="margin-bottom: 10px;">Order #<?php echo htmlspecialchars($order['order_number']); ?></h2>
                <div style="color: #666; font-size: 14px;">
                    Placed on <?php echo date('F j, Y - g:i A', strtotime($order['created_at'])); ?>
                </div>
            </div>
            <div>
                <span style="display: inline-block; padding: 8px 20px; border-radius: 20px; font-size: 14px; font-weight: 600; background: <?php echo $status_steps[$display_status]['color']; ?>; color: white;">
                    <?php echo strtoupper(str_replace('_', ' ', $display_status)); ?>
                </span>
            </div>
        </div>

        <!-- Order Tracking Timeline or Status Notice -->
        <?php if ($__return_completed): ?>
            <!-- Returned Status (mirror cancelled style) -->
            <div style="background: #fffbeb; padding: 30px; border-radius: 8px; margin-top: 20px; text-align: center; border:1px solid #fde68a;">
                <div style="width: 80px; height: 80px; border-radius: 50%; background: #f59e0b; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-undo" style="color: white; font-size: 40px;"></i>
                </div>
                <h3 style="margin-bottom: 10px; color: #b45309;">Order Returned</h3>
                <p style="color: #92400e;">This order was returned on <?php echo date('F j, Y', strtotime($order['updated_at'])); ?>.</p>
            </div>
        <?php elseif ($current_status != 'cancelled'): ?>
            <div style="background: #f8f8f8; padding: 30px; border-radius: 8px; margin-top: 20px;">
                <h3 style="margin-bottom: 30px; font-size: 18px; font-weight: 600;">Order Tracking</h3>
                
                <div style="display: flex; justify-content: space-between; position: relative;">
                    <!-- Progress Line -->
                    <div style="position: absolute; top: 30px; left: 0; right: 0; height: 4px; background: #e0e0e0; z-index: 0;">
                        <div style="height: 100%; background: linear-gradient(to right, #f53d2d, #ff6633); width: <?php echo $current_step !== false ? (($current_step + 1) / count($status_order)) * 100 : 0; ?>%; transition: width 0.5s;"></div>
                    </div>

                    <?php foreach ($status_order as $index => $status): ?>
                        <?php 
                        $is_completed = $index <= $current_step;
                        $is_current = $index == $current_step;
                        ?>
                        <div style="flex: 1; text-align: center; position: relative; z-index: 1;">
                            <!-- Circle -->
                            <div style="width: 60px; height: 60px; border-radius: 50%; background: <?php echo $is_completed ? 'linear-gradient(135deg, #f53d2d, #ff6633)' : '#e0e0e0'; ?>; margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                <i class="fas <?php echo $status_steps[$status]['icon']; ?>" style="color: white; font-size: 24px;"></i>
                            </div>
                            
                            <!-- Label -->
                            <div style="font-weight: 600; margin-bottom: 5px; color: <?php echo $is_completed ? '#333' : '#999'; ?>;">
                                <?php echo $status_steps[$status]['label']; ?>
                            </div>
                            
                            <!-- Date/Time -->
                            <?php if ($is_completed): ?>
                                <div style="font-size: 12px; color: #666;">
                                    <?php 
                                    if ($is_current) {
                                        echo date('M j, g:i A', strtotime($order['updated_at']));
                                    } else {
                                        echo date('M j', strtotime($order['created_at']));
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Tracking Number -->
                <?php if ($order['tracking_number']): ?>
                    <div style="margin-top: 30px; padding: 20px; background: white; border-radius: 8px; text-align: center;">
                        <div style="font-size: 14px; color: #666; margin-bottom: 8px;">Tracking Number</div>
                        <div style="font-size: 24px; font-weight: 700; color: #f53d2d; letter-spacing: 2px;">
                            <?php echo htmlspecialchars($order['tracking_number']); ?>
                        </div>
                        <button onclick="copyTrackingNumber()" class="btn btn-secondary" style="margin-top: 15px; padding: 10px 30px;">
                            <i class="fas fa-copy"></i> Copy Tracking Number
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Estimated Delivery -->
                <?php if ($current_status == 'shipped'): ?>
                    <div style="margin-top: 20px; padding: 15px; background: #e3f2fd; border-left: 4px solid #2196f3; border-radius: 4px;">
                        <i class="fas fa-info-circle" style="color: #2196f3; margin-right: 10px;"></i>
                        <strong>Estimated Delivery:</strong> 
                        <?php 
                        $estimated_delivery = date('F j, Y', strtotime($order['updated_at'] . ' +5 days'));
                        echo $estimated_delivery;
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Cancelled Status -->
            <div style="background: #ffebee; padding: 30px; border-radius: 8px; margin-top: 20px; text-align: center;">
                <div style="width: 80px; height: 80px; border-radius: 50%; background: #dc3545; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-times" style="color: white; font-size: 40px;"></i>
                </div>
                <h3 style="margin-bottom: 10px; color: #dc3545;">Order Cancelled</h3>
                <p style="color: #666;">This order was cancelled on <?php echo date('F j, Y', strtotime($order['updated_at'])); ?></p>
                <?php if (!empty($order['cancel_reason'] ?? '')): ?>
                    <div style="margin-top: 15px; padding: 15px; background: white; border-radius: 4px;">
                        <strong>Reason:</strong> <?php echo htmlspecialchars($order['cancel_reason'] ?? ''); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 400px; gap: 30px;">
        <!-- Order Items -->
        <div>
            <div class="card">
                <h3 style="margin-bottom: 20px; font-size: 20px; font-weight: 600;">Order Items</h3>
                
                <?php foreach ($order_items as $item): ?>
                    <div style="display: flex; gap: 15px; padding: 20px 0; border-bottom: 1px solid #e5e5e5;">
                        <img src="<?php echo $item['image_url'] ?? 'assets/images/no-image.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                             style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px;">
                        <div style="flex: 1;">
                            <div style="font-weight: 600; font-size: 16px; margin-bottom: 8px;">
                                <?php echo htmlspecialchars($item['product_name']); ?>
                            </div>
                            <div style="color: #666; font-size: 14px; margin-bottom: 8px;">
                                Quantity: <strong><?php echo $item['quantity']; ?></strong>
                            </div>
                            <div style="font-size: 18px; font-weight: 700; color: #f53d2d;">
                                <?php echo CURRENCY_SYMBOL . number_format($item['subtotal'], 2); ?>
                            </div>
                            
                            <?php if ($current_status == 'delivered'): ?>
                                <div style="margin-top: 10px;">
                                    <a href="product.php?id=<?php echo $item['product_id']; ?>" class="btn btn-secondary" style="padding: 8px 15px; font-size: 13px;">
                                        <i class="fas fa-star"></i> Write Review
                                    </a>
                                    <a href="#" class="btn btn-secondary" style="padding: 8px 15px; font-size: 13px; margin-left: 10px;">
                                        <i class="fas fa-shopping-cart"></i> Buy Again
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Shipping Address -->
            <div class="card" style="margin-top: 20px;">
                <h3 style="margin-bottom: 15px; font-size: 18px; font-weight: 600;">
                    <i class="fas fa-map-marker-alt" style="color: #f53d2d;"></i> Shipping Address
                </h3>
                <div style="color: #666; line-height: 1.8;">
                    <strong style="color: #333; display: block; margin-bottom: 8px; font-size: 16px;">
                        <?php echo htmlspecialchars($order['full_name']); ?>
                    </strong>
                    <?php echo htmlspecialchars($order['address_line1']); ?><br>
                    <?php if ($order['address_line2']): ?>
                        <?php echo htmlspecialchars($order['address_line2']); ?><br>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($order['city']); ?>, 
                    <?php echo htmlspecialchars($order['state']); ?> 
                    <?php echo htmlspecialchars($order['postal_code']); ?><br>
                    <?php echo htmlspecialchars($order['country']); ?><br>
                    <div style="margin-top: 10px;">
                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($order['phone']); ?>
                    </div>
                </div>
            </div>

            <!-- Order Notes -->
            <?php if ($order['notes']): ?>
                <div class="card" style="margin-top: 20px;">
                    <h3 style="margin-bottom: 15px; font-size: 18px; font-weight: 600;">
                        <i class="fas fa-sticky-note" style="color: #f53d2d;"></i> Order Notes
                    </h3>
                    <p style="color: #666; line-height: 1.6;">
                        <?php echo nl2br(htmlspecialchars($order['notes'])); ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Order Summary Sidebar -->
        <div>
            <div class="card" style="position: sticky; top: 20px;">
                <h3 style="margin-bottom: 20px; font-size: 20px; font-weight: 600;">Order Summary</h3>

                <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #e5e5e5;">
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
                        <span style="font-weight: 600;">
                            <?php echo $order['shipping_amount'] == 0 ? 'FREE' : CURRENCY_SYMBOL . number_format($order['shipping_amount'], 2); ?>
                        </span>
                    </div>

                    <?php if ($order['discount_amount'] > 0): ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                            <span style="color: #4caf50;">Discount:</span>
                            <span style="font-weight: 600; color: #4caf50;">
                                -<?php echo CURRENCY_SYMBOL . number_format($order['discount_amount'], 2); ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <div style="border-top: 2px solid #e5e5e5; margin-top: 15px; padding-top: 15px; display: flex; justify-content: space-between; font-size: 20px; font-weight: 700;">
                        <span>Total:</span>
                        <span style="color: #f53d2d;"><?php echo CURRENCY_SYMBOL . number_format($order['total_amount'], 2); ?></span>
                    </div>
                </div>

                <!-- Payment Info -->
                <div style="background: #f8f8f8; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">Payment Method</h4>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                        <i class="fas fa-credit-card" style="color: #666; font-size: 20px;"></i>
                        <span style="color: #666;">
                            <?php
                            $payment_methods = [
                                'credit_card' => 'Credit/Debit Card',
                                'paypal' => 'PayPal',
                                'cod' => 'Cash on Delivery'
                            ];
                            echo $payment_methods[$order['payment_method']] ?? $order['payment_method'];
                            ?>
                        </span>
                    </div>
                    <div>
                        <span style="padding: 4px 10px; background: <?php echo $order['payment_status'] == 'paid' ? '#4caf50' : '#ff9800'; ?>; color: white; border-radius: 3px; font-size: 12px; font-weight: 600;">
                            <?php echo strtoupper($order['payment_status']); ?>
                        </span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <?php if ($current_status == 'pending'): ?>
                    <button onclick="if(confirm('Are you sure you want to cancel this order?')) cancelOrder(<?php echo $order_id; ?>)" 
                            class="btn btn-secondary btn-full" style="margin-bottom: 10px;">
                        <i class="fas fa-times"></i> Cancel Order
                    </button>
                <?php endif; ?>

                <?php if ($current_status == 'delivered' && !$hide_return_button): ?>
                    <a href="request-return.php?order=<?php echo $order_id; ?>" class="btn btn-secondary btn-full" style="margin-bottom: 10px;">
                        <i class="fas fa-undo"></i> Request Return
                    </a>
                <?php endif; ?>

                <button onclick="window.print()" class="btn btn-secondary btn-full" style="margin-bottom: 10px;">
                    <i class="fas fa-print"></i> Print Order
                </button>

                <a href="orders.php" class="btn btn-primary btn-full">
                    <i class="fas fa-list"></i> View All Orders
                </a>

                <!-- Help -->
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e5e5; text-align: center;">
                    <div style="font-size: 13px; color: #666; margin-bottom: 10px;">
                        Need help with this order?
                    </div>
                    <a href="contact.php" style="color: #f53d2d; font-weight: 600;">
                        <i class="fas fa-headset"></i> Contact Support
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyTrackingNumber() {
    const trackingNumber = '<?php echo $order['tracking_number']; ?>';
    navigator.clipboard.writeText(trackingNumber).then(() => {
        showNotification('Tracking number copied to clipboard!', 'success');
    }).catch(err => {
        console.error('Failed to copy:', err);
    });
}

function cancelOrder(orderId) {
    // Simple, original logic: POST immediately to cancel without extra modal or reason
    if (!orderId || Number.isNaN(orderId)) {
        showNotification('Invalid order reference', 'error');
        return;
    }

    const params = new URLSearchParams();
    params.append('order_id', String(orderId));

    fetch('ajax/cancel-order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
        },
        body: params.toString()
    })
    .then(res => res.json())
    .then(data => {
        if (data && data.success) {
            showNotification('Order cancelled successfully', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            showNotification((data && data.message) || 'Failed to cancel order', 'error');
        }
    })
    .catch(err => {
        console.error('Cancel error:', err);
        showNotification('Failed to cancel order', 'error');
    });
}
</script>

<style>
@media print {
    header, nav, footer, .btn, button {
        display: none !important;
    }
}
</style>

<?php include 'footer.php'; ?>