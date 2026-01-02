<?php require_admin_login(); ?>
<?php
// Initial admin notifications count (UI only, JS will keep it updated)
$__adminNotifCount = 0;
try {
    if (isset($conn) && $conn instanceof mysqli) {
        $pendingReturns = 0;
        if ($__res = $conn->query("SELECT COUNT(*) AS c FROM return_requests WHERE status = 'pending'")) {
            $__row = $__res->fetch_assoc();
            $pendingReturns = isset($__row['c']) ? (int)$__row['c'] : 0;
        }
        $openOrders = 0;
        if ($__res2 = $conn->query("SELECT COUNT(*) AS c FROM orders WHERE (order_status = 'pending' OR (order_status IN ('processing','shipped','delivered') AND payment_status <> 'paid'))")) {
            $__row2 = $__res2->fetch_assoc();
            $openOrders = isset($__row2['c']) ? (int)$__row2['c'] : 0;
        }
        $recentUsers = 0;
        if ($__res3 = $conn->query("SELECT COUNT(*) AS c FROM users WHERE created_at >= (NOW() - INTERVAL 1 HOUR)")) {
            $__row3 = $__res3->fetch_assoc();
            $recentUsers = isset($__row3['c']) ? (int)$__row3['c'] : 0;
        }
        $recentSubscribers = 0;
        if ($__res4 = $conn->query("SELECT COUNT(*) AS c FROM newsletter_subscribers WHERE subscribed_at >= (NOW() - INTERVAL 1 HOUR)")) {
            $__row4 = $__res4->fetch_assoc();
            $recentSubscribers = isset($__row4['c']) ? (int)$__row4['c'] : 0;
        }
        // Unified approximation to align with backend aggregation
        $cntUnified = 0;
        if ($__u1 = $conn->query("SELECT COUNT(*) AS c FROM return_requests WHERE status IN ('approved','rejected','completed') AND updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")) {
            $cntUnified += (int)($__u1->fetch_assoc()['c'] ?? 0);
        }
        if ($__u2 = $conn->query("SELECT COUNT(*) AS c FROM orders WHERE (payment_status <> 'paid' OR order_status <> 'delivered')")) {
            $cntUnified += (int)($__u2->fetch_assoc()['c'] ?? 0);
        }
        if ($__u3 = $conn->query("SELECT COUNT(*) AS c FROM users WHERE created_at >= (NOW() - INTERVAL 1 HOUR)")) {
            $cntUnified += (int)($__u3->fetch_assoc()['c'] ?? 0);
        }
        if ($__u4 = $conn->query("SELECT COUNT(*) AS c FROM newsletter_subscribers WHERE subscribed_at >= (NOW() - INTERVAL 1 HOUR)")) {
            $cntUnified += (int)($__u4->fetch_assoc()['c'] ?? 0);
        }
        if ($__u5 = $conn->query("SELECT COUNT(*) AS c FROM orders WHERE order_status = 'cancelled' AND (updated_at >= (NOW() - INTERVAL 1 HOUR) OR created_at >= (NOW() - INTERVAL 1 HOUR))")) {
            $cntUnified += (int)($__u5->fetch_assoc()['c'] ?? 0);
        }
        if ($__u6 = $conn->query("SELECT COUNT(*) AS c FROM orders WHERE payment_status = 'paid' AND created_at >= (NOW() - INTERVAL 1 HOUR)")) {
            $cntUnified += (int)($__u6->fetch_assoc()['c'] ?? 0);
        }
        $__adminNotifCount = $cntUnified;
    }
} catch (Throwable $e) {
    // silent fail: purely presentational
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Admin Dashboard'; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 448 512%22><path fill=%22%23f53d2d%22 d=%22M160 112c0-35.3 28.7-64 64-64s64 28.7 64 64v48H160V112zm-48 48H48c-26.5 0-48 21.5-48 48v192c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V208c0-26.5-21.5-48-48-48h-64v-48C336 50.1 285.9 0 224 0S112 50.1 112 112v48z%22/></svg>" />
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../assets/js/password-strength.js"></script>
</head>
<body class="admin-panel">
    <div class="admin-wrapper">
        <!-- Top Navigation -->
        <nav class="admin-topnav">
            <div class="admin-topnav-left">
                <button class="sidebar-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="admin-logo">
                    <i class="fas fa-shopping-bag"></i>
                    <span><?php echo SITE_NAME; ?> Admin</span>
                </div>
            </div>
            
            <div class="admin-topnav-right">
                <a href="../index.php" class="topnav-item" target="_blank">
                    <i class="fas fa-external-link-alt"></i>
                    <span>View Store</span>
                </a>
                
                <div class="topnav-item notifications" id="notifToggle" title="Notifications" style="position: relative;">
                    <i class="fas fa-bell"></i>
                        <span class="notification-badge" id="notifCount" style="display:<?php echo $__adminNotifCount > 0 ? 'inline-block' : 'none'; ?>; position:absolute; top:-6px; right:-6px; background:#ef4444; color:#fff; border-radius:999px; font-size:11px; font-weight:700; padding:2px 6px; line-height:1; min-width:18px; text-align:center;">
                            <?php echo (int)$__adminNotifCount; ?>
                        </span>
                    <div class="notif-dropdown" id="notifDropdown" style="display:none; position:absolute; right:0; top:38px; background:#fff; border:1px solid #e5e7eb; border-radius:8px; box-shadow:0 10px 25px rgba(0,0,0,0.08); width:360px; z-index:999;">
                        <div style="padding:12px 14px; border-bottom:1px solid #f1f5f9; font-weight:600;">Notifications</div>
                        <div id="notifList" style="max-height:360px; overflow:auto;"></div>
                        <div style="padding:10px 12px; border-top:1px solid #f1f5f9; text-align:center;">
                            <a href="return-requests.php" style="color:#2563eb; text-decoration:none;">View all return requests</a>
                        </div>
                    </div>
                </div>
                
                <?php
                    // Determine admin avatar dynamically based on uploaded file named by admin_id
                    $adminId = $_SESSION['admin_id'] ?? null;
                    $avatarPath = 'assets/images/user_avatar_def.jpg';
                    if ($adminId) {
                        $avatarDir = __DIR__ . '/../assets/images/avatars/';
                        $avatarRelBase = 'assets/images/avatars/';
                        $candidates = [
                            $avatarDir . $adminId . '.jpg' => $avatarRelBase . $adminId . '.jpg',
                            $avatarDir . $adminId . '.jpeg' => $avatarRelBase . $adminId . '.jpeg',
                            $avatarDir . $adminId . '.png' => $avatarRelBase . $adminId . '.png',
                            $avatarDir . $adminId . '.webp' => $avatarRelBase . $adminId . '.webp',
                        ];
                        foreach ($candidates as $fs => $rel) {
                            if (file_exists($fs)) {
                                $avatarPath = $rel . '?t=' . urlencode((string)@filemtime($fs));
                                break;
                            }
                        }
                    }
                ?>
                <div class="topnav-item admin-profile">
                    <img src="<?php echo htmlspecialchars($avatarPath); ?>" alt="Admin" class="admin-avatar">
                    <span><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    <div class="admin-dropdown">
                        <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                        <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </nav>
        <script>
        (function(){
            // Determine base for admin paths to ensure cookies/session apply regardless of nesting
            var adminBase = (function(){
                var path = window.location.pathname.replace(/\\+/g,'/');
                var marker = '/admin/';
                var idx = path.indexOf(marker);
                if (idx === -1) return marker;
                return path.substring(0, idx + marker.length);
            })();
            var NOTIF_URL = adminBase + 'ajax/get-notifications.php';
            // Profile dropdown
            function closeProfile(){
                document.querySelectorAll('.admin-profile').forEach(function(el){ el.classList.remove('open'); });
            }
            document.addEventListener('click', function(e){
                var container = e.target.closest('.admin-profile');
                var trigger = container ? container.querySelector('span, img') : null;
                if (container && (e.target === trigger || e.target.closest('span') || e.target.closest('img'))) {
                    closeProfile();
                    container.classList.toggle('open');
                    return;
                }
                if (!e.target.closest('.admin-profile')) closeProfile();
            });
            document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeProfile(); });

            // Notifications
            const toggle = document.getElementById('notifToggle');
            const dropdown = document.getElementById('notifDropdown');
            const list = document.getElementById('notifList');
            const countEl = document.getElementById('notifCount');
            const footerLink = dropdown ? dropdown.querySelector('a[href="return-requests.php"]') : null;
            if (footerLink) footerLink.setAttribute('href', adminBase + 'return-requests.php');

            function dismissNotification(type,id,el){
                fetch(adminBase + 'ajax/dismiss-notification.php', {
                    method:'POST',
                    credentials:'include',
                    headers:{'Content-Type':'application/x-www-form-urlencoded'},
                    body:'type=' + encodeURIComponent(type) + '&id=' + encodeURIComponent(id)
                }).then(r=>r.json()).then(resp=>{
                    if(resp && resp.success){
                        // Remove element visually
                        if(el){ el.parentNode.remove(); }
                        // Recompute badge by triggering refresh
                        refreshNotifications();
                    }
                }).catch(()=>{});
            }

            function renderItems(items){
                if (!items || items.length === 0) {
                    list.innerHTML = '<div style="padding:14px; color:#64748b;">No notifications.</div>';
                    return;
                }
                list.innerHTML = items.map(function(it){
                    const email = it.email || '';
                    const typeRaw = (it.type||'return').toLowerCase();
                    let label = 'Notification';
                    let icon = 'fa-info-circle';
                    const iconBg = '#eff6ff';
                    const iconColor = '#2563eb';
                    let subtitle = '';
                    let detail = '';
                    const created = it.created_at || '';
                    const orderLink = it.href || it.orderLink || '#';

                    if (typeRaw === 'return_update') {
                        label = 'Return update';
                        subtitle = (it.order_number ? ('Order #' + it.order_number) : '') + (email ? ' • ' + email : '');
                        detail = (it.message || 'Return status changed');
                        icon = 'fa-undo';
                    } else if (typeRaw === 'order_outstanding') {
                        label = 'Order outstanding';
                        subtitle = (it.order_number ? ('Order #' + it.order_number) : '') + (email ? ' • ' + email : '');
                        detail = (it.message || 'Order requires attention');
                        icon = 'fa-truck';
                    } else if (typeRaw === 'order_cancel') {
                        label = 'Order cancelled';
                        const safeOrder = (it.order_number||('Order #' + it.order_id));
                        subtitle = `${safeOrder}` + (email ? ' • ' + email : '');
                        detail = it.message || 'Order was cancelled';
                        icon = 'fa-ban';
                    } else if (typeRaw === 'order_success') {
                        label = 'Order paid';
                        const safeOrder = (it.order_number||('Order #' + it.order_id));
                        subtitle = `${safeOrder}` + (email ? ' • ' + email : '');
                        detail = it.message || 'Payment received';
                        icon = 'fa-circle-check';
                    } else if (typeRaw === 'user_new') {
                        label = 'New registration';
                        subtitle = email;
                        detail = it.message || 'New customer account created';
                        icon = 'fa-user-plus';
                    } else if (typeRaw === 'subscriber_new') {
                        label = 'New subscriber';
                        subtitle = email;
                        detail = it.message || 'Joined newsletter';
                        icon = 'fa-envelope-open-text';
                    } else {
                        // Fallback legacy
                        const safeOrder = (it.order_number||('Order #' + it.order_id || ''));
                        subtitle = (safeOrder ? safeOrder : email);
                        detail = it.message || 'Notification';
                    }

                    const dismissBtn = `<button type="button" aria-label="Dismiss" data-type="${typeRaw}" data-id="${it.id}" style="background:transparent;border:none;color:#94a3b8;cursor:pointer;font-size:12px;padding:4px;">✕</button>`;
                    return (`
    <div style="position:relative;">
        <a href="${orderLink}" style="text-decoration:none; color:inherit; display:block;">
            <div style="display:flex; gap:10px; padding:10px 36px 10px 12px; border-bottom:1px solid #f8fafc;">
                <div style="width:36px; height:36px; border-radius:50%; background:${iconBg}; display:flex; align-items:center; justify-content:center; color:${iconColor};">
                    <i class="fas ${icon}"></i>
                </div>
                <div style="flex:1;">
                    <div style="font-weight:600;">${label}</div>
                    <div style="font-size:12px; color:#64748b;">${subtitle}</div>
                    <div style="font-size:12px; color:#334155;">${detail}</div>
                    <div style="font-size:12px; color:#94a3b8;">${created}</div>
                </div>
            </div>
        </a>
        <div style="position:absolute; top:8px; right:8px;">${dismissBtn}</div>
    </div>`);
                }).join('');
                // Attach dismiss handlers
                list.querySelectorAll('button[data-type][data-id]').forEach(function(btn){
                    btn.addEventListener('click', function(e){
                        e.preventDefault();
                        e.stopPropagation();
                        const t = btn.getAttribute('data-type');
                        const i = btn.getAttribute('data-id');
                        dismissNotification(t,i,btn);
                    });
                });
            }

            function refreshNotifications(){
                fetch(NOTIF_URL + '?_=' + Date.now(), { credentials: 'include' })
                .then(r => r.json())
                .then(data => {
                    if (!data || !data.success) {
                        // Show an explicit empty state if backend returns invalid payload
                        countEl.style.display = 'none';
                        renderItems([]);
                        console.warn('[Notifications] Invalid response payload', data);
                        return;
                    }
                    const c = data.count || 0;
                    // If count is zero but we actually have items, derive count from items length (failsafe)
                    const derivedCount = (c === 0 && Array.isArray(data.items) && data.items.length > 0) ? data.items.length : c;
                    countEl.textContent = derivedCount;
                    countEl.style.display = derivedCount > 0 ? 'inline-block' : 'none';
                    renderItems(data.items || []);
                })
                .catch((err) => {
                    // Network/parse error: still render empty state so dropdown doesn't look broken
                    countEl.style.display = 'none';
                    renderItems([]);
                    console.error('[Notifications] Fetch error', err);
                });
            }

            // Toggle dropdown
            toggle && toggle.addEventListener('click', function(e){
                e.stopPropagation();
                const visible = dropdown.style.display === 'block';
                dropdown.style.display = visible ? 'none' : 'block';
                if (!visible) refreshNotifications();
            });

            // Close when clicking outside
            document.addEventListener('click', function(e){
                if (!e.target.closest('#notifToggle')) {
                    dropdown.style.display = 'none';
                }
            });
            document.addEventListener('keydown', function(e){ if (e.key === 'Escape') dropdown.style.display = 'none'; });

            // Poll periodically (every 10s) for faster badge updates
            setInterval(refreshNotifications, 10000);
            // Refresh when tab becomes active again
            document.addEventListener('visibilitychange', function(){
                if (!document.hidden) refreshNotifications();
            });
            // Initial fetch
            renderItems([]); // Ensure a visible empty state before first fetch completes
            refreshNotifications();
        })();
        </script>