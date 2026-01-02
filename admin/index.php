<?php
require_once 'config.php';
require_admin_login(); // Protect this page

$page_title = "Dashboard";

// Get statistics
$stats = [];

// Total Sales (Total Orders: all non-cancelled; Total Revenue: only PAID)
$result = $conn->query("SELECT 
                       COUNT(*) as total_orders,
                       SUM(CASE 
                               WHEN payment_status = 'paid' 
                                    AND NOT EXISTS (
                                        SELECT 1 FROM return_requests rr 
                                        WHERE rr.order_id = orders.order_id 
                                          AND rr.status = 'completed'
                                    )
                               THEN total_amount ELSE 0 
                           END) as total_revenue
                       FROM orders 
                       WHERE order_status != 'cancelled'");
$stats['orders'] = $result->fetch_assoc();

// Today's Sales (Revenue counts only PAID orders)
$result = $conn->query("SELECT 
                       COUNT(*) as today_orders,
                       SUM(CASE 
                               WHEN payment_status = 'paid' 
                                    AND NOT EXISTS (
                                        SELECT 1 FROM return_requests rr 
                                        WHERE rr.order_id = orders.order_id 
                                          AND rr.status = 'completed'
                                    )
                               THEN total_amount ELSE 0 
                           END) as today_revenue 
                       FROM orders 
                       WHERE DATE(created_at) = CURDATE() AND order_status != 'cancelled'");
$stats['today'] = $result->fetch_assoc();

// Total Users
$result = $conn->query("SELECT COUNT(*) as total_users FROM users WHERE is_active = 1");
$stats['users'] = $result->fetch_assoc();

// Total Products
$result = $conn->query("SELECT COUNT(*) as total_products FROM products WHERE is_active = 1");
$stats['products'] = $result->fetch_assoc();

// Pending Orders
$result = $conn->query("SELECT COUNT(*) as pending_orders FROM orders WHERE order_status = 'pending'");
$stats['pending'] = $result->fetch_assoc();

// Low Stock Products
$result = $conn->query("SELECT COUNT(*) as low_stock FROM product_variants WHERE stock_quantity < 10");
$stats['low_stock'] = $result->fetch_assoc();

// Recent Orders (UI-only status: show 'returned' if a completed return exists)
$recent_orders = [];
$result = $conn->query("SELECT o.*, u.first_name, u.last_name,
                               EXISTS (SELECT 1 FROM return_requests rr 
                                       WHERE rr.order_id = o.order_id AND rr.status = 'completed') AS return_completed,
                               EXISTS (SELECT 1 FROM return_requests rr2 
                                       WHERE rr2.order_id = o.order_id AND rr2.status IN ('pending','approved','rejected')) AS return_noncompleted_exists
                        FROM orders o 
                        JOIN users u ON o.user_id = u.user_id 
                        ORDER BY o.created_at DESC LIMIT 10");
while ($row = $result->fetch_assoc()) {
    $recent_orders[] = $row;
}

// Top Products
$top_products = [];
$result = $conn->query("SELECT p.product_name, SUM(oi.quantity) as total_sold, SUM(oi.subtotal) as revenue
                       FROM order_items oi
                       JOIN products p ON oi.product_id = p.product_id
                       GROUP BY oi.product_id
                       ORDER BY total_sold DESC
                       LIMIT 5");
while ($row = $result->fetch_assoc()) {
    $top_products[] = $row;
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="admin-header">
    <h1>Dashboard Overview</h1>
    <div class="admin-header-actions">
        <span class="last-update">Last updated: <?php echo date('M d, Y - g:i A'); ?></span>
    </div>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card bg-blue">
        <div class="stat-icon">
            <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?php echo CURRENCY_SYMBOL . number_format($stats['orders']['total_revenue'] ?? 0, 2); ?></div>
            <div class="stat-label">Total Revenue</div>
        </div>
    </div>
    
    <div class="stat-card bg-green">
        <div class="stat-icon">
            <i class="fas fa-shopping-cart"></i>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?php echo number_format($stats['orders']['total_orders'] ?? 0); ?></div>
            <div class="stat-label">Total Orders</div>
        </div>
    </div>
    
    <div class="stat-card bg-purple">
        <div class="stat-icon">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?php echo number_format($stats['users']['total_users'] ?? 0); ?></div>
            <div class="stat-label">Total Users</div>
        </div>
    </div>
    
    <div class="stat-card bg-orange">
        <div class="stat-icon">
            <i class="fas fa-box"></i>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?php echo number_format($stats['products']['total_products'] ?? 0); ?></div>
            <div class="stat-label">Total Products</div>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="quick-stats">
    <div class="quick-stat-card">
        <div class="quick-stat-value"><?php echo CURRENCY_SYMBOL . number_format($stats['today']['today_revenue'] ?? 0, 2); ?></div>
        <div class="quick-stat-label">Today's Revenue</div>
    </div>
    
    <div class="quick-stat-card">
        <div class="quick-stat-value"><?php echo $stats['today']['today_orders'] ?? 0; ?></div>
        <div class="quick-stat-label">Today's Orders</div>
    </div>
    
    <div class="quick-stat-card alert">
        <div class="quick-stat-value"><?php echo $stats['pending']['pending_orders'] ?? 0; ?></div>
        <div class="quick-stat-label">Pending Orders</div>
    </div>
    
    <div class="quick-stat-card warning">
        <div class="quick-stat-value"><?php echo $stats['low_stock']['low_stock'] ?? 0; ?></div>
        <div class="quick-stat-label">Low Stock Items</div>
    </div>
</div>

<div class="admin-grid-2">
    <!-- Recent Orders -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Recent Orders</h3>
            <a href="orders.php" class="btn btn-sm btn-primary">View All</a>
        </div>
        <div class="admin-card-body">
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_orders)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 30px; color: #999;">No orders yet</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_orders as $order): ?>
                                <?php
                                    // UI mapping without modifying database workflow
                                    $is_return_completed = !empty($order['return_completed']) && (int)$order['return_completed'] === 1;
                                    $has_noncompleted_return = !empty($order['return_noncompleted_exists']) && (int)$order['return_noncompleted_exists'] === 1;
                                    $ui_status = $order['order_status'];
                                    if ($is_return_completed) {
                                        $ui_status = 'returned';
                                    } elseif ($has_noncompleted_return) {
                                        $ui_status = 'delivered';
                                    }
                                ?>
                                <tr>
                                    <td><a href="order-details.php?id=<?php echo $order['order_id']; ?>"><?php echo htmlspecialchars($order['order_number']); ?></a></td>
                                    <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                    <td><?php echo CURRENCY_SYMBOL . number_format($order['total_amount'], 2); ?></td>
                                    <td><span class="status-badge status-<?php echo $ui_status; ?>"><?php echo ucfirst($ui_status); ?></span></td>
                                    <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Top Products -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Top Selling Products</h3>
        </div>
        <div class="admin-card-body">
            <?php if (empty($top_products)): ?>
                <p style="text-align: center; padding: 30px; color: #999;">No sales data available yet</p>
            <?php else: ?>
                <div class="top-products-list">
                    <?php foreach ($top_products as $index => $product): ?>
                        <div class="top-product-item">
                            <div class="top-product-rank"><?php echo $index + 1; ?></div>
                            <div class="top-product-info">
                                <div class="top-product-name"><?php echo htmlspecialchars($product['product_name']); ?></div>
                                <div class="top-product-stats">
                                    <span><?php echo $product['total_sold']; ?> sold</span>
                                    <span class="revenue"><?php echo CURRENCY_SYMBOL . number_format($product['revenue'], 2); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>