<?php
require_once 'config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$product_id = intval($_GET['id']);

// Get product details
$stmt = $conn->prepare("SELECT p.*, c.category_name, c.slug as category_slug,
                        (SELECT AVG(rating) FROM product_reviews WHERE product_id = p.product_id) as avg_rating,
                        (SELECT COUNT(*) FROM product_reviews WHERE product_id = p.product_id) as review_count
                        FROM products p 
                        LEFT JOIN categories c ON p.category_id = c.category_id
                        WHERE p.product_id = ? AND p.is_active = 1");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$page_title = $product['product_name'] . " - " . SITE_NAME;

// Get product images
$images = [];
$result = $conn->query("SELECT * FROM product_images WHERE product_id = $product_id ORDER BY is_primary DESC, display_order ASC");
while ($row = $result->fetch_assoc()) {
    $images[] = $row;
}

// Get product variants
$variants = [];
$result = $conn->query("SELECT * FROM product_variants WHERE product_id = $product_id");
while ($row = $result->fetch_assoc()) {
    $variants[] = $row;
}

$sizes = array_unique(array_column($variants, 'size'));
$colors = array_unique(array_column($variants, 'color'));
// Free Size detection: product has variants but none of them have a non-blank size
$__hasNonBlank = false;
foreach ($sizes as $__s) { if (trim((string)$__s) !== '') { $__hasNonBlank = true; break; } }
$is_free_size_product = (!$__hasNonBlank && count($variants) > 0);

// Get product reviews
$reviews = [];
$result = $conn->query("SELECT pr.*, u.first_name, u.last_name 
                        FROM product_reviews pr 
                        JOIN users u ON pr.user_id = u.user_id 
                        WHERE pr.product_id = $product_id 
                        ORDER BY pr.created_at DESC LIMIT 10");
while ($row = $result->fetch_assoc()) {
    $reviews[] = $row;
}

// Get related products
$related_products = [];
$stmt = $conn->prepare("SELECT p.*, pi.image_url 
                        FROM products p 
                        LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                        WHERE p.category_id = ? AND p.product_id != ? AND p.is_active = 1
                        ORDER BY RAND() LIMIT 4");
$stmt->bind_param("ii", $product['category_id'], $product_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $related_products[] = $row;
}

// Check if in wishlist
$in_wishlist = false;
if (is_logged_in()) {
    $user_id = get_user_id();
    $stmt = $conn->prepare("SELECT wishlist_id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $in_wishlist = $stmt->get_result()->num_rows > 0;
}

include 'header.php';
?>

<!-- Size Finder Modal -->
<div id="size-finder-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 30px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h3 style="font-size: 24px; font-weight: 700; color: #333;">Find Your Perfect Size</h3>
            <button onclick="closeSizeFinder()" style="background: none; border: none; font-size: 28px; cursor: pointer; color: #999;">&times;</button>
        </div>
        
        <!-- Dynamic subtitle for category -->
        <div style="margin-bottom:10px; color:#666; font-size:12px;">
            Category: <strong><?php echo htmlspecialchars($product['category_name'] ?? ''); ?></strong>
        </div>

        <!-- UNISEX (default) -->
        <form id="size-form-unisex" onsubmit="calculateSize(event)" style="display:none;">
            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 10px; font-weight: 600;">Measurement Unit:</label>
                <div style="display: flex; gap: 15px;">
                    <label style="cursor: pointer; flex: 1;">
                        <input type="radio" name="unit-unisex" value="metric" checked onchange="toggleUnits()" style="margin-right: 8px;">
                        <span style="font-weight: 600;">Metric (cm/kg)</span>
                    </label>
                    <label style="cursor: pointer; flex: 1;">
                        <input type="radio" name="unit-unisex" value="imperial" onchange="toggleUnits()" style="margin-right: 8px;">
                        <span style="font-weight: 600;">Imperial (in/lbs)</span>
                    </label>
                </div>
            </div>
            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Height <span id="height-unit">(cm)</span>:</label>
                <input type="number" id="height-input" name="height" class="form-control" step="0.1" required placeholder="e.g., 170" style="padding: 12px; border: 2px solid #ddd; border-radius: 6px; width: 100%;">
                <small style="color: #666; font-size: 12px; display: block; margin-top: 5px;">Enter your height in <span id="height-example">centimeters (e.g., 170)</span></small>
            </div>
            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Weight <span id="weight-unit">(kg)</span>:</label>
                <input type="number" id="weight-input" name="weight" class="form-control" step="0.1" required placeholder="e.g., 70" style="padding: 12px; border: 2px solid #ddd; border-radius: 6px; width: 100%;">
                <small style="color: #666; font-size: 12px; display: block; margin-top: 5px;">Enter your weight in <span id="weight-example">kilograms (e.g., 70)</span></small>
            </div>
            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Body Type:</label>
                <select name="body_type" class="form-control" style="padding: 12px; border: 2px solid #ddd; border-radius: 6px; width: 100%; cursor: pointer;">
                    <option value="slim">Slim/Lean Build</option>
                    <option value="average" selected>Average Build</option>
                    <option value="athletic">Athletic/Muscular</option>
                    <option value="large">Large/Plus Size</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Preferred Fit:</label>
                <select name="fit_preference" class="form-control" style="padding: 12px; border: 2px solid #ddd; border-radius: 6px; width: 100%; cursor: pointer;">
                    <option value="tight">Tight Fit</option>
                    <option value="regular" selected>Regular Fit</option>
                    <option value="loose">Loose/Relaxed Fit</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 25px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Gender:</label>
                <select name="gender" class="form-control" style="padding: 12px; border: 2px solid #ddd; border-radius: 6px; width: 100%; cursor: pointer;">
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="unisex">Unisex</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 16px; font-weight: 600;"><i class="fas fa-search"></i> Find My Size</button>
        </form>

        <!-- KIDS -->
        <form id="size-form-kids" onsubmit="calculateSize(event)" style="display:none;">
            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 10px; font-weight: 600;">Measurement Unit:</label>
                <div style="display: flex; gap: 15px;">
                    <label style="cursor: pointer; flex: 1;"><input type="radio" name="unit-kids" value="metric" checked onchange="toggleUnits()" style="margin-right: 8px;"><span style="font-weight: 600;">Metric (cm)</span></label>
                    <label style="cursor: pointer; flex: 1;"><input type="radio" name="unit-kids" value="imperial" onchange="toggleUnits()" style="margin-right: 8px;"><span style="font-weight: 600;">Imperial (in)</span></label>
                </div>
            </div>
            <div class="form-group" style="margin-bottom: 12px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Height <span id="height-unit-kids">(cm)</span>:</label>
                <input type="number" id="height-input-kids" name="height" class="form-control" step="0.1" placeholder="e.g., 120" style="padding: 12px; border: 2px solid #ddd; border-radius: 6px; width: 100%;">
            </div>
            <div class="form-group" style="margin-bottom: 12px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Chest <span id="chest-unit-kids">(cm)</span>:</label>
                <input type="number" id="chest-input-kids" name="chest" class="form-control" step="0.1" required placeholder="e.g., 64" style="padding: 12px; border: 2px solid #ddd; border-radius: 6px; width: 100%;">
            </div>
            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Age:</label>
                <input type="number" id="age-input-kids" name="age" class="form-control" min="1" max="16" placeholder="e.g., 8" style="padding: 12px; border: 2px solid #ddd; border-radius: 6px; width: 100%;">
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 16px; font-weight: 600;"><i class="fas fa-search"></i> Find My Size</button>
        </form>

        <!-- TOPS -->
        <form id="size-form-tops" onsubmit="calculateSize(event)" style="display:none;">
            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 10px; font-weight: 600;">Measurement Unit:</label>
                <div style="display: flex; gap: 15px;">
                    <label style="cursor: pointer; flex: 1;"><input type="radio" name="unit-tops" value="metric" checked onchange="toggleUnits()" style="margin-right: 8px;"><span style="font-weight: 600;">Metric (cm)</span></label>
                    <label style="cursor: pointer; flex: 1;"><input type="radio" name="unit-tops" value="imperial" onchange="toggleUnits()" style="margin-right: 8px;"><span style="font-weight: 600;">Imperial (in)</span></label>
                </div>
            </div>
            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Chest <span id="chest-unit-tops">(cm)</span>:</label>
                <input type="number" id="chest-input-tops" name="chest" class="form-control" step="0.1" required placeholder="e.g., 96" style="padding: 12px; border: 2px solid #ddd; border-radius: 6px; width: 100%;">
            </div>
            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Preferred Fit:</label>
                <select name="fit_preference_tops" class="form-control" style="padding: 12px; border: 2px solid #ddd; border-radius: 6px; width: 100%; cursor: pointer;">
                    <option value="tight">Slim/Tight</option>
                    <option value="regular" selected>Regular</option>
                    <option value="loose">Relaxed</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 16px; font-weight: 600;"><i class="fas fa-search"></i> Find My Size</button>
        </form>

        <!-- BOTTOMS -->
        <form id="size-form-bottoms" onsubmit="calculateSize(event)" style="display:none;">
            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 10px; font-weight: 600;">Measurement Unit:</label>
                <div style="display: flex; gap: 15px;">
                    <label style="cursor: pointer; flex: 1;"><input type="radio" name="unit-bottoms" value="metric" checked onchange="toggleUnits()" style="margin-right: 8px;"><span style="font-weight: 600;">Metric (cm)</span></label>
                    <label style="cursor: pointer; flex: 1;"><input type="radio" name="unit-bottoms" value="imperial" onchange="toggleUnits()" style="margin-right: 8px;"><span style="font-weight: 600;">Imperial (in)</span></label>
                </div>
            </div>
            <div class="form-group" style="margin-bottom: 12px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Waist <span id="waist-unit-bottoms">(cm)</span>:</label>
                <input type="number" id="waist-input-bottoms" name="waist" class="form-control" step="0.1" required placeholder="e.g., 82" style="padding: 12px; border: 2px solid #ddd; border-radius: 6px; width: 100%;">
            </div>
            <div class="form-group" style="margin-bottom: 12px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Hips <span id="hips-unit-bottoms">(cm)</span>:</label>
                <input type="number" id="hips-input-bottoms" name="hips" class="form-control" step="0.1" placeholder="e.g., 98" style="padding: 12px; border: 2px solid #ddd; border-radius: 6px; width: 100%;">
            </div>
            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Inseam <span id="inseam-unit-bottoms">(cm)</span>:</label>
                <input type="number" id="inseam-input-bottoms" name="inseam" class="form-control" step="0.1" placeholder="e.g., 79" style="padding: 12px; border: 2px solid #ddd; border-radius: 6px; width: 100%;">
                <small style="color: #666; font-size: 12px; display: block; margin-top: 5px;">Measure from crotch to ankle.</small>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 16px; font-weight: 600;"><i class="fas fa-search"></i> Find My Size</button>
        </form>

        <!-- DRESSES -->
        <form id="size-form-dresses" onsubmit="calculateSize(event)" style="display:none;">
            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 10px; font-weight: 600;">Measurement Unit:</label>
                <div style="display: flex; gap: 15px;">
                    <label style="cursor: pointer; flex: 1;"><input type="radio" name="unit-dresses" value="metric" checked onchange="toggleUnits()" style="margin-right: 8px;"><span style="font-weight: 600;">Metric (cm)</span></label>
                    <label style="cursor: pointer; flex: 1;"><input type="radio" name="unit-dresses" value="imperial" onchange="toggleUnits()" style="margin-right: 8px;"><span style="font-weight: 600;">Imperial (in)</span></label>
                </div>
            </div>
            <div class="form-group" style="margin-bottom: 12px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Bust <span id="bust-unit-dresses">(cm)</span>:</label>
                <input type="number" id="bust-input-dresses" name="bust" class="form-control" step="0.1" required placeholder="e.g., 90" style="padding: 12px; border: 2px solid #ddd; border-radius: 6px; width: 100%;">
            </div>
            <div class="form-group" style="margin-bottom: 12px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Waist <span id="waist-unit-dresses">(cm)</span>:</label>
                <input type="number" id="waist-input-dresses" name="waist" class="form-control" step="0.1" required placeholder="e.g., 70" style="padding: 12px; border: 2px solid #ddd; border-radius: 6px; width: 100%;">
            </div>
            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Hips <span id="hips-unit-dresses">(cm)</span>:</label>
                <input type="number" id="hips-input-dresses" name="hips" class="form-control" step="0.1" required placeholder="e.g., 96" style="padding: 12px; border: 2px solid #ddd; border-radius: 6px; width: 100%;">
            </div>
            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Preferred Fit:</label>
                <select name="fit_preference_dresses" class="form-control" style="padding: 12px; border: 2px solid #ddd; border-radius: 6px; width: 100%; cursor: pointer;">
                    <option value="tight">Slim</option>
                    <option value="regular" selected>Regular</option>
                    <option value="loose">Relaxed</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 16px; font-weight: 600;"><i class="fas fa-search"></i> Find My Size</button>
        </form>

        <!-- SHOES -->
        <form id="size-form-shoes" onsubmit="calculateSize(event)" style="display:none;">
            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 10px; font-weight: 600;">Measurement Unit:</label>
                <div style="display: flex; gap: 15px;">
                    <label style="cursor: pointer; flex: 1;"><input type="radio" name="unit-shoes" value="metric" checked onchange="toggleUnits()" style="margin-right: 8px;"><span style="font-weight: 600;">Metric (cm)</span></label>
                    <label style="cursor: pointer; flex: 1;"><input type="radio" name="unit-shoes" value="imperial" onchange="toggleUnits()" style="margin-right: 8px;"><span style="font-weight: 600;">Imperial (in)</span></label>
                </div>
            </div>
            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Foot Length <span id="foot-unit-shoes">(cm)</span>:</label>
                <input type="number" id="foot-input-shoes" name="foot_length" class="form-control" step="0.1" required placeholder="e.g., 26.0" style="padding: 12px; border: 2px solid #ddd; border-radius: 6px; width: 100%;">
                <small style="color: #666; font-size: 12px; display: block; margin-top: 5px;">Stand on paper, mark heel to longest toe, measure the distance.</small>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 16px; font-weight: 600;"><i class="fas fa-search"></i> Find My Size</button>
        </form>

        <!-- Recommendation output -->
        <div id="size-recommendation" style="display:none; margin-top: 18px; padding: 14px; border-radius: 8px; background:#ecfdf5; border:1px solid #a7f3d0;">
            <div style="display:flex; align-items:center; gap:10px; color:#065f46;">
                <i class="fas fa-check-circle"></i>
                <div>
                    Recommended size: <strong id="recommended-size-value">M</strong>
                </div>
            </div>
            <div style="margin-top:10px; display:flex; gap:10px;">
                <button type="button" class="btn btn-success" onclick="applyRecommendedSize()" style="padding:10px 14px; font-weight:600;">
                    Select This Size
                </button>
                <button type="button" class="btn" onclick="closeSizeFinder()" style="padding:10px 14px; border:1px solid #ddd;">Close</button>
            </div>
        </div>

        <!-- Size Chart Reference: multiple variants shown per category -->
        <div id="chart-unisex" style="display:none; margin-top: 25px; padding: 15px; background: #f8f8f8; border-radius: 8px;">
            <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 10px; color: #333;"><i class="fas fa-info-circle"></i> Size Chart Reference</h4>
            <table style="width: 100%; font-size: 12px; border-collapse: collapse;">
                <thead><tr style="background: #e0e0e0;"><th style="padding:8px; text-align:left;">Size</th><th style="padding:8px; text-align:center;">Chest (cm)</th><th style="padding:8px; text-align:center;">Waist (cm)</th></tr></thead>
                <tbody>
                    <tr><td style="padding:6px; border-bottom:1px solid #ddd;">XS</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">81-86</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">66-71</td></tr>
                    <tr><td style="padding:6px; border-bottom:1px solid #ddd;">S</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">86-91</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">71-76</td></tr>
                    <tr><td style="padding:6px; border-bottom:1px solid #ddd;">M</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">91-97</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">76-81</td></tr>
                    <tr><td style="padding:6px; border-bottom:1px solid #ddd;">L</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">97-102</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">81-86</td></tr>
                    <tr><td style="padding:6px; border-bottom:1px solid #ddd;">XL</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">102-107</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">86-91</td></tr>
                    <tr><td style="padding:6px;">XXL</td><td style="padding:6px; text-align:center;">107-112</td><td style="padding:6px; text-align:center;">91-97</td></tr>
                </tbody>
            </table>
        </div>

        <div id="chart-tops" style="display:none; margin-top: 25px; padding: 15px; background: #f8f8f8; border-radius: 8px;">
            <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 10px; color: #333;"><i class="fas fa-info-circle"></i> Tops Size Chart</h4>
            <table style="width: 100%; font-size: 12px; border-collapse: collapse;">
                <thead><tr style="background:#e0e0e0;"><th style="padding:8px; text-align:left;">Size</th><th style="padding:8px; text-align:center;">Chest (cm)</th></tr></thead>
                <tbody>
                    <tr><td style="padding:6px; border-bottom:1px solid #ddd;">XS</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">81-86</td></tr>
                    <tr><td style="padding:6px; border-bottom:1px solid #ddd;">S</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">86-91</td></tr>
                    <tr><td style="padding:6px; border-bottom:1px solid #ddd;">M</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">91-97</td></tr>
                    <tr><td style="padding:6px; border-bottom:1px solid #ddd;">L</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">97-102</td></tr>
                    <tr><td style="padding:6px; border-bottom:1px solid #ddd;">XL</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">102-107</td></tr>
                    <tr><td style="padding:6px;">XXL</td><td style="padding:6px; text-align:center;">107-112</td></tr>
                </tbody>
            </table>
        </div>

        <div id="chart-bottoms" style="display:none; margin-top: 25px; padding: 15px; background: #f8f8f8; border-radius: 8px;">
            <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 10px; color: #333;"><i class="fas fa-info-circle"></i> Bottoms Size Chart</h4>
            <table style="width: 100%; font-size: 12px; border-collapse: collapse;">
                <thead><tr style="background:#e0e0e0;"><th style="padding:8px; text-align:left;">Size</th><th style="padding:8px; text-align:center;">Waist (cm)</th><th class="waist-inch-col" style="padding:8px; text-align:center; display:none;">Waist (in)</th><th style="padding:8px; text-align:center;">Inseam (cm)</th></tr></thead>
                <tbody>
                    <tr>
                        <td style="padding:6px; border-bottom:1px solid #ddd;">S</td>
                        <td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">71-76</td>
                        <td class="waist-inch-col" style="padding:6px; text-align:center; border-bottom:1px solid #ddd; display:none;">28-30</td>
                        <td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">76-78</td>
                    </tr>
                    <tr>
                        <td style="padding:6px; border-bottom:1px solid #ddd;">M</td>
                        <td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">76-81</td>
                        <td class="waist-inch-col" style="padding:6px; text-align:center; border-bottom:1px solid #ddd; display:none;">30-32</td>
                        <td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">79-81</td>
                    </tr>
                    <tr>
                        <td style="padding:6px; border-bottom:1px solid #ddd;">L</td>
                        <td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">81-86</td>
                        <td class="waist-inch-col" style="padding:6px; text-align:center; border-bottom:1px solid #ddd; display:none;">32-34</td>
                        <td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">81-83</td>
                    </tr>
                    <tr>
                        <td style="padding:6px; border-bottom:1px solid #ddd;">XL</td>
                        <td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">86-91</td>
                        <td class="waist-inch-col" style="padding:6px; text-align:center; border-bottom:1px solid #ddd; display:none;">34-36</td>
                        <td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">83-86</td>
                    </tr>
                    <tr>
                        <td style="padding:6px;">XXL</td>
                        <td style="padding:6px; text-align:center;">91-97</td>
                        <td class="waist-inch-col" style="padding:6px; text-align:center; display:none;">36-38</td>
                        <td style="padding:6px; text-align:center;">86-89</td>
                    </tr>
                </tbody>
            </table>
            <p id="waist-inch-note" style="display:none; font-size:11px; margin-top:8px; color:#555;">Waist (in) ranges are approximate (1 in = 2.54 cm). Numeric recommendation uses nearest available waist size.</p>
        </div>

    <div id="chart-dresses" style="display:none; margin-top: 25px; padding: 15px; background: #f8f8f8; border-radius: 8px;">
            <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 10px; color: #333;"><i class="fas fa-info-circle"></i> Dresses Size Chart</h4>
            <table style="width: 100%; font-size: 12px; border-collapse: collapse;">
                <thead><tr style="background:#e0e0e0;"><th style="padding:8px; text-align:left;">Size</th><th style="padding:8px; text-align:center;">Bust (cm)</th><th style="padding:8px; text-align:center;">Waist (cm)</th><th style="padding:8px; text-align:center;">Hips (cm)</th></tr></thead>
                <tbody>
                    <tr><td style="padding:6px; border-bottom:1px solid #ddd;">XS</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">78-82</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">60-64</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">84-88</td></tr>
                    <tr><td style="padding:6px; border-bottom:1px solid #ddd;">S</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">82-86</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">64-68</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">88-92</td></tr>
                    <tr><td style="padding:6px; border-bottom:1px solid #ddd;">M</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">86-91</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">68-74</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">92-98</td></tr>
                    <tr><td style="padding:6px; border-bottom:1px solid #ddd;">L</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">91-96</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">74-80</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">98-104</td></tr>
                    <tr><td style="padding:6px;">XL</td><td style="padding:6px; text-align:center;">96-102</td><td style="padding:6px; text-align:center;">80-86</td><td style="padding:6px; text-align:center;">104-110</td></tr>
                </tbody>
            </table>
        </div>

        <div id="chart-kids" style="display:none; margin-top: 25px; padding: 15px; background: #f8f8f8; border-radius: 8px;">
            <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 10px; color: #333;"><i class="fas fa-info-circle"></i> Kids Size Chart (Guide)</h4>
            <table style="width: 100%; font-size: 12px; border-collapse: collapse;">
                <thead><tr style="background:#e0e0e0;"><th style="padding:8px; text-align:left;">Kids Size</th><th style="padding:8px; text-align:center;">Chest (cm)</th><th style="padding:8px; text-align:center;">Approx Height (cm)</th></tr></thead>
                <tbody>
                    <tr><td style="padding:6px; border-bottom:1px solid #ddd;">XS</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">54–60</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">100–116</td></tr>
                    <tr><td style="padding:6px; border-bottom:1px solid #ddd;">S</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">60–66</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">116–128</td></tr>
                    <tr><td style="padding:6px; border-bottom:1px solid #ddd;">M</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">66–72</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">128–140</td></tr>
                    <tr><td style="padding:6px; border-bottom:1px solid #ddd;">L</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">72–78</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">140–152</td></tr>
                    <tr><td style="padding:6px;">XL</td><td style="padding:6px; text-align:center;">78–84</td><td style="padding:6px; text-align:center;">152–164</td></tr>
                </tbody>
            </table>
        </div>

        <div id="chart-shoes" style="display:none; margin-top: 25px; padding: 15px; background: #f8f8f8; border-radius: 8px;">
            <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 10px; color: #333;"><i class="fas fa-info-circle"></i> Shoes Size Chart</h4>
            <table style="width: 100%; font-size: 12px; border-collapse: collapse;">
                <thead>
                    <tr style="background:#e0e0e0;">
                        <th style="padding:8px; text-align:left;">EU</th>
                        <th style="padding:8px; text-align:center;">UK</th>
                        <th style="padding:8px; text-align:center;">US</th>
                        <th style="padding:8px; text-align:center;">Foot Length (cm)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td style="padding:6px; border-bottom:1px solid #ddd;">37</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">4</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">5</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">23.1–23.6</td></tr>
                    <tr><td style="padding:6px; border-bottom:1px solid #ddd;">38</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">5</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">6</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">23.7–24.4</td></tr>
                    <tr><td style="padding:6px; border-bottom:1px solid #ddd;">39</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">6</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">7</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">24.5–25.0</td></tr>
                    <tr><td style="padding:6px; border-bottom:1px solid #ddd;">40</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">6.5</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">7.5</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">25.1–25.6</td></tr>
                    <tr><td style="padding:6px; border-bottom:1px solid #ddd;">41</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">7.5</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">8.5</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">25.7–26.2</td></tr>
                    <tr><td style="padding:6px; border-bottom:1px solid #ddd;">42</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">8</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">9</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">26.3–26.8</td></tr>
                    <tr><td style="padding:6px; border-bottom:1px solid #ddd;">43</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">9</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">10</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">26.9–27.4</td></tr>
                    <tr><td style="padding:6px; border-bottom:1px solid #ddd;">44</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">9.5</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">10.5</td><td style="padding:6px; text-align:center; border-bottom:1px solid #ddd;">27.5–28.0</td></tr>
                    <tr><td style="padding:6px;">45</td><td style="padding:6px; text-align:center;">10.5</td><td style="padding:6px; text-align:center;">11.5</td><td style="padding:6px; text-align:center;">28.1–28.6</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="container" style="margin-top: 20px;">
    <!-- Breadcrumb -->
    <div style="margin-bottom: 20px; font-size: 14px; color: #666;">
        <a href="index.php" style="color: #666;">Home</a>
        <span> / </span>
        <a href="category.php?cat=<?php echo urlencode($product['category_slug']); ?>" style="color: #666;">
            <?php echo htmlspecialchars($product['category_name']); ?>
        </a>
        <span> / </span>
        <span style="color: #333;"><?php echo htmlspecialchars($product['product_name']); ?></span>
    </div>

    <!-- Product Details -->
    <div class="card">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
            <!-- Image Gallery -->
            <div>
                <div style="margin-bottom: 15px;">
                    <a id="main-image-link" href="<?php echo $images[0]['image_url'] ?? 'assets/images/no-image.jpg'; ?>" target="_blank" rel="noopener" title="Open full size in a new tab">
                        <img id="main-product-image" 
                             src="<?php echo $images[0]['image_url'] ?? 'assets/images/no-image.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                             style="width: 100%; height: 500px; object-fit: cover; border-radius: 8px; cursor: zoom-in;">
                    </a>
                    <div style="margin-top: 6px; font-size: 12px; color:#6b7280;">
                        <i class="fas fa-up-right-from-square"></i> Click the image to view full size
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px;">
                    <?php foreach ($images as $image): ?>
                        <img src="<?php echo $image['image_url']; ?>" 
                             alt="Product image"
                             onclick="setMainImage(this.src)"
                             style="width: 100%; height: 100px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 2px solid transparent; transition: border 0.3s;"
                             onmouseover="this.style.borderColor='#f53d2d'"
                             onmouseout="this.style.borderColor='transparent'">
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Product Info -->
            <div>
                <h1 style="font-size: 28px; margin-bottom: 15px; font-weight: 600;">
                    <?php echo htmlspecialchars($product['product_name']); ?>
                </h1>

                <!-- Rating -->
                <?php if ($product['avg_rating']): ?>
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #e5e5e5;">
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <span style="color: #f53d2d; font-size: 16px; font-weight: 600;">
                                <?php echo number_format($product['avg_rating'], 1); ?>
                            </span>
                            <div>
                                <?php
                                $rating = round($product['avg_rating']);
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $rating ? '<i class="fas fa-star" style="color: #ffc107;"></i>' : '<i class="far fa-star" style="color: #ffc107;"></i>';
                                }
                                ?>
                            </div>
                        </div>
                        <div style="color: #666; border-left: 1px solid #e5e5e5; padding-left: 15px;">
                            <?php echo $product['review_count']; ?> Reviews
                        </div>
                        <div style="color: #666; border-left: 1px solid #e5e5e5; padding-left: 15px;">
                            <?php
                            $total_stock = array_sum(array_column($variants, 'stock_quantity'));
                            echo $total_stock > 0 ? "$total_stock In Stock" : "Out of Stock";
                            ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Price -->
                <div style="margin-bottom: 25px;">
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                        <span style="font-size: 32px; color: #f53d2d; font-weight: 700;">
                            <?php echo CURRENCY_SYMBOL . number_format($product['final_price'], 2); ?>
                        </span>
                        <?php if ($product['discount_percentage'] > 0): ?>
                            <span style="font-size: 20px; color: #999; text-decoration: line-through;">
                                <?php echo CURRENCY_SYMBOL . number_format($product['base_price'], 2); ?>
                            </span>
                            <span style="background: #f53d2d; color: white; padding: 5px 12px; border-radius: 3px; font-size: 14px; font-weight: 600;">
                                -<?php echo $product['discount_percentage']; ?>% OFF
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php if ($product['brand']): ?>
                        <div style="color: #666; font-size: 14px;">
                            Brand: <strong><?php echo htmlspecialchars($product['brand']); ?></strong>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Size Selection -->
                <?php if ($is_free_size_product): ?>
                    <div style="margin-bottom: 25px;">
                        <label style="font-weight: 600; color: #333; display:block; margin-bottom: 10px;">Size:</label>
                        <div style="display:inline-block; padding: 10px 16px; border: 2px dashed #d1d5db; background:#f9fafb; border-radius: 6px; font-weight: 700; color:#111827;">Free Size</div>
                    </div>
                <?php elseif (!empty($sizes)): ?>
                    <div style="margin-bottom: 25px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <label style="font-weight: 600; color: #333;">Select Size:</label>
                            <button type="button" onclick="openSizeFinder()" class="btn-secondary size-finder-trigger" style="padding: 6px 15px; font-size: 13px; display: flex; align-items: center; gap: 5px;">
                                <i class="fas fa-ruler"></i> Find My Size
                            </button>
                        </div>
                        <div style="display: flex; flex-wrap: wrap; gap: 10px;" id="size-selector">
                            <?php foreach ($sizes as $size): if (trim((string)$size) === '') continue; ?>
                                <label style="cursor: pointer;">
                                    <input type="radio" name="size" value="<?php echo htmlspecialchars($size); ?>" style="display: none;" required>
                                    <span style="display: block; padding: 10px 20px; border: 2px solid #ddd; border-radius: 4px; transition: all 0.3s; min-width: 60px; text-align: center; font-weight: 600;">
                                        <?php echo htmlspecialchars($size); ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <div id="size-recommendation" style="display: none; margin-top: 10px; padding: 12px; background: #e8f5e9; border-radius: 4px; color: #2e7d32;">
                            <i class="fas fa-check-circle"></i> <strong>Recommended:</strong> <span id="recommended-size"></span>
                            <button onclick="applySizeRecommendation()" style="margin-left: 10px; background: #2e7d32; color: white; border: none; padding: 4px 12px; border-radius: 3px; cursor: pointer; font-size: 12px; font-weight: 600;">
                                Select This Size
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Color Selection -->
                <?php if (!empty($colors)): ?>
                    <div style="margin-bottom: 25px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 10px; color: #333;">Select Color:</label>
                        <div style="display: flex; flex-wrap: wrap; gap: 10px;" id="color-selector">
                            <?php foreach ($colors as $color): ?>
                                <label style="cursor: pointer;">
                                    <input type="radio" name="color" value="<?php echo htmlspecialchars($color); ?>" style="display: none;" required>
                                    <span style="display: block; padding: 10px 20px; border: 2px solid #ddd; border-radius: 4px; transition: all 0.3s; font-weight: 600;">
                                        <?php echo htmlspecialchars($color); ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Quantity -->
                <div style="margin-bottom: 25px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 10px; color: #333;">Quantity:</label>
                    <div class="quantity-selector" style="display: flex; align-items: center; gap: 10px; width: fit-content; border: 2px solid #ddd; border-radius: 4px; padding: 5px;">
                        <button class="qty-minus" type="button" style="background: none; border: none; padding: 10px 15px; cursor: pointer; font-size: 18px; font-weight: 600;">-</button>
                        <input type="number" class="qty-input" value="1" min="1" max="999" style="width: 60px; text-align: center; border: none; font-size: 16px; font-weight: 600;">
                        <button class="qty-plus" type="button" style="background: none; border: none; padding: 10px 15px; cursor: pointer; font-size: 18px; font-weight: 600;">+</button>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div style="display: flex; gap: 15px; margin-bottom: 25px;">
                    <button onclick="addProductToCart()" class="btn btn-primary" style="flex: 1; padding: 15px; font-size: 16px; font-weight: 600;">
                        <i class="fas fa-shopping-cart"></i> Add to Cart
                    </button>
                    <button onclick="toggleWishlist(<?php echo $product_id; ?>)" 
                            class="btn <?php echo $in_wishlist ? 'btn-primary' : 'btn-secondary'; ?>" 
                            id="wishlist-btn"
                            style="padding: 15px 25px;">
                        <i class="<?php echo $in_wishlist ? 'fas' : 'far'; ?> fa-heart"></i>
                    </button>
                </div>

                <!-- Product Description -->
                <div style="padding-top: 25px; border-top: 1px solid #e5e5e5;">
                    <h3 style="font-size: 18px; margin-bottom: 15px; font-weight: 600;">Product Description</h3>
                    <p style="color: #666; line-height: 1.8;">
                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Reviews -->
    <div class="card" style="margin-top: 30px;">
        <h3 style="font-size: 22px; margin-bottom: 25px; font-weight: 600;">Customer Reviews</h3>

        <?php if (is_logged_in()): ?>
            <div style="background: #f8f8f8; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                <h4 style="margin-bottom: 15px;">Write a Review</h4>
                <form id="review-form" onsubmit="submitReview(event)">
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Rating:</label>
                        <div id="star-rating" style="font-size: 28px; cursor: pointer;">
                            <i class="far fa-star" data-rating="1"></i>
                            <i class="far fa-star" data-rating="2"></i>
                            <i class="far fa-star" data-rating="3"></i>
                            <i class="far fa-star" data-rating="4"></i>
                            <i class="far fa-star" data-rating="5"></i>
                        </div>
                        <input type="hidden" name="rating" id="rating-value" required>
                    </div>
                    <div class="form-group">
                        <label>Review Title</label>
                        <input type="text" name="review_title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Your Review</label>
                        <textarea name="review_text" class="form-control" rows="4" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Review</button>
                </form>
            </div>
        <?php else: ?>
            <div class="alert alert-info" style="margin-bottom: 20px;">
                <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" style="color: #f53d2d; font-weight: 600;">Login</a> to write a review
            </div>
        <?php endif; ?>

        <?php if (empty($reviews)): ?>
            <p style="text-align: center; color: #666; padding: 40px;">No reviews yet. Be the first to review this product!</p>
        <?php else: ?>
            <div style="display: grid; gap: 20px;">
                <?php foreach ($reviews as $review): ?>
                    <div style="border-bottom: 1px solid #e5e5e5; padding-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <div>
                                <strong style="font-size: 16px;">
                                    <?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?>
                                </strong>
                                <?php if ($review['is_verified_purchase']): ?>
                                    <span style="color: #4caf50; margin-left: 10px; font-size: 13px;">
                                        <i class="fas fa-check-circle"></i> Verified Purchase
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div style="color: #666; font-size: 14px;">
                                <?php echo date('F j, Y', strtotime($review['created_at'])); ?>
                            </div>
                        </div>
                        <div style="color: #ffc107; margin-bottom: 10px;">
                            <?php
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $review['rating'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                            }
                            ?>
                        </div>
                        <?php if ($review['review_title']): ?>
                            <h4 style="font-size: 16px; margin-bottom: 8px; font-weight: 600;">
                                <?php echo htmlspecialchars($review['review_title']); ?>
                            </h4>
                        <?php endif; ?>
                        <p style="color: #666; line-height: 1.6;">
                            <?php echo nl2br(htmlspecialchars($review['review_text'])); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Related Products -->
    <?php if (!empty($related_products)): ?>
        <div style="margin-top: 40px;">
            <h3 style="font-size: 22px; margin-bottom: 25px; font-weight: 600;">Related Products</h3>
            <div class="product-grid">
                <?php foreach ($related_products as $rel_product): ?>
                    <div class="product-card" onclick="window.location.href='product.php?id=<?php echo $rel_product['product_id']; ?>'">
                        <img src="<?php echo $rel_product['image_url'] ?? 'assets/images/no-image.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($rel_product['product_name']); ?>" 
                             class="product-image">
                        <div class="product-info">
                            <div class="product-name"><?php echo htmlspecialchars($rel_product['product_name']); ?></div>
                            <div class="product-price"><?php echo CURRENCY_SYMBOL . number_format($rel_product['final_price'], 2); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
const variants = <?php echo json_encode($variants); ?>;
let recommendedSizeValue = null;

// Lightweight notification helper (non-invasive, only if global not already defined)
if (typeof window.showNotification !== 'function') {
    window.showNotification = function(message, type = 'info') {
        try {
            let container = document.getElementById('toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'toast-container';
                container.style.position = 'fixed';
                container.style.top = '15px';
                container.style.right = '15px';
                container.style.zIndex = '20000';
                container.style.display = 'flex';
                container.style.flexDirection = 'column';
                container.style.gap = '10px';
                document.body.appendChild(container);
            }
            const toast = document.createElement('div');
            toast.textContent = message;
            toast.style.padding = '10px 14px';
            toast.style.borderRadius = '6px';
            toast.style.fontSize = '13px';
            toast.style.fontWeight = '600';
            toast.style.boxShadow = '0 4px 10px rgba(0,0,0,0.12)';
            toast.style.background = type === 'success' ? '#16a34a' : (type === 'error' ? '#dc2626' : '#4b5563');
            toast.style.color = '#fff';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-6px)';
            toast.style.transition = 'all .3s ease';
            container.appendChild(toast);
            requestAnimationFrame(()=>{
                toast.style.opacity = '1';
                toast.style.transform = 'translateY(0)';
            });
            setTimeout(()=>{
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-4px)';
                setTimeout(()=> toast.remove(), 300);
            }, 3200);
        } catch(e) { console.warn('Notification error', e); }
    }
}

// Update main image and ensure link opens the full-size image
function setMainImage(url) {
    var img = document.getElementById('main-product-image');
    var link = document.getElementById('main-image-link');
    if (img) img.src = url;
    if (link) link.href = url;
}

// ============================================
// SIZE FINDER FUNCTIONS
// ============================================
function openSizeFinder() {
    document.getElementById('size-finder-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeSizeFinder() {
    document.getElementById('size-finder-modal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

function toggleUnits() {
    const isMetric = document.querySelector('input[name="unit"]:checked').value === 'metric';
    
    document.getElementById('height-unit').textContent = isMetric ? '(cm)' : '(inches)';
    document.getElementById('weight-unit').textContent = isMetric ? '(kg)' : '(lbs)';
    document.getElementById('height-example').textContent = isMetric ? 'centimeters (e.g., 170)' : 'inches (e.g., 67)';
    document.getElementById('weight-example').textContent = isMetric ? 'kilograms (e.g., 70)' : 'pounds (e.g., 154)';
    
    document.getElementById('height-input').placeholder = isMetric ? 'e.g., 170' : 'e.g., 67';
    document.getElementById('weight-input').placeholder = isMetric ? 'e.g., 70' : 'e.g., 154';
}

function calculateSize(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    let height = parseFloat(formData.get('height'));
    let weight = parseFloat(formData.get('weight'));
    const unit = formData.get('unit');
    const bodyType = formData.get('body_type');
    const fitPreference = formData.get('fit_preference');
    const gender = formData.get('gender');
    
    // Convert to metric if needed
    if (unit === 'imperial') {
        height = height * 2.54; // inches to cm
        weight = weight * 0.453592; // lbs to kg
    }
    
    // Calculate BMI
    const heightInMeters = height / 100;
    const bmi = weight / (heightInMeters * heightInMeters);
    
    // Determine base size from BMI and height
    let recommendedSize = 'M'; // default
    
    // Base size determination
    if (gender === 'female') {
        if (bmi < 18.5) {
            recommendedSize = height < 160 ? 'XS' : 'S';
        } else if (bmi < 22) {
            recommendedSize = height < 160 ? 'S' : 'M';
        } else if (bmi < 25) {
            recommendedSize = height < 160 ? 'M' : 'L';
        } else if (bmi < 28) {
            recommendedSize = 'L';
        } else if (bmi < 32) {
            recommendedSize = 'XL';
        } else {
            recommendedSize = 'XXL';
        }
    } else { // male or unisex
        if (bmi < 18.5) {
            recommendedSize = height < 170 ? 'S' : 'M';
        } else if (bmi < 23) {
            recommendedSize = height < 170 ? 'M' : 'L';
        } else if (bmi < 26) {
            recommendedSize = height < 170 ? 'L' : 'XL';
        } else if (bmi < 30) {
            recommendedSize = 'XL';
        } else {
            recommendedSize = 'XXL';
        }
    }
    
    // Adjust based on body type
    const sizeOrder = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
    let currentIndex = sizeOrder.indexOf(recommendedSize);
    
    if (bodyType === 'slim') {
        currentIndex = Math.max(0, currentIndex - 1);
    } else if (bodyType === 'athletic') {
        currentIndex = Math.min(sizeOrder.length - 1, currentIndex + 0); // Keep same or adjust
    } else if (bodyType === 'large') {
        currentIndex = Math.min(sizeOrder.length - 1, currentIndex + 1);
    }
    
    // Adjust based on fit preference
    if (fitPreference === 'tight') {
        currentIndex = Math.max(0, currentIndex - 1);
    } else if (fitPreference === 'loose') {
        currentIndex = Math.min(sizeOrder.length - 1, currentIndex + 1);
    }
    
    recommendedSize = sizeOrder[currentIndex];
    
    // Store recommended size
    recommendedSizeValue = recommendedSize;
    
    // Display recommendation
    const recDiv = document.getElementById('size-recommendation');
    const recSize = document.getElementById('recommended-size');
    recSize.textContent = recommendedSize;
    recDiv.style.display = 'block';
    
    // Close modal
    closeSizeFinder();
    
    // Scroll to size selection
    document.getElementById('size-selector').scrollIntoView({ behavior: 'smooth', block: 'center' });
    
    showNotification(`Based on your measurements, we recommend size ${recommendedSize}!`, 'success');
}

function applySizeRecommendation() {
    if (!recommendedSizeValue) return;
    
    // Find and select the recommended size
    const sizeInputs = document.querySelectorAll('input[name="size"]');
    sizeInputs.forEach(input => {
        if (input.value === recommendedSizeValue) {
            input.checked = true;
            input.dispatchEvent(new Event('change'));
        }
    });
    
    showNotification(`Size ${recommendedSizeValue} selected!`, 'success');
}

// Close modal when clicking outside
document.getElementById('size-finder-modal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeSizeFinder();
    }
});

// Size and color selection
document.querySelectorAll('#size-selector input, #color-selector input').forEach(input => {
    input.addEventListener('change', function() {
        this.parentElement.parentElement.querySelectorAll('span').forEach(span => {
            span.style.borderColor = '#ddd';
            span.style.background = 'white';
            span.style.color = '#333';
        });
        this.nextElementSibling.style.borderColor = '#f53d2d';
        this.nextElementSibling.style.background = '#f53d2d';
        this.nextElementSibling.style.color = 'white';
    });
});

// Quantity buttons
document.querySelector('.qty-minus').addEventListener('click', function() {
    const input = document.querySelector('.qty-input');
    if (input.value > 1) input.value = parseInt(input.value) - 1;
});

document.querySelector('.qty-plus').addEventListener('click', function() {
    const input = document.querySelector('.qty-input');
    input.value = parseInt(input.value) + 1;
});

// Star rating
document.querySelectorAll('#star-rating i').forEach(star => {
    star.addEventListener('click', function() {
        const rating = this.dataset.rating;
        document.getElementById('rating-value').value = rating;
        document.querySelectorAll('#star-rating i').forEach((s, index) => {
            if (index < rating) {
                s.className = 'fas fa-star';
                s.style.color = '#ffc107';
            } else {
                s.className = 'far fa-star';
                s.style.color = '#ccc';
            }
        });
    });
});

function addProductToCart() {
    const quantity = document.querySelector('.qty-input').value;
    const size = document.querySelector('input[name="size"]:checked')?.value;
    const color = document.querySelector('input[name="color"]:checked')?.value;
    
    // For free-size products (all variant sizes blank) skip the size requirement.
    const isFreeSizeProduct = <?php echo $is_free_size_product ? 'true':'false'; ?>;
    <?php if (!empty($sizes)): ?>
    if (!isFreeSizeProduct && !size) {
        showNotification('Please select a size', 'error');
        return;
    }
    <?php endif; ?>
    
    <?php if (!empty($colors)): ?>
    if (!color) {
        showNotification('Please select a color', 'error');
        return;
    }
    <?php endif; ?>
    
    let variantId = null;
    if (Array.isArray(variants)) {
        for (let variant of variants) {
            // Match by size/color if they exist; for free-size, size may be empty
            const variantSize = (variant.size || '').trim();
            const variantColor = (variant.color || '').trim();
            const desiredSize = (size || '').trim();
            const desiredColor = (color || '').trim();
            const sizeMatches = isFreeSizeProduct ? true : (variantSize === desiredSize);
            const colorMatches = (!desiredColor && !variantColor) || (variantColor === desiredColor);
            if (sizeMatches && colorMatches) {
                variantId = variant.variant_id;
                break;
            }
        }
        // Free-size single-variant fallback
        if (isFreeSizeProduct && !variantId && variants.length === 1) {
            variantId = variants[0].variant_id;
        }
    }
    
    addToCart(<?php echo $product_id; ?>, variantId, quantity);
}

function submitReview(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('product_id', <?php echo $product_id; ?>);
    
    fetch('ajax/submit-review.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Review submitted successfully!', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message || 'Failed to submit review', 'error');
        }
    });
}

