<?php
// Pending return requests count for sidebar badge (UI only)
$__pendingReturnsCount = 0;
// Outstanding orders badge count (initialize to avoid notices if DB unavailable)
$__outstandingOrdersCount = 0;
try {
    if (isset($conn) && $conn instanceof mysqli) {
        if ($__res = $conn->query("SELECT COUNT(*) AS c FROM return_requests WHERE status = 'pending'")) {
            $__row = $__res->fetch_assoc();
            $__pendingReturnsCount = isset($__row['c']) ? (int)$__row['c'] : 0;
        }
        // Outstanding orders count for "All Orders" badge (UI only)
        // Definition:
        // - Orders with status = 'pending' (any payment state)
        //   OR
        // - Orders with status in ('processing','shipped','delivered') AND payment_status <> 'paid'
        // - (Cancelled orders are excluded implicitly)
        if ($__res2 = $conn->query(
            "SELECT COUNT(*) AS c FROM orders "+
            "WHERE (order_status = 'pending' "+
            "       OR (order_status IN ('processing','shipped','delivered') AND payment_status <> 'paid'))"
        )) {
            $__row2 = $__res2->fetch_assoc();
            $__outstandingOrdersCount = isset($__row2['c']) ? (int)$__row2['c'] : 0;
        }
    }
} catch (Throwable $e) {
    // Fail silently to avoid breaking the UI
}
?>
<!-- Sidebar -->
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="sidebar-menu">
                <a href="index.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                
                <div class="menu-section">Products</div>
                <a href="products.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">
                    <i class="fas fa-box"></i>
                    <span>All Products</span>
                </a>
                <a href="add-product.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'add-product.php' ? 'active' : ''; ?>">
                    <i class="fas fa-plus-circle"></i>
                    <span>Add Product</span>
                </a>
                <a href="categories.php" class="menu-item">
                    <i class="fas fa-tags"></i>
                    <span>Categories</span>
                </a>
                
                <div class="menu-section">Orders</div>
                <a href="orders.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart"></i>
                    <span>All Orders</span>
                    <?php if (!empty($__outstandingOrdersCount)): ?>
                        <span style="margin-left:auto; background:#ef4444; color:#fff; padding:2px 8px; border-radius:999px; font-size:12px; font-weight:700; line-height:1;">
                            <?php echo (int)$__outstandingOrdersCount; ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a href="return-requests.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'return-requests.php' ? 'active' : ''; ?>">
                    <i class="fas fa-undo"></i>
                    <span>Return Requests</span>
                    <?php if ($__pendingReturnsCount > 0): ?>
                        <span style="margin-left:auto; background:#ef4444; color:#fff; padding:2px 8px; border-radius:999px; font-size:12px; font-weight:700; line-height:1;"><?php echo (int)$__pendingReturnsCount; ?></span>
                    <?php endif; ?>
                </a>
                
                <div class="menu-section">Users</div>
                <a href="users.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Customers</span>
                </a>
                
                <div class="menu-section">Finance</div>
                <a href="transactions.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'transactions.php' ? 'active' : ''; ?>">
                    <i class="fas fa-credit-card"></i>
                    <span>Transactions</span>
                </a>
                <a href="coupons.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'coupons.php' ? 'active' : ''; ?>">
                    <i class="fas fa-ticket-alt"></i>
                    <span>Coupons</span>
                </a>
                
                <div class="menu-section">Content</div>
                <a href="content.php" class="menu-item">
                    <i class="fas fa-edit"></i>
                    <span>Content Manager</span>
                </a>
                
                <div class="menu-section">Reports</div>
                <a href="analytics.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Analytics</span>
                </a>
                
                <div class="menu-section">System</div>
                <a href="settings.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-content">