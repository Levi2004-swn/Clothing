<?php
require_once 'config.php';
$page_title = "Transactions";

$transactions = [];
$result = $conn->query("SELECT o.*, u.first_name, u.last_name, u.email
                       FROM orders o
                       JOIN users u ON o.user_id = u.user_id
                       ORDER BY o.created_at DESC
                       LIMIT 100");
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}

// Get stats
$total_revenue = 0;
$total_paid = 0;
$total_pending = 0;

$result = $conn->query("SELECT 
                       SUM(CASE 
                               WHEN payment_status = 'paid' 
                                    AND NOT EXISTS (
                                        SELECT 1 FROM return_requests rr 
                                        WHERE rr.order_id = orders.order_id 
                                          AND rr.status = 'completed'
                                    )
                               THEN total_amount ELSE 0 
                           END) as paid,
                       SUM(CASE WHEN payment_status = 'pending' THEN total_amount ELSE 0 END) as pending,
                       SUM(CASE 
                               WHEN NOT EXISTS (
                                   SELECT 1 FROM return_requests rr 
                                   WHERE rr.order_id = orders.order_id 
                                     AND rr.status = 'completed'
                               ) THEN total_amount ELSE 0 
                           END) as total
                       FROM orders
                       WHERE order_status != 'cancelled'");
$stats = $result->fetch_assoc();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="admin-header">
    <h1>Payment Transactions</h1>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card bg-green">
        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?php echo CURRENCY_SYMBOL . number_format($stats['paid'] ?? 0, 2); ?></div>
            <div class="stat-label">Total Paid</div>
        </div>
    </div>
    
    <div class="stat-card bg-orange">
        <div class="stat-icon"><i class="fas fa-clock"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?php echo CURRENCY_SYMBOL . number_format($stats['pending'] ?? 0, 2); ?></div>
            <div class="stat-label">Pending Payments</div>
        </div>
    </div>
    
    <div class="stat-card bg-blue">
        <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?php echo CURRENCY_SYMBOL . number_format($stats['paid'] ?? 0, 2); ?></div>
            <div class="stat-label">Total Revenue</div>
        </div>
    </div>
</div>

<!-- Transactions Table -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3>Recent Transactions</h3>
    </div>
    <div class="admin-card-body">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Payment Method</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $txn): ?>
                        <tr>
                            <td><code>TXN-<?php echo $txn['order_id']; ?></code></td>
                            <td><a href="order-details.php?id=<?php echo $txn['order_id']; ?>"><?php echo $txn['order_number']; ?></a></td>
                            <td><?php echo htmlspecialchars($txn['first_name'] . ' ' . $txn['last_name']); ?></td>
                            <td>
                                <i class="fas fa-<?php echo $txn['payment_method'] == 'paypal' ? 'cc-paypal' : 'credit-card'; ?>"></i>
                                <?php echo ucfirst(str_replace('_', ' ', $txn['payment_method'])); ?>
                            </td>
                            <td><strong><?php echo CURRENCY_SYMBOL . number_format($txn['total_amount'], 2); ?></strong></td>
                            <td>
                                <span class="payment-badge payment-<?php echo $txn['payment_status']; ?>">
                                    <?php echo ucfirst($txn['payment_status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y g:i A', strtotime($txn['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="order-details.php?id=<?php echo $txn['order_id']; ?>" class="btn-icon" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($txn['payment_status'] == 'paid' && has_permission('super_admin')): ?>
                                        <button class="btn-icon btn-warning" title="Refund" onclick="processRefund(<?php echo $txn['order_id']; ?>)">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function processRefund(orderId) {
    if (!confirm('Are you sure you want to process a refund for this order?')) {
        return;
    }
    
    const amount = prompt('Enter refund amount:');
    if (!amount) return;
    
    fetch('ajax/process-refund.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `order_id=${orderId}&amount=${amount}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Refund processed successfully', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message || 'Failed to process refund', 'error');
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>