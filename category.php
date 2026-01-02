<?php
require_once 'config.php';

$page_title = "Shop - " . SITE_NAME;

// Get filter parameters
$category_slug = $_GET['cat'] ?? '';
$sub = $_GET['sub'] ?? '';
$search = $_GET['search'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$size = $_GET['size'] ?? '';
$color = $_GET['color'] ?? '';
$brand = $_GET['brand'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

// Build query
$where = ["p.is_active = 1"];
$params = [];
$types = "";

// Category handling
// If a Men/Women dropdown sub-filter is present, we do NOT restrict to that category only;
// instead, we search across all categories but exclude the opposite segment and kids, per requirement.
if ($category_slug && empty($sub)) {
    $where[] = "c.slug = ?";
    $params[] = $category_slug;
    $types .= "s";
}

// Segment keyword logic from header dropdowns
if ($sub && in_array(strtolower($category_slug), ['men','women'], true)) {
    $segment = strtolower($category_slug);
    // Exclude categories by segment
    if ($segment === 'men') {
        $where[] = "(c.slug NOT IN ('women','kids'))";
    } else {
        $where[] = "(c.slug NOT IN ('men','kids'))";
    }
    // Keyword derived from submenu label: prefer singular base when possible
    $kwRaw = strtolower(trim($sub));
    $map = [
        'shirts' => 'shirt',
        'tops' => 'top',
        'dresses' => 'dress',
        'pants' => 'pant',
        'shoes' => 'shoe'
    ];
    $kwSingular = $map[$kwRaw] ?? rtrim($kwRaw, 's');
    // Match either plural or singular token in product fields and category meta
    $kwPlural = "%" . $kwRaw . "%";
    $kwSingle = "%" . $kwSingular . "%";
    $where[] = "((p.product_name LIKE ? OR p.brand LIKE ? OR p.description LIKE ? OR c.category_name LIKE ? OR c.slug LIKE ?) 
                 OR (p.product_name LIKE ? OR p.brand LIKE ? OR p.description LIKE ? OR c.category_name LIKE ? OR c.slug LIKE ?))";
    array_push($params, $kwPlural, $kwPlural, $kwPlural, $kwPlural, $kwPlural);
    array_push($params, $kwSingle, $kwSingle, $kwSingle, $kwSingle, $kwSingle);
    $types .= str_repeat('s', 10);
}

if ($search) {
    // Match across product name, brand, description, and category (name or slug)
    $where[] = "(p.product_name LIKE ? OR p.brand LIKE ? OR p.description LIKE ? OR c.category_name LIKE ? OR c.slug LIKE ?)";
    $search_term = "%" . $search . "%";
    $params[] = $search_term; // product_name
    $params[] = $search_term; // brand
    $params[] = $search_term; // description
    $params[] = $search_term; // category_name
    $params[] = $search_term; // category slug
    $types .= "sssss";
}

if ($min_price) {
    $where[] = "p.final_price >= ?";
    $params[] = $min_price;
    $types .= "d";
}

if ($max_price) {
    $where[] = "p.final_price <= ?";
    $params[] = $max_price;
    $types .= "d";
}

if ($brand) {
    $where[] = "p.brand = ?";
    $params[] = $brand;
    $types .= "s";
}

// Variant-based filtering (size/color) must ensure BOTH match on the same variant when both provided.
// We use EXISTS subquery referencing product_variants; if only one provided we filter on that attribute alone.
if ($size || $color) {
    $variantConds = ["pv.product_id = p.product_id"];
    if ($size) {
        $variantConds[] = "pv.size = ?";
        $params[] = $size;
        $types .= "s";
    }
    if ($color) {
        $variantConds[] = "pv.color = ?";
        $params[] = $color;
        $types .= "s";
    }
    $where[] = "EXISTS (SELECT 1 FROM product_variants pv WHERE " . implode(' AND ', $variantConds) . ")";
}

// Sorting
$order_by = "p.created_at DESC";
switch ($sort) {
    case 'price_low':
        $order_by = "p.final_price ASC";
        break;
    case 'price_high':
        $order_by = "p.final_price DESC";
        break;
    case 'rating':
        $order_by = "avg_rating DESC";
        break;
    case 'popular':
        $order_by = "p.views DESC";
        break;
}

$where_clause = implode(" AND ", $where);

$sql = "SELECT p.*, pi.image_url, c.category_name,
        (SELECT AVG(rating) FROM product_reviews WHERE product_id = p.product_id) as avg_rating,
        (SELECT COUNT(*) FROM product_reviews WHERE product_id = p.product_id) as review_count
        FROM products p
        LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE $where_clause
        ORDER BY $order_by";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

// Get available brands
$brands = [];
$result = $conn->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != '' ORDER BY brand");
while ($row = $result->fetch_assoc()) {
    $brands[] = $row['brand'];
}

// Get available sizes (exclude blank/null, only from active products)
$sizes = [];
$sizesSql = "SELECT DISTINCT pv.size
             FROM product_variants pv
             INNER JOIN products p2 ON pv.product_id = p2.product_id AND p2.is_active = 1
             WHERE pv.size IS NOT NULL AND pv.size <> ''
             ORDER BY pv.size";
$result = $conn->query($sizesSql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $sizes[] = $row['size'];
    }
}

