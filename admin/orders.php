<?php
require_once 'config.php';
require_admin_login();

$page_title = "Orders Management";
$success = '';
$error = '';

// Handle status update
if (isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = clean_input($_POST['status']);
    
    $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'returned'];
    
    if ($order_id && in_array($new_status, $valid_statuses)) {
        $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
        $stmt->bind_param("si", $new_status, $order_id);
        
        if ($stmt->execute()) {
            log_admin_activity('update_order_status', "Updated order #$order_id to $new_status");
            $success = "Order status updated successfully!";
        } else {
            $error = "Failed to update order status";
        }
    } else {
        $error = "Invalid order ID or status";
    }
}

// Get filters
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$where = ["1=1"];
if ($status) {
    $status_safe = $conn->real_escape_string($status);
    $where[] = "o.order_status = '$status_safe'";
}
if ($search) {
    $search_safe = $conn->real_escape_string($search);
    $where[] = "(o.order_number LIKE '%$search_safe%' OR u.email LIKE '%$search_safe%' OR CONCAT(u.first_name, ' ', u.last_name) LIKE '%$search_safe%')";
}
if ($date_from) {
    $where[] = "DATE(o.created_at) >= '$date_from'";
}
if ($date_to) {
    $where[] = "DATE(o.created_at) <= '$date_to'";
}

$where_clause = implode(" AND ", $where);

