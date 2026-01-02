<?php
require_once 'config.php';
$page_title = "System Settings";

if (!has_permission('super_admin')) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $settings = [
        'site_name' => clean_input($_POST['site_name']),
        'site_email' => clean_input($_POST['site_email']),
        'tax_rate' => floatval($_POST['tax_rate']),
        'shipping_fee' => floatval($_POST['shipping_fee']),
        'free_shipping_threshold' => floatval($_POST['free_shipping_threshold']),
        'currency_symbol' => clean_input($_POST['currency_symbol']),
        'items_per_page' => intval($_POST['items_per_page'])
    ];
    
    foreach ($settings as $key => $value) {
        $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) 
                               ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->bind_param("sss", $key, $value, $value);
        $stmt->execute();
    }
    
    log_admin_activity('update_settings', 'System settings updated');
    $success = "Settings updated successfully!";
}

// Get current settings
$current_settings = [];
$result = $conn->query("SELECT setting_key, setting_value FROM site_settings");
while ($row = $result->fetch_assoc()) {
    $current_settings[$row['setting_key']] = $row['setting_value'];
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="admin-header">
    <h1>System Settings</h1>
</div>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<form method="POST" action="">
    <div class="admin-grid-2">
        <!-- General Settings -->
        <div>
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3><i class="fas fa-cog"></i> General Settings</h3>
                </div>
                <div class="admin-card-body">
                    <div class="form-group">
                        <label>Site Name</label>
                        <input type="text" name="site_name" class="form-control" 
                               value="<?php echo htmlspecialchars($current_settings['site_name'] ?? SITE_NAME); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Site Email</label>
                        <input type="email" name="site_email" class="form-control" 
                               value="<?php echo htmlspecialchars($current_settings['site_email'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Currency Symbol</label>
                        <input type="text" name="currency_symbol" class="form-control" 
                               value="<?php echo htmlspecialchars($current_settings['currency_symbol'] ?? CURRENCY_SYMBOL); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Items Per Page</label>
                        <input type="number" name="items_per_page" class="form-control" min="1" max="100"
                               value="<?php echo htmlspecialchars($current_settings['items_per_page'] ?? 20); ?>" required>
                    </div>
                </div>
            </div>
            
            <!-- Payment Settings -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3><i class="fas fa-credit-card"></i> Payment Settings</h3>
                </div>
                <div class="admin-card-body">
                    <div class="form-group">
                        <label>Tax Rate (%)</label>
                        <input type="number" name="tax_rate" class="form-control" step="0.01" min="0" max="100"
                               value="<?php echo htmlspecialchars($current_settings['tax_rate'] ?? 10); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Shipping Fee</label>
                        <input type="number" name="shipping_fee" class="form-control" step="0.01" min="0"
                               value="<?php echo htmlspecialchars($current_settings['shipping_fee'] ?? 5.99); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Free Shipping Threshold</label>
                        <input type="number" name="free_shipping_threshold" class="form-control" step="0.01" min="0"
                               value="<?php echo htmlspecialchars($current_settings['free_shipping_threshold'] ?? 50); ?>" required>
                        <small class="text-muted">Orders above this amount get free shipping</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Admin Management -->
        <div>
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3><i class="fas fa-user-shield"></i> Admin Users</h3>
                </div>
                <div class="admin-card-body">
                    <?php
                    $admins = [];
                    $result = $conn->query("SELECT * FROM admins ORDER BY created_at DESC");
                    while ($row = $result->fetch_assoc()) {
                        $admins[] = $row;
                    }
                    ?>
                    
                    <div class="admin-users-list">
                        <?php foreach ($admins as $admin): ?>
                            <div class="admin-user-item">
                                <div class="admin-user-info">
                                    <strong><?php echo htmlspecialchars($admin['username']); ?></strong>
                                    <small><?php echo htmlspecialchars($admin['email']); ?></small>
                                </div>
                                <div class="admin-user-role">
                                    <span class="role-badge role-<?php echo $admin['role']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $admin['role'])); ?>
                                    </span>
                                </div>
                                <div class="admin-user-status">
                                    <span class="status-badge status-<?php echo $admin['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $admin['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Activity Logs -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3><i class="fas fa-history"></i> Recent Activity</h3>
                </div>
                <div class="admin-card-body">
                    <?php
                    $logs = [];
                    $result = $conn->query("SELECT al.*, a.username 
                                           FROM admin_logs al 
                                           JOIN admins a ON al.admin_id = a.admin_id 
                                           ORDER BY al.created_at DESC 
                                           LIMIT 10");
                    while ($row = $result->fetch_assoc()) {
                        $logs[] = $row;
                    }
                    ?>
                    
                    <div class="activity-logs">
                        <?php foreach ($logs as $log): ?>
                            <div class="activity-log-item">
                                <div class="activity-icon">
                                    <i class="fas fa-circle"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-text">
                                        <strong><?php echo htmlspecialchars($log['username']); ?></strong>
                                        <?php echo htmlspecialchars($log['action']); ?>
                                    </div>
                                    <div class="activity-time">
                                        <?php echo date('M d, Y g:i A', strtotime($log['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Database Backup -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3><i class="fas fa-database"></i> Database Management</h3>
                </div>
                <div class="admin-card-body">
                    <p class="text-muted">Backup your database to ensure data safety</p>
                    <button type="button" class="btn btn-secondary btn-full" onclick="backupDatabase()">
                        <i class="fas fa-download"></i> Download Database Backup
                    </button>
                    
                    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e5e5;">
                        <p class="text-muted" style="margin-bottom: 10px;">Last backup: Never</p>
                        <button type="button" class="btn btn-danger btn-full" onclick="if(confirm('This will delete all test data. Continue?')) alert('Feature coming soon')">
                            <i class="fas fa-trash"></i> Clear Test Data
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="admin-card">
        <div class="admin-card-body" style="text-align: center;">
            <button type="submit" class="btn btn-primary" style="min-width: 200px;">
                <i class="fas fa-save"></i> Save All Settings
            </button>
        </div>
    </div>
</form>

<script>
function backupDatabase() {
    if (confirm('Generate database backup? This may take a few moments.')) {
        window.location.href = 'ajax/backup-database.php';
    }
}
</script>

<?php include 'includes/footer.php'; ?>