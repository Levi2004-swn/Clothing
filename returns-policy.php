<?php
require_once 'config.php';

$page_title = 'Returns & Refunds - ' . SITE_NAME;
include 'header.php';
?>

<style>
/* Scoped styles for the Returns Policy page */
.returns-hero { background: #fff7ed; border: 1px solid #fed7aa; padding: 28px; border-radius: 12px; margin: 24px 0; }
.returns-hero h1 { margin: 0 0 8px; font-size: 1.8rem; color: #9a3412; }
.returns-hero p { color: #7c2d12; margin: 0; }

.benefit-badges { display: flex; flex-wrap: wrap; gap: 10px; margin: 16px 0 0; }
.benefit-badges .badge { display: inline-flex; align-items: center; gap: 8px; background: #f1f5f9; color: #0f172a; padding: 8px 12px; border-radius: 999px; font-weight: 600; border: 1px solid #e2e8f0; }
.benefit-badges .badge i { color: #f97316; }

.policy-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; margin: 24px 0; }
.policy-card { border: 1px solid #e5e7eb; border-radius: 10px; background: #fff; padding: 16px; }
.policy-card h3 { margin: 0 0 6px; font-size: 1.1rem; color: #111827; }
.policy-card p, .policy-card li { color: #4b5563; font-size: 0.95rem; }
.policy-card ul { margin: 8px 0 0; padding-left: 18px; }

.note { background: #ecfeff; border: 1px solid #a5f3fc; color: #155e75; padding: 12px 14px; border-radius: 8px; }
.warn { background: #fff7ed; border: 1px solid #fed7aa; color: #9a3412; padding: 12px 14px; border-radius: 8px; }

.actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 8px; }
.actions .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 8px; text-decoration: none; font-weight: 700; }
.actions .btn-primary { background: #f53d2d; color: #fff; }
.actions .btn-secondary { background: #f1f5f9; color: #0f172a; border: 1px solid #e2e8f0; }
.actions .btn i { opacity: 0.9; }

.section-title { font-size: 1.25rem; color: #111827; margin: 30px 0 10px; }
.small { font-size: 0.92rem; color: #4b5563; }
</style>

<div class="container" style="margin-top: 20px; margin-bottom: 60px;">
    <div class="returns-hero">
        <h1><i class="fas fa-undo"></i> Returns & Refunds Policy</h1>
        <p>Please review the conditions below before initiating a return. This page is for guidance only and does not change the existing process.</p>
        <div class="benefit-badges">
            <span class="badge"><i class="fas fa-clock"></i> Within 30 days of payment</span>
            <span class="badge"><i class="fas fa-box"></i> Delivered + Paid orders only</span>
            <span class="badge"><i class="fas fa-check-circle"></i> Valid reason required</span>
            <span class="badge"><i class="fas fa-receipt"></i> Proof of purchase</span>
        </div>
    </div>

    <div class="policy-grid">
        <div class="policy-card">
            <h3>Eligibility</h3>
            <ul>
                <li>Return requests must be submitted within <strong>30 days of payment</strong>.</li>
                <li>Order must be <strong>Delivered</strong> and <strong>Paid</strong>.</li>
                <li>Items must be in original condition: unworn, unwashed, with tags and packaging intact.</li>
                <li>A <strong>valid reason</strong> for return is required (e.g., defective item, wrong size/color, wrong item shipped).</li>
                <li>Proof of purchase (order number and email) is required.</li>
            </ul>
        </div>

        <div class="policy-card">
            <h3>Not Eligible</h3>
            <ul>
                <li>Requests made after the 30-day window.</li>
                <li>Orders that are not delivered or not paid.</li>
                <li>Items marked final sale, or personalized/altered products.</li>
                <li>Items showing wear, wash, stains, odors, or missing tags/accessories.</li>
            </ul>
        </div>

        <div class="policy-card">
            <h3>How Returns Work</h3>
            <ol class="small">
                <li>Go to <strong>My Orders</strong> and select the relevant order.</li>
                <li>Choose <strong>Request Return</strong> and provide a valid reason and details.
                </li>
                <li>Our team will review your request. Approved requests proceed to return instructions.</li>
                <li>Once received and inspected, refunds are processed per your original payment method.</li>
            </ol>
            <div class="actions">
                <a class="btn btn-primary" href="<?php echo SITE_URL; ?>/orders.php"><i class="fas fa-box-open"></i> View My Orders</a>
                <a class="btn btn-secondary" href="<?php echo SITE_URL; ?>/track-order.php"><i class="fas fa-location-dot"></i> Track Order</a>
            </div>
        </div>

        <div class="policy-card">
            <h3>Refunds</h3>
            <p class="small">Refunds are issued for eligible items after inspection. Timing may vary by payment provider. Shipping fees may be non-refundable unless the return is due to our error. Returns beyond 30 days of payment are not eligible.</p>
            <div class="note small"><i class="fas fa-info-circle"></i> Refund processing follows our internal verification and the original payment channel timelines.</div>
        </div>

        <div class="policy-card">
            <h3>Delivery & Exchanges</h3>
            <p class="small">For delivery timelines and shipping costs, see our Shipping & Tracking page. Exchanges depend on available stock and may require returning the original item first.</p>
            <div class="actions">
                <a class="btn btn-secondary" href="<?php echo SITE_URL; ?>/shipping-info.php"><i class="fas fa-truck"></i> Shipping & Tracking</a>
            </div>
        </div>

        <div class="policy-card">
            <h3>Need Help?</h3>
            <p class="small">If you have questions about eligibility or need assistance with your return, our support team can help.</p>
            <div class="actions">
                <a class="btn btn-secondary" href="<?php echo SITE_URL; ?>/contact.php"><i class="fas fa-headset"></i> Contact Support</a>
            </div>
        </div>
    </div>

    <div class="warn small" style="margin-top: 4px;">
        <strong>Important:</strong> This page summarizes our return conditions. It does not change your order status or the return workflow. To initiate a return, use the Request Return feature from your order in My Orders.
    </div>
</div>

<?php include 'footer.php'; ?>
