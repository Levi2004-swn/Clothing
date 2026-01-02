<?php
require_once 'config.php';
require_admin_login();

$page_title = "Coupons Management";
$error = '';
$success = '';

// Handle coupon creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_coupon'])) {
    $code = strtoupper(clean_input($_POST['code']));
    $discount_type = clean_input($_POST['discount_type']);
    $discount_value = floatval($_POST['discount_value']);
    $min_purchase = floatval($_POST['min_purchase']);
    $max_discount = floatval($_POST['max_discount']);
    $usage_limit = intval($_POST['usage_limit']);
    $expires_at = clean_input($_POST['expires_at']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Normalize HTML datetime-local (YYYY-MM-DDTHH:MM) to MySQL DATETIME (YYYY-MM-DD HH:MM:SS)
    if (!empty($expires_at)) {
        $expires_at = str_replace('T', ' ', $expires_at);
        if (strlen($expires_at) === 16) { // e.g., 2025-11-09 14:30
            $expires_at .= ':00';
        }
    }
    
    if (empty($code) || $discount_value <= 0 || empty($expires_at)) {
        $error = "Please fill in all required fields";
    } else {
        // Check if code already exists
        $check = $conn->prepare("SELECT coupon_id FROM coupons WHERE code = ?");
        $check->bind_param("s", $code);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $error = "Coupon code already exists";
        } else {
            $stmt = $conn->prepare("INSERT INTO coupons (code, discount_type, discount_value, min_purchase_amount, 
                                   max_discount_amount, usage_limit, expires_at, is_active) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdddisi", $code, $discount_type, $discount_value, $min_purchase, 
                             $max_discount, $usage_limit, $expires_at, $is_active);
            
            if ($stmt->execute()) {
                log_admin_activity('create_coupon', "Created coupon: $code");
                $success = "Coupon created successfully!";
            } else {
                $error = "Failed to create coupon: " . $stmt->error;
            }
        }
    }
}

// Handle coupon update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_coupon'])) {
    $coupon_id = intval($_POST['coupon_id']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $stmt = $conn->prepare("UPDATE coupons SET is_active = ? WHERE coupon_id = ?");
    $stmt->bind_param("ii", $is_active, $coupon_id);
    
    if ($stmt->execute()) {
        log_admin_activity('update_coupon', "Updated coupon ID: $coupon_id");
        $success = "Coupon updated successfully!";
    } else {
        $error = "Failed to update coupon";
    }
}

// Handle coupon deletion
if (isset($_GET['delete']) && has_permission('inventory_manager')) {
    $coupon_id = intval($_GET['delete']);
    
    // Get coupon code for logging
    $result = $conn->query("SELECT code FROM coupons WHERE coupon_id = $coupon_id");
    $coupon = $result ? $result->fetch_assoc() : null;
    
    if ($conn->query("DELETE FROM coupons WHERE coupon_id = $coupon_id")) {
        $deletedCode = ($coupon && isset($coupon['code'])) ? $coupon['code'] : "#$coupon_id";
        log_admin_activity('delete_coupon', "Deleted coupon: " . $deletedCode);
        $success = "Coupon deleted successfully!";
    } else {
        $error = "Failed to delete coupon";
    }
}

// Check if coupons table exists
$table_exists = false;
$table_check = $conn->query("SHOW TABLES LIKE 'coupons'");
if ($table_check && $table_check->num_rows > 0) {
    $table_exists = true;
}

