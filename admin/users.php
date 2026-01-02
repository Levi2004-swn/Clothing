<?php
require_once 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
    header("Location: " . SITE_URL . "/admin/login.php");
    exit;
}

$page_title = "Users Management";

// Get filters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

// Build base filters (no soft-delete filtering in hard-delete mode)
$where = ["1=1"];
if ($search) {
    $search_safe = $conn->real_escape_string($search);
    $where[] = "(u.email LIKE '%$search_safe%' OR CONCAT(u.first_name, ' ', u.last_name) LIKE '%$search_safe%')";
}
if ($status !== '') {
    $status_safe = intval($status);
    $where[] = "u.is_active = $status_safe";
}

$where_clause = implode(" AND ", $where);

$users = [];
$query = "SELECT u.*, 
          COUNT(DISTINCT o.order_id) as total_orders,
          COALESCE(SUM(o.total_amount), 0) as total_spent
          FROM users u
          LEFT JOIN orders o ON u.user_id = o.user_id
          WHERE $where_clause
          GROUP BY u.user_id
          ORDER BY u.created_at DESC";

$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="admin-header">
    <h1>Users Management</h1>
</div>

<?php if (isset($_GET['deleted'])): ?>
    <?php if ($_GET['deleted'] == '1'): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> User deleted successfully.</div>
    <?php else: ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Failed to delete user. Please try again.</div>
    <?php endif; ?>
<?php endif; ?>

<!-- Filters -->
<div class="admin-card" style="margin-bottom: 20px;">
    <form method="GET" class="filter-form">
        <div class="filter-group">
            <input type="text" name="search" placeholder="Search users..." 
                   value="<?php echo htmlspecialchars($search); ?>" class="form-control">
        </div>
        
        <div class="filter-group">
            <select name="status" class="form-control">
                <option value="">All Status</option>
                <option value="1" <?php echo $status === '1' ? 'selected' : ''; ?>>Active</option>
                <option value="0" <?php echo $status === '0' ? 'selected' : ''; ?>>Blocked</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="users.php" class="btn btn-secondary">Clear</a>
    </form>
</div>

<!-- Users Table -->
<div class="admin-card">
    <div class="admin-card-body">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Orders</th>
                        <th>Total Spent</th>
                        <th>Loyalty Points</th>
                        <th>Joined</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px;">No users found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <?php echo strtoupper(substr($user['first_name'] ?? 'U', 0, 1)); ?>
                                        </div>
                                        <strong><?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?></strong>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                <td><?php echo $user['total_orders'] ?? 0; ?></td>
                                <td><strong><?php echo CURRENCY_SYMBOL . number_format($user['total_spent'] ?? 0, 2); ?></strong></td>
                                <td><?php echo number_format($user['loyalty_points'] ?? 0); ?></td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $user['is_active'] ? 'Active' : 'Blocked'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="user-details.php?id=<?php echo $user['user_id']; ?>" 
                                           class="btn-icon" title="View Details">
                                            <i class="fas fa-eye"></i>
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
.filter-form {
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
    padding: 20px;
}

.filter-group {
    flex: 1;
    min-width: 200px;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 16px;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.btn-icon {
    padding: 8px 12px;
    border: none;
    background: #f3f4f6;
    color: #6b7280;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-icon:hover {
    background: #e5e7eb;
    color: #374151;
}

.btn-icon.btn-danger {
    background: #fee2e2;
    color: #dc2626;
}

.btn-icon.btn-danger:hover {
    background: #fecaca;
}

.btn-icon.btn-success {
    background: #dcfce7;
    color: #16a34a;
}

.btn-icon.btn-success:hover {
    background: #bbf7d0;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}

.status-active {
    background: #d1fae5;
    color: #065f46;
}

.status-inactive {
    background: #fee2e2;
    color: #991b1b;
}
</style>

<script>
function toggleUserStatus(userId, newStatus) {
    const action = newStatus == 1 ? 'unblock' : 'block';
    if (!confirm(`Are you sure you want to ${action} this user?`)) {
        return;
    }
    
    // Create form data
    const formData = new FormData();
    formData.append('user_id', userId);
    formData.append('status', newStatus);
    
    fetch('ajax/block-user.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message || 'Failed to update user status', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    `;
    
    if (type === 'success') {
        notification.style.background = '#d1fae5';
        notification.style.color = '#065f46';
        notification.style.border = '1px solid #6ee7b7';
    } else {
        notification.style.background = '#fee2e2';
        notification.style.color = '#991b1b';
        notification.style.border = '1px solid #fca5a5';
    }
    
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transition = 'opacity 0.3s';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}
</script>

<?php include 'includes/footer.php'; ?>