// Custom ordering: standard apparel sizes first, then numeric ascending, then alpha fallback
if (!empty($sizes)) {
    $orderMap = [
        'XXS' => 1,
        'XS'  => 2,
        'S'   => 3,
        'M'   => 4,
        'L'   => 5,
        'XL'  => 6,
        'XXL' => 7,
        'XXXL'=> 8,
        '4XL' => 9,
        '5XL' => 10,
    ];
    usort($sizes, function($a, $b) use ($orderMap) {
        $aStr = trim((string)$a);
        $bStr = trim((string)$b);
        $aUp = strtoupper($aStr);
        $bUp = strtoupper($bStr);

        $aKey = $orderMap[$aUp] ?? null;
        $bKey = $orderMap[$bUp] ?? null;
        if ($aKey !== null || $bKey !== null) {
            // If one or both are in map, compare by map (missing treated as after mapped)
            $aKey = $aKey ?? PHP_INT_MAX;
            $bKey = $bKey ?? PHP_INT_MAX;
            if ($aKey !== $bKey) return $aKey <=> $bKey;
        }
        // Numeric detection (e.g., jeans or shoe sizes)
        $aNum = is_numeric($aStr) ? (float)$aStr : null;
        $bNum = is_numeric($bStr) ? (float)$bStr : null;
        if ($aNum !== null && $bNum !== null) return $aNum <=> $bNum;
        if ($aNum !== null) return -1; // numeric before pure alpha (after mapped)
        if ($bNum !== null) return 1;
        // Fallback alphabetical
        return strnatcasecmp($aStr, $bStr);
    });
    // Ensure uniqueness in case different cases existed
    $sizes = array_values(array_unique($sizes));
}

// Get available colors (exclude blank/null, only from active products)
$colors = [];
$colorsSql = "SELECT DISTINCT pv.color
              FROM product_variants pv
              INNER JOIN products p3 ON pv.product_id = p3.product_id AND p3.is_active = 1
              WHERE pv.color IS NOT NULL AND pv.color <> ''
              ORDER BY pv.color";
$result = $conn->query($colorsSql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $colors[] = $row['color'];
    }
}

include 'header.php';
?>

