<?php
require_once 'config.php';

$page_title = SITE_NAME . " - Best Clothing Online Store";

// Get featured products
$featured_products = [];
$result = $conn->query("SELECT p.*, pi.image_url, 
                        (SELECT AVG(rating) FROM product_reviews WHERE product_id = p.product_id) as avg_rating,
                        (SELECT COUNT(*) FROM product_reviews WHERE product_id = p.product_id) as review_count
                        FROM products p 
                        LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                        WHERE p.is_featured = 1 AND p.is_active = 1
                        ORDER BY p.created_at DESC LIMIT 8");
while ($row = $result->fetch_assoc()) {
    $featured_products[] = $row;
}

// Get trending products
$trending_products = [];
$result = $conn->query("SELECT p.*, pi.image_url, 
                        (SELECT AVG(rating) FROM product_reviews WHERE product_id = p.product_id) as avg_rating
                        FROM products p 
                        LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                        WHERE p.is_trending = 1 AND p.is_active = 1
                        ORDER BY p.created_at DESC LIMIT 8");
while ($row = $result->fetch_assoc()) {
    $trending_products[] = $row;
}

// Get categories
$categories = [];
$result = $conn->query("SELECT * FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY category_name");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

// Get distinct brands (only active products) and map to available brand images
$brand_cards = [];
$brand_result = $conn->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand <> '' AND is_active = 1 ORDER BY brand ASC LIMIT 12");
if ($brand_result) {
    while ($b = $brand_result->fetch_assoc()) {
        $brandName = trim($b['brand']);
        if ($brandName === '') { continue; }
        // Slugify brand to match image filenames like 'nike.png', 'new-balance.png'
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $brandName));
        $slug = trim($slug, '-');

        // Try common image extensions in priority
        $candidates = [
            __DIR__ . "/brands/{$slug}.png" => "brands/{$slug}.png",
            __DIR__ . "/brands/{$slug}.jpg" => "brands/{$slug}.jpg",
            __DIR__ . "/brands/{$slug}.jpeg" => "brands/{$slug}.jpeg",
            __DIR__ . "/brands/{$slug}.webp" => "brands/{$slug}.webp",
        ];

        foreach ($candidates as $fsPath => $webPath) {
            if (file_exists($fsPath)) {
                $brand_cards[] = [
                    'name' => $brandName,
                    'image' => $webPath,
                    'slug'  => $slug,
                ];
                break;
            }
        }
        // Skip brands without an image to keep UI consistent
    }
}

include 'header.php';
?>

