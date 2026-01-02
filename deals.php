<?php
require_once 'config.php';

$page_title = "Deals - " . SITE_NAME;

// Sorting (default: highest discount first)
$sort = $_GET['sort'] ?? 'highest_discount';
$order_by = "p.discount_percentage DESC, p.created_at DESC";
switch ($sort) {
    case 'price_low':
        $order_by = "p.final_price ASC";
        break;
    case 'price_high':
        $order_by = "p.final_price DESC";
        break;
    case 'newest':
        $order_by = "p.created_at DESC";
        break;
}

// Fetch discounted products
$sql = "SELECT p.*, pi.image_url, c.category_name,
        (SELECT AVG(rating) FROM product_reviews WHERE product_id = p.product_id) as avg_rating,
        (SELECT COUNT(*) FROM product_reviews WHERE product_id = p.product_id) as review_count
        FROM products p
        LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE p.is_active = 1 AND p.discount_percentage > 0
        ORDER BY $order_by";

$result = $conn->query($sql);
$products = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

include 'header.php';
?>

<div class="container" style="margin-top: 20px;">
    <!-- Heading -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 15px; background: white; border-radius: 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
        <div>
            <h2 style="margin: 0;">Deals</h2>
            <div style="font-size: 14px; color: #666;">All discounted products</div>
        </div>
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 14px; color: #666;">Sort by:</span>
            <select name="sort" class="form-control" style="width: 200px; padding: 8px;"
                    onchange="window.location.href=updateURLParameter('sort', this.value)">
                <option value="highest_discount" <?php echo $sort == 'highest_discount' ? 'selected' : ''; ?>>Highest Discount</option>
                <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest</option>
                <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
            </select>
        </div>
    </div>

    <?php if (empty($products)): ?>
        <div class="card" style="text-align: center; padding: 60px 20px;">
            <i class="fas fa-tag" style="font-size: 80px; color: #ddd; margin-bottom: 20px;"></i>
            <h3 style="margin-bottom: 10px;">No Deals Right Now</h3>
            <p style="color: #666; margin-bottom: 30px;">Check back later for great discounts!</p>
            <a href="category.php" class="btn btn-primary">Browse All Products</a>
        </div>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($products as $product): ?>
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
                                -<?php echo (int)$product['discount_percentage']; ?>%
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
                                <span>(<?php echo (int)$product['review_count']; ?>)</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function updateURLParameter(param, value) {
    const url = new URL(window.location.href);
    url.searchParams.set(param, value);
    return url.toString();
}
</script>

<?php include 'footer.php'; ?>
<?php
// end of file
?>
