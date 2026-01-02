<?php
require_once 'config.php';

$page_title = 'Frequently Asked Questions - ' . SITE_NAME;
include 'header.php';
?>

<style>
/* Scoped styles for the FAQ page */
.faq-hero { background: #eef2ff; border: 1px solid #c7d2fe; padding: 28px; border-radius: 12px; margin: 24px 0; }
.faq-hero h1 { margin: 0 0 8px; font-size: 1.8rem; color: #3730a3; }
.faq-hero p { color: #312e81; margin: 0; }

.faq-badges { display: flex; flex-wrap: wrap; gap: 10px; margin: 16px 0 0; }
.faq-badges .badge { display: inline-flex; align-items: center; gap: 8px; background: #f1f5f9; color: #0f172a; padding: 8px 12px; border-radius: 999px; font-weight: 600; border: 1px solid #e2e8f0; }
.faq-badges .badge i { color: #6366f1; }

.faq-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 12px; }
.faq-actions .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 8px; text-decoration: none; font-weight: 700; }
.faq-actions .btn-primary { background: #f53d2d; color: #fff; }
.faq-actions .btn-secondary { background: #f1f5f9; color: #0f172a; border: 1px solid #e2e8f0; }

.faq-search { display: flex; align-items: center; gap: 10px; margin: 18px 0 8px; }
.faq-search input { flex: 1; padding: 10px 12px; border-radius: 8px; border: 1px solid #e5e7eb; }

/* Quick links to top FAQs */
.faq-quicklinks { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
.faq-quicklinks a { display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 999px; background: #f8fafc; border: 1px solid #e5e7eb; color: #111827; font-weight: 700; text-decoration: none; }
.faq-quicklinks a:hover { background: #eef2ff; border-color: #c7d2fe; }

.faq-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; margin: 16px 0 24px; }
.faq-card { border: 1px solid #e5e7eb; border-radius: 10px; background: #fff; padding: 16px; }
.faq-card h3 { margin: 0 0 10px; font-size: 1.05rem; color: #111827; }

/* Accessible accordion using details/summary */
.faq-item { border-top: 1px dashed #e5e7eb; padding-top: 10px; margin-top: 10px; }
.faq-item:first-of-type { border-top: 0; padding-top: 0; margin-top: 0; }
.faq-item summary { cursor: pointer; list-style: none; font-weight: 600; color: #0f172a; display: flex; align-items: center; justify-content: space-between; }
.faq-item summary::-webkit-details-marker { display: none; }
.faq-item summary i { color: #6b7280; margin-left: 10px; transition: transform .2s ease; }
.faq-item[open] summary i { transform: rotate(180deg); }
.faq-item .answer { color: #4b5563; font-size: .95rem; margin-top: 8px; }

.note { background: #ecfeff; border: 1px solid #a5f3fc; color: #155e75; padding: 12px 14px; border-radius: 8px; }
.small { font-size: 0.92rem; }

/* Search highlighting */
.faq-highlight { background: #fff59a; padding: 0 2px; border-radius: 2px; }
</style>

<div class="container" style="margin-top: 20px; margin-bottom: 60px;">
    <div class="faq-hero">
        <h1><i class="fas fa-circle-question"></i> Frequently Asked Questions</h1>
        <p>Find quick answers to common questions. This page is informational only and doesn't change your order workflow.</p>
        <div class="faq-badges">
            <span class="badge"><i class="fas fa-truck"></i> Shipping & Delivery</span>
            <span class="badge"><i class="fas fa-undo"></i> 30-Day Returns</span>
            <span class="badge"><i class="fas fa-shield-halved"></i> Secure Payments</span>
            <span class="badge"><i class="fas fa-location-dot"></i> Order Tracking</span>
        </div>
        <div class="faq-actions">
            <a class="btn btn-primary" href="<?php echo SITE_URL; ?>/shipping-info.php"><i class="fas fa-truck"></i> Shipping & Tracking</a>
            <a class="btn btn-secondary" href="<?php echo SITE_URL; ?>/track-order.php"><i class="fas fa-location-arrow"></i> Track Order</a>
            <a class="btn btn-secondary" href="<?php echo SITE_URL; ?>/returns-policy.php"><i class="fas fa-clipboard-check"></i> Returns Policy</a>
            <a class="btn btn-secondary" href="<?php echo SITE_URL; ?>/contact.php"><i class="fas fa-headset"></i> Contact Support</a>
        </div>
        <div class="faq-search">
            <input id="faqSearch" type="text" placeholder="Search FAQs (e.g., shipping time, refunds, size)">
        </div>
        <nav class="faq-quicklinks" aria-label="Top FAQs">
            <a href="#faq-ship-when"><i class="fas fa-truck-fast"></i> Shipping time</a>
            <a href="#faq-return-window"><i class="fas fa-rotate-left"></i> Return window</a>
            <a href="#faq-refund-time"><i class="fas fa-clock"></i> Refund time</a>
            <a href="#faq-track-where"><i class="fas fa-location-dot"></i> Track order</a>
            <a href="#faq-size-how"><i class="fas fa-ruler"></i> Choose size</a>
        </nav>
    </div>

    <div class="faq-grid" id="faqGrid">
        <div class="faq-card" data-section="orders-shipping">
            <h3>Orders & Shipping</h3>
            <details class="faq-item" id="faq-ship-when"><summary>When will my order ship? <i class="fas fa-chevron-down"></i></summary>
                <div class="answer">Most orders ship within 1–2 business days after payment confirmation. You'll receive a shipping confirmation email with tracking once dispatched.</div>
            </details>
            <details class="faq-item"><summary>How much is shipping? <i class="fas fa-chevron-down"></i></summary>
                <div class="answer">Shipping fees vary by destination and cart value. See our <a href="<?php echo SITE_URL; ?>/shipping-info.php">Shipping & Tracking</a> page for current rates and delivery estimates.</div>
            </details>
            <details class="faq-item"><summary>Do you ship internationally? <i class="fas fa-chevron-down"></i></summary>
                <div class="answer">Availability may vary by region. Check the shipping options at checkout; if your country appears, we can ship there.</div>
            </details>
            <details class="faq-item"><summary>Can I change my address after placing an order? <i class="fas fa-chevron-down"></i></summary>
                <div class="answer">If your order hasn’t shipped, contact support as soon as possible via our <a href="<?php echo SITE_URL; ?>/contact.php">Contact</a> page. Address changes aren’t guaranteed once processing starts.</div>
            </details>
        </div>

        <div class="faq-card" data-section="returns-refunds">
            <h3>Returns & Refunds</h3>
            <details class="faq-item" id="faq-return-window"><summary>What is your return window? <i class="fas fa-chevron-down"></i></summary>
                <div class="answer">Returns are allowed within <strong>30 days of payment</strong> for <strong>delivered and paid</strong> orders with a valid reason. See the full <a href="<?php echo SITE_URL; ?>/returns-policy.php">Returns Policy</a>.</div>
            </details>
            <details class="faq-item"><summary>How do I start a return? <i class="fas fa-chevron-down"></i></summary>
                <div class="answer">Go to <a href="<?php echo SITE_URL; ?>/orders.php">My Orders</a>, select the order, and choose <em>Request Return</em>. Our team will review and guide you through the next steps.</div>
            </details>
            <details class="faq-item" id="faq-refund-time"><summary>How long do refunds take? <i class="fas fa-chevron-down"></i></summary>
                <div class="answer">Once your return is received and inspected, refunds are issued via the original payment method. Processing time depends on your payment provider.</div>
            </details>
        </div>

        <div class="faq-card" data-section="payments-security">
            <h3>Payments & Security</h3>
            <details class="faq-item"><summary>What payment methods do you accept? <i class="fas fa-chevron-down"></i></summary>
                <div class="answer">We accept major cards and popular digital payment methods available in your region. Options will appear at checkout.</div>
            </details>
            <details class="faq-item"><summary>Is my payment information secure? <i class="fas fa-chevron-down"></i></summary>
                <div class="answer">Yes. We use industry-standard encryption and do not store sensitive card details on our servers.</div>
            </details>
            <details class="faq-item"><summary>Why was my payment declined? <i class="fas fa-chevron-down"></i></summary>
                <div class="answer">Declines can occur for insufficient funds, verification issues, or bank restrictions. Try another method or contact your bank.</div>
            </details>
        </div>

        <div class="faq-card" data-section="account-privacy">
            <h3>Account & Privacy</h3>
            <details class="faq-item"><summary>Do I need an account to place an order? <i class="fas fa-chevron-down"></i></summary>
                <div class="answer">An account helps you track orders and manage returns more easily. You can create one on our <a href="<?php echo SITE_URL; ?>/register.php">Register</a> page or during checkout.</div>
            </details>
            <details class="faq-item"><summary>How do I update my profile or address? <i class="fas fa-chevron-down"></i></summary>
                <div class="answer">Sign in and visit <a href="<?php echo SITE_URL; ?>/profile.php">My Profile</a> to update your information. Manage addresses under <a href="<?php echo SITE_URL; ?>/addresses.php">My Addresses</a>.</div>
            </details>
            <details class="faq-item"><summary>How is my data used? <i class="fas fa-chevron-down"></i></summary>
                <div class="answer">We only use your information for order processing and support. For more details, contact our support team.</div>
            </details>
        </div>

        <div class="faq-card" data-section="products-sizing">
            <h3>Products & Sizing</h3>
            <details class="faq-item" id="faq-size-how"><summary>How do I choose the right size? <i class="fas fa-chevron-down"></i></summary>
                <div class="answer">Check product descriptions for fit notes. If in doubt, compare your measurements with sizing guidance on the product page.</div>
            </details>
            <details class="faq-item"><summary>Will an item be back in stock? <i class="fas fa-chevron-down"></i></summary>
                <div class="answer">Stock varies. Add the item to your <a href="<?php echo SITE_URL; ?>/wishlist.php">Wishlist</a> to keep an eye on it, or check back later.</div>
            </details>
        </div>

        <div class="faq-card" data-section="tracking-orders">
            <h3>Tracking Orders</h3>
            <details class="faq-item" id="faq-track-where"><summary>Where can I track my order? <i class="fas fa-chevron-down"></i></summary>
                <div class="answer">Use our <a href="<?php echo SITE_URL; ?>/track-order.php">Track Order</a> page. You'll need your order number (and optionally your email).</div>
            </details>
            <details class="faq-item"><summary>My tracking isn’t updating—what should I do? <i class="fas fa-chevron-down"></i></summary>
                <div class="answer">Carriers sometimes delay scans. If there's no update for 48–72 hours, contact our <a href="<?php echo SITE_URL; ?>/contact.php">Support</a> with your order number.</div>
            </details>
        </div>

        <div class="faq-card" data-section="promotions-coupons">
            <h3>Promotions & Coupons</h3>
            <details class="faq-item"><summary>Where do I enter a promo code? <i class="fas fa-chevron-down"></i></summary>
                <div class="answer">Enter valid codes at checkout in the promo field. One code per order; exclusions may apply.</div>
            </details>
            <details class="faq-item"><summary>Why isn’t my code working? <i class="fas fa-chevron-down"></i></summary>
                <div class="answer">Codes may expire or have restrictions. Verify spelling, validity period, and eligible items.</div>
            </details>
        </div>
    </div>

    <div class="note small">
        Still need help? Our team is here for you. Visit <a href="<?php echo SITE_URL; ?>/contact.php">Contact Support</a>.
    </div>
</div>

<script>
// Client-side FAQ filtering + search term highlighting (scoped)
(function(){
    var input = document.getElementById('faqSearch');
    if (!input) return;

    function escapeRegExp(str){ return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }

    function clearHighlights(root){
        root.querySelectorAll('mark.faq-highlight').forEach(function(m){
            var text = document.createTextNode(m.textContent);
            m.parentNode.replaceChild(text, m);
            m.remove();
        });
    }

    function highlight(el, query){
        if (!query) return;
        var rx = new RegExp('(' + escapeRegExp(query) + ')', 'ig');
        // Highlight in summary and answer separately to avoid breaking structure
        el.querySelectorAll('summary, .answer').forEach(function(node){
            // Remove previous highlights inside this node
            clearHighlights(node);
            // Walk text nodes and wrap matches
            var walker = document.createTreeWalker(node, NodeFilter.SHOW_TEXT, null, false);
            var toWrap = [];
            while (walker.nextNode()) {
                var txt = walker.currentNode;
                if (txt.nodeValue && rx.test(txt.nodeValue)) {
                    toWrap.push(txt);
                }
            }
            toWrap.forEach(function(txt){
                var frag = document.createDocumentFragment();
                var parts = txt.nodeValue.split(rx);
                for (var i = 0; i < parts.length; i++) {
                    if (i % 2 === 1) {
                        var mark = document.createElement('mark');
                        mark.className = 'faq-highlight';
                        mark.textContent = parts[i];
                        frag.appendChild(mark);
                    } else if (parts[i]) {
                        frag.appendChild(document.createTextNode(parts[i]));
                    }
                }
                txt.parentNode.replaceChild(frag, txt);
            });
        });
    }

    function openIfLinked(){
        if (location.hash) {
            var el = document.getElementById(location.hash.substring(1));
            if (el && el.tagName.toLowerCase() === 'details') {
                el.open = true;
                var sum = el.querySelector('summary');
                if (sum) sum.focus({preventScroll:true});
                el.scrollIntoView({behavior:'smooth', block:'start'});
            }
        }
    }

    input.addEventListener('input', function(){
        var q = (this.value || '').trim().toLowerCase();
        var grid = document.getElementById('faqGrid');
        if (!grid) return;
        // Clear previous highlights globally
        clearHighlights(grid);
        var cards = grid.querySelectorAll('.faq-card');
        cards.forEach(function(card){
            var any = false;
            card.querySelectorAll('.faq-item').forEach(function(item){
                var text = (item.textContent || '').toLowerCase();
                var match = q.length === 0 || text.indexOf(q) !== -1;
                item.style.display = match ? '' : 'none';
                if (match && q.length > 0) highlight(item, q);
                if (match) any = true;
            });
            card.style.display = any || q.length === 0 ? '' : 'none';
        });
    });

    // Ensure quick links open their target accordions
    document.querySelectorAll('.faq-quicklinks a').forEach(function(a){
        a.addEventListener('click', function(){
            setTimeout(openIfLinked, 0);
        });
    });
    // Also handle manual hash navigation
    window.addEventListener('hashchange', openIfLinked);
    // Initial check
    openIfLinked();
})();
</script>

<?php include 'footer.php'; ?>