// Get coupons
$coupons = [];
if ($table_exists) {
        $result = $conn->query("SELECT c.*, 
                                                     COALESCE((SELECT COUNT(*) 
                                                                         FROM orders 
                                                                         WHERE coupon_id = c.coupon_id 
                                                                             AND discount_amount > 0), 0) as usage_count
                                                     FROM coupons c
                                                     ORDER BY c.created_at DESC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $coupons[] = $row;
        }
    }
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="admin-header">
    <h1><i class="fas fa-ticket-alt"></i> Coupons & Promotions</h1>
    <div class="admin-header-actions">
        <?php if ($table_exists): ?>
            <button class="btn btn-primary" onclick="openModal()">
                <i class="fas fa-plus"></i> Create Coupon
            </button>
        <?php endif; ?>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<?php if (!$table_exists): ?>
    <div class="admin-card">
        <div class="admin-card-body" style="text-align: center; padding: 60px 20px;">
            <i class="fas fa-database" style="font-size: 64px; color: #ddd; margin-bottom: 20px;"></i>
            <h3 style="color: #666; margin-bottom: 15px;">Coupons Table Not Found</h3>
            <p style="color: #999; margin-bottom: 25px;">The coupons table doesn't exist in your database. Please create it first.</p>
            <button onclick="document.getElementById('sql-modal').style.display='flex'" class="btn btn-primary">
                <i class="fas fa-code"></i> Show SQL Code
            </button>
        </div>
    </div>
    
    <!-- SQL Modal -->
    <div id="sql-modal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h3>Create Coupons Table</h3>
                <button class="modal-close" onclick="document.getElementById('sql-modal').style.display='none'">&times;</button>
            </div>
            <div class="modal-body">
                <p style="margin-bottom: 15px; color: #666;">Copy and run this SQL in phpMyAdmin:</p>
                <textarea readonly style="width: 100%; height: 400px; font-family: monospace; font-size: 13px; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">CREATE TABLE IF NOT EXISTS `coupons` (
  `coupon_id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL UNIQUE,
  `discount_type` enum('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `discount_value` decimal(10,2) NOT NULL,
  `min_purchase_amount` decimal(10,2) DEFAULT 0.00,
  `max_discount_amount` decimal(10,2) DEFAULT 0.00,
  `usage_limit` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`coupon_id`),
  KEY `code` (`code`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add coupon support to orders table
ALTER TABLE `orders` 
ADD COLUMN IF NOT EXISTS `coupon_id` int(11) DEFAULT NULL AFTER `user_id`,
ADD COLUMN IF NOT EXISTS `discount_amount` decimal(10,2) DEFAULT 0.00 AFTER `subtotal`;
</textarea>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="document.getElementById('sql-modal').style.display='none'">Close</button>
            </div>
        </div>
    </div>
<?php else: ?>

<!-- Coupons Table -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3><i class="fas fa-list"></i> All Coupons (<?php echo count($coupons); ?>)</h3>
    </div>
    <div class="admin-card-body">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Type</th>
                        <th>Discount</th>
                        <th>Min Purchase</th>
                        <th>Max Discount</th>
                        <th>Usage</th>
                        <th>Expires</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($coupons)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px;">
                                <i class="fas fa-ticket-alt" style="font-size: 48px; color: #ddd; margin-bottom: 10px; display: block;"></i>
                                <p style="color: #999;">No coupons found. Create your first coupon!</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($coupons as $coupon): ?>
                            <?php
                            $is_expired = strtotime($coupon['expires_at']) < time();
                            $is_limit_reached = $coupon['usage_limit'] > 0 && $coupon['usage_count'] >= $coupon['usage_limit'];
                            ?>
                            <tr>
                                <td>
                                    <strong style="font-family: monospace; font-size: 16px; color: var(--primary-color);">
                                        <?php echo htmlspecialchars($coupon['code']); ?>
                                    </strong>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $coupon['discount_type'] == 'percentage' ? 'info' : 'success'; ?>">
                                        <?php echo ucfirst($coupon['discount_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong>
                                        <?php if ($coupon['discount_type'] == 'percentage'): ?>
                                            <?php echo $coupon['discount_value']; ?>%
                                        <?php else: ?>
                                            <?php echo CURRENCY_SYMBOL . number_format($coupon['discount_value'], 2); ?>
                                        <?php endif; ?>
                                    </strong>
                                </td>
                                <td>
                                    <?php if ($coupon['min_purchase_amount'] > 0): ?>
                                        <?php echo CURRENCY_SYMBOL . number_format($coupon['min_purchase_amount'], 2); ?>
                                    <?php else: ?>
                                        <span style="color: #999;">No minimum</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($coupon['max_discount_amount'] > 0): ?>
                                        <?php echo CURRENCY_SYMBOL . number_format($coupon['max_discount_amount'], 2); ?>
                                    <?php else: ?>
                                        <span style="color: #999;">No limit</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo $coupon['usage_count']; ?>
                                        <?php if ($coupon['usage_limit'] > 0): ?>
                                            / <?php echo $coupon['usage_limit']; ?>
                                        <?php else: ?>
                                            / ∞
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $expires_date = strtotime($coupon['expires_at']);
                                    $days_left = ceil(($expires_date - time()) / 86400);
                                    ?>
                                    <div>
                                        <?php echo date('M d, Y', $expires_date); ?>
                                        <?php if (!$is_expired && $days_left <= 7): ?>
                                            <small style="color: #ff9800; display: block;">
                                                <i class="fas fa-exclamation-triangle"></i> <?php echo $days_left; ?> days left
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($is_expired): ?>
                                        <span class="status-badge status-cancelled">Expired</span>
                                    <?php elseif ($is_limit_reached): ?>
                                        <span class="status-badge status-inactive">Limit Reached</span>
                                    <?php elseif ($coupon['is_active']): ?>
                                        <span class="status-badge status-active">Active</span>
                                    <?php else: ?>
                                        <span class="status-badge status-inactive">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn" title="Toggle Status" 
                                                onclick="toggleStatus(<?php echo $coupon['coupon_id']; ?>, <?php echo $coupon['is_active'] ? 0 : 1; ?>)">
                                            <i class="fas fa-<?php echo $coupon['is_active'] ? 'toggle-on' : 'toggle-off'; ?>"></i>
                                        </button>
                                        <?php if (has_permission('inventory_manager')): ?>
                                            <button class="action-btn delete" title="Delete"
                                                    onclick="if(confirm('Are you sure you want to delete this coupon?')) window.location.href='?delete=<?php echo $coupon['coupon_id']; ?>'">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
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

<!-- Create Coupon Modal -->
<div id="coupon-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle"></i> Create New Coupon</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" action="">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Coupon Code *</label>
                        <input type="text" name="code" class="form-control" required 
                               placeholder="e.g., SUMMER2025" 
                               style="text-transform: uppercase;">
                        <small style="color: #999; font-size: 12px;">Will be converted to uppercase</small>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-percentage"></i> Discount Type *</label>
                        <select name="discount_type" class="form-control" required id="discount-type">
                            <option value="percentage">Percentage (%)</option>
                            <option value="fixed">Fixed Amount (<?php echo CURRENCY_SYMBOL; ?>)</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-calculator"></i> Discount Value *</label>
                        <input type="number" name="discount_value" class="form-control" 
                               step="0.01" min="0.01" required placeholder="10.00">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-shopping-cart"></i> Minimum Purchase Amount</label>
                        <input type="number" name="min_purchase" class="form-control" 
                               step="0.01" min="0" value="0" placeholder="0.00">
                        <small style="color: #999; font-size: 12px;">0 = No minimum</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-money-bill-wave"></i> Maximum Discount Amount</label>
                        <input type="number" name="max_discount" class="form-control" 
                               step="0.01" min="0" value="0" placeholder="0.00">
                        <small style="color: #999; font-size: 12px;">For percentage discounts (0 = No limit)</small>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-users"></i> Usage Limit</label>
                        <input type="number" name="usage_limit" class="form-control" 
                               min="0" value="0" placeholder="0">
                        <small style="color: #999; font-size: 12px;">0 = Unlimited</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-calendar"></i> Expiration Date *</label>
                    <input type="datetime-local" name="expires_at" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" name="is_active" checked>
                        <span><i class="fas fa-check-circle"></i> Active (Available for use)</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" name="create_coupon" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create Coupon
                </button>
            </div>
        </form>
    </div>
</div>

<?php endif; ?>

<style>
/* Form Row */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
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
}

.action-btn:hover {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

.action-btn.delete:hover {
    background: var(--danger-color);
    border-color: var(--danger-color);
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.6);
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 700px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

.modal-header {
    padding: 24px;
    border-bottom: 1px solid #e5e5e5;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    font-size: 20px;
    color: #333;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    color: #999;
    cursor: pointer;
}

.modal-close:hover {
    color: var(--danger-color);
}

.modal-body {
    padding: 24px;
}

.modal-footer {
    padding: 20px 24px;
    border-top: 1px solid #e5e5e5;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function openModal() {
    document.getElementById('coupon-modal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('coupon-modal').style.display = 'none';
}

function toggleStatus(couponId, newStatus) {
    if (confirm('Are you sure you want to ' + (newStatus ? 'activate' : 'deactivate') + ' this coupon?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="coupon_id" value="${couponId}">
            <input type="hidden" name="is_active" value="${newStatus}">
            <input type="hidden" name="update_coupon" value="1">
        `;
        if (!newStatus) {
            form.querySelector('[name="is_active"]').remove();
        }
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modal on outside click
window.onclick = function(event) {
    const modal = document.getElementById('coupon-modal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>

<?php include 'includes/footer.php'; ?>