<?php
require_once 'config.php';
$page_title = "Analytics & Reports";

// Date range
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Sales overview
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
                           END) as total_revenue,
                       SUM(CASE 
                               WHEN payment_status = 'paid'
                                    AND NOT EXISTS (
                                        SELECT 1 FROM return_requests rr 
                                        WHERE rr.order_id = orders.order_id 
                                          AND rr.status = 'completed'
                                    )
                               THEN (total_amount - shipping_amount - tax_amount) ELSE 0 
                           END) as net_revenue,
                       AVG(CASE WHEN payment_status = 'paid' THEN total_amount END) as avg_order_value
                       FROM orders
                       WHERE DATE(created_at) BETWEEN '$date_from' AND '$date_to'
                       AND order_status != 'cancelled'");
$sales_overview = $result->fetch_assoc();

// Daily sales data
$daily_sales = [];
$result = $conn->query("SELECT DATE(created_at) as date, 
                       COUNT(*) as orders,
                       SUM(CASE 
                               WHEN NOT EXISTS (
                                   SELECT 1 FROM return_requests rr 
                                   WHERE rr.order_id = orders.order_id 
                                     AND rr.status = 'completed'
                               ) THEN total_amount ELSE 0 
                           END) as revenue
                       FROM orders
                       WHERE DATE(created_at) BETWEEN '$date_from' AND '$date_to'
                       AND order_status != 'cancelled'
                       GROUP BY DATE(created_at)
                       ORDER BY DATE(created_at)");
while ($row = $result->fetch_assoc()) {
    $daily_sales[] = $row;
}

// Top products
$top_products = [];
$result = $conn->query("SELECT p.product_name, 
                                             SUM(oi.quantity) as units_sold,
                                             SUM(oi.subtotal) as revenue
                                             FROM order_items oi
                                             JOIN products p ON oi.product_id = p.product_id
                                             JOIN orders o ON oi.order_id = o.order_id
                                             WHERE DATE(o.created_at) BETWEEN '$date_from' AND '$date_to'
                                             AND o.order_status != 'cancelled'
                                             AND NOT EXISTS (
                                                     SELECT 1 FROM return_requests rr 
                                                     WHERE rr.order_id = o.order_id 
                                                         AND rr.status = 'completed'
                                             )
                                             GROUP BY oi.product_id
                                             ORDER BY revenue DESC
                                             LIMIT 10");
while ($row = $result->fetch_assoc()) {
    $top_products[] = $row;
}

// Category performance
$category_performance = [];
$result = $conn->query("SELECT c.category_name,
                                             COUNT(oi.order_item_id) as items_sold,
                                             SUM(oi.subtotal) as revenue
                                             FROM order_items oi
                                             JOIN products p ON oi.product_id = p.product_id
                                             JOIN categories c ON p.category_id = c.category_id
                                             JOIN orders o ON oi.order_id = o.order_id
                                             WHERE DATE(o.created_at) BETWEEN '$date_from' AND '$date_to'
                                             AND o.order_status != 'cancelled'
                                             AND NOT EXISTS (
                                                     SELECT 1 FROM return_requests rr 
                                                     WHERE rr.order_id = o.order_id 
                                                         AND rr.status = 'completed'
                                             )
                                             GROUP BY c.category_id
                                             ORDER BY revenue DESC");
while ($row = $result->fetch_assoc()) {
    $category_performance[] = $row;
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="admin-header">
    <h1>Analytics & Reports</h1>
    <div class="admin-header-actions">
        <button class="btn btn-primary" onclick="window.print()">
            <i class="fas fa-print"></i> Export Report
        </button>
    </div>
</div>

<!-- Date Filter -->
<div class="admin-card">
    <form method="GET" class="filter-form">
        <div class="filter-group">
            <label>From:</label>
            <input type="date" name="date_from" value="<?php echo $date_from; ?>" class="form-control">
        </div>
        <div class="filter-group">
            <label>To:</label>
            <input type="date" name="date_to" value="<?php echo $date_to; ?>" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">Apply</button>
    </form>
</div>

<!-- Sales Overview -->
<div class="stats-grid">
    <div class="stat-card bg-blue">
        <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?php echo CURRENCY_SYMBOL . number_format($sales_overview['total_revenue'] ?? 0, 2); ?></div>
            <div class="stat-label">Total Revenue</div>
        </div>
    </div>
    
    <div class="stat-card bg-green">
        <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?php echo number_format($sales_overview['total_orders'] ?? 0); ?></div>
            <div class="stat-label">Total Orders</div>
        </div>
    </div>
    
    <div class="stat-card bg-purple">
        <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?php echo CURRENCY_SYMBOL . number_format($sales_overview['avg_order_value'] ?? 0, 2); ?></div>
            <div class="stat-label">Avg Order Value</div>
        </div>
    </div>
    
    <div class="stat-card bg-orange">
        <div class="stat-icon"><i class="fas fa-coins"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?php echo CURRENCY_SYMBOL . number_format($sales_overview['net_revenue'] ?? 0, 2); ?></div>
            <div class="stat-label">Net Revenue</div>
        </div>
    </div>
</div>

<div class="admin-grid-2">
    <!-- Top Products -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Top Selling Products</h3>
        </div>
        <div class="admin-card-body">
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Units Sold</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_products as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                <td><?php echo $product['units_sold']; ?></td>
                                <td><strong><?php echo CURRENCY_SYMBOL . number_format($product['revenue'], 2); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Category Performance -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Category Performance</h3>
        </div>
        <div class="admin-card-body">
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Items Sold</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($category_performance as $cat): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($cat['category_name']); ?></td>
                                <td><?php echo $cat['items_sold']; ?></td>
                                <td><strong><?php echo CURRENCY_SYMBOL . number_format($cat['revenue'], 2); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Sales Chart -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3>Daily Sales Trend</h3>
    </div>
    <div class="admin-card-body">
        <canvas id="salesChart" height="80"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($daily_sales, 'date')); ?>,
        datasets: [{
            label: 'Revenue',
            data: <?php echo json_encode(array_column($daily_sales, 'revenue')); ?>,
            borderColor: '#f53d2d',
            backgroundColor: 'rgba(245, 61, 45, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '<?php echo CURRENCY_SYMBOL; ?>' + value.toFixed(2);
                    }
                }
            }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>