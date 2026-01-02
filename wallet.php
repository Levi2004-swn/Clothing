<?php
require_once 'config.php';
require_login();

$page_title = 'My Wallet - ' . SITE_NAME;
$error = '';
$success = '';

$user_id = get_user_id();

// Fetch current balance fresh from DB
$stmt = $conn->prepare("SELECT wallet_balance, first_name FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$current_balance = isset($user['wallet_balance']) ? (float)$user['wallet_balance'] : 0.0;
$user_name = $user['first_name'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['topup_confirm'])) {
    $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0.0;
    $method = isset($_POST['method']) ? strtolower(trim($_POST['method'])) : '';

    // Basic showcase validations
    if ($amount <= 0) {
        $error = 'Please enter a valid amount greater than 0.';
    } elseif ($amount > 10000) {
        $error = 'Amount exceeds the maximum allowed per top-up (10,000).';
    } elseif (!in_array($method, ['credit', 'debit'], true)) {
        $error = 'Please choose a valid payment method (Credit or Debit).';
    } else {
        // Simulate a successful card charge (showcase only) and credit wallet
        $stmt = $conn->prepare('UPDATE users SET wallet_balance = wallet_balance + ? WHERE user_id = ?');
        $stmt->bind_param('di', $amount, $user_id);
        if ($stmt->execute()) {
            $success = 'Wallet credited with ' . format_currency($amount) . ' via ' . ucfirst($method) . ' Card (showcase).';
            // Refresh balance
            $stmt = $conn->prepare('SELECT wallet_balance FROM users WHERE user_id = ?');
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $current_balance = (float)($stmt->get_result()->fetch_assoc()['wallet_balance'] ?? 0);
        } else {
            $error = 'Failed to update wallet balance. Please try again.';
        }
    }
}

include 'header.php';
?>

<div class="container" style="margin-top: 20px; max-width: 800px;">
    <h2 style="margin-bottom: 20px;">My Wallet</h2>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="card" style="margin-bottom: 20px;">
        <div style="display:flex; align-items:center; justify-content:space-between; padding: 15px 20px;">
            <div>
                <div style="font-size:14px; color:#666;">Current Balance</div>
                <div style="font-size:28px; font-weight:700; color:#4caf50; margin-top:5px;">
                    <?php echo format_currency($current_balance); ?>
                </div>
            </div>
            <div style="text-align:right; color:#666; font-size:13px;">
                <?php if ($user_name): ?>
                    Hello, <?php echo htmlspecialchars($user_name); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <h3 style="margin: 15px 20px 0; font-size: 18px; font-weight: 600;">Top-up Wallet (Showcase)</h3>
        <form method="POST" action="" style="padding: 20px;">
            <div class="form-group">
                <label>Amount *</label>
                <input type="number" name="amount" class="form-control" step="0.01" min="0.01" placeholder="0.00" required>
                <small style="color:#999;">Max per top-up: <?php echo format_currency(10000); ?></small>
            </div>

            <div class="form-group">
                <label>Payment Method *</label>
                <select name="method" class="form-control" required>
                    <option value="credit">Credit Card</option>
                    <option value="debit">Debit Card</option>
                </select>
            </div>

            <button type="submit" name="topup_confirm" class="btn btn-primary">
                <i class="fas fa-credit-card"></i> Confirm Top-up
            </button>
            <span style="color:#999; font-size:12px; margin-left:8px;">This is a showcase only (no real charge).</span>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>
