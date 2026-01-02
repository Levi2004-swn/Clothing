<?php
require_once 'config.php';
require_admin_login();

$page_title = "Order Details";
$success = '';
$error = '';

// Get order ID
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$order_id) {
    header('Location: orders.php');
    exit;
}

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $new_status = clean_input($_POST['order_status']);
    $admin_notes = isset($_POST['admin_notes']) ? clean_input($_POST['admin_notes']) : '';
    // Optional payment status update (for COD orders etc.)
    $new_payment_status = isset($_POST['payment_status']) ? strtolower(clean_input($_POST['payment_status'])) : null;
    $valid_payment_statuses = ['pending', 'paid'];
    $updatePayment = in_array($new_payment_status, $valid_payment_statuses, true);

    if ($updatePayment) {
        $stmt = $conn->prepare("UPDATE orders SET order_status = ?, payment_status = ? WHERE order_id = ?");
        $stmt->bind_param("ssi", $new_status, $new_payment_status, $order_id);
    } else {
        $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
        $stmt->bind_param("si", $new_status, $order_id);
    }

    if ($stmt->execute()) {
        // Update admin notes if column exists
        $check_column = $conn->query("SHOW COLUMNS FROM orders LIKE 'admin_notes'");
        if ($check_column->num_rows > 0) {
            $stmt2 = $conn->prepare("UPDATE orders SET admin_notes = ? WHERE order_id = ?");
            $stmt2->bind_param("si", $admin_notes, $order_id);
            $stmt2->execute();
        }

        // Log activity
        if ($updatePayment) {
            log_admin_activity('update_order_status', "Updated order #$order_id to $new_status and payment to $new_payment_status");
        } else {
            log_admin_activity('update_order_status', "Updated order #$order_id to $new_status");
        }
        $success = $updatePayment ? "Order and payment status updated successfully!" : "Order status updated successfully!";
        
        // Refresh order data
        $stmt = $conn->prepare("SELECT 
                                   o.*, 
                                   u.first_name, u.last_name, u.email, u.phone,
                                   ua.full_name AS shipping_name,
                                   CONCAT_WS(', ', ua.address_line1, NULLIF(ua.address_line2, '')) AS shipping_address,
                                   ua.city AS shipping_city,
                                   ua.state AS shipping_state,
                                   ua.postal_code AS shipping_zip,
                                   ua.phone AS shipping_phone
                               FROM orders o
                               JOIN users u ON o.user_id = u.user_id
                               LEFT JOIN user_addresses ua ON o.shipping_address_id = ua.address_id
                               WHERE o.order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
    } else {
        $error = "Failed to update order status";
    }
}

