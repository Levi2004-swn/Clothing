<?php
require_once 'config.php';

if (!is_logged_in()) {
    header('Location: ' . SITE_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$page_title = "Checkout - " . SITE_NAME;
$user_id = get_user_id();
$error = '';

// Get cart items
$cart_items = [];
$stmt = $conn->prepare("SELECT ci.*, p.product_name, p.final_price, pi.image_url, pv.size, pv.color
                        FROM cart c
                        JOIN cart_items ci ON c.cart_id = ci.cart_id
                        JOIN products p ON ci.product_id = p.product_id
                        LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                        LEFT JOIN product_variants pv ON ci.variant_id = pv.variant_id
                        WHERE c.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $cart_items[] = $row;
}

if (empty($cart_items)) {
    header('Location: ' . SITE_URL . '/cart.php');
    exit;
}

// Get user addresses
$addresses = [];
$result = $conn->query("SELECT * FROM user_addresses WHERE user_id = $user_id ORDER BY is_default DESC");
while ($row = $result->fetch_assoc()) {
    $addresses[] = $row;
}

$cart_summary = $_SESSION['cart_summary'] ?? [];
$subtotal = $cart_summary['subtotal'] ?? 0;
$tax_amount = $cart_summary['tax_amount'] ?? 0;
$shipping_amount = $cart_summary['shipping_amount'] ?? 0;
$total = $cart_summary['total'] ?? 0;

// Fetch wallet balance (for wallet payment option)
$wallet_balance = 0.0;
$stmt = $conn->prepare("SELECT wallet_balance FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$resBal = $stmt->get_result();
if ($resBal && ($rowBal = $resBal->fetch_assoc())) {
    $wallet_balance = (float)($rowBal['wallet_balance'] ?? 0);
}

// If a coupon is already applied in session, pick it up for initial render
$applied_coupon = $_SESSION['applied_coupon'] ?? null;
$applied_discount = 0.0;
if (is_array($applied_coupon)) {
    $applied_discount = (float)($applied_coupon['discount_amount'] ?? 0);
}
$display_total = max(0, $total - $applied_discount);
$payable_total = $display_total; // used for server-side inserts and wallet deduction
$applied_coupon_id = is_array($applied_coupon) ? ($applied_coupon['coupon_id'] ?? null) : null;

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    $address_id = intval($_POST['address_id'] ?? 0);
    $payment_method = clean_input($_POST['payment_method'] ?? '');
    $notes = clean_input($_POST['notes'] ?? '');
    // Recalculate payable_total defensively in case session changed between render and submit
    $applied_coupon = $_SESSION['applied_coupon'] ?? null;
    $applied_discount = is_array($applied_coupon) ? (float)($applied_coupon['discount_amount'] ?? 0) : 0.0;
    $payable_total = max(0, $total - $applied_discount);
    $applied_coupon_id = is_array($applied_coupon) ? ($applied_coupon['coupon_id'] ?? null) : null;
    
    // Detect if orders table has coupon columns; if not, avoid using them in INSERTs
    $hasDiscountCol = false; $hasCouponCol = false; $canInsertCoupon = false;
    if ($conn) {
        $res1 = $conn->query("SHOW COLUMNS FROM orders LIKE 'discount_amount'");
        if ($res1 && $res1->num_rows > 0) { $hasDiscountCol = true; }
        $res2 = $conn->query("SHOW COLUMNS FROM orders LIKE 'coupon_id'");
        if ($res2 && $res2->num_rows > 0) { $hasCouponCol = true; }
        $canInsertCoupon = ($hasDiscountCol && $hasCouponCol);
    }
    
    if ($address_id == 0) {
        $error = "Please select a shipping address";
    } elseif (empty($payment_method)) {
        $error = "Please select a payment method";
    } else {
        $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));

        if ($payment_method === 'wallet') {
            // Wallet flow: ensure sufficient balance and deduct atomically, then mark paid
            try {
                $conn->begin_transaction();
                $deduct = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE user_id = ? AND wallet_balance >= ?");
                $deduct->bind_param("did", $payable_total, $user_id, $payable_total);
                $deduct->execute();
                if ($deduct->affected_rows !== 1) {
                    $conn->rollback();
                    $error = "Not enough balance. Choose other options";
                } else {
                    $payment_status = 'paid';
                    if ($canInsertCoupon) {
                        $stmt = $conn->prepare("INSERT INTO orders (user_id, order_number, subtotal, tax_amount, shipping_amount, discount_amount, 
                                               total_amount, payment_method, payment_status, shipping_address_id, coupon_id, notes) 
                                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $discAmt = (float)$applied_discount;
                        $couponId = $applied_coupon_id !== null ? (int)$applied_coupon_id : null;
                        $stmt->bind_param("isdddddssiis", $user_id, $order_number, $subtotal, $tax_amount, $shipping_amount, 
                                         $discAmt, $payable_total, $payment_method, $payment_status, $address_id, $couponId, $notes);
                    } else {
                        $stmt = $conn->prepare("INSERT INTO orders (user_id, order_number, subtotal, tax_amount, shipping_amount, 
                                               total_amount, payment_method, payment_status, shipping_address_id, notes) 
                                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("isddddssis", $user_id, $order_number, $subtotal, $tax_amount, $shipping_amount, 
                                         $payable_total, $payment_method, $payment_status, $address_id, $notes);
                    }
                    if ($stmt->execute()) {
                        $order_id = $stmt->insert_id;
                        foreach ($cart_items as $item) {
                            $item_subtotal = $item['price'] * $item['quantity'];
                            $oi = $conn->prepare("INSERT INTO order_items (order_id, product_id, variant_id, product_name, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)");
                            $oi->bind_param("iiisidd", $order_id, $item['product_id'], $item['variant_id'], $item['product_name'], $item['quantity'], $item['price'], $item_subtotal);
                            $oi->execute();
                            if ($item['variant_id']) {
                                $conn->query("UPDATE product_variants SET stock_quantity = stock_quantity - {$item['quantity']} WHERE variant_id = {$item['variant_id']}");
                            }
                        }
                        $conn->query("DELETE FROM cart WHERE user_id = $user_id");
                        $points = floor($payable_total);
                        $conn->query("UPDATE users SET loyalty_points = loyalty_points + $points WHERE user_id = $user_id");
                        // Clear applied coupon after successful order
                        unset($_SESSION['applied_coupon']);
                        $conn->commit();
                        $conn->autocommit(true);
                        header('Location: ' . SITE_URL . '/order-confirmation.php?order=' . $order_number);
                        exit;
                    } else {
                        $errMsg = $stmt->error ?: 'Failed to place order. Please try again.';
                        $conn->rollback();
                        $conn->autocommit(true);
                        $error = $errMsg;
                    }
                }
            } catch (Throwable $ex) {
                try { $conn->rollback(); $conn->autocommit(true); } catch (Throwable $e2) {}
                $error = "Failed to place order. Please try again.";
            }
        } else {
            // Existing flow (PayPal/Credit set to paid by current logic; COD pending)
            $payment_status = in_array($payment_method, ['credit_card','paypal'], true) ? 'paid' : 'pending';
            $isCOD = ($payment_method === 'cod');
            $insert_discount = $isCOD ? 0.0 : (float)$applied_discount;
            $insert_coupon_id = $isCOD ? null : ($applied_coupon_id !== null ? (int)$applied_coupon_id : null);
            $insert_total = $isCOD ? $total : $payable_total;
            if ($canInsertCoupon) {
                $stmt = $conn->prepare("INSERT INTO orders (user_id, order_number, subtotal, tax_amount, shipping_amount, discount_amount, 
                                       total_amount, payment_method, payment_status, shipping_address_id, coupon_id, notes) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isdddddssiis", $user_id, $order_number, $subtotal, $tax_amount, $shipping_amount, 
                                 $insert_discount, $insert_total, $payment_method, $payment_status, $address_id, $insert_coupon_id, $notes);
            } else {
                $stmt = $conn->prepare("INSERT INTO orders (user_id, order_number, subtotal, tax_amount, shipping_amount, 
                                       total_amount, payment_method, payment_status, shipping_address_id, notes) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isddddssis", $user_id, $order_number, $subtotal, $tax_amount, $shipping_amount, 
                                 $insert_total, $payment_method, $payment_status, $address_id, $notes);
            }
            if ($stmt->execute()) {
                $order_id = $stmt->insert_id;
                foreach ($cart_items as $item) {
                    $item_subtotal = $item['price'] * $item['quantity'];
                    $oi = $conn->prepare("INSERT INTO order_items (order_id, product_id, variant_id, product_name, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $oi->bind_param("iiisidd", $order_id, $item['product_id'], $item['variant_id'], $item['product_name'], $item['quantity'], $item['price'], $item_subtotal);
                    $oi->execute();
                    if ($item['variant_id']) {
                        $conn->query("UPDATE product_variants SET stock_quantity = stock_quantity - {$item['quantity']} WHERE variant_id = {$item['variant_id']}");
                    }
                }
                $conn->query("DELETE FROM cart WHERE user_id = $user_id");
                $points = floor($insert_total);
                $conn->query("UPDATE users SET loyalty_points = loyalty_points + $points WHERE user_id = $user_id");
                // Clear applied coupon after successful order (and ensure removed for COD too)
                unset($_SESSION['applied_coupon']);
                header('Location: ' . SITE_URL . '/order-confirmation.php?order=' . $order_number);
                exit;
            } else {
                $error = $stmt->error ?: "Failed to place order. Please try again.";
            }
        }
    }
}

