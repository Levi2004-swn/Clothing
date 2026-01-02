<?php
require_once 'config.php';

$page_title = 'Help & How It Works - ' . SITE_NAME;
include 'header.php';
?>

<style>
/***** Help page scoped styles *****/
.help-hero {
  background: linear-gradient(135deg, #fff 0%, #fff5f4 100%);
  border-bottom: 1px solid #f1f5f9;
  padding: 36px 0 24px;
}
.help-hero .title {
  display:flex; align-items:center; gap:12px;
  font-size: 28px; font-weight: 800; color:#1f2937;
}
.help-hero .title i { color:#f53d2d; font-size:32px; }
.help-hero .subtitle { color:#475569; margin-top:8px; font-size:16px; }

.help-grid { display:grid; grid-template-columns: repeat(12, 1fr); gap:20px; margin: 28px 0 10px; }
.help-card { grid-column: span 6; background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:18px; box-shadow:0 6px 16px rgba(0,0,0,0.04); }
@media (min-width: 992px){ .help-card { grid-column: span 4; } }
.help-card .hc-head { display:flex; gap:12px; align-items:center; margin-bottom:10px; }
.help-card .hc-head .hc-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; background:#fff1f0; color:#f53d2d; }
.help-card .hc-head h3 { margin:0; font-size:18px; color:#111827; }
.help-card p { color:#4b5563; line-height:1.6; margin:0; }
.help-card ul { margin:8px 0 0 20px; color:#4b5563; }
.help-card ul li { margin:6px 0; }

.help-section { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:22px; box-shadow:0 6px 16px rgba(0,0,0,0.04); }
.help-section h2 { margin:0 0 10px; font-size:20px; color:#111827; }
.help-section .muted { color:#64748b; }
.help-section .points-badges { display:flex; flex-wrap:wrap; gap:10px; margin:10px 0 6px; }
.help-section .badge { background:#fff1f0; color:#f53d2d; border:1px solid #ffd1cb; border-radius:999px; padding:6px 10px; font-weight:600; font-size:13px; }
.help-section .rule { display:flex; gap:10px; align-items:flex-start; padding:10px 0; border-top:1px dashed #e5e7eb; }
.help-section .rule:first-of-type { border-top:none; }
.help-section .rule i { color:#f53d2d; }

.help-cta { display:flex; gap:10px; flex-wrap:wrap; margin-top:16px; }
.help-cta a.btn { display:inline-flex; gap:8px; align-items:center; text-decoration:none; padding:10px 14px; border-radius:8px; font-weight:700; }
.help-cta .btn-primary { background:#f53d2d; color:#fff; }
.help-cta .btn-secondary { background:#f5f5f5; color:#111827; }
</style>

<div class="help-hero">
  <div class="container">
    <div class="title"><i class="fas fa-circle-question"></i> Help & How It Works</div>
    <div class="subtitle">A quick guide to browsing, shopping, and making the most of your loyalty points.</div>
  </div>
</div>

<div class="container" style="margin: 24px auto 36px;">

  <div class="help-grid">
    <div class="help-card">
      <div class="hc-head">
        <div class="hc-icon"><i class="fas fa-compass"></i></div>
        <h3>Browse & Search</h3>
      </div>
      <p>Explore our Men, Women, Kids, and Accessories categories. Use the search bar to quickly find styles and brands you love. Filters on category pages help you narrow down by price, size, color, and more.</p>
      <ul>
        <li>Try keywords like “running shoes” or “linen shirt”.</li>
        <li>Use the Deals tab for seasonal offers.</li>
      </ul>
    </div>

    <div class="help-card">
      <div class="hc-head">
        <div class="hc-icon"><i class="fas fa-shirt"></i></div>
        <h3>Product Details</h3>
      </div>
      <p>Open a product to see photos, sizes, colors, and reviews. Add to Wishlist to save items for later, or add directly to Cart when you’re ready.</p>
      <ul>
        <li>Check the Size Guide for fit tips.</li>
        <li>Read reviews and ratings before you buy.</li>
      </ul>
    </div>

    <div class="help-card">
      <div class="hc-head">
        <div class="hc-icon"><i class="fas fa-cart-shopping"></i></div>
        <h3>Cart & Checkout</h3>
      </div>
      <p>Review your cart, apply any available coupons, and choose your payment method. You can also opt for standard or express shipping where available.</p>
      <ul>
        <li>Confirm your address and contact info.</li>
        <li>We’ll email your order confirmation instantly.</li>
      </ul>
    </div>

    <div class="help-card">
      <div class="hc-head">
        <div class="hc-icon"><i class="fas fa-box-open"></i></div>
        <h3>Orders & Tracking</h3>
      </div>
      <p>Track your order status from your account under “My Orders”. You’ll receive updates when your order is processed, shipped, and delivered.</p>
      <ul>
        <li>View invoices and order details anytime.</li>
        <li>Need help? Start with our Returns & Support below.</li>
      </ul>
    </div>

    <div class="help-card">
      <div class="hc-head">
        <div class="hc-icon"><i class="fas fa-rotate-left"></i></div>
        <h3>Returns & Support</h3>
      </div>
      <p>If an item isn’t right, request a return from your order details within the allowed window. Our team will guide you through the process.</p>
      <ul>
        <li>Check our Returns Policy for timelines and conditions.</li>
        <li>Contact Support for sizing or order questions.</li>
      </ul>
    </div>
  </div>

  <div class="help-section" aria-labelledby="loyalty-title">
    <h2 id="loyalty-title"><i class="fas fa-gift" style="color:#f53d2d;"></i> Loyalty Points: Earn & Redeem</h2>
    <p class="muted">Make every purchase more rewarding. Here’s how our loyalty points work (illustrative logic):</p>

    <div class="points-badges">
      <span class="badge"><i class="fas fa-coins"></i> Earn: 1 point per $1 spent</span>
      <span class="badge"><i class="fas fa-percent"></i> Redeem: 100 pts = $1 off</span>
      <span class="badge"><i class="fas fa-calendar-check"></i> Credit after delivery</span>
      <span class="badge"><i class="fas fa-hourglass-half"></i> Points expire in 12 months</span>
    </div>

    <div class="rule">
      <i class="fas fa-circle-plus"></i>
      <div>
        <strong>Earning Points.</strong>
        <div>You earn <b>1 point per $1</b> on paid orders (subtotal after discounts; excludes shipping and taxes). Points are credited <b>after your order is delivered</b>.</div>
      </div>
    </div>

    <div class="rule">
      <i class="fas fa-cart-arrow-down"></i>
      <div>
        <strong>Redeeming Points.</strong>
        <div><b>100 points = $1</b> discount. You can redeem in checkout once you’ve collected at least <b>500 points</b>. For balance and fair use, we cap redemptions at <b>up to 20% of the order subtotal</b> per purchase.</div>
      </div>
    </div>

    <div class="rule">
      <i class="fas fa-scale-balanced"></i>
      <div>
        <strong>Fair-Use & Expiry.</strong>
        <div>Points expire <b>12 months</b> from earning. Cancelled or refunded orders don’t earn points. Abuse may void points according to our policy.</div>
      </div>
    </div>

    <div class="rule">
      <i class="fas fa-hand-holding-dollar"></i>
      <div>
        <strong>How to Apply at Checkout.</strong>
        <div>On the payment step, enter the number of points to apply. We’ll auto-calc your discount and show the new total before you confirm.</div>
        <ul style="margin-top:6px;">
          <li>Points can’t be combined with certain coupon types or COD in some cases.</li>
          <li>We’ll always show what’s best for you when there’s a conflict.</li>
        </ul>
      </div>
    </div>

    <div class="help-cta">
      <a class="btn btn-primary" href="<?php echo SITE_URL; ?>/index.php"><i class="fas fa-shopping-bag"></i> Start Shopping</a>
      <a class="btn btn-secondary" href="<?php echo SITE_URL; ?>/orders.php"><i class="fas fa-receipt"></i> View My Orders</a>
      <a class="btn btn-secondary" href="<?php echo SITE_URL; ?>/contact.php"><i class="fas fa-headset"></i> Contact Support</a>
    </div>
  </div>

</div>

<?php include 'footer.php'; ?>
