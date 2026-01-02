<?php
require_once 'config.php';

$page_title = "Track Order - " . SITE_NAME;
$order = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' || isset($_GET['order'])) {
    $order_number = clean_input($_POST['order_number'] ?? $_GET['order'] ?? '');
    $email = clean_input($_POST['email'] ?? $_GET['email'] ?? '');

    // Normalize common input variants (e.g., leading '#') without changing workflow
    if (strlen($order_number) > 0 && $order_number[0] === '#') {
        $order_number = substr($order_number, 1);
    }
    
    if (empty($order_number)) {
        $error = "Please enter your order number";
    } else {
        // Fetch order and UI-only return flags to mirror order-details.php status
        $stmt = $conn->prepare("SELECT 
                                   o.*, 
                                   u.email AS user_email,
                                   EXISTS(SELECT 1 FROM return_requests rr WHERE rr.order_id = o.order_id AND rr.status = 'completed') AS return_completed,
                                   EXISTS(SELECT 1 FROM return_requests rr WHERE rr.order_id = o.order_id AND rr.status IN ('pending','approved','rejected')) AS return_noncompleted_exists
                               FROM orders o 
                               JOIN users u ON o.user_id = u.user_id
                               WHERE o.order_number = ?");
        $stmt->bind_param("s", $order_number);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        
        if (!$order) {
            $error = "Order not found. Please check your order number.";
        } elseif ($email && strtolower($order['user_email']) != strtolower($email)) {
            $error = "Order number and email do not match.";
            $order = null;
        }
    }
}

include 'header.php';
?>