// Fetch order details (FIXED)
if (!isset($order)) {
    $stmt = $conn->prepare("SELECT 
                               o.*, 
                               u.first_name, u.last_name, u.email, u.phone,
                               ua.full_name AS shipping_name,
                               CONCAT_WS(', ', ua.address_line1, NULLIF(ua.address_line2, '')) AS shipping_address,
                               ua.city AS shipping_city,
                               ua.state AS shipping_state,
                               ua.postal_code AS shipping_zip,
                               ua.phone AS shipping_phone
                           FROM orders o
                           JOIN users u ON o.user_id = u.user_id
                           LEFT JOIN user_addresses ua ON o.shipping_address_id = ua.address_id
                           WHERE o.order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
}

if (!$order) {
    header('Location: orders.php');
    exit;
}

    // UI-only flags to reflect return status without changing workflow
    try {
        $is_return_completed = false;
        $has_noncompleted_return = false;

        if (isset($conn) && $conn instanceof mysqli) {
            // Completed return exists?
            if ($__res1 = $conn->query("SELECT EXISTS(SELECT 1 FROM return_requests rr WHERE rr.order_id = " . (int)$order_id . " AND rr.status = 'completed') AS rc")) {
                if ($__row1 = $__res1->fetch_assoc()) {
                    $is_return_completed = ((int)($__row1['rc'] ?? 0) === 1);
                }
            }
            // Any non-completed (pending/approved/rejected) return exists?
            if ($__res2 = $conn->query("SELECT EXISTS(SELECT 1 FROM return_requests rr WHERE rr.order_id = " . (int)$order_id . " AND rr.status IN ('pending','approved','rejected')) AS rnc")) {
                if ($__row2 = $__res2->fetch_assoc()) {
                    $has_noncompleted_return = ((int)($__row2['rnc'] ?? 0) === 1);
                }
            }
        }
    } catch (Throwable $e) {
        // fail silently; purely presentational
    }

    // Compute admin UI status: show 'returned' only when return is completed; otherwise
    // if DB says 'returned' or a non-completed return exists, display as 'delivered'.
    $admin_ui_status = $order['order_status'] ?? 'pending';
    if ($is_return_completed) {
        $admin_ui_status = 'returned';
    } elseif (($admin_ui_status === 'returned') || $has_noncompleted_return) {
        $admin_ui_status = 'delivered';
    }

// Fetch order items
$order_items = [];
$stmt = $conn->prepare("SELECT oi.*, p.product_name, pi.image_url, pv.size, pv.color
                       FROM order_items oi
                       JOIN products p ON oi.product_id = p.product_id
                       LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                       LEFT JOIN product_variants pv ON oi.variant_id = pv.variant_id
                       WHERE oi.order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $order_items[] = $row;
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="admin-header">
    <h1><i class="fas fa-receipt"></i> Order #<?php echo $order['order_number']; ?></h1>
    <div class="admin-header-actions">
        <a href="orders.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Orders
        </a>
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Print Invoice
        </button>
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

<div class="admin-grid-3">
    <!-- Left Column - Order Items -->
    <div style="grid-column: span 2;">
        <!-- Order Items -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3><i class="fas fa-shopping-bag"></i> Order Items (<?php echo count($order_items); ?>)</h3>
            </div>
            <div class="admin-card-body">
                <?php if (empty($order_items)): ?>
                    <div class="empty-state">
                        <i class="fas fa-shopping-bag"></i>
                        <p>No items in this order</p>
                    </div>
                <?php else: ?>
                    <div class="order-items-list">
                        <?php foreach ($order_items as $item): ?>
                            <div class="order-item">
                                <img src="../<?php echo $item['image_url'] ?? 'assets/images/no-image.png'; ?>" 
                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                     class="order-item-image"
                                     onerror="this.src='../assets/images/no-image.png'">
                                <div class="order-item-details">
                                    <h4><?php echo htmlspecialchars($item['product_name']); ?></h4>
                                    <?php if (!empty($item['size']) || !empty($item['color'])): ?>
                                        <p class="item-variant">
                                            <?php if (!empty($item['size'])): ?>
                                                <span><i class="fas fa-ruler"></i> Size: <?php echo htmlspecialchars($item['size']); ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($item['color'])): ?>
                                                <span><i class="fas fa-palette"></i> Color: <?php echo htmlspecialchars($item['color']); ?></span>
                                            <?php endif; ?>
                                        </p>
                                    <?php endif; ?>
                                    <p class="item-price">
                                        <?php echo CURRENCY_SYMBOL . number_format($item['price'], 2); ?> × <?php echo $item['quantity']; ?>
                                    </p>
                                </div>
                                <div class="order-item-total">
                                    <strong><?php echo CURRENCY_SYMBOL . number_format($item['price'] * $item['quantity'], 2); ?></strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Order Summary -->
                    <div class="order-summary">
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span><?php echo CURRENCY_SYMBOL . number_format($order['subtotal'], 2); ?></span>
                        </div>
                        <?php if (isset($order['discount_amount']) && $order['discount_amount'] > 0): ?>
                            <div class="summary-row discount">
                                <span>Discount:</span>
                                <span>-<?php echo CURRENCY_SYMBOL . number_format($order['discount_amount'], 2); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="summary-row">
                            <span>Tax:</span>
                            <span><?php echo CURRENCY_SYMBOL . number_format($order['tax_amount'] ?? 0, 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Shipping:</span>
                            <span><?php echo CURRENCY_SYMBOL . number_format($order['shipping_amount'] ?? 0, 2); ?></span>
                        </div>
                        <div class="summary-row total">
                            <span>Total:</span>
                            <span><?php echo CURRENCY_SYMBOL . number_format($order['total_amount'], 2); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Shipping Address -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3><i class="fas fa-map-marker-alt"></i> Shipping Address</h3>
            </div>
            <div class="admin-card-body">
                <div class="address-info">
                    <p><strong><i class="fas fa-user"></i> <?php echo htmlspecialchars($order['shipping_name'] ?? $order['first_name'] . ' ' . $order['last_name']); ?></strong></p>
                    <p><i class="fas fa-home"></i> <?php echo htmlspecialchars($order['shipping_address'] ?? 'N/A'); ?></p>
                    <p><i class="fas fa-city"></i> <?php echo htmlspecialchars($order['shipping_city'] ?? 'N/A'); ?>, <?php echo htmlspecialchars($order['shipping_state'] ?? 'N/A'); ?></p>
                    <p><i class="fas fa-mail-bulk"></i> <?php echo htmlspecialchars($order['shipping_zip'] ?? 'N/A'); ?></p>
                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($order['shipping_phone'] ?? $order['phone'] ?? 'N/A'); ?></p>
                </div>

                <?php if (($order['order_status'] ?? '') === 'cancelled' && !empty($order['cancel_reason'] ?? '')): ?>
                    <div style="margin-top: 15px; padding: 12px 14px; border-radius: 8px; background: #fff6f6; border: 1px solid #f5c2c7;">
                        <div style="display:flex; align-items:center; gap:8px; color:#b4232a; font-weight:600; margin-bottom:6px;">
                            <i class="fas fa-comment-slash"></i>
                            <span>Cancellation Reason</span>
                        </div>
                        <div style="color:#7a1a1f; line-height:1.5; white-space:pre-wrap;">
                            <?php echo nl2br(htmlspecialchars($order['cancel_reason'])); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column - Order Info & Actions -->
    <div>
        <!-- Order Status -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3><i class="fas fa-info-circle"></i> Order Information</h3>
            </div>
            <div class="admin-card-body">
                <div class="order-info-list">
                    <div class="info-row">
                        <span class="info-label">Order Number:</span>
                        <span class="info-value"><strong><?php echo $order['order_number']; ?></strong></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Date:</span>
                        <span class="info-value"><?php echo date('M d, Y - g:i A', strtotime($order['created_at'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status:</span>
                        <span class="info-value">
                            <span class="status-badge status-<?php echo $admin_ui_status; ?>">
                                <?php echo ucfirst($admin_ui_status); ?>
                            </span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Payment:</span>
                        <span class="info-value">
                            <span class="badge badge-<?php echo $order['payment_status'] == 'paid' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($order['payment_status']); ?>
                            </span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Payment Method:</span>
                        <span class="info-value"><?php echo ucfirst($order['payment_method'] ?? 'N/A'); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer Info -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3><i class="fas fa-user"></i> Customer Information</h3>
            </div>
            <div class="admin-card-body">
                <div class="customer-info">
                    <p>
                        <strong><i class="fas fa-user"></i> <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></strong>
                    </p>
                    <p>
                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($order['email']); ?>
                    </p>
                    <p>
                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($order['phone'] ?? 'N/A'); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Update Status -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3><i class="fas fa-edit"></i> Update Order Status</h3>
            </div>
            <div class="admin-card-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Order Status</label>
                        <select name="order_status" class="form-control" required>
                            <option value="pending" <?php echo $admin_ui_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $admin_ui_status == 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="shipped" <?php echo $admin_ui_status == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo $admin_ui_status == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $admin_ui_status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="returned" <?php echo $admin_ui_status == 'returned' ? 'selected' : ''; ?>>Returned</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Payment Status</label>
                        <select name="payment_status" class="form-control">
                            <option value="pending" <?php echo ($order['payment_status'] ?? 'pending') === 'pending' ? 'selected' : ''; ?>>Pending (unpaid)</option>
                            <option value="paid" <?php echo ($order['payment_status'] ?? '') === 'paid' ? 'selected' : ''; ?>>Paid</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Admin Notes (Optional)</label>
                        <textarea name="admin_notes" class="form-control" rows="3" placeholder="Add notes about this order update..."></textarea>
                    </div>
                    
                    <button type="submit" name="update_status" class="btn btn-primary btn-full">
                        <i class="fas fa-save"></i> Update Status
                    </button>
                </form>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
            </div>
            <div class="admin-card-body">
                <div class="quick-actions">
                    <button onclick="window.print()" class="btn btn-secondary btn-full">
                        <i class="fas fa-print"></i> Print Invoice
                    </button>
                    <a href="mailto:<?php echo $order['email']; ?>" class="btn btn-secondary btn-full">
                        <i class="fas fa-envelope"></i> Email Customer
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Order Items */
.order-items-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.order-item {
    display: flex;
    gap: 15px;
    padding: 15px;
    background: #fafafa;
    border-radius: 8px;
    align-items: center;
}

.order-item-image {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #f0f0f0;
}

.order-item-details {
    flex: 1;
}

.order-item-details h4 {
    margin: 0 0 8px 0;
    font-size: 15px;
    color: #333;
}

.item-variant {
    display: flex;
    gap: 12px;
    font-size: 13px;
    color: #666;
    margin: 5px 0;
}

.item-variant i {
    margin-right: 4px;
}

.item-price {
    font-size: 14px;
    color: #666;
    margin: 5px 0 0 0;
}

.order-item-total {
    font-size: 16px;
    font-weight: 700;
    color: var(--primary-color);
}

/* Order Summary */
.order-summary {
    margin-top: 25px;
    padding-top: 20px;
    border-top: 2px solid #e5e5e5;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    font-size: 14px;
}

.summary-row.discount {
    color: var(--success-color);
}

.summary-row.total {
    border-top: 2px solid #e5e5e5;
    margin-top: 10px;
    padding-top: 15px;
    font-size: 18px;
    font-weight: 700;
    color: var(--primary-color);
}

/* Address Info */
.address-info p {
    margin: 8px 0;
    color: #666;
    line-height: 1.8;
}

.address-info p i {
    margin-right: 8px;
    color: var(--primary-color);
    width: 20px;
}

.address-info p strong {
    color: #333;
    font-size: 16px;
}

/* Order Info List */
.order-info-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    color: #666;
    font-size: 14px;
}

.info-value {
    font-weight: 600;
    color: #333;
    text-align: right;
}

/* Customer Info */
.customer-info p {
    margin: 10px 0;
    color: #666;
}

.customer-info p strong {
    color: #333;
    font-size: 16px;
    display: block;
    margin-bottom: 5px;
}

.customer-info i {
    margin-right: 8px;
    color: var(--primary-color);
    width: 20px;
}

/* Quick Actions */
.quick-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #999;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 15px;
    color: #ddd;
    display: block;
}

/* Badge */
.badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}

.badge-success {
    background: #d1fae5;
    color: #065f46;
}

.badge-warning {
    background: #fef3c7;
    color: #92400e;
}

/* Print Styles */
@media print {
    .admin-sidebar,
    .admin-topnav,
    .admin-header-actions,
    .admin-card-header h3 i,
    .quick-actions,
    .btn,
    .alert,
    form {
        display: none !important;
    }
    
    .admin-content {
        margin: 0;
        padding: 20px;
    }
    
    .admin-card {
        box-shadow: none;
        border: 1px solid #ddd;
        page-break-inside: avoid;
    }
}

/* Responsive */
@media (max-width: 1024px) {
    .admin-grid-3 {
        grid-template-columns: 1fr;
    }
    
    .admin-grid-3 > div:first-child {
        grid-column: span 1;
    }
}

@media (max-width: 768px) {
    .order-item {
        flex-direction: column;
        text-align: center;
    }
    
    .order-item-image {
        margin: 0 auto;
    }
    
    .info-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .info-value {
        text-align: left;
    }
}
</style>

<?php include 'includes/footer.php'; ?>