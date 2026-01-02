<?php
require_once 'config.php';

if (!is_logged_in()) {
    header('Location: ' . SITE_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$page_title = "My Orders - " . SITE_NAME;
$user_id = get_user_id();

$orders = [];
// Include UI-only flags to align with admin logic:
// - return_completed: there is a completed return request for this order
// - return_noncompleted_exists: there is a return request in pending/approved/rejected
$result = $conn->query("SELECT o.*, COUNT(oi.order_item_id) as item_count,
                                                                                                EXISTS (SELECT 1 FROM return_requests rr 
                                                                                                                                WHERE rr.order_id = o.order_id 
                                                                                                                                        AND rr.user_id = o.user_id 
                                                                                                                                        AND rr.status = 'completed') AS return_completed,
                                                                                                EXISTS (SELECT 1 FROM return_requests rr2
                                                                                                                                WHERE rr2.order_id = o.order_id
                                                                                                                                    AND rr2.user_id = o.user_id
                                                                                                                                    AND rr2.status IN ('pending','approved','rejected')) AS return_noncompleted_exists
                                                                                                FROM orders o
                                                                                                LEFT JOIN order_items oi ON o.order_id = oi.order_id
                                                                                                WHERE o.user_id = $user_id
                                                                                                GROUP BY o.order_id
                                                                                                ORDER BY o.created_at DESC");
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

include 'header.php';
?>

<div class="container" style="margin-top: 20px;">
    <h2 style="margin-bottom: 30px;">My Orders</h2>

    <?php if (empty($orders)): ?>
        <div class="card" style="text-align: center; padding: 60px 20px;">
            <i class="fas fa-shopping-bag" style="font-size: 80px; color: #ddd; margin-bottom: 20px;"></i>
            <h3 style="margin-bottom: 10px;">No Orders Yet</h3>
            <p style="color: #666; margin-bottom: 30px;">Start shopping to see your orders here!</p>
            <a href="category.php" class="btn btn-primary">Start Shopping</a>
        </div>
    <?php else: ?>
        <div style="display: grid; gap: 20px;">
            <?php foreach ($orders as $order): ?>
                <div class="card" style="cursor: pointer; transition: box-shadow 0.3s;" 
                     onclick="window.location.href='order-details.php?id=<?php echo $order['order_id']; ?>'"
                     onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'"
                     onmouseout="this.style.boxShadow='0 1px 2px rgba(0,0,0,0.1)'">
                    
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                        <div>
                            <div style="font-size: 18px; font-weight: 600; margin-bottom: 5px;">
                                Order #<?php echo htmlspecialchars($order['order_number']); ?>
                            </div>
                            <div style="font-size: 14px; color: #666;">
                                Placed on <?php echo date('F j, Y', strtotime($order['created_at'])); ?>
                            </div>
                        </div>
                        <div>
                            <?php 
                                $is_return_completed = !empty($order['return_completed']) && (int)$order['return_completed'] === 1;
                                $has_noncompleted_return = !empty($order['return_noncompleted_exists']) && (int)$order['return_noncompleted_exists'] === 1;
                                // UI rules for customers (no workflow change):
                                // - If completed return exists => show RETURNED
                                // - Else if DB says returned OR there is a non-completed return => show DELIVERED
                                // - Else show actual DB status
                                if ($is_return_completed) {
                                    $display_status = 'returned';
                                } elseif ($has_noncompleted_return || $order['order_status'] === 'returned') {
                                    $display_status = 'delivered';
                                } else {
                                    $display_status = $order['order_status'];
                                }
                                // Colors: pending=#ff9800, shipped=#2196f3, delivered=#4caf50, cancelled=#dc3545, returned=#f59e0b
                                $bg_color = '#ff9800';
                                if ($display_status === 'shipped') { $bg_color = '#2196f3'; }
                                elseif ($display_status === 'delivered') { $bg_color = '#4caf50'; }
                                elseif ($display_status === 'cancelled') { $bg_color = '#dc3545'; }
                                elseif ($display_status === 'returned') { $bg_color = '#f59e0b'; }
                            ?>
                            <span style="display: inline-block; padding: 6px 15px; border-radius: 20px; font-size: 12px; font-weight: 600; background: <?php echo $bg_color; ?>; color: white;">
                                <?php echo strtoupper(str_replace('_', ' ', $display_status)); ?>
                            </span>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 15px; border-top: 1px solid #e5e5e5;">
                        <div>
                            <div style="color: #666; font-size: 14px; margin-bottom: 5px;">
                                <?php echo $order['item_count']; ?> item<?php echo $order['item_count'] != 1 ? 's' : ''; ?>
                            </div>
                            <div style="font-size: 20px; font-weight: 700; color: #f53d2d;">
                                <?php echo CURRENCY_SYMBOL . number_format($order['total_amount'], 2); ?>
                            </div>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <?php if ($order['tracking_number']): ?>
                                <button onclick="event.stopPropagation(); alert('Tracking: <?php echo $order['tracking_number']; ?>')" 
                                        class="btn btn-secondary" style="padding: 10px 20px;">
                                    <i class="fas fa-truck"></i> Track
                                </button>
                            <?php endif; ?>
                            <button class="btn btn-primary" style="padding: 10px 20px;">
                                View Details
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>