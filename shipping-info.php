<?php
require_once 'config.php';

$page_title = 'Shipping & Tracking - ' . SITE_NAME;

// Load site settings for dynamic values (e.g., shipping fee)
$settings = [];
if (isset($conn) && $conn instanceof mysqli) {
    if ($res = $conn->query("SELECT setting_key, setting_value FROM site_settings")) {
        while ($row = $res->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
}
$shipping_fee = isset($settings['shipping_fee']) ? (float)$settings['shipping_fee'] : 5.99;

include 'header.php';
?>

<style>
/* Scoped styles for the Shipping & Tracking page */
.shipping-hero {
    background: linear-gradient(135deg, #f8fafc, #ffffff);
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 28px;
    margin-top: 20px;
}
.shipping-hero h1 {
    margin: 0 0 8px;
    font-size: 1.75rem;
    color: #111827;
}
.shipping-hero p { color: #4b5563; }

.policy-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; margin: 24px 0; }
.policy-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 18px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
}
.policy-card h3 { margin: 0 0 6px; font-size: 1.1rem; color: #111827; }
.policy-card p, .policy-card li { color: #4b5563; line-height: 1.55; }
.policy-icon { width: 36px; height: 36px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; margin-right: 10px; }
.icon-blue { background: #e0f2fe; color: #0369a1; }
.icon-green { background: #dcfce7; color: #166534; }
.icon-orange { background: #ffedd5; color: #9a3412; }
.icon-purple { background: #ede9fe; color: #5b21b6; }
.icon-yellow { background: #fef9c3; color: #854d0e; }
.icon-pink { background: #fee2e2; color: #991b1b; }

.policy-list { margin: 8px 0 0 22px; }
.badge { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: .75rem; border: 1px solid #e5e7eb; color: #374151; background: #f9fafb; }

.track-box { 
    display:flex; 
    gap:8px; 
    margin-top: 12px; 
    align-items: stretch; 
    width: 100%;
}
.track-box input { 
    flex:1 1 auto; 
    min-width: 0; /* allow shrinking within flex to prevent overflow */
    padding: 10px 12px; 
    border:1px solid #d1d5db; 
    border-radius: 8px; 
    font-size: 0.95rem; 
    box-sizing: border-box;
}
.track-box button { 
    flex: 0 0 auto; 
    padding: 10px 14px; 
    border:none; 
    border-radius: 8px; 
    background:#111827; 
    color:#fff; 
    cursor:pointer; 
    white-space: nowrap;
}
.track-box button:hover { background:#0b1220; }

/* Stack input and button on narrow screens to keep layout neat */
@media (max-width: 480px) {
    .track-box { flex-direction: column; }
    .track-box button { width: 100%; }
}

.note { font-size: 0.9rem; color:#6b7280; margin-top: 6px; }
</style>

<div class="container">
    <div class="shipping-hero">
        <span class="badge">Help Center</span>
        <h1>Shipping & Order Tracking</h1>
        <p>Below is a quick overview of how we process and deliver your orders, plus how to track their progress once shipped.</p>
    </div>

    <div class="policy-grid">
        <div class="policy-card">
            <div style="display:flex; align-items:center; margin-bottom:8px;">
                <span class="policy-icon icon-blue"><i class="fa-solid fa-truck"></i></span>
                <h3 style="margin:0;">Processing & Handling</h3>
            </div>
            <p>
                • Orders are typically processed within 24–48 business hours after payment confirmation.<br/>
                • You’ll be noticed once your order is packed and ready to ship.
            </p>
        </div>

        <div class="policy-card">
            <div style="display:flex; align-items:center; margin-bottom:8px;">
                <span class="policy-icon icon-green"><i class="fa-solid fa-shipping-fast"></i></span>
                <h3 style="margin:0;">Delivery Estimates</h3>
            </div>
            <ul class="policy-list">
                <li>Standard: Usually 3–7 business days after dispatch</li>
                <li>Express: Usually 1–3 business days after dispatch</li>
            </ul>
            <p class="note">Actual timelines vary by destination and courier capacity. Estimated delivery dates are shown at checkout and in your order emails.</p>
        </div>

        <div class="policy-card">
            <div style="display:flex; align-items:center; margin-bottom:8px;">
                <span class="policy-icon icon-orange"><i class="fa-solid fa-bag-shopping"></i></span>
                <h3 style="margin:0;">Shipping Costs</h3>
            </div>
            <p>
                Standard shipping starts at <strong><?php echo number_format($shipping_fee, 2); ?></strong> (final rates are calculated at checkout based on your address and cart).
            </p>
            <p class="note">Any promotional free shipping thresholds will be displayed on product and cart pages when applicable.</p>
        </div>

        <div class="policy-card">
            <div style="display:flex; align-items:center; margin-bottom:8px;">
                <span class="policy-icon icon-purple"><i class="fa-solid fa-location-dot"></i></span>
                <h3 style="margin:0;">Order Tracking</h3>
            </div>
            <p>Once your order ships, we’ll send you a tracking link by email. You can also track using the button below.</p>
            <div class="track-box">
                <input type="text" id="trackInput" placeholder="Enter your order number (e.g. #12345)" aria-label="Order number" />
                <button type="button" onclick="goTrack()"><i class="fa-solid fa-location-arrow" style="margin-right:6px;"></i>Track</button>
            </div>
            <p class="note">You can also visit the dedicated tracker: <a href="<?php echo SITE_URL; ?>/track-order.php">Track Order</a></p>
        </div>

        <div class="policy-card">
            <div style="display:flex; align-items:center; margin-bottom:8px;">
                <span class="policy-icon icon-yellow"><i class="fa-solid fa-triangle-exclamation"></i></span>
                <h3 style="margin:0;">Delays & Exceptions</h3>
            </div>
            <p>
                During sales or holidays, processing and delivery may take longer. Severe weather or carrier issues can also impact timelines. We’ll keep you updated via email if delays occur.
            </p>
        </div>

        <div class="policy-card">
            <div style="display:flex; align-items:center; margin-bottom:8px;">
                <span class="policy-icon icon-pink"><i class="fa-solid fa-rotate-left"></i></span>
                <h3 style="margin:0;">Returns & Exchanges</h3>
            </div>
            <p>
                If you need to return an item after delivery, you can submit a request from your order page. Review our return window and eligibility before submitting.
            </p>
            <p class="note">Request a return: <a href="<?php echo SITE_URL; ?>/orders.php">My Orders</a> → select the order → Request Return.</p>
        </div>

        <div class="policy-card">
            <div style="display:flex; align-items:center; margin-bottom:8px;">
                <span class="policy-icon icon-blue"><i class="fa-solid fa-shield"></i></span>
                <h3 style="margin:0;">Address Accuracy</h3>
            </div>
            <p>
                Please double‑check your shipping address at checkout. We can’t reroute parcels once they’ve shipped. Undeliverable packages will be processed per carrier policy.
            </p>
        </div>

        <div class="policy-card">
            <div style="display:flex; align-items:center; margin-bottom:8px;">
                <span class="policy-icon icon-green"><i class="fa-solid fa-circle-info"></i></span>
                <h3 style="margin:0;">Need Help?</h3>
            </div>
            <p>
                Our team is here to help with tracking updates or delivery questions.
            </p>
            <p><a href="<?php echo SITE_URL; ?>/contact.php">Contact Support</a></p>
        </div>
    </div>
</div>

<script>
function goTrack() {
    var val = document.getElementById('trackInput').value.trim();
    if (!val) { return; }
    // Non-invasive redirect to existing tracker. No backend changes.
    window.location.href = '<?php echo SITE_URL; ?>/track-order.php?order=' + encodeURIComponent(val.replace(/^#/, ''));
}
</script>

<?php include 'footer.php'; ?>
