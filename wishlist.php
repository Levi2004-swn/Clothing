<?php
require_once 'config.php';

if (!is_logged_in()) {
    header('Location: ' . SITE_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$page_title = "My Wishlist - " . SITE_NAME;
$user_id = get_user_id();

$wishlist_items = [];
$stmt = $conn->prepare("SELECT w.*, p.product_name, p.slug, p.final_price, p.base_price, p.discount_percentage, 
                        pi.image_url, 
                        (SELECT AVG(rating) FROM product_reviews WHERE product_id = p.product_id) as avg_rating,
                        (SELECT COUNT(*) FROM product_variants WHERE product_id = p.product_id) as variant_count
                        FROM wishlist w
                        JOIN products p ON w.product_id = p.product_id
                        LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                        WHERE w.user_id = ?
                        ORDER BY w.added_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $wishlist_items[] = $row;
}

include 'header.php';
?>

<div class="container" style="margin-top: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h2>My Wishlist</h2>
        <div id="wishlist-count" style="color: #666;">
            <?php echo count($wishlist_items); ?> item<?php echo count($wishlist_items) != 1 ? 's' : ''; ?>
        </div>
    </div>

    <?php if (empty($wishlist_items)): ?>
        <div class="card" style="text-align: center; padding: 60px 20px;">
            <i class="fas fa-heart" style="font-size: 80px; color: #ddd; margin-bottom: 20px;"></i>
            <h3 style="margin-bottom: 10px;">Your Wishlist is Empty</h3>
            <p style="color: #666; margin-bottom: 30px;">Save items you love by clicking the heart icon!</p>
            <a href="category.php" class="btn btn-primary">Start Shopping</a>
        </div>
    <?php else: ?>
        <div class="product-grid" id="wishlist-grid">
            <?php foreach ($wishlist_items as $item): ?>
                <div class="product-card" style="position: relative;" data-product-id="<?php echo $item['product_id']; ?>" data-wishlist-product-id="<?php echo $item['product_id']; ?>" data-has-variants="<?php echo (int)($item['variant_count'] ?? 0) > 0 ? '1' : '0'; ?>">
                    <button class="wishlist-remove-btn" data-product-id="<?php echo $item['product_id']; ?>"
                            style="position: absolute; top: 10px; right: 10px; background: white; border: none; width: 36px; height: 36px; border-radius: 50%; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.1); z-index: 10;">
                        <i class="fas fa-times" style="color: #dc3545; font-size: 16px;"></i>
                    </button>
                    
                    <div onclick="window.location.href='product.php?id=<?php echo $item['product_id']; ?>'" style="cursor: pointer;">
                        <div style="position: relative;">
                            <img src="<?php echo $item['image_url'] ?? 'assets/images/no-image.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                 class="product-image">
                            <?php if ($item['discount_percentage'] > 0): ?>
                                <span style="position: absolute; top: 10px; left: 10px; background: #f53d2d; color: white; padding: 5px 10px; border-radius: 3px; font-size: 12px; font-weight: 600;">
                                    -<?php echo $item['discount_percentage']; ?>%
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <div class="product-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                            <div>
                                <span class="product-price"><?php echo CURRENCY_SYMBOL . number_format($item['final_price'], 2); ?></span>
                                <?php if ($item['discount_percentage'] > 0): ?>
                                    <span class="product-old-price"><?php echo CURRENCY_SYMBOL . number_format($item['base_price'], 2); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($item['avg_rating']): ?>
                                <div class="product-rating">
                                    <?php
                                    $rating = round($item['avg_rating']);
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo $i <= $rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ((int)($item['variant_count'] ?? 0) > 0): ?>
                        <a href="product.php?id=<?php echo $item['product_id']; ?>" 
                           onclick="event.stopPropagation();" 
                           class="btn btn-primary btn-full" style="margin-top: 10px; padding: 10px; text-align:center; display:block;">
                            <i class="fas fa-shopping-cart"></i> Add to cart
                        </a>
                    <?php else: ?>
                        <button onclick="event.stopPropagation(); addToCart(<?php echo $item['product_id']; ?>, null, 1)" 
                                class="btn btn-primary btn-full" style="margin-top: 10px; padding: 10px;">
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>
                    <?php endif; ?>
                    
                    <div style="margin-top: 10px; text-align: center; font-size: 12px; color: #666;">
                        Added <?php echo date('M j, Y', strtotime($item['added_at'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>