<!-- Deals Banner CTA (under hero) -->
<div style="padding: 0px 0 8px; max-width: 1200px; margin: 0 auto;">
    <div class="container">
        <a href="deals.php" 
           style="display:block; position:relative; text-decoration:none; color:inherit;">
            <!-- Background: on-brand gradient with subtle pattern overlay -->
            <div class="deals-cta" style="
                background: linear-gradient(135deg, #f53d2d 0%, #ff6b6b 100%);
                min-height: 160px;
                display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center;
                padding: 32px 28px; box-shadow: 0 8px 24px rgba(245,61,45,0.25);
            ">
                <!-- Subtle pattern overlay -->
                <div aria-hidden="true" style="position:absolute; inset:0; opacity:.08; background:
                    radial-gradient(circle at 20% 30%, #ffffff 0 2px, transparent 2px) 0 0/24px 24px,
                    radial-gradient(circle at 80% 70%, #ffffff 0 2px, transparent 2px) 0 0/28px 28px;
                "></div>

                <div class="deals-cta-content" style="position:relative; z-index:1;">
                    <h2 style="margin:0 0 4px; color:#fff; font-size:32px; line-height:1.2; font-weight:800; margin-top: -10px;">
                        Hot Deals • Up to 70% Off
                    </h2>
                    <p style="margin:0; color:#fff; opacity:.95; font-size:15px;">
                        Flash discounts across all categories. Limited-time offers—don’t miss out.
                    </p>
                </div>

                <div class="deals-cta-chips" style="position:relative; z-index:1; display:flex; align-items:center; justify-content:center; gap:12px; flex-wrap:wrap; margin-top:14px;">
                    <span style="background:#fff; color:#f53d2d; font-weight:800; padding:12px 16px; border-radius:10px; display:inline-flex; align-items:center; gap:8px; box-shadow: 0 6px 16px rgba(255,255,255,0.25);">
                        <i class="fas fa-tag"></i> Extra Savings
                    </span>
                    <span style="background:rgba(255,255,255,0.16); color:#fff; font-weight:700; padding:12px 16px; border-radius:10px; display:inline-flex; align-items:center; gap:8px;">
                        <i class="fas fa-clock"></i> Limited Time
                    </span>
                    <!-- Countdown chip (progressive enhancement) -->
                    <span id="deals-countdown-chip" style="background:rgba(255,255,255,0.16); color:#fff; font-weight:700; padding:12px 16px; border-radius:10px; display:inline-flex; align-items:center; gap:8px;">
                        <i class="fas fa-hourglass-half"></i>
                        <span id="deals-countdown" aria-live="polite">Ends soon</span>
                    </span>
                    <span style="background:rgba(255,255,255,0.16); color:#fff; font-weight:700; padding:12px 16px; border-radius:10px; display:inline-flex; align-items:center; gap:8px;">
                        <i class="fas fa-arrow-right"></i> Shop Deals
                    </span>
                </div>
            </div>
        </a>
    </div>
    
    <!-- Scoped responsive tweaks for the deals CTA -->
    <style>
        /* Make banner background extend full width like hero */
        .deals-cta { 
            margin-left: calc(50% - 50vw); 
            margin-right: calc(50% - 50vw); 
            border-radius: 0 !important;
        }
        @media (max-width: 768px) {
            .deals-cta h2 { font-size: 24px !important; }
            .deals-cta { padding: 20px !important; }
            .deals-cta-chips { flex-wrap: wrap; justify-content: center; }
        }
        @media (max-width: 480px) {
            .deals-cta { min-height: 140px !important; padding: 16px !important; }
            .deals-cta h2 { font-size: 18px !important; }
            .deals-cta p { font-size: 13px !important; }
            .deals-cta-chips span { padding: 10px 12px !important; font-size: 12px !important; }
        }
    </style>
    
    <!-- small spacing to next section -->
    <div style="height: 10px;"></div>
</div>

<!-- Hero Banner -->
<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 60px 0; color: white; margin-bottom: 30px;">
    <div class="container">
        <div style="max-width: 600px;">
            <h1 style="font-size: 48px; margin-bottom: 20px; font-weight: 700;">
                New Season<br>New Collection
            </h1>
            <p style="font-size: 18px; margin-bottom: 30px; opacity: 0.9;">
                Discover the latest trends in fashion. Shop now and get up to 50% off on selected items!
            </p>
            <a href="category.php" class="btn btn-primary" style="background: white; color: #667eea; font-size: 16px; padding: 15px 40px;">
                Shop Now <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</div>

<div class="container">
    <!-- Categories -->
    <div style="margin-bottom: 40px;">
        <h2 style="margin-bottom: 20px;">Shop by Category</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <?php foreach ($categories as $category): ?>
                <a href="category.php?cat=<?php echo urlencode($category['slug']); ?>" 
                   style="background: white; border-radius: 8px; padding: 30px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: transform 0.3s;"
                   onmouseover="this.style.transform='translateY(-5px)'"
                   onmouseout="this.style.transform='translateY(0)'">
                    <i class="fas fa-tshirt" style="font-size: 48px; color: #f53d2d; margin-bottom: 15px;"></i>
                    <h3 style="font-size: 18px; font-weight: 600; color: #333;">
                        <?php echo htmlspecialchars($category['category_name']); ?>
                    </h3>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Featured Products -->
    <?php if (!empty($featured_products)): ?>
        <div style="margin-bottom: 40px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Featured Products</h2>
                <a href="category.php?featured=1" style="color: #f53d2d; font-weight: 600;">
                    View All <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <div class="product-grid">
                <?php foreach ($featured_products as $product): ?>
                    <?php
                        $in_wishlist = false;
                        if (is_logged_in()) {
                            $in_wishlist = is_in_wishlist($conn, get_user_id(), (int)$product['product_id']);
                        }
                    ?>
                    <div class="product-card" onclick="window.location.href='product.php?id=<?php echo $product['product_id']; ?>'">
                        <div style="position: relative;">
                            <img src="<?php echo $product['image_url'] ?? 'assets/images/no-image.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                                 class="product-image">
                            <?php if ($product['discount_percentage'] > 0): ?>
                                <span style="position: absolute; top: 10px; left: 10px; background: #f53d2d; color: white; padding: 5px 10px; border-radius: 3px; font-size: 12px; font-weight: 600;">
                                    -<?php echo $product['discount_percentage']; ?>%
                                </span>
                            <?php endif; ?>
                            <div style="position: absolute; top: 10px; right: 10px;">
                                <button onclick="event.stopPropagation(); addToWishlist(<?php echo (int)$product['product_id']; ?>, this);" 
                                        class="wishlist-toggle-btn<?php echo $in_wishlist ? ' active btn-primary' : ''; ?>"
                                        data-product-id="<?php echo (int)$product['product_id']; ?>"
                                        title="Add to wishlist" 
                                        style="background: white; border: none; width: 36px; height: 36px; border-radius: 50%; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: inline-flex; align-items: center; justify-content: center;">
                                    <i class="<?php echo $in_wishlist ? 'fas' : 'far'; ?> fa-heart" style="color:<?php echo $in_wishlist ? '#e74c3c' : '#bbb'; ?>; font-size:16px;"></i>
                                </button>
                            </div>
                        </div>
                        <div class="product-info">
                            <div class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></div>
                            <div>
                                <span class="product-price"><?php echo CURRENCY_SYMBOL . number_format($product['final_price'], 2); ?></span>
                                <?php if ($product['discount_percentage'] > 0): ?>
                                    <span class="product-old-price"><?php echo CURRENCY_SYMBOL . number_format($product['base_price'], 2); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($product['avg_rating']): ?>
                                <div class="product-rating">
                                    <?php
                                    $rating = round($product['avg_rating']);
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo $i <= $rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                    }
                                    ?>
                                    <span>(<?php echo $product['review_count']; ?>)</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($brand_cards)): ?>
        <!-- Featured Brands (brand banners under Featured Products) -->
        <div style="margin-bottom: 40px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h2>Featured Brands</h2>
                <a href="category.php" style="color: #f53d2d; font-weight: 600;">All Products <i class="fas fa-arrow-right"></i></a>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 16px;">
                <?php foreach ($brand_cards as $brand): ?>
                    <a href="category.php?brand=<?php echo urlencode($brand['name']); ?>"
                       title="<?php echo htmlspecialchars($brand['name']); ?>"
                       style="display: block; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.06); transition: transform .2s, box-shadow .2s;"
                       onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 6px 18px rgba(0,0,0,0.10)';"
                       onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.06)';">
                        <div style="width: 100%; aspect-ratio: 3/2; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                            <img src="<?php echo htmlspecialchars($brand['image']); ?>"
                                 alt="<?php echo htmlspecialchars($brand['name']); ?>"
                                 style="max-width: 100%; max-height: 100%; object-fit: contain;">
                        </div>
                        <div style="margin-top: 10px; font-weight: 600; color: #111827; font-size: 14px;">
                            <?php echo htmlspecialchars($brand['name']); ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Newsletter Subscription -->
    <div style="background: linear-gradient(135deg, #f53d2d, #ff6b6b); padding: 50px; border-radius: 8px; text-align: center; color: white; margin-bottom: 40px;">
        <h2 style="margin-bottom: 15px;">Subscribe to Our Newsletter</h2>
        <p style="margin-bottom: 25px; opacity: 0.9;">Get the latest updates on new products and upcoming sales</p>
        <div style="max-width: 500px; margin: 0 auto; display: flex; gap: 10px;">
            <input type="email" id="newsletter-email" placeholder="Enter your email" 
                   style="flex: 1; padding: 15px; border: none; border-radius: 4px; font-size: 14px;">
            <button onclick="subscribeNewsletter()" class="btn" 
                    style="background: white; color: #f53d2d; padding: 15px 30px; font-weight: 600;">
                Subscribe
            </button>
        </div>
    </div>