function toggleWishlist(productId) {
    const btn = document.getElementById('wishlist-btn');
    if (!btn) return;
    const icon = btn.querySelector('i');
    if (!icon) return;

    const isActive = icon.classList.contains('fas');
    if (isActive) {
        // Only update UI after successful removal
        removeFromWishlist(productId).then(function(data){
            if (data && data.success) {
                icon.classList.remove('fas');
                icon.classList.add('far');
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-secondary');
            }
            // If auth_required or failure, UI remains unchanged
        });
    } else {
        // Only update UI after successful add
        addToWishlist(productId, btn).then(function(data){
            if (data && data.success) {
                icon.classList.remove('far');
                icon.classList.add('fas');
                btn.classList.remove('btn-secondary');
                btn.classList.add('btn-primary');
            }
            // If auth_required or failure, UI remains unchanged
        });
    }
}
</script>

<?php include 'footer.php'; ?>
<script>
// =============================
// Category-aware Size Finder
// =============================
(function(){
    const categoryName = (<?php echo json_encode($product['category_name'] ?? ''); ?> || '').toLowerCase();
    const catSlug = (<?php echo json_encode($product['category_slug'] ?? ''); ?> || '').toLowerCase();
    const catTokens = [categoryName, catSlug];
    const isFreeSizeProduct = <?php echo $is_free_size_product ? 'true' : 'false'; ?>;

    // Map categories to form/chart groups
    // Order matters (first match used)
    const CATEGORY_MAP = [
        { match: ['kid','child','children','boy','girl','toddler','infant'], form: 'kids', charts: ['kids'] },
        { match: ['dress','gown'], form: 'dresses', charts: ['dresses'] },
        { match: ['pant','trouser','jean','bottom','short'], form: 'bottoms', charts: ['bottoms'] },
        { match: ['shoe','sneaker','boot'], form: 'shoes', charts: ['shoes'] },
        { match: ['top','t-shirt','shirt','hoodie','sweat','blouse','tee'], form: 'tops', charts: ['tops'] },
        // Fallback for outerwear / sportswear / general apparel
        { match: ['outer','jacket','coat','sport','active','athletic','gym','unisex','apparel'], form: 'unisex', charts: ['unisex'] }
    ];

    function detectCategory(){
        for (const def of CATEGORY_MAP){
            if (def.match.some(m => catTokens.some(ct => ct.includes(m)))){
                return def;
            }
        }
        return { form: 'unisex', charts: ['unisex'] }; // default
    }

    const chosen = detectCategory();

    // Detect if this bottoms product uses numeric waist sizing (e.g., jeans: 28, 30, 32 ...)
    function detectNumericWaistProduct(){
        if (chosen.form !== 'bottoms') return false;
        const sizeInputs = Array.from(document.querySelectorAll('input[name="size"]'));
        const values = sizeInputs.map(i => (i.value||'').trim()).filter(v => v !== '');
        if (!values.length) return false;
        // All sizes must be 2-digit numbers within typical denim waist range
        if (!values.every(v => /^\d{2}$/.test(v))) return false;
        const nums = values.map(v => parseInt(v,10));
        // Accept if every number between 24 and 50 (broad, allows larger) and at least 3 distinct sizes
        if (nums.every(n => n >= 24 && n <= 50) && new Set(nums).size >= 3) return true;
        return false;
    }
    // Will be re-evaluated on open to handle late-loaded variants
    window.__numericWaistProduct = false;

    function showGroup(){
        const forms = ['unisex','tops','bottoms','dresses','shoes','kids'];
        const charts = ['unisex','tops','bottoms','dresses','shoes','kids'];
        forms.forEach(f => { const el = document.getElementById('size-form-'+f); if (el) el.style.display = (f===chosen.form)?'block':'none'; });
        charts.forEach(c => { const el = document.getElementById('chart-'+c); if (el) el.style.display = (chosen.charts.includes(c)?'block':'none'); });
        // Reveal inch waist column if numeric waist product
        if (window.__numericWaistProduct && chosen.form === 'bottoms'){
            document.querySelectorAll('.waist-inch-col').forEach(td => td.style.display='table-cell');
            const note = document.getElementById('waist-inch-note'); if (note) note.style.display='block';
        }
    }

    // Conversion helpers
    function toMetric(value, unit){ return unit==='imperial' ? value * 2.54 : value; }
    function toMetricWeight(value, unit){ return unit==='imperial' ? value * 0.45359237 : value; }

    // Primary calculation
    window.calculateSize = function(e){
        e.preventDefault();
        const form = e.target;
        let rec = 'M';
        let unit = 'metric';

        // Determine which form type submitted
        let type = 'unisex';
        if (form.id.includes('tops')) type='tops';
        else if (form.id.includes('bottoms')) type='bottoms';
        else if (form.id.includes('dresses')) type='dresses';
    else if (form.id.includes('shoes')) type='shoes';
    else if (form.id.includes('kids')) type='kids';

        // Extract fields per form
        if (type==='unisex'){
            unit = form.querySelector('input[name="unit-unisex"]:checked')?.value || 'metric';
            const h = parseFloat(form.querySelector('#height-input').value);
            const w = parseFloat(form.querySelector('#weight-input').value);
            const bodyType = form.body_type.value;
            const fitPref = form.fit_preference.value;
            // Convert to metric for internal logic
            const heightCm = toMetric(h, unit);
            const weightKg = toMetricWeight(w, unit);
            // Basic BMI & body-type heuristic
            const bmi = weightKg / Math.pow(heightCm/100,2);
            if (bmi < 20) rec='S';
            if (bmi >= 24) rec='L';
            if (bmi >= 28) rec='XL';
            if (bmi >= 32) rec='XXL';
            if (bodyType==='slim' && (bmi < 24)) rec = (rec==='L'?'M': (rec==='M'?'S':rec));
            if (bodyType==='athletic' && (bmi < 28)) rec = (rec==='M'?'L': (rec==='L'?'XL':rec));
            if (bodyType==='large') rec = (rec==='M'?'L': (rec==='L'?'XL': (rec==='XL'?'XXL':rec)));
            if (fitPref==='tight') rec = sizeDown(rec);
            if (fitPref==='loose') rec = sizeUp(rec);
        } else if (type==='tops'){
            unit = form.querySelector('input[name="unit-tops"]:checked')?.value || 'metric';
            const chest = parseFloat(form.querySelector('#chest-input-tops').value);
            const chestCm = toMetric(chest, unit);
            rec = chestToSize(chestCm, [
                {size:'XS', max:86}, {size:'S', max:91}, {size:'M', max:97}, {size:'L', max:102}, {size:'XL', max:107}, {size:'XXL', max:112}, {size:'XXXL', max:118}
            ]);
            const fitPref = form.fit_preference_tops.value;
            if (fitPref==='tight') rec = sizeDown(rec);
            if (fitPref==='loose') rec = sizeUp(rec);
        } else if (type==='bottoms'){
            unit = form.querySelector('input[name="unit-bottoms"]:checked')?.value || 'metric';
            const waist = parseFloat(form.querySelector('#waist-input-bottoms').value);
            const waistCm = toMetric(waist, unit);
            rec = chestToSize(waistCm, [
                {size:'S', max:76}, {size:'M', max:81}, {size:'L', max:86}, {size:'XL', max:91}, {size:'XXL', max:97}, {size:'XXXL', max:104}
            ]);
            // Numeric waist product: override recommendation to nearest available numeric waist size
            if (window.__numericWaistProduct){
                const numericSizes = Array.from(document.querySelectorAll('input[name="size"]'))
                    .map(i => (i.value||'').trim())
                    .filter(v => /^\d{2}$/.test(v));
                if (numericSizes.length){
                    const waistIn = waistCm / 2.54;
                    let nearest = numericSizes[0];
                    let bestDiff = Math.abs(parseInt(nearest,10) - waistIn);
                    for (const s of numericSizes){
                        const diff = Math.abs(parseInt(s,10) - waistIn);
                        if (diff < bestDiff){ bestDiff = diff; nearest = s; }
                    }
                    rec = nearest; // Use pure numeric for easier auto-selection
                    window.__numericWaistInches = waistIn.toFixed(1);
                }
            }
        } else if (type==='dresses'){
            unit = form.querySelector('input[name="unit-dresses"]:checked')?.value || 'metric';
            const bust = toMetric(parseFloat(form.querySelector('#bust-input-dresses').value), unit);
            const waist = toMetric(parseFloat(form.querySelector('#waist-input-dresses').value), unit);
            const hips = toMetric(parseFloat(form.querySelector('#hips-input-dresses').value), unit);
            // Weighted approach: bust & waist primary, adjust if hips significantly larger
            let bustSize = chestToSize(bust, [
                {size:'XS', max:82},{size:'S', max:86},{size:'M', max:91},{size:'L', max:96},{size:'XL', max:102},{size:'XXL', max:108}
            ]);
            let waistSize = chestToSize(waist, [
                {size:'XS', max:64},{size:'S', max:68},{size:'M', max:74},{size:'L', max:80},{size:'XL', max:86},{size:'XXL', max:92}
            ]);
            let hipsSize = chestToSize(hips, [
                {size:'XS', max:88},{size:'S', max:92},{size:'M', max:98},{size:'L', max:104},{size:'XL', max:110},{size:'XXL', max:116}
            ]);
            rec = maxSize([bustSize, waistSize, hipsSize]);
            const fitPref = form.fit_preference_dresses.value;
            if (fitPref==='tight') rec = sizeDown(rec);
            if (fitPref==='loose') rec = sizeUp(rec);
        } else if (type==='shoes'){
            unit = form.querySelector('input[name="unit-shoes"]:checked')?.value || 'metric';
            const foot = toMetric(parseFloat(form.querySelector('#foot-input-shoes').value), unit);
            // Shoes lookup table (approximate, unisex baseline)
            const SHOE_TABLE = [
                {eu:37, uk:'4',   us:'5',   min:23.1, max:23.6},
                {eu:38, uk:'5',   us:'6',   min:23.7, max:24.4},
                {eu:39, uk:'6',   us:'7',   min:24.5, max:25.0},
                {eu:40, uk:'6.5', us:'7.5', min:25.1, max:25.6},
                {eu:41, uk:'7.5', us:'8.5', min:25.7, max:26.2},
                {eu:42, uk:'8',   us:'9',   min:26.3, max:26.8},
                {eu:43, uk:'9',   us:'10',  min:26.9, max:27.4},
                {eu:44, uk:'9.5', us:'10.5',min:27.5, max:28.0},
                {eu:45, uk:'10.5',us:'11.5',min:28.1, max:28.6}
            ];
            // Select best row by containing range or nearest
            let best = null, bestDist = Infinity;
            for (const row of SHOE_TABLE){
                if (foot >= row.min && foot <= row.max){ best = row; bestDist = 0; break; }
                const mid = (row.min + row.max)/2;
                const d = Math.abs(foot - mid);
                if (d < bestDist){ best = row; bestDist = d; }
            }
            // Flag out-of-range measurements for fallback messaging
            const outOfRange = foot < SHOE_TABLE[0].min || foot > SHOE_TABLE[SHOE_TABLE.length-1].max;
            // Persist last shoe recommendation for consistent display and selection (independent of availability)
            window.__lastShoeRec = best;
            // Inspect available size labels on the page
            const sizeInputs = Array.from(document.querySelectorAll('input[name="size"]'));
            const labels = sizeInputs.map(i => (i.value || '').trim());
            const norm = (s)=>s.replace(/\s+/g,'').toUpperCase();
            const hasUK = labels.some(l => /^\s*UK\b/i.test(l));
            const hasUS = labels.some(l => /^\s*US\b/i.test(l));
            const hasEU = labels.some(l => /^\s*EU\b/i.test(l)) || labels.some(l => /^\s*\d+[\d\.]*\s*$/i.test(l));

            // Build candidates based on the table row
            const candidates = [];
            if (hasEU)  candidates.push(`EU ${best.eu}`, String(best.eu));
            if (hasUK)  candidates.push(`UK ${best.uk}`);
            if (hasUS)  candidates.push(`US ${best.us}`);

            // If exclusively one system exists, restrict to it
            const systemsPresent = [hasEU?1:0, hasUK?1:0, hasUS?1:0].reduce((a,b)=>a+b,0);
            if (systemsPresent === 1){
                if (hasUK){ candidates.splice(0, candidates.length, `UK ${best.uk}`); }
                else if (hasUS){ candidates.splice(0, candidates.length, `US ${best.us}`); }
                else { candidates.splice(0, candidates.length, `EU ${best.eu}`, String(best.eu)); }
            }

            // Pick first candidate that matches available labels (flexible normalization)
            let found = null;
            const labelNorms = labels.map(l => norm(l));
            for (const c of candidates){
                const cN = norm(c);
                const alt = /EU\s*(\d+(?:\.\d+)?)/i.test(c) ? RegExp.$1 : cN; // allow plain number for EU
                if (labelNorms.includes(cN) || labelNorms.includes(norm(alt))){ found = c; break; }
            }
            // If still not found, try nearest numeric by diff
            if (!found && labels.length){
                const targetNum = parseFloat(String(best.eu));
                let bestLabel = null, bestDelta = Infinity;
                for (const l of labels){
                    const num = parseFloat((l.match(/\d+(?:\.\d+)?/)||[])[0]);
                    if (!isNaN(num)){
                        const d = Math.abs((/UK/i.test(l)? num+33 : /US/i.test(l)? num+32 : num) - targetNum); // rough align to EU scale
                        if (d < bestDelta){ bestDelta = d; bestLabel = l; }
                    }
                }
                found = bestLabel || (`EU ${best.eu}`);
            }
            rec = found || (`EU ${best.eu}`);
            if (outOfRange) {
                // Append concise fallback note for display logic later
                window.__shoeOutOfRangeNote = 'Foot length outside chart range; using nearest size.';
            } else {
                window.__shoeOutOfRangeNote = '';
            }
        } else if (type==='kids') {
            unit = form.querySelector('input[name="unit-kids"]:checked')?.value || 'metric';
            const height = toMetric(parseFloat(form.querySelector('#height-input-kids').value), unit);
            const chest = toMetric(parseFloat(form.querySelector('#chest-input-kids').value), unit);
            const age = parseInt(form.querySelector('#age-input-kids').value, 10);
            // Basic chest-driven sizing, narrower bands
            rec = chestToSize(chest, [
                {size:'XS', max:60}, {size:'S', max:66}, {size:'M', max:72}, {size:'L', max:78}, {size:'XL', max:84}
            ]);
            if (age <= 6) rec = sizeDown(rec);
            if (age >= 11) rec = sizeUp(rec);
        }

        const out = document.getElementById('size-recommendation');
        if (out){
            // For shoes: always show chart-based conversion across EU/UK/US + fallback note if out of range
            if (type==='shoes'){
                const b = window.__lastShoeRec;
                const base = b ? (`EU ${b.eu} / UK ${b.uk} / US ${b.us}`) : rec;
                const note = window.__shoeOutOfRangeNote ? `\n${window.__shoeOutOfRangeNote}` : '';
                document.getElementById('recommended-size-value').textContent = base + note;
            } else if (type==='bottoms' && window.__numericWaistProduct){
                // Display numeric waist in inches with approximate cm
                const recNum = parseInt(rec,10);
                const cmApprox = (recNum * 2.54).toFixed(0);
                document.getElementById('recommended-size-value').textContent = `${rec} in (≈${cmApprox} cm)`;
            } else {
                document.getElementById('recommended-size-value').textContent = rec;
            }
            out.style.display = 'block';
            out.scrollIntoView({behavior:'smooth', block:'nearest'});
        }
    };

    function chestToSize(value, ranges){
        for (const r of ranges){ if (value <= r.max) return r.size; }
        return ranges[ranges.length-1].size;
    }
    const ORDER = ['XS','S','M','L','XL','XXL','XXXL'];
    function sizeUp(size){ const i = ORDER.indexOf(size); return i>=0 && i<ORDER.length-1 ? ORDER[i+1] : size; }
    function sizeDown(size){ const i = ORDER.indexOf(size); return i>0 ? ORDER[i-1] : size; }
    function maxSize(sizes){
        let max = sizes[0];
        sizes.forEach(s=>{ if (ORDER.indexOf(s) > ORDER.indexOf(max)) max = s; });
        return max;
    }

    function buildMultiSystemDisplay(primary){
        // primary may already include prefix (EU/UK/US). Attempt to append conversions only if those systems exist in page options.
        const sizeInputs = Array.from(document.querySelectorAll('input[name="size"]'));
        const labels = sizeInputs.map(i => (i.value||'').trim());
        const hasUK = labels.some(l => /^\s*UK\b/i.test(l));
        const hasUS = labels.some(l => /^\s*US\b/i.test(l));
        const hasEU = labels.some(l => /^\s*EU\b/i.test(l)) || labels.some(l => /^\d+\b/.test(l));
        // Extract EU number if possible
        let euNum = null;
        if (/EU\s*(\d+(?:\.\d+)?)/i.test(primary)) euNum = parseFloat(RegExp.$1);
        else if (/^(\d{2})$/.test(primary)) euNum = parseFloat(RegExp.$1);
        // Quick reverse table for conversions (must match SHOE_TABLE used above)
        const conv = {
            37:{uk:'4', us:'5'},38:{uk:'5', us:'6'},39:{uk:'6', us:'7'},40:{uk:'6.5',us:'7.5'},41:{uk:'7.5',us:'8.5'},42:{uk:'8',us:'9'},43:{uk:'9',us:'10'},44:{uk:'9.5',us:'10.5'},45:{uk:'10.5',us:'11.5'}
        };
        if (!euNum || !conv[euNum]) return primary; // cannot enrich
        const parts = [primary];
        if (hasUK) parts.push(`UK ${conv[euNum].uk}`);
        if (hasUS) parts.push(`US ${conv[euNum].us}`);
        // If only one system present, primary already constrained earlier; still return just primary
        return parts.join(' / ');
    }

    window.applyRecommendedSize = function(){
        // Attempt to select a variant matching recommended shoe size; supports multi-system display
        const recText = document.getElementById('recommended-size-value')?.textContent.trim();
        if (!recText) return closeSizeFinder();
        let matched = false;

        // Build candidate labels to try: prefer exact table-based EU/UK/US for shoes
        let candidates = [];
        if (window.__lastShoeRec && typeof window.__lastShoeRec.eu !== 'undefined'){
            const b = window.__lastShoeRec;
            candidates = [`EU ${b.eu}`, String(b.eu), `UK ${b.uk}`, `US ${b.us}`];
        } else {
            candidates = [recText];
        }

        const tryMatch = (val)=>{
            if (!val) return false;
            const v = val.toLowerCase();
            // 1) <select name="size">
            const sizeSelect = document.querySelector('select[name="size"]');
            if (sizeSelect){
                for (const opt of sizeSelect.options){
                    if (opt.text.toLowerCase() === v || opt.value.toLowerCase() === v){
                        sizeSelect.value = opt.value; sizeSelect.dispatchEvent(new Event('change')); return true;
                    }
                    // Allow numeric EU match if option text/value is plain number only
                    const euNum = (v.match(/\b(\d+(?:\.\d+)?)\b/)||[])[1];
                    if (euNum){
                        const optNum = (opt.text.match(/\b(\d{2})\b/)||[])[1] || (opt.value.match(/\b(\d{2})\b/)||[])[1];
                        if (opt.text.trim() === euNum || opt.value.trim() === euNum || (optNum && optNum === euNum)){
                            sizeSelect.value = opt.value; sizeSelect.dispatchEvent(new Event('change')); return true;
                        }
                    }
                }
            }
            // 2) radios name="size"
            const sizeRadios = Array.from(document.querySelectorAll('input[type="radio"][name="size"]'));
            for (const r of sizeRadios){
                const rv = (r.value||'').trim().toLowerCase();
                if (rv === v) { r.checked = true; r.dispatchEvent(new Event('change')); return true; }
                const euNum = (v.match(/\b(\d+(?:\.\d+)?)\b/)||[])[1];
                if (euNum){
                    const rNum = (r.value.match(/\b(\d{2})\b/)||[])[1];
                    if (r.value.trim() === euNum || (rNum && rNum === euNum)) { r.checked = true; r.dispatchEvent(new Event('change')); return true; }
                }
            }
            // 3) variant radios by data-size or label text
            const radios = Array.from(document.querySelectorAll('input[type="radio"][name="variant_id"]'));
            for (const r of radios){
                const ds = ((r.dataset && (r.dataset.size||r.getAttribute('data-size'))) || '').trim().toLowerCase();
                const label = (r.closest('label')?.textContent || '').trim().toLowerCase();
                if (ds === v || label === v) { r.checked = true; r.dispatchEvent(new Event('change')); return true; }
                const euNum = (v.match(/\b(\d+(?:\.\d+)?)\b/)||[])[1];
                if (euNum){
                    const dsNum = (ds.match(/\b(\d{2})\b/)||[])[1];
                    const labelNum = (label.match(/\b(\d{2})\b/)||[])[1];
                    if (ds === euNum || label === euNum || dsNum === euNum || labelNum === euNum) { r.checked = true; r.dispatchEvent(new Event('change')); return true; }
                }
            }
            return false;
        };

        for (const c of candidates){ if (tryMatch(c)) { matched = true; break; } }

        closeSizeFinder();
        if (matched) {
            if (typeof showNotification === 'function') { showNotification('Size selected', 'success'); }
        } else {
            if (typeof showNotification === 'function') { showNotification('Recommended size not available', 'error'); }
        }
    };

    window.closeSizeFinder = function(){
        const modal = document.getElementById('size-finder-modal');
        if (modal) modal.style.display='none';
    };

    // Expose toggleUnits if existing code expects it (noop aside from labels)
    window.toggleUnits = function(){
        // Update unit labels dynamically for each form
        const pairs = [
            ['height-unit','height-example','cm','centimeters (e.g., 170)','in','inches (e.g., 67)'],
            ['weight-unit','weight-example','kg','kilograms (e.g., 70)','lbs','pounds (e.g., 154)'],
            ['chest-unit-tops',null,'cm',null,'in',null],
            ['waist-unit-bottoms',null,'cm',null,'in',null],
            ['hips-unit-bottoms',null,'cm',null,'in',null],
            ['inseam-unit-bottoms',null,'cm',null,'in',null],
            ['bust-unit-dresses',null,'cm',null,'in',null],
            ['waist-unit-dresses',null,'cm',null,'in',null],
            ['hips-unit-dresses',null,'cm',null,'in',null],
            ['foot-unit-shoes',null,'cm',null,'in',null],
            ['height-unit-kids',null,'cm',null,'in',null],
            ['chest-unit-kids',null,'cm',null,'in',null]
        ];
        // Determine currently visible form
        const visibleForm = document.querySelector('form[id^="size-form-"][style*="display: block"], form[id^="size-form-"]:not([style])');
        let primaryUnit = 'metric';
        if (visibleForm){
            const checked = visibleForm.querySelector('input[type=radio][name^=unit-]:checked');
            primaryUnit = checked?.value || 'metric';
        }
        pairs.forEach(p=>{
            const span = document.getElementById(p[0]);
            if (!span) return;
            if (primaryUnit==='imperial') span.textContent = '('+p[4]+')'; else span.textContent = '('+p[2]+')';
            if (p[1]){
                const ex = document.getElementById(p[1]);
                if (ex){ ex.textContent = primaryUnit==='imperial'? p[5]: p[3]; }
            }
        });
    };

    // Initialize
    window.openSizeFinder = function(){
        const modal = document.getElementById('size-finder-modal');
        // Allow opening if category is size-based OR product actually has sized variants
        if (!isSizeBasedCategory() && !hasSizeVariants()) return; // guard
        // Recompute numeric waist flag on each open (variants might be AJAX-updated)
        window.__numericWaistProduct = detectNumericWaistProduct();
        showGroup();
        if (modal) modal.style.display='flex';
        window.toggleUnits();
    };

    document.addEventListener('DOMContentLoaded', function(){
        showGroup();
        // Hide size trigger + selector for free-size and non-size categories without sized variants
        if (<?php echo $is_free_size_product ? 'true':'false'; ?> || (!isSizeBasedCategory() && !hasSizeVariants())){
            const triggerBtn = document.querySelector('button[onclick="openSizeFinder()"]');
            if (triggerBtn) triggerBtn.style.display='none';
            const sizeSelector = document.getElementById('size-selector');
            if (sizeSelector) sizeSelector.style.display='none';
        }
        // Initial numeric waist detection (in case modal opened later)
        window.__numericWaistProduct = detectNumericWaistProduct();
    });

    function isSizeBasedCategory(){
        // Categories typically NOT size-based, but allow exceptions if sized variants exist
        const NON_SIZE = ['bag','accessor','accessory','belt','wallet','cap','hat','scarf','glove','jewelry','bracelet','necklace'];
        const looksNonSize = NON_SIZE.some(k => catTokens.some(ct => ct.includes(k)));
        if (!looksNonSize) return true;
        // If there are non-blank size radios, consider size-based for this product
        const inputs = Array.from(document.querySelectorAll('input[name="size"]'));
        return inputs.some(i => (i.value||'').trim() !== '');
    }

    function hasSizeVariants(){
        const inputs = Array.from(document.querySelectorAll('input[name="size"]'));
        const unique = new Set(inputs.map(i => (i.value||'').trim()).filter(v => v !== ''));
        return unique.size > 0;
    }
})();
</script>