<div class="container" style="margin-top: 40px; margin-bottom: 60px;">
    <h2 style="text-align: center; margin-bottom: 40px;">Track Your Order</h2>

    <?php if (!$order): ?>
        <!-- Tracking Form -->
        <div class="card" style="max-width: 500px; margin: 0 auto;">
            <div style="text-align: center; margin-bottom: 30px;">
                <i class="fas fa-search-location" style="font-size: 60px; color: #f53d2d; margin-bottom: 15px;"></i>
                <p style="color: #666;">Enter your order details to track your shipment</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Order Number *</label>
                    <input type="text" name="order_number" class="form-control" required 
                           placeholder="e.g., ORD-20250127-ABC123"
                           value="<?php echo $_POST['order_number'] ?? ''; ?>">
                    <small style="color: #666; display: block; margin-top: 5px;">
                        You can find this in your order confirmation email
                    </small>
                </div>

                <div class="form-group">
                    <label>Email Address (Optional)</label>
                    <input type="email" name="email" class="form-control" 
                           placeholder="your-email@example.com"
                           value="<?php echo $_POST['email'] ?? ''; ?>">
                </div>

                <button type="submit" class="btn btn-primary btn-full">
                    <i class="fas fa-search"></i> Track Order
                </button>
            </form>

            <div style="margin-top: 30px; padding-top: 30px; border-top: 1px solid #e5e5e5; text-align: center;">
                <p style="color: #666; font-size: 14px; margin-bottom: 15px;">
                    Already have an account?
                </p>
                <a href="login.php" style="color: #f53d2d; font-weight: 600;">
                    Login to view all your orders
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Tracking Results -->
        <div class="card" style="max-width: 800px; margin: 0 auto;">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 8px; color: white; margin-bottom: 30px;">
                <div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Order Number</div>
                <div style="font-size: 28px; font-weight: 700; margin-bottom: 15px;">
                    <?php echo htmlspecialchars($order['order_number']); ?>
                </div>
                <div style="font-size: 14px; opacity: 0.9;">
                    Placed on <?php echo date('F j, Y', strtotime($order['created_at'])); ?>
                </div>
            </div>

            <!-- Current Status -->
            <div style="background: #f8f8f8; padding: 25px; border-radius: 8px; text-align: center; margin-bottom: 30px;">
                <?php 
                    // Compute UI status to match customer order-details page
                    $ui_status = $order['order_status'];
                    if (!empty($order['return_completed'])) {
                        $ui_status = 'returned';
                    }

                    $status_color = '#f53d2d';
                    if ($ui_status === 'delivered') { $status_color = '#4caf50'; }
                    elseif ($ui_status === 'cancelled') { $status_color = '#dc3545'; }
                    elseif ($ui_status === 'returned') { $status_color = '#0ea5e9'; }
                ?>
                <div style="font-size: 14px; color: #666; margin-bottom: 10px;">Current Status</div>
                <div style="font-size: 32px; font-weight: 700; color: <?php echo $status_color; ?>; margin-bottom: 10px;">
                    <?php echo strtoupper(str_replace('_', ' ', $ui_status)); ?>
                </div>
                <div style="color: #666; font-size: 14px;">
                    Last updated: <?php echo date('F j, Y - g:i A', strtotime($order['updated_at'])); ?>
                </div>
            </div>

            <!-- Tracking Timeline -->
            <?php
            $status_steps = [
                'pending' => ['label' => 'Order Placed', 'icon' => 'fa-shopping-bag'],
                'processing' => ['label' => 'Processing', 'icon' => 'fa-cog'],
                'shipped' => ['label' => 'Shipped', 'icon' => 'fa-truck'],
                'delivered' => ['label' => 'Delivered', 'icon' => 'fa-check-circle']
            ];

            $status_order = ['pending', 'processing', 'shipped', 'delivered'];
            $current_step = array_search($order['order_status'], $status_order);
            ?>

            <?php if ($ui_status != 'cancelled' && $ui_status != 'returned'): ?>
                <div style="padding: 30px; background: white; border-radius: 8px; margin-bottom: 30px;">
                    <div style="display: flex; justify-content: space-between; position: relative;">
                        <!-- Progress Line -->
                        <div style="position: absolute; top: 30px; left: 0; right: 0; height: 4px; background: #e0e0e0; z-index: 0;">
                            <div style="height: 100%; background: #f53d2d; width: <?php echo $current_step !== false ? (($current_step + 1) / count($status_order)) * 100 : 0; ?>%;"></div>
                        </div>

                        <?php foreach ($status_order as $index => $status): ?>
                            <?php $is_completed = $index <= $current_step; ?>
                            <div style="flex: 1; text-align: center; position: relative; z-index: 1;">
                                <div style="width: 60px; height: 60px; border-radius: 50%; background: <?php echo $is_completed ? '#f53d2d' : '#e0e0e0'; ?>; margin: 0 auto 15px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas <?php echo $status_steps[$status]['icon']; ?>" style="color: white; font-size: 24px;"></i>
                                </div>
                                <div style="font-weight: 600; color: <?php echo $is_completed ? '#333' : '#999'; ?>;">
                                    <?php echo $status_steps[$status]['label']; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($ui_status === 'returned'): ?>
                <div style="background: #e0f2fe; padding: 16px; border-radius: 8px; margin-bottom: 30px; text-align:center; color:#075985;">
                    <i class="fas fa-undo" aria-hidden="true"></i>
                    This order has been returned.
                </div>
            <?php endif; ?>

            <!-- Tracking Number -->
            <?php if ($order['tracking_number']): ?>
                <div style="background: #fff3cd; padding: 20px; border-radius: 8px; margin-bottom: 30px; text-align: center;">
                    <div style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">
                        <i class="fas fa-shipping-fast"></i> Tracking Number
                    </div>
                    <div style="font-size: 24px; font-weight: 700; color: #333; letter-spacing: 2px;">
                        <?php echo htmlspecialchars($order['tracking_number']); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Actions -->
            <div style="display: flex; gap: 15px; justify-content: center;">
                <button onclick="window.print()" class="btn btn-secondary">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="contact.php" class="btn btn-secondary">
                    <i class="fas fa-headset"></i> Contact Support
                </a>
                <?php if (is_logged_in()): ?>
                    <a href="order-details.php?id=<?php echo $order['order_id']; ?>" class="btn btn-primary">
                        <i class="fas fa-eye"></i> View Full Details
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>