</div>

<!-- Countdown script for deals banner -->
<script>
// Lightweight countdown to next Sunday 23:59:59 (local time)
(function(){
    try {
        var chip = document.getElementById('deals-countdown-chip');
        var el = document.getElementById('deals-countdown');
        if (!chip || !el) return;

        function pad(n){ return n < 10 ? '0'+n : ''+n; }

        function nextSunday2359(from){
            var d = new Date(from.getTime());
            var day = d.getDay(); // 0=Sun
            var diff = (7 - day) % 7; // days to next Sunday
            // If already Sunday but past target time, move to the next Sunday
            var target = new Date(d.getTime());
            target.setDate(d.getDate() + diff);
            target.setHours(23,59,59,999);
            if (target <= d) {
                target.setDate(target.getDate() + 7);
            }
            return target;
        }

        var start = new Date();
        var end = nextSunday2359(start);
        console.log('[deals-countdown] start', { now: start.toISOString(), ends: end.toISOString() });

        function tick(){
            var now = new Date();
            var ms = end - now;
            if (ms <= 0) {
                el.textContent = 'Sale ended';
                chip.style.background = 'rgba(255,255,255,0.20)';
                console.log('[deals-countdown] ended');
                clearInterval(t);
                return;
            }
            var sec = Math.floor(ms/1000);
            var days = Math.floor(sec / 86400); sec %= 86400;
            var hrs = Math.floor(sec / 3600); sec %= 3600;
            var mins = Math.floor(sec / 60); sec %= 60;
            if (days > 0) {
                el.textContent = days + 'd ' + pad(hrs) + ':' + pad(mins) + ':' + pad(sec);
            } else {
                el.textContent = pad(hrs) + ':' + pad(mins) + ':' + pad(sec);
            }
        }
        tick();
        var t = setInterval(tick, 1000);
    } catch(e) {
        // Fail silently – keep banner static
    }
})();
</script>

<?php include 'footer.php'; ?>