include 'header.php';
?>

<div class="container" style="margin-top: 20px;">
    <h2 style="margin-bottom: 30px;">Checkout</h2>

    <?php if ($error): ?>
        <div class="alert alert-error" data-error-text="<?php echo htmlspecialchars($error, ENT_QUOTES); ?>" style="display:none;"></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div style="display: grid; grid-template-columns: 1fr 400px; gap: 30px;">
            <div>
                <!-- Shipping Address -->
                <div class="card">
                    <h3 style="margin-bottom: 20px;"><i class="fas fa-map-marker-alt" style="color: #f53d2d;"></i> Shipping Address</h3>

                    <?php if (empty($addresses)): ?>
                        <div class="alert alert-info">
                            <p>No saved addresses. <a href="addresses.php" style="color: #f53d2d; font-weight: 600;">Add an address</a></p>
                        </div>
                    <?php else: ?>
                        <div style="display: grid; gap: 15px;">
                            <?php foreach ($addresses as $addr): ?>
                                <label style="cursor: pointer;">
                                    <input type="radio" name="address_id" value="<?php echo $addr['address_id']; ?>" 
                                           <?php echo $addr['is_default'] ? 'checked' : ''; ?> required style="display: none;">
                                    <div class="address-option" style="border: 2px solid #ddd; border-radius: 8px; padding: 20px; transition: all 0.3s;">
                                        <?php if ($addr['is_default']): ?>
                                            <span style="background: #f53d2d; color: white; padding: 3px 10px; border-radius: 3px; font-size: 11px; font-weight: 600; float: right;">DEFAULT</span>
                                        <?php endif; ?>
                                        <div style="font-weight: 600; font-size: 16px; margin-bottom: 8px;">
                                            <?php echo htmlspecialchars($addr['full_name']); ?>
                                        </div>
                                        <div style="color: #666; line-height: 1.6;">
                                            <?php echo htmlspecialchars($addr['address_line1']); ?><br>
                                            <?php if ($addr['address_line2']): echo htmlspecialchars($addr['address_line2']) . '<br>'; endif; ?>
                                            <?php echo htmlspecialchars($addr['city']); ?>, <?php echo htmlspecialchars($addr['state']); ?> <?php echo htmlspecialchars($addr['postal_code']); ?><br>
                                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($addr['phone']); ?>
                                        </div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Payment Method -->
                <div class="card" style="margin-top: 20px;">
                    <h3 style="margin-bottom: 20px;"><i class="fas fa-credit-card" style="color: #f53d2d;"></i> Payment Method</h3>

                    <div style="display: grid; gap: 15px;">
                        <label style="cursor: pointer;">
                            <input type="radio" name="payment_method" value="wallet" required style="display: none;">
                            <div class="payment-option" style="border: 2px solid #ddd; border-radius: 8px; padding: 20px; display: flex; align-items: center; gap: 15px;">
                                <i class="fas fa-wallet" style="font-size: 28px; color: #795548;"></i>
                                <div>
                                    <div style="font-weight: 600;">Pay with Wallet</div>
                                    <div style="font-size: 13px; color: #666;">Balance: <?php echo CURRENCY_SYMBOL . number_format($wallet_balance, 2); ?></div>
                                </div>
                            </div>
                        </label>
                        <label style="cursor: pointer;">
                            <input type="radio" name="payment_method" value="credit_card" required style="display: none;">
                            <div class="payment-option" style="border: 2px solid #ddd; border-radius: 8px; padding: 20px; display: flex; align-items: center; gap: 15px;">
                                <i class="fas fa-credit-card" style="font-size: 28px; color: #666;"></i>
                                <div>
                                    <div style="font-weight: 600;">Credit/Debit Card</div>
                                    <div style="font-size: 13px; color: #666;">Visa, Mastercard, Amex</div>
                                </div>
                            </div>
                        </label>

                        <label style="cursor: pointer;">
                            <input type="radio" name="payment_method" value="paypal" required style="display: none;">
                            <div class="payment-option" style="border: 2px solid #ddd; border-radius: 8px; padding: 20px; display: flex; align-items: center; gap: 15px;">
                                <i class="fab fa-paypal" style="font-size: 28px; color: #0070ba;"></i>
                                <div>
                                    <div style="font-weight: 600;">PayPal</div>
                                    <div style="font-size: 13px; color: #666;">Fast & secure</div>
                                </div>
                            </div>
                        </label>

                        <label style="cursor: pointer;">
                            <input type="radio" name="payment_method" value="cod" required style="display: none;">
                            <div class="payment-option" style="border: 2px solid #ddd; border-radius: 8px; padding: 20px; display: flex; align-items: center; gap: 15px;">
                                <i class="fas fa-money-bill-wave" style="font-size: 28px; color: #4caf50;"></i>
                                <div>
                                    <div style="font-weight: 600;">Cash on Delivery</div>
                                    <div style="font-size: 13px; color: #666;">Pay when you receive</div>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Order Notes -->
                <div class="card" style="margin-top: 20px;">
                    <h3 style="margin-bottom: 15px;">Order Notes (Optional)</h3>
                    <textarea name="notes" class="form-control" rows="4" placeholder="Special instructions..."></textarea>
                </div>
            </div>

            <!-- Order Summary -->
            <div>
                <div class="card" style="position: sticky; top: 20px;">
                    <h3 style="margin-bottom: 20px;">Order Summary</h3>

                    <div style="max-height: 300px; overflow-y: auto; margin-bottom: 20px;">
                        <?php foreach ($cart_items as $item): ?>
                            <div style="display: flex; gap: 12px; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #e5e5e5;">
                                <img src="<?php echo $item['image_url'] ?? 'assets/images/no-image.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                                <div style="flex: 1;">
                                    <div style="font-weight: 600; font-size: 14px; margin-bottom: 5px;">
                                        <?php echo htmlspecialchars($item['product_name']); ?>
                                    </div>
                                    <div style="font-size: 12px; color: #666;">
                                        Qty: <?php echo $item['quantity']; ?>
                                    </div>
                                    <div style="font-weight: 600; color: #f53d2d;">
                                        <?php echo CURRENCY_SYMBOL . number_format($item['price'] * $item['quantity'], 2); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="border-top: 1px solid #e5e5e5; padding-top: 20px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                            <span style="color: #666;">Subtotal:</span>
                            <span style="font-weight: 600;"><?php echo CURRENCY_SYMBOL . number_format($subtotal, 2); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                            <span style="color: #666;">Tax:</span>
                            <span style="font-weight: 600;"><?php echo CURRENCY_SYMBOL . number_format($tax_amount, 2); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                            <span style="color: #666;">Shipping:</span>
                            <span style="font-weight: 600;"><?php echo $shipping_amount == 0 ? 'FREE' : CURRENCY_SYMBOL . number_format($shipping_amount, 2); ?></span>
                        </div>
                        <div style="border-top: 2px solid #e5e5e5; margin-top: 15px; padding-top: 15px; display: flex; justify-content: space-between; font-size: 20px; font-weight: 700;">
                            <span>Total:</span>
                            <span style="color: #f53d2d;" id="orderTotalDisplay"><?php echo CURRENCY_SYMBOL . number_format($display_total, 2); ?></span>
                        </div>
                        <?php if ($applied_discount > 0): ?>
                            <div style="margin-top:8px; font-size:13px; color:#2e7d32; display:flex; align-items:center; gap:8px;">
                                <span>Coupon Applied: -<?php echo CURRENCY_SYMBOL . number_format($applied_discount, 2); ?> (<?php echo htmlspecialchars($applied_coupon['code']); ?>)</span>
                                <button type="button" id="removeCouponBtn" style="background:none; border:none; color:#c62828; cursor:pointer; font-size:12px; font-weight:600;">Remove</button>
                            </div>
                        <?php endif; ?>
                        <div style="margin-top:20px;">
                            <label style="font-weight:600; font-size:14px; display:block; margin-bottom:6px;">Promo / Coupon Code</label>
                            <div style="display:flex; gap:8px;">
                                <input type="text" id="couponCodeInput" class="form-control" placeholder="Enter code" style="flex:1; text-transform:uppercase;" value="">
                                <button type="button" id="applyCouponBtn" class="btn btn-secondary" style="white-space:nowrap;">
                                    <i class="fas fa-ticket-alt"></i> Apply
                                </button>
                            </div>
                            <div id="couponFeedback" style="margin-top:6px; font-size:12px; color:#666;"></div>
                        </div>
                    </div>

                    <button type="submit" name="place_order" class="btn btn-primary btn-full" id="placeOrderBtn" style="margin-top: 20px; padding: 15px;">
                        <i class="fas fa-lock"></i> Place Order
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Toast Container -->
<div id="toast-container" style="position:fixed; top:20px; right:20px; z-index:9999; display:flex; flex-direction:column; gap:10px; max-width:320px;"></div>
<!-- Wallet Meta -->
<div id="walletMeta" data-balance="<?php echo number_format($wallet_balance, 2, '.', ''); ?>" data-total="<?php echo number_format($payable_total, 2, '.', ''); ?>" style="display:none;"></div>

