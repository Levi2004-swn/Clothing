<?php
require_once 'config.php';
require_admin_login();

$page_title = "User Details";
$success = '';
$error = '';

// Get user ID
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$user_id) {
    header('Location: users.php');
    exit;
}

// Handle user update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_user'])) {
    $first_name = clean_input($_POST['first_name']);
    $last_name  = clean_input($_POST['last_name']);
    $phone      = clean_input($_POST['phone']);
    $is_active  = isset($_POST['is_active']) ? 1 : 0;

    // Fetch current email (email must NOT be changed on this page)
    $email_lookup = $conn->prepare("SELECT email FROM users WHERE user_id = ?");
    $email_lookup->bind_param("i", $user_id);
    $email_lookup->execute();
    $email_row = $email_lookup->get_result()->fetch_assoc();
    $current_email = $email_row['email'] ?? '';

    // Update only allowed fields (no email change here)
    $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ?, is_active = ? WHERE user_id = ?");
    $stmt->bind_param("sssii", $first_name, $last_name, $phone, $is_active, $user_id);

    if ($stmt->execute()) {
        log_admin_activity('update_user', "Updated user: $current_email");
        $success = "User updated successfully!";
    } else {
        $error = "Failed to update user";
    }
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    $new_password = clean_input($_POST['new_password']);
    
    if (strlen($new_password) < 10) {
        $error = "Password must be at least 10 characters";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($stmt->execute()) {
            log_admin_activity('reset_user_password', "Reset password for user ID: $user_id");
            $success = "Password reset successfully!";
        } else {
            $error = "Failed to reset password";
        }
    }
}

// Fetch user details
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header('Location: users.php');
    exit;
}

