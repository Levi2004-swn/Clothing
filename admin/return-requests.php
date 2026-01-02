<?php
require_once 'config.php';
require_admin_login();

$page_title = 'Return Requests';
$success = '';
$error = '';

// Handle status change (optional convenience, does not alter existing refund flow)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!has_permission('super_admin')) {
        $error = 'Unauthorized';
    } else {
        $return_id = intval($_POST['return_id'] ?? 0);
        $new_status = $_POST['status'] ?? 'pending';
        if ($return_id > 0 && in_array($new_status, ['pending','approved','rejected','completed'], true)) {
            // Fetch current status and order_id for idempotency and stock restoration
            $stmtCur = $conn->prepare("SELECT status, order_id FROM return_requests WHERE return_id = ?");
            $stmtCur->bind_param('i', $return_id);
            $stmtCur->execute();
            $curRes = $stmtCur->get_result();
            $current = $curRes ? $curRes->fetch_assoc() : null;
            $stmtCur->close();

            if (!$current) {
                $error = 'Return request not found.';
            } else {
                $prev_status = $current['status'] ?? 'pending';
                $order_id_for_return = (int)($current['order_id'] ?? 0);

                // Begin a light-weight transaction to keep updates grouped
                $conn->begin_transaction();
                $ok = true;

                // Update status first
                $stmt = $conn->prepare("UPDATE return_requests SET status = ?, updated_at = NOW() WHERE return_id = ?");
                $stmt->bind_param('si', $new_status, $return_id);
                if (!$stmt->execute()) { $ok = false; }
                $stmt->close();

                // If transitioning to completed for the first time, restore stock for this order's items
                if ($ok && $new_status === 'completed' && $prev_status !== 'completed' && $order_id_for_return > 0) {
                    // Select order items and increment variant stock quantities
                    $stmtItems = $conn->prepare("SELECT variant_id, quantity FROM order_items WHERE order_id = ?");
                    if ($stmtItems) {
                        $stmtItems->bind_param('i', $order_id_for_return);
                        if ($stmtItems->execute()) {
                            $resItems = $stmtItems->get_result();
                            if ($resItems && $resItems->num_rows > 0) {
                                $stmtUpd = $conn->prepare("UPDATE product_variants SET stock_quantity = stock_quantity + ? WHERE variant_id = ?");
                                if ($stmtUpd) {
                                    while ($row = $resItems->fetch_assoc()) {
                                        $variantId = (int)($row['variant_id'] ?? 0);
                                        $qty = (int)($row['quantity'] ?? 0);
                                        if ($variantId > 0 && $qty > 0) {
                                            $stmtUpd->bind_param('ii', $qty, $variantId);
                                            // Ignore per-item failures to avoid blocking the status change; keep best-effort
                                            $stmtUpd->execute();
                                        }
                                    }
                                    $stmtUpd->close();
                                }
                            }
                        }
                        $stmtItems->close();
                    }
                    // Log admin activity for traceability
                    if (function_exists('log_admin_activity')) {
                        @log_admin_activity('restock_on_return', 'return_id: ' . $return_id . ', order_id: ' . $order_id_for_return);
                    }
                }

                // Commit or rollback
                if ($ok) {
                    $conn->commit();
                    $success = 'Status updated.' . ($new_status === 'completed' && $prev_status !== 'completed' ? ' Stock updated for returned items.' : '');
                } else {
                    $conn->rollback();
                    $error = 'Failed to update status.';
                }
            }
        } else {
            $error = 'Invalid parameters';
        }
    }
}

// Filters
$status = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? ''); // order number or email
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$where = ['1=1'];
$params = [];
$types = '';

if ($status !== '') {
    $where[] = 'rr.status = ?';
    $params[] = $status;
    $types .= 's';
}
if ($search !== '') {
    $where[] = '(o.order_number LIKE CONCAT("%", ?, "%") OR u.email LIKE CONCAT("%", ?, "%"))';
    $params[] = $search; $params[] = $search;
    $types .= 'ss';
}
if ($date_from !== '') {
    $where[] = 'DATE(rr.created_at) >= ?';
    $params[] = $date_from;
    $types .= 's';
}
if ($date_to !== '') {
    $where[] = 'DATE(rr.created_at) <= ?';
    $params[] = $date_to;
    $types .= 's';
}