$orders = [];
// Include computed flags:
// - return_completed: if a completed return request exists for the order
// - return_noncompleted_exists: if there is any return request for this order in pending/approved/rejected state
$result = $conn->query("SELECT o.*, u.first_name, u.last_name, u.email,
                       COUNT(oi.order_item_id) as item_count,
                       EXISTS (SELECT 1 FROM return_requests rr WHERE rr.order_id = o.order_id AND rr.status = 'completed') AS return_completed,
                       EXISTS (SELECT 1 FROM return_requests rr2 WHERE rr2.order_id = o.order_id AND rr2.status IN ('pending','approved','rejected')) AS return_noncompleted_exists
                       FROM orders o
                       JOIN users u ON o.user_id = u.user_id
                       LEFT JOIN order_items oi ON o.order_id = oi.order_id
                       WHERE $where_clause
                       GROUP BY o.order_id
                       ORDER BY o.created_at DESC");
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

// Get order stats (UI-derived): classify orders with completed returns as 'returned' without altering DB
$stats = [];
$result = $conn->query("SELECT 
                           CASE 
                               WHEN EXISTS (SELECT 1 FROM return_requests rr 
                                            WHERE rr.order_id = o.order_id AND rr.status = 'completed') 
                               THEN 'returned' 
                               ELSE o.order_status 
                           END AS computed_status,
                           COUNT(*) AS count
                        FROM orders o
                        GROUP BY computed_status");
while ($row = $result->fetch_assoc()) {
    $stats[$row['computed_status']] = (int)$row['count'];
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="admin-header">
    <h1><i class="fas fa-shopping-cart"></i> Orders Management</h1>
    <div class="admin-header-actions">
        <span class="last-update">Total: <?php echo count($orders); ?> orders</span>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<!-- Order Stats -->
<div class="order-stats">
    <div class="order-stat-card all">
        <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
        <div class="stat-info">
            <div class="stat-label">All Orders</div>
            <div class="stat-value"><?php echo array_sum($stats); ?></div>
        </div>
    </div>
    <div class="order-stat-card pending">
        <div class="stat-icon"><i class="fas fa-clock"></i></div>
        <div class="stat-info">
            <div class="stat-label">Pending</div>
            <div class="stat-value"><?php echo $stats['pending'] ?? 0; ?></div>
        </div>
    </div>
    <div class="order-stat-card processing">
        <div class="stat-icon"><i class="fas fa-cog"></i></div>
        <div class="stat-info">
            <div class="stat-label">Processing</div>
            <div class="stat-value"><?php echo $stats['processing'] ?? 0; ?></div>
        </div>
    </div>
    <div class="order-stat-card shipped">
        <div class="stat-icon"><i class="fas fa-shipping-fast"></i></div>
        <div class="stat-info">
            <div class="stat-label">Shipped</div>
            <div class="stat-value"><?php echo $stats['shipped'] ?? 0; ?></div>
        </div>
    </div>
    <div class="order-stat-card delivered">
        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info">
            <div class="stat-label">Delivered</div>
            <div class="stat-value"><?php echo $stats['delivered'] ?? 0; ?></div>
        </div>
    </div>
    <div class="order-stat-card cancelled">
        <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
        <div class="stat-info">
            <div class="stat-label">Cancelled</div>
            <div class="stat-value"><?php echo $stats['cancelled'] ?? 0; ?></div>
        </div>
    </div>
    <div class="order-stat-card returned">
        <div class="stat-icon"><i class="fas fa-undo-alt"></i></div>
        <div class="stat-info">
            <div class="stat-label">Returned</div>
            <div class="stat-value"><?php echo $stats['returned'] ?? 0; ?></div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="admin-card">
    <div class="admin-card-body">
        <form method="GET" class="filter-form">
            <div class="filter-group">
                <input type="text" name="search" placeholder="Search orders, customer..." 
                       value="<?php echo htmlspecialchars($search); ?>" class="form-control">
            </div>
            
            <div class="filter-group">
                <select name="status" class="form-control">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="processing" <?php echo $status == 'processing' ? 'selected' : ''; ?>>Processing</option>
                    <option value="shipped" <?php echo $status == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                    <option value="delivered" <?php echo $status == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                    <option value="cancelled" <?php echo $status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            
            <div class="filter-group">
                <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" 
                       class="form-control" placeholder="From">
            </div>
            
            <div class="filter-group">
                <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" 
                       class="form-control" placeholder="To">
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter"></i> Filter
            </button>
            <a href="orders.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Clear
            </a>
        </form>
    </div>
</div>

<!-- Orders Table -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3><i class="fas fa-list"></i> All Orders (<?php echo count($orders); ?>)</h3>
    </div>
    <div class="admin-card-body">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px;">
                                <i class="fas fa-inbox" style="font-size: 48px; color: #ddd; margin-bottom: 10px; display: block;"></i>
                                <p style="color: #999;">No orders found</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <?php 
                                // UI selection rules (no workflow changes):
                                // - If a return is completed => show 'returned'
                                // - Else if a non-completed return exists (pending/approved/rejected) => force 'delivered'
                                // - Else show the actual DB status
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
                                <td>
                                    <a href="order-details.php?id=<?php echo $order['order_id']; ?>" class="order-link">
                                        <strong><?php echo $order['order_number']; ?></strong>
                                    </a>
                                </td>
                                <td>
                                    <div class="customer-info">
                                        <strong><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></strong>
                                        <small><?php echo htmlspecialchars($order['email']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo $order['item_count']; ?> items
                                    </span>
                                </td>
                                <td><strong><?php echo CURRENCY_SYMBOL . number_format($order['total_amount'], 2); ?></strong></td>
                                <td>
                                    <form method="POST" style="display: inline;" id="status-form-<?php echo $order['order_id']; ?>">
                                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                        <input type="hidden" name="update_status" value="1">
                    <!-- Style reflects the UI-selected status so 'returned' is visibly distinct -->
                    <select class="status-select status-<?php echo $ui_status; ?>" 
                                                name="status"
                                                onchange="confirmStatusUpdate(this, <?php echo $order['order_id']; ?>)">
                                            <option value="pending" <?php echo $ui_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="processing" <?php echo $ui_status == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                            <option value="shipped" <?php echo $ui_status == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                            <option value="delivered" <?php echo $ui_status == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                            <option value="returned" <?php echo $ui_status == 'returned' ? 'selected' : ''; ?>>Returned</option>
                                            <option value="cancelled" <?php echo $ui_status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <span class="payment-badge payment-<?php echo $order['payment_status']; ?>">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="order-details.php?id=<?php echo $order['order_id']; ?>" 
                                           class="action-btn" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="print-invoice.php?id=<?php echo $order['order_id']; ?>" 
                                           target="_blank"
                                           class="action-btn" title="Print Invoice">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
/* Order Stats */
.order-stats {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.order-stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
    transition: transform 0.3s;
    border-left: 4px solid;
}

.order-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.order-stat-card.all { border-left-color: #667eea; }
.order-stat-card.pending { border-left-color: #ffc107; }
.order-stat-card.processing { border-left-color: #00bcd4; }
.order-stat-card.shipped { border-left-color: #9c27b0; }
.order-stat-card.delivered { border-left-color: #26aa99; }
.order-stat-card.cancelled { border-left-color: #dc3545; }

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
    flex-shrink: 0;
}

.order-stat-card.all .stat-icon { background: #667eea; }
.order-stat-card.pending .stat-icon { background: #ffcb30ff; }
.order-stat-card.processing .stat-icon { background: #00bcd4; }
.order-stat-card.shipped .stat-icon { background: #9c27b0; }
.order-stat-card.delivered .stat-icon { background: #26aa99; }
.order-stat-card.cancelled .stat-icon { background: #dc3545; }
.order-stat-card.returned .stat-icon { background: #ff9d00ff; }

.stat-info {
    flex: 1;
}

.stat-label {
    font-size: 13px;
    color: #666;
    margin-bottom: 5px;
    font-weight: 500;
}

.stat-value {
    font-size: 28px;
    font-weight: 700;
    color: #333;
    line-height: 1;
}

/* Filter Form */
.filter-form {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}

.filter-group {
    flex: 1;
    min-width: 180px;
}

/* Customer Info */
.customer-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.customer-info strong {
    font-size: 14px;
    color: #333;
}

.customer-info small {
    font-size: 12px;
    color: #999;
}

/* Order Link */
.order-link {
    color: var(--primary-color);
    font-weight: 600;
    text-decoration: none;
    transition: color 0.3s;
}

.order-link:hover {
    text-decoration: underline;
    color: #c8511b;
}

/* Status Select */
.status-select {
    padding: 6px 12px;
    border-radius: 20px;
    border: 2px solid;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    text-transform: uppercase;
    transition: all 0.3s;
    outline: none;
}

.status-select:hover {
    transform: scale(1.05);
}

.status-select:focus {
    box-shadow: 0 0 0 3px rgba(238, 77, 45, 0.1);
}

.status-select.status-pending {
    background: #fff3cd;
    color: #856404;
    border-color: #ffc107;
}

.status-select.status-processing {
    background: #cfe2ff;
    color: #084298;
    border-color: #00bcd4;
}

.status-select.status-shipped {
    background: #e2d9f3;
    color: #6f42c1;
    border-color: #9c27b0;
}

.status-select.status-delivered {
    background: #d4edda;
    color: #155724;
    border-color: #26aa99;
}

.status-select.status-cancelled {
    background: #f8d7da;
    color: #721c24;
    border-color: #dc3545;
}

/* Unique Returned status styling to differentiate clearly */
.status-select.status-returned {
    background: #fff4e5; /* light amber */
    color: #8a3c00;      /* dark amber text */
    border-color: #ff9d00; /* amber border */
}

/* Payment Badge */
.payment-badge {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 14px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.payment-badge.payment-paid {
    background: #d4edda;
    color: #155724;
}

.payment-badge.payment-pending {
    background: #fff3cd;
    color: #856404;
}

.payment-badge.payment-failed {
    background: #f8d7da;
    color: #721c24;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 6px;
}

.action-btn {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    border: 1px solid #ddd;
    background: white;
    color: #666;
    transition: all 0.3s;
    cursor: pointer;
    text-decoration: none;
}

.action-btn:hover {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
    transform: translateY(-2px);
}

/* Responsive */
@media (max-width: 1400px) {
    .order-stats {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .order-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .stat-value {
        font-size: 22px;
    }
    
    .filter-form {
        flex-direction: column;
    }
    
    .filter-group {
        width: 100%;
    }
    
    .admin-table {
        font-size: 13px;
    }
}

@media (max-width: 480px) {
    .order-stats {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function confirmStatusUpdate(selectElement, orderId) {
    const newStatus = selectElement.value;
    
    if (confirm('Are you sure you want to update this order status to "' + newStatus.toUpperCase() + '"?')) {
        document.getElementById('status-form-' + orderId).submit();
    } else {
        // Reset to original value if cancelled
        location.reload();
    }
}
</script>

<?php include 'includes/footer.php'; ?>