<style>
.toast { background:#333; color:#fff; padding:12px 16px; border-radius:6px; box-shadow:0 4px 12px rgba(0,0,0,.15); font-size:14px; display:flex; align-items:flex-start; gap:10px; animation:fadeIn .25s ease; }
.toast.success { background:#2e7d32; }
.toast.error { background:#c62828; }
.toast.info { background:#1565c0; }
.toast .toast-close { background:none; border:none; color:#fff; font-size:16px; line-height:1; cursor:pointer; padding:0 4px; }
@keyframes fadeIn { from {opacity:0; transform:translateY(-6px);} to {opacity:1; transform:translateY(0);} }
</style>

<script>
// Toast utility
function showToast(message, type = 'error', duration = 3000) {
    const container = document.getElementById('toast-container');
    if (!container) return;
    const el = document.createElement('div');
    el.className = 'toast ' + type;
    el.innerHTML = `<span style="flex:1;">${message}</span><button class="toast-close" aria-label="Close">&times;</button>`;
    container.appendChild(el);
    const remove = () => { el.style.opacity = '0'; setTimeout(()=> el.remove(), 250); };
    el.querySelector('.toast-close').addEventListener('click', remove);
    setTimeout(remove, duration);
}

// Apply coupon via AJAX
document.getElementById('applyCouponBtn')?.addEventListener('click', function(){
    const codeEl = document.getElementById('couponCodeInput');
    if (!codeEl) return;
    const raw = (codeEl.value || '').trim().toUpperCase();
    if (!raw) { showToast('Enter a coupon code', 'error'); return; }
    fetch('ajax/apply-coupon.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept':'application/json' },
        body: 'code=' + encodeURIComponent(raw)
    })
    .then(r => { if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
    .then(data => {
        if (!data) { showToast('Unexpected response', 'error'); return; }
        if (data.success) {
            const discount = parseFloat(data.discount_amount || '0');
            const newTotal = parseFloat(data.new_total || '0');
            const totalEl = document.getElementById('orderTotalDisplay');
            if (totalEl) totalEl.textContent = '<?php echo CURRENCY_SYMBOL; ?>' + newTotal.toFixed(2);
            const wm = document.getElementById('walletMeta');
            if (wm) wm.dataset.total = newTotal.toFixed(2);
            codeEl.value = '';
            // Inject applied coupon UI (if not already present)
            if (!document.getElementById('removeCouponBtn')) {
                const totalContainer = document.getElementById('orderTotalDisplay').parentElement.parentElement;
                const appliedDiv = document.createElement('div');
                appliedDiv.style.marginTop='8px'; appliedDiv.style.fontSize='13px'; appliedDiv.style.color='#2e7d32'; appliedDiv.style.display='flex'; appliedDiv.style.alignItems='center'; appliedDiv.style.gap='8px';
                appliedDiv.innerHTML = '<span>Coupon Applied: -<?php echo CURRENCY_SYMBOL; ?>'+discount.toFixed(2)+' ('+ (data.code || '') +')</span>'+
                    '<button type="button" id="removeCouponBtn" style="background:none; border:none; color:#c62828; cursor:pointer; font-size:12px; font-weight:600;">Remove</button>';
                totalContainer.appendChild(appliedDiv);
                attachRemoveCouponHandler();
            }
            showToast('Coupon applied: -<?php echo CURRENCY_SYMBOL; ?>' + discount.toFixed(2) + (data.notice ? '\n'+data.notice : ''), 'success');
        } else {
            showToast(data.message || 'Invalid coupon', 'error');
        }
    })
    .catch(() => showToast('Network error applying coupon', 'error'));
});

function attachRemoveCouponHandler(){
    const btn = document.getElementById('removeCouponBtn');
    if (!btn) return;
    btn.addEventListener('click', function(){
        fetch('ajax/remove-coupon.php', {
            method:'POST',
            headers:{'Accept':'application/json'}
        })
        .then(r => { if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
        .then(data=>{
            if (data.success){
                const newTotal = parseFloat(data.new_total || '0');
                const totalEl = document.getElementById('orderTotalDisplay');
                if (totalEl) totalEl.textContent = '<?php echo CURRENCY_SYMBOL; ?>' + newTotal.toFixed(2);
                const wm = document.getElementById('walletMeta');
                if (wm) wm.dataset.total = newTotal.toFixed(2);
                btn.parentElement.remove();
                showToast('Coupon removed', 'info');
            } else {
                showToast(data.message || 'Failed to remove coupon', 'error');
            }
        })
        .catch(()=> showToast('Network error', 'error'));
    });
}
attachRemoveCouponHandler();

// Intercept Place Order click for client-side validation to show toast messages
document.getElementById('placeOrderBtn')?.addEventListener('click', function(e){
    const addressRadios = document.querySelectorAll('input[name="address_id"]');
    const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
    const hasAddresses = addressRadios.length > 0;
    const selectedAddress = document.querySelector('input[name="address_id"]:checked');
    const selectedPayment = document.querySelector('input[name="payment_method"]:checked');

    // If no addresses at all OR none selected
    if (!hasAddresses) {
        e.preventDefault();
        showToast('Add your address first', 'error');
        return;
    } else if (!selectedAddress) {
        e.preventDefault();
        showToast('Add your address first', 'error');
        return;
    }

    // Payment method missing
    if (!selectedPayment) {
        e.preventDefault();
        showToast('Choose payment method', 'error');
        return;
    }

    // Wallet balance check (client-side convenience)
    if (selectedPayment && selectedPayment.value === 'wallet') {
        const wm = document.getElementById('walletMeta');
        const bal = parseFloat(wm?.dataset.balance || '0');
        const tot = parseFloat(wm?.dataset.total || '0');
        if (bal < tot) {
            e.preventDefault();
            showToast('Not enough balance. Choose other options', 'error');
            return;
        }
    }
    // Allow normal submit
});

// If backend set any error, convert to toast so user always sees the reason
const backendErrorEl = document.querySelector('.alert.alert-error[data-error-text]');
if (backendErrorEl) {
    const raw = backendErrorEl.getAttribute('data-error-text') || '';
    const msg = raw.trim();
    if (msg) {
        let m = msg;
        if (/shipping address/i.test(msg)) m = 'Add your address first';
        else if (/payment method/i.test(msg)) m = 'Choose payment method';
        else if (/not enough balance/i.test(msg)) m = 'Not enough balance. Choose other options';
        showToast(m, 'error');
    }
}

document.querySelectorAll('input[name="address_id"]').forEach(input => {
    input.addEventListener('change', function() {
        document.querySelectorAll('.address-option').forEach(option => {
            option.style.borderColor = '#ddd';
            option.style.background = 'white';
        });
        this.nextElementSibling.style.borderColor = '#f53d2d';
        this.nextElementSibling.style.background = '#fff5f3';
    });
    if (input.checked) {
        input.nextElementSibling.style.borderColor = '#f53d2d';
        input.nextElementSibling.style.background = '#fff5f3';
    }
});

document.querySelectorAll('input[name="payment_method"]').forEach(input => {
    input.addEventListener('change', function() {
        document.querySelectorAll('.payment-option').forEach(option => {
            option.style.borderColor = '#ddd';
            option.style.background = 'white';
        });
        this.nextElementSibling.style.borderColor = '#f53d2d';
        this.nextElementSibling.style.background = '#fff5f3';
        // If COD selected and coupon applied, auto remove coupon (not allowed for COD)
        if (this.value === 'cod' && document.getElementById('removeCouponBtn')) {
            fetch('ajax/remove-coupon.php', {method:'POST', headers:{'Accept':'application/json'}})
             .then(r => { if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
             .then(data=>{
                if (data.success){
                    const newTotal = parseFloat(data.new_total || '0');
                    const totalEl = document.getElementById('orderTotalDisplay');
                    if (totalEl) totalEl.textContent = '<?php echo CURRENCY_SYMBOL; ?>' + newTotal.toFixed(2);
                    const wm = document.getElementById('walletMeta');
                    if (wm) wm.dataset.total = newTotal.toFixed(2);
                    document.getElementById('removeCouponBtn')?.parentElement.remove();
                    showToast('Coupons aren\'t available for Cash on Delivery', 'info');
                }
             })
             .catch(()=> showToast('Network error', 'error'));
        }
    });
});
</script>

<?php include 'footer.php'; ?>