$where_clause = implode(' AND ', $where);

$sql = "SELECT rr.*, u.email, CONCAT(u.first_name, ' ', u.last_name) AS customer_name, o.order_number
        FROM return_requests rr
        JOIN users u ON rr.user_id = u.user_id
        JOIN orders o ON rr.order_id = o.order_id
        WHERE $where_clause
        ORDER BY rr.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<style>
/* Scoped styles for Return Requests page */
.admin-return-requests .admin-header { margin-bottom: 10px; }
.admin-return-requests .admin-header h1 { display:flex; align-items:center; gap:10px; font-size:24px; }
.admin-return-requests .sub-header { display:flex; justify-content:space-between; align-items:center; color:#6b7280; font-size:13px; margin: 0 0 12px; }

.admin-return-requests .stats-grid { display:grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap:12px; }
.admin-return-requests .stat-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:14px 16px; display:flex; align-items:center; gap:12px; }
.admin-return-requests .stat-card .icon { width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; }
.admin-return-requests .stat-card .meta { line-height:1.2; }
.admin-return-requests .stat-card .label { color:#6b7280; font-size:12px; text-transform:uppercase; letter-spacing:.04em; }
.admin-return-requests .stat-card .value { font-weight:700; font-size:18px; color:#111827; }

.admin-return-requests .filters-card { padding:16px 18px; }
.admin-return-requests .filter-form { display:grid; grid-template-columns: repeat(5, minmax(0,1fr)); gap:12px; align-items:end; }
.admin-return-requests .filter-group label { display:block; font-size:12px; color:#6b7280; text-transform:uppercase; letter-spacing:.04em; margin-bottom:6px; }
.admin-return-requests .filter-group input,
.admin-return-requests .filter-group select { width:100%; padding:10px 12px; border:1px solid #e5e7eb; border-radius:8px; background:#fff; color:#111827; height:40px; transition: border-color .15s, box-shadow .15s; }
.admin-return-requests .filter-group input:focus,
.admin-return-requests .filter-group select:focus { outline:none; border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,0.15); }
.admin-return-requests .filter-group .btn { height:40px; }
.admin-return-requests .filter-group .btn + .btn { margin-left:6px; }

.admin-return-requests .admin-card { border-radius:12px; }
.admin-return-requests .admin-table thead th { position:sticky; top:0; background:#f9fafb; z-index:1; }
.admin-return-requests .admin-table tbody tr:hover { background:#fbfbfd; }
.admin-return-requests .admin-table td { vertical-align:top; }
.admin-return-requests .admin-table td.actions { white-space:nowrap; }
.admin-return-requests .admin-table td .btn { margin-top:6px; }

/* Status badges */
.admin-return-requests .badge { display:inline-block; padding:6px 10px; border-radius:999px; font-weight:600; font-size:12px; }
.admin-return-requests .badge.status-pending { background:#fffbeb; color:#92400e; border:1px solid #f59e0b33; }
.admin-return-requests .badge.status-approved { background:#ecfdf5; color:#065f46; border:1px solid #10b98133; }
.admin-return-requests .badge.status-rejected { background:#fef2f2; color:#991b1b; border:1px solid #ef444433; }
.admin-return-requests .badge.status-completed { background:#eff6ff; color:#1e40af; border:1px solid #3b82f633; }

@media (max-width: 1100px) {
  .admin-return-requests .filter-form { grid-template-columns: repeat(3, minmax(0,1fr)); }
}
@media (max-width: 768px) {
  .admin-return-requests .filter-form { grid-template-columns: repeat(2, minmax(0,1fr)); }
  .admin-return-requests .stats-grid { grid-template-columns: repeat(2, minmax(0,1fr)); }
}
@media (max-width: 500px) {
  .admin-return-requests .filter-form { grid-template-columns: 1fr; }
  .admin-return-requests .stats-grid { grid-template-columns: 1fr; }
}
</style>

<div class="admin-return-requests">
<div class="admin-header">
    <h1><i class="fas fa-undo"></i> Return Requests</h1>
</div>

<?php 
// Quick counts (purely presentational)
$__counts = ['pending'=>0,'approved'=>0,'rejected'=>0,'completed'=>0];
foreach ($requests as $__r) { $st = $__r['status'] ?? 'pending'; if (isset($__counts[$st])) { $__counts[$st]++; } }
$__total = count($requests);
?>

<div class="sub-header">
    <div>Showing <?php echo number_format($__total); ?> request<?php echo $__total===1?'':'s'; ?></div>
</div>

<div class="admin-card stats-grid" style="margin-bottom: 14px;">
    <div class="stat-card">
        <div class="icon" style="background:#f59e0b;"><i class="fas fa-clock"></i></div>
        <div class="meta">
            <div class="label">Pending</div>
            <div class="value"><?php echo number_format($__counts['pending']); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="icon" style="background:#10b981;"><i class="fas fa-check-circle"></i></div>
        <div class="meta">
            <div class="label">Approved</div>
            <div class="value"><?php echo number_format($__counts['approved']); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="icon" style="background:#ef4444;"><i class="fas fa-times-circle"></i></div>
        <div class="meta">
            <div class="label">Rejected</div>
            <div class="value"><?php echo number_format($__counts['rejected']); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="icon" style="background:#3b82f6;"><i class="fas fa-box-open"></i></div>
        <div class="meta">
            <div class="label">Completed</div>
            <div class="value"><?php echo number_format($__counts['completed']); ?></div>
        </div>
    </div>
</div>

<?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="admin-card filters-card" style="margin-bottom: 16px;">
    <form method="get" class="filter-form">
        <div class="filter-group">
            <label>Status</label>
            <select name="status">
                <option value="" <?php echo $status===''?'selected':''; ?>>All</option>
                <?php foreach (['pending','approved','rejected','completed'] as $st): ?>
                    <option value="<?php echo $st; ?>" <?php echo $status===$st?'selected':''; ?>><?php echo ucfirst($st); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label>Search</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Order # or Email">
        </div>
        <div class="filter-group">
            <label>From</label>
            <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
        </div>
        <div class="filter-group">
            <label>To</label>
            <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
        </div>
        <div class="filter-group">
            <label>&nbsp;</label>
            <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Filter</button>
            <a class="btn" href="return-requests.php">Reset</a>
        </div>
    </form>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Created</th>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Type</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                <tr><td colspan="8" style="text-align:center; color:#666;">No requests found.</td></tr>
                <?php else: foreach ($requests as $r): ?>
                <tr>
                    <td>#<?php echo (int)$r['return_id']; ?></td>
                    <td><?php echo htmlspecialchars($r['created_at']); ?></td>
                    <td>
                        <a href="order-details.php?id=<?php echo (int)$r['order_id']; ?>">
                            <?php echo htmlspecialchars($r['order_number']); ?>
                        </a>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($r['customer_name']); ?><br>
                        <span style="color:#666; font-size:12px;"><?php echo htmlspecialchars($r['email']); ?></span>
                    </td>
                    <td><?php echo htmlspecialchars(ucfirst($r['request_type'])); ?></td>
                    <td style="max-width: 360px; white-space: normal;"><?php echo nl2br(htmlspecialchars($r['reason'])); ?></td>
                    <td><span class="badge status-<?php echo htmlspecialchars($r['status']); ?>"><?php echo htmlspecialchars(ucfirst($r['status'])); ?></span></td>
                    <td class="actions">
                        <form method="post" style="display:inline-block;">
                            <input type="hidden" name="return_id" value="<?php echo (int)$r['return_id']; ?>">
                            <input type="hidden" name="update_status" value="1">
                            <select name="status" class="form-control" style="display:inline-block; width:auto;">
                                <?php foreach (['pending','approved','rejected','completed'] as $st): ?>
                                    <option value="<?php echo $st; ?>" <?php echo $r['status']===$st?'selected':''; ?>><?php echo ucfirst($st); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-small" type="submit">Update</button>
                        </form>
                        <a class="btn btn-small btn-secondary" href="order-details.php?id=<?php echo (int)$r['order_id']; ?>">View Order</a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

</div><!-- /.admin-return-requests -->

<?php include 'includes/footer.php'; ?>