// Fetch user orders
$orders = [];
// Include UI-only return flags to align status display with Orders page:
// - return_completed: completed return exists for the order
// - return_noncompleted_exists: any pending/approved/rejected return exists for the order
$stmt = $conn->prepare("SELECT o.*, 
               COUNT(oi.order_item_id) AS item_count,
               EXISTS (SELECT 1 FROM return_requests rr 
                   WHERE rr.order_id = o.order_id AND rr.status = 'completed') AS return_completed,
               EXISTS (SELECT 1 FROM return_requests rr2 
                   WHERE rr2.order_id = o.order_id AND rr2.status IN ('pending','approved','rejected')) AS return_noncompleted_exists
               FROM orders o
               LEFT JOIN order_items oi ON o.order_id = oi.order_id
               WHERE o.user_id = ?
               GROUP BY o.order_id
               ORDER BY o.created_at DESC
               LIMIT 10");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

// Get user statistics
$stats = [];

// Total orders
$result = $conn->query("SELECT COUNT(*) as total FROM orders WHERE user_id = $user_id");
$stats['total_orders'] = $result->fetch_assoc()['total'];

// Total spent
$result = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE user_id = $user_id AND payment_status = 'paid'");
$stats['total_spent'] = $result->fetch_assoc()['total'] ?? 0;

// Average order value
$stats['avg_order'] = $stats['total_orders'] > 0 ? $stats['total_spent'] / $stats['total_orders'] : 0;

// Pending orders
$result = $conn->query("SELECT COUNT(*) as total FROM orders WHERE user_id = $user_id AND order_status IN ('pending', 'processing')");
$stats['pending_orders'] = $result->fetch_assoc()['total'];

// Fetch user addresses (if addresses table exists)
$addresses = [];
$table_check = $conn->query("SHOW TABLES LIKE 'user_addresses'");
if ($table_check->num_rows > 0) {
    $stmt = $conn->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $addresses[] = $row;
    }
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="admin-header">
    <h1><i class="fas fa-user"></i> User Details - <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h1>
    <div class="admin-header-actions">
        <a href="users.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Customers
        </a>
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

<!-- User Statistics -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">
            <i class="fas fa-shopping-cart"></i>
        </div>
        <div class="stat-info">
            <div class="stat-label">Total Orders</div>
            <div class="stat-value"><?php echo $stats['total_orders']; ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="stat-info">
            <div class="stat-label">Total Spent</div>
            <div class="stat-value"><?php echo CURRENCY_SYMBOL . number_format($stats['total_spent'], 2); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-info">
            <div class="stat-label">Average Order</div>
            <div class="stat-value"><?php echo CURRENCY_SYMBOL . number_format($stats['avg_order'], 2); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-info">
            <div class="stat-label">Pending Orders</div>
            <div class="stat-value"><?php echo $stats['pending_orders']; ?></div>
        </div>
    </div>
</div>

<div class="admin-grid-3">
    <!-- Left Column -->
    <div style="grid-column: span 2;">
        <!-- Recent Orders -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3><i class="fas fa-list"></i> Recent Orders (<?php echo count($orders); ?>)</h3>
            </div>
            <div class="admin-card-body">
                <?php if (empty($orders)): ?>
                    <div class="empty-state">
                        <i class="fas fa-shopping-cart"></i>
                        <p>No orders yet</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Items</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>
                                            <a href="order-details.php?id=<?php echo $order['order_id']; ?>" class="order-link">
                                                <?php echo $order['order_number']; ?>
                                            </a>
                                        </td>
                                        <td><?php echo $order['item_count']; ?> items</td>
                                        <td><strong><?php echo CURRENCY_SYMBOL . number_format($order['total_amount'], 2); ?></strong></td>
                                        <td>
                                            <?php 
                                                // Match admin/orders.php UI rules without changing workflow:
                                                // - If a return is completed => show 'returned'
                                                // - Else if a non-completed return exists => force 'delivered'
                                                // - Else show actual DB status
                                                $is_return_completed = !empty($order['return_completed']) && (int)$order['return_completed'] === 1; 
                                                $has_noncompleted_return = !empty($order['return_noncompleted_exists']) && (int)$order['return_noncompleted_exists'] === 1;
                                                $ui_status = $order['order_status'];
                                                if ($is_return_completed) {
                                                    $ui_status = 'returned';
                                                } elseif ($has_noncompleted_return) {
                                                    $ui_status = 'delivered';
                                                }
                                            ?>
                                            <a class="status-badge status-<?php echo $ui_status; ?>" href="order-details.php?id=<?php echo $order['order_id']; ?>" title="View order status">
                                                <?php echo ucfirst($ui_status); ?>
                                            </a>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <a href="order-details.php?id=<?php echo $order['order_id']; ?>" class="action-btn" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Addresses -->
        <?php if (!empty($addresses)): ?>
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3><i class="fas fa-map-marker-alt"></i> Saved Addresses</h3>
                </div>
                <div class="admin-card-body">
                    <div class="addresses-grid">
                        <?php foreach ($addresses as $address): ?>
                            <div class="address-card">
                                <?php if ($address['is_default']): ?>
                                    <span class="default-badge">Default</span>
                                <?php endif; ?>
                                <h4><?php echo htmlspecialchars($address['address_label'] ?? 'Address'); ?></h4>
                                <p><?php echo htmlspecialchars($address['full_name'] ?? ''); ?></p>
                                <p><?php echo htmlspecialchars($address['address_line1'] ?? ''); ?></p>
                                <?php if (!empty($address['address_line2'])): ?>
                                    <p><?php echo htmlspecialchars($address['address_line2']); ?></p>
                                <?php endif; ?>
                                <p><?php echo htmlspecialchars($address['city'] ?? ''); ?>, <?php echo htmlspecialchars($address['state'] ?? ''); ?> <?php echo htmlspecialchars($address['zip_code'] ?? ''); ?></p>
                                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($address['phone'] ?? ''); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Right Column -->
    <div>
        <!-- User Information -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3><i class="fas fa-info-circle"></i> User Information</h3>
            </div>
            <div class="admin-card-body">
                <div class="user-info-list">
                    <div class="info-item">
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            <span class="status-badge status-<?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Phone</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Member Since</div>
                        <div class="info-value"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Last Login</div>
                        <div class="info-value">
                            <?php 
                            if (!empty($user['last_login'])) {
                                echo date('M d, Y g:i A', strtotime($user['last_login']));
                            } else {
                                echo 'Never';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit User -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3><i class="fas fa-edit"></i> Edit User</h3>
            </div>
            <div class="admin-card-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="first_name" class="form-control" 
                               value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" class="form-control" 
                               value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" 
                               value="<?php echo htmlspecialchars($user['email']); ?>" readonly disabled>
                    </div>
                    
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" name="is_active" <?php echo $user['is_active'] ? 'checked' : ''; ?>>
                            <span>Active Account</span>
                        </label>
                    </div>
                    
                    <button type="submit" name="update_user" class="btn btn-primary btn-full">
                        <i class="fas fa-save"></i> Update User
                    </button>
                </form>
            </div>
        </div>

        <!-- Reset Password -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3><i class="fas fa-key"></i> Reset Password</h3>
            </div>
            <div class="admin-card-body">
                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to reset this user\'s password?')">
                    <div class="form-group">
                        <label>New Password * (minimum 10 characters)</label>
                        <input type="password" name="new_password" class="form-control" 
                               placeholder="Enter new password" minlength="10" required>
                        <div class="pwd-strength"></div>
                    </div>
                    
                    <button type="submit" name="reset_password" class="btn btn-danger btn-full">
                        <i class="fas fa-key"></i> Reset Password
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
                    <a href="mailto:<?php echo $user['email']; ?>" class="btn btn-secondary btn-full">
                        <i class="fas fa-envelope"></i> Send Email
                    </a>
                    <a href="orders.php?search=<?php echo urlencode($user['email']); ?>" class="btn btn-secondary btn-full">
                        <i class="fas fa-list"></i> View All Orders
                    </a>
                    <?php if (has_permission('super_admin')): ?>
                        <button onclick="if(confirm('Are you sure you want to delete this user? This action cannot be undone.')) window.location.href='delete-user.php?id=<?php echo $user_id; ?>'" 
                                class="btn btn-danger btn-full">
                            <i class="fas fa-trash"></i> Delete User
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Statistics Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
    transition: transform 0.3s;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
}