<div class="container" style="margin-top: 20px;">
    <!-- Breadcrumb -->
    <div style="margin-bottom: 20px; font-size: 14px; color: #666;">
        <a href="index.php" style="color: #666;">Home</a>
        <span> / </span>
        <span style="color: #333;">Shop</span>
        <?php if ($category_slug): ?>
            <span> / </span>
            <span style="color: #333;"><?php echo htmlspecialchars($category_slug); ?></span>
        <?php endif; ?>
    </div>

    <div style="display: grid; grid-template-columns: 250px 1fr; gap: 30px;">
        <!-- Filters Sidebar -->
        <div>
            <div class="card">
                <h3 style="margin-bottom: 20px; font-size: 18px; font-weight: 600;">Filters</h3>
                
                <form method="GET" action="" id="filter-form">
                    <!-- Preserve category -->
                    <?php if ($category_slug): ?>
                        <input type="hidden" name="cat" value="<?php echo htmlspecialchars($category_slug); ?>">
                    <?php endif; ?>
                    
                    <!-- Price Range -->
                    <div style="margin-bottom: 25px;">
                        <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 15px;">Price Range</h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <input type="number" name="min_price" placeholder="Min" class="form-control" 
                                   value="<?php echo htmlspecialchars($min_price); ?>" style="padding: 8px;">
                            <input type="number" name="max_price" placeholder="Max" class="form-control" 
                                   value="<?php echo htmlspecialchars($max_price); ?>" style="padding: 8px;">
                        </div>
                    </div>

                    <!-- Brand -->
                    <?php if (!empty($brands)): ?>
                        <div style="margin-bottom: 25px;">
                            <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 15px;">Brand</h4>
                            <select name="brand" class="form-control" style="padding: 8px;">
                                <option value="">All Brands</option>
                                <?php foreach ($brands as $b): ?>
                                    <option value="<?php echo htmlspecialchars($b); ?>" 
                                            <?php echo $brand == $b ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($b); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <!-- Size -->
                    <?php if (!empty($sizes)): ?>
                        <div style="margin-bottom: 25px;">
                            <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 15px;">Size</h4>
                            <select name="size" class="form-control" style="padding: 8px;">
                                <option value="">All Sizes</option>
                                <?php foreach ($sizes as $s): ?>
                                    <option value="<?php echo htmlspecialchars($s); ?>" <?php echo $size == $s ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($s); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <!-- Color -->
                    <?php if (!empty($colors)): ?>
                        <div style="margin-bottom: 25px;">
                            <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 15px;">Color</h4>
                            <select name="color" class="form-control" style="padding: 8px;">
                                <option value="">All Colors</option>
                                <?php foreach ($colors as $c): ?>
                                    <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $color == $c ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary btn-full" style="margin-bottom: 10px;">
                        Apply Filters
                    </button>
                    <a href="category.php<?php echo $category_slug ? '?cat=' . urlencode($category_slug) : ''; ?>" 
                       class="btn btn-secondary btn-full">
                        Clear Filters
                    </a>
                </form>
            </div>
        </div>

        <!-- Products -->
        <div>
            <!-- Toolbar -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 15px; background: white; border-radius: 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                <div style="font-size: 14px; color: #666;">
                    Showing <strong><?php echo count($products); ?></strong> products
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 14px; color: #666;">Sort by:</span>
                    <select name="sort" class="form-control" style="width: 200px; padding: 8px;" 
                            onchange="window.location.href=updateURLParameter('sort', this.value)">
                        <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest</option>
                        <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="rating" <?php echo $sort == 'rating' ? 'selected' : ''; ?>>Top Rated</option>
                        <option value="popular" <?php echo $sort == 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                    </select>
                </div>
            </div>

            <!-- Product Grid -->
            <?php if (empty($products)): ?>
                <div class="card" style="text-align: center; padding: 60px 20px;">
                    <i class="fas fa-shopping-bag" style="font-size: 80px; color: #ddd; margin-bottom: 20px;"></i>
                    <h3 style="margin-bottom: 10px;">No Products Found</h3>
                    <p style="color: #666; margin-bottom: 30px;">Try adjusting your filters or search criteria</p>
                    <a href="category.php" class="btn btn-primary">View All Products</a>
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
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function updateURLParameter(param, value) {
    const url = new URL(window.location.href);
    url.searchParams.set(param, value);
    return url.toString();
}
</script>

<?php include 'footer.php'; ?>