.stat-icon.blue { background: #667eea; }
.stat-icon.green { background: #26aa99; }
.stat-icon.purple { background: #9c27b0; }
.stat-icon.orange { background: #ff9800; }

.stat-info {
    flex: 1;
}

.stat-label {
    font-size: 13px;
    color: #666;
    margin-bottom: 5px;
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #333;
}

/* User Info List */
.user-info-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.info-item {
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
}

.info-item:last-child {
    border-bottom: none;
}

.info-label {
    font-size: 12px;
    color: #999;
    text-transform: uppercase;
    margin-bottom: 5px;
    font-weight: 600;
}

.info-value {
    font-size: 14px;
    color: #333;
    font-weight: 500;
}

/* Order Link */
.order-link {
    color: var(--primary-color);
    font-weight: 600;
    text-decoration: none;
}

.order-link:hover {
    text-decoration: underline;
}

/* Addresses Grid */
.addresses-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.address-card {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid var(--primary-color);
    position: relative;
}

.address-card h4 {
    font-size: 16px;
    color: #333;
    margin: 0 0 15px 0;
}

.address-card p {
    font-size: 14px;
    color: #666;
    margin: 5px 0;
    line-height: 1.6;
}

.default-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #26aa99;
    color: white;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
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

/* Action Button */
.action-btn {
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    border: 1px solid #ddd;
    background: white;
    color: #666;
    transition: all 0.3s;
    text-decoration: none;
}

.action-btn:hover {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

/* Responsive */
@media (max-width: 1200px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .addresses-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .admin-grid-3 {
        grid-template-columns: 1fr;
    }
    
    .admin-grid-3 > div:first-child {
        grid-column: span 1;
    }
}
</style>

<?php include 'includes/footer.php'; ?>