<?php
require_once 'config.php';
require_admin_login();

$page_title = "Edit Product";
$success = '';
$error = '';

// Get product ID
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$product_id) {
    header('Location: products.php');
    exit;
}

// Fetch product details
$stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header('Location: products.php');
    exit;
}

// Fetch categories
$categories = [];
$result = $conn->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY category_name");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

// Fetch product images
$images = [];
$result = $conn->query("SELECT * FROM product_images WHERE product_id = $product_id ORDER BY is_primary DESC, image_id");
while ($row = $result->fetch_assoc()) {
    $images[] = $row;
}

// Fetch product variants
$variants = [];
$result = $conn->query("SELECT * FROM product_variants WHERE product_id = $product_id ORDER BY variant_id");
while ($row = $result->fetch_assoc()) {
    $variants[] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['add_variant'])) {
    $product_name = clean_input($_POST['product_name']);
    $category_id = intval($_POST['category_id']);
    $brand = clean_input($_POST['brand'] ?? '');
    $description = clean_input($_POST['description']);
    $base_price = floatval($_POST['base_price'] ?? 0);
    $discount_percentage = floatval($_POST['discount_percentage'] ?? 0);
    if ($discount_percentage < 0) $discount_percentage = 0; if ($discount_percentage > 100) $discount_percentage = 100;
    $final_price = $base_price - ($base_price * $discount_percentage / 100);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    
    if (empty($product_name) || empty($category_id) || $base_price <= 0) {
        $error = "Please fill all required fields with valid data";
    } else {
        // Update product including pricing + brand
    $stmt = $conn->prepare("UPDATE products SET product_name = ?, category_id = ?, brand = ?, description = ?, base_price = ?, discount_percentage = ?, final_price = ?, is_active = ?, is_featured = ?, updated_at = NOW() WHERE product_id = ?");
    // Types: s (product_name), i (category_id), s (brand), s (description), d (base_price), d (discount_percentage), d (final_price), i (is_active), i (is_featured), i (product_id)
    $stmt->bind_param("sissdddiii", $product_name, $category_id, $brand, $description, $base_price, $discount_percentage, $final_price, $is_active, $is_featured, $product_id);
        
        if ($stmt->execute()) {
            // Handle image upload
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $filename = $_FILES['product_image']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowed)) {
                    // Create uploads directory if it doesn't exist
                    if (!file_exists('../uploads/products')) {
                        mkdir('../uploads/products', 0777, true);
                    }
                    
                    $new_filename = 'product_' . $product_id . '_' . time() . '.' . $ext;
                    $upload_path = '../uploads/products/' . $new_filename;
                    
                    if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                        // Insert new image
                        $image_url = 'uploads/products/' . $new_filename;
                        $is_primary = empty($images) ? 1 : 0;
                        
                        $stmt = $conn->prepare("INSERT INTO product_images (product_id, image_url, is_primary) VALUES (?, ?, ?)");
                        $stmt->bind_param("isi", $product_id, $image_url, $is_primary);
                        $stmt->execute();
                    }
                }
            }
            
            log_admin_activity('update_product', "Updated product: $product_name");
            $success = "Product updated successfully!";
            
            // Refresh product data
            $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $product = $stmt->get_result()->fetch_assoc();
            
            // Refresh images
            $images = [];
            $result = $conn->query("SELECT * FROM product_images WHERE product_id = $product_id ORDER BY is_primary DESC, image_id");
            while ($row = $result->fetch_assoc()) {
                $images[] = $row;
            }
        } else {
            $error = "Failed to update product: " . $stmt->error;
        }
    }
}

// Handle variant addition
if (isset($_POST['add_variant'])) {
    $size = clean_input($_POST['size']); // may be empty for Free Size
    $color = clean_input($_POST['color']);
    $stock = intval($_POST['stock_quantity']);
    $sku = clean_input($_POST['sku']);
    // If SKU left blank, auto-generate a unique placeholder to avoid duplicate '' constraint errors
    if ($sku === '') {
        $baseSku = 'VAR' . $product_id . '-' . preg_replace('/[^A-Za-z0-9]/', '', strtoupper(substr($size, 0, 6))) . '-' . preg_replace('/[^A-Za-z0-9]/', '', strtoupper(substr($color, 0, 6)));
        if ($baseSku === 'VAR' . $product_id . '--') { // size & color both empty characters
            $baseSku .= 'X';
        }
        $candidate = $baseSku;
        $i = 1;
        $checkStmt = $conn->prepare("SELECT 1 FROM product_variants WHERE sku = ? LIMIT 1");
        while (true) {
            $checkStmt->bind_param('s', $candidate);
            $checkStmt->execute();
            $checkStmt->store_result();
            if ($checkStmt->num_rows === 0) {
                break; // unique found
            }
            $candidate = $baseSku . '-' . $i;
            $i++;
            if ($i > 50) { // safety fallback to random hash segment
                $candidate = $baseSku . '-' . substr(sha1(microtime(true)), 0, 6);
                break;
            }
        }
        $checkStmt->close();
        $sku = $candidate;
    }
    
    if ($stock < 0) {
        $error = "Please fill variant details correctly";
    } else {
        $stmt = $conn->prepare("INSERT INTO product_variants (product_id, size, color, stock_quantity, sku) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issis", $product_id, $size, $color, $stock, $sku);
        
        if ($stmt->execute()) {
            $success = "Variant added successfully!";
            
            // Refresh variants
            $variants = [];
            $result = $conn->query("SELECT * FROM product_variants WHERE product_id = $product_id ORDER BY variant_id");
            while ($row = $result->fetch_assoc()) {
                $variants[] = $row;
            }
        } else {
            $error = "Failed to add variant";
        }
    }
}

// Handle actions
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'delete_image' && isset($_GET['image_id'])) {
        $image_id = intval($_GET['image_id']);
        
        // Get image details
        $stmt = $conn->prepare("SELECT * FROM product_images WHERE image_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $image_id, $product_id);
        $stmt->execute();
        $image = $stmt->get_result()->fetch_assoc();
        
        if ($image) {
            // Delete file
            if (file_exists('../' . $image['image_url'])) {
                unlink('../' . $image['image_url']);
            }
            
            // Delete from database
            $conn->query("DELETE FROM product_images WHERE image_id = $image_id");
            
            log_admin_activity('delete_image', "Deleted product image ID: $image_id");
            header("Location: edit-product.php?id=$product_id&msg=image_deleted");
            exit;
        }
    }
    
    if ($_GET['action'] == 'set_primary' && isset($_GET['image_id'])) {
        $image_id = intval($_GET['image_id']);
        
        // Reset all images
        $conn->query("UPDATE product_images SET is_primary = 0 WHERE product_id = $product_id");
        
        // Set new primary
        $conn->query("UPDATE product_images SET is_primary = 1 WHERE image_id = $image_id");
        
        header("Location: edit-product.php?id=$product_id&msg=primary_set");
        exit;
    }
    
    if ($_GET['action'] == 'delete_variant' && isset($_GET['variant_id'])) {
        $variant_id = intval($_GET['variant_id']);

        // Check if this variant is referenced by any order items; if so, do not delete to avoid FK errors
        $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM order_items WHERE variant_id = ?");
        $stmt->bind_param("i", $variant_id);
        $stmt->execute();
        $ref = $stmt->get_result()->fetch_assoc();
        $in_use = intval($ref['cnt'] ?? 0) > 0;

        if ($in_use) {
            // Don't delete; show a friendly error instead of triggering a fatal FK constraint error
            $error = "This variant cannot be deleted because it is referenced by existing orders. You can set its stock to 0 or mark it inactive instead.";
        } else {
            // Safe to delete
            $stmt = $conn->prepare("DELETE FROM product_variants WHERE variant_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $variant_id, $product_id);
            if ($stmt->execute()) {
                log_admin_activity('delete_variant', "Deleted variant ID: $variant_id");
                header("Location: edit-product.php?id=$product_id&msg=variant_deleted");
                exit;
            } else {
                $error = "Failed to delete variant: " . $stmt->error;
            }
        }
    }
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="admin-header">
    <h1><i class="fas fa-edit"></i> Edit Product</h1>
    <div class="admin-header-actions">
        <a href="products.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Products
        </a>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php 
        if ($_GET['msg'] == 'image_deleted') echo 'Image deleted successfully!';
        if ($_GET['msg'] == 'primary_set') echo 'Primary image updated!';
        if ($_GET['msg'] == 'variant_deleted') echo 'Variant deleted successfully!';
        ?>
    </div>
<?php endif; ?>

<div class="admin-grid-2">
    <!-- Left Column -->
    <div>
        <!-- Product Information -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3><i class="fas fa-info-circle"></i> Product Information</h3>
            </div>
            <div class="admin-card-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Product Name *</label>
                        <input type="text" name="product_name" class="form-control" value="<?php echo htmlspecialchars($product['product_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-folder"></i> Category *</label>
                        <select name="category_id" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['category_id']; ?>" <?php echo ($category['category_id'] == $product['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-industry"></i> Brand</label>
                        <input type="text" name="brand" class="form-control" value="<?php echo htmlspecialchars($product['brand'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-align-left"></i> Description</label>
                        <textarea name="description" class="form-control" rows="6"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <?php
                        // Backfill pricing fields if legacy product lacks base_price
                        $legacy_base = isset($product['base_price']) && $product['base_price'] > 0 ? $product['base_price'] : ($product['final_price'] ?? 0);
                        $legacy_discount = isset($product['discount_percentage']) ? $product['discount_percentage'] : 0;
                        $legacy_final = $legacy_base - ($legacy_base * $legacy_discount / 100);
                    ?>
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Base Price (<?php echo CURRENCY_SYMBOL; ?>) *</label>
                        <input type="number" name="base_price" id="base_price" class="form-control" step="0.01" min="0" value="<?php echo htmlspecialchars(number_format($legacy_base, 2, '.', '')); ?>" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-percent"></i> Discount Percentage</label>
                        <input type="number" name="discount_percentage" id="discount_percentage" class="form-control" step="0.01" min="0" max="100" value="<?php echo htmlspecialchars($legacy_discount); ?>">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-equals"></i> Final Price (<?php echo CURRENCY_SYMBOL; ?>)</label>
                        <div id="final_price_display" style="font-weight:600; padding:10px; background:#f9f9f9; border:1px solid #e5e5e5; border-radius:6px;">
                            <?php echo CURRENCY_SYMBOL . number_format($legacy_final, 2); ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-image"></i> Add New Image</label>
                        <input type="file" name="product_image" class="form-control" accept="image/*">
                        <small style="color: #666; font-size: 12px;">JPG, PNG, GIF, WEBP - Max 5MB</small>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" name="is_active" <?php echo $product['is_active'] ? 'checked' : ''; ?>>
                            <span><i class="fas fa-toggle-on"></i> Active (Visible on store)</span>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" name="is_featured" <?php echo $product['is_featured'] ? 'checked' : ''; ?>>
                            <span><i class="fas fa-star"></i> Featured Product</span>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-full">
                        <i class="fas fa-save"></i> Update Product
                    </button>
                </form>
            </div>
        </div>

        <!-- Product Images -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3><i class="fas fa-images"></i> Product Images (<?php echo count($images); ?>)</h3>
            </div>
            <div class="admin-card-body">
                <?php if (empty($images)): ?>
                    <div class="empty-state">
                        <i class="fas fa-image"></i>
                        <p>No images uploaded yet</p>
                    </div>
                <?php else: ?>
                    <div class="product-images-grid">
                        <?php foreach ($images as $image): ?>
                            <div class="product-image-item">
                                <img src="../<?php echo $image['image_url']; ?>" alt="Product Image">
                                <?php if ($image['is_primary']): ?>
                                    <span class="primary-badge">Primary</span>
                                <?php endif; ?>
                                <div class="image-actions">
                                    <?php if (!$image['is_primary']): ?>
                                        <a href="?id=<?php echo $product_id; ?>&action=set_primary&image_id=<?php echo $image['image_id']; ?>" class="btn btn-sm btn-secondary" title="Set as Primary">
                                            <i class="fas fa-star"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="?id=<?php echo $product_id; ?>&action=delete_image&image_id=<?php echo $image['image_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this image?')" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column -->
    <div>
        <!-- Product Variants -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3><i class="fas fa-boxes"></i> Product Variants (<?php echo count($variants); ?>)</h3>
            </div>
            <div class="admin-card-body">
                <!-- Add Variant Form -->
                <form method="POST" action="" class="variant-form">
                    <h4 style="margin-bottom: 15px; font-size: 14px; color: #666;">Add New Variant</h4>
                    
                    <div class="form-group">
                        <label>Size (optional)</label>
                        <input type="text" name="size" class="form-control" placeholder="Leave blank for Free Size">
                    </div>
                    
                    <div class="form-group">
                        <label>Color</label>
                        <input type="text" name="color" class="form-control" placeholder="e.g., Red, Blue, Black">
                    </div>
                    
                    <div class="form-group">
                        <label>Stock Quantity *</label>
                        <input type="number" name="stock_quantity" class="form-control" min="0" value="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label>SKU</label>
                        <input type="text" name="sku" class="form-control" placeholder="e.g., PROD-001-M-RED">
                    </div>
                    
                    <button type="submit" name="add_variant" class="btn btn-success btn-full">
                        <i class="fas fa-plus"></i> Add Variant
                    </button>
                </form>

                <hr style="margin: 25px 0; border: none; border-top: 1px solid #e5e5e5;">

                <!-- Existing Variants -->
                <?php if (empty($variants)): ?>
                    <div class="empty-state">
                        <i class="fas fa-box"></i>
                        <p>No variants added yet</p>
                    </div>
                <?php else: ?>
                    <h4 style="margin-bottom: 15px; font-size: 14px; color: #666;">Existing Variants</h4>
                    <div class="variants-list">
                        <?php foreach ($variants as $variant): ?>
                            <div class="variant-item">
                                <div class="variant-info">
                                    <div class="variant-name">
                                        <strong>Size:</strong> <?php echo $variant['size'] === '' ? '<span class="badge badge-secondary">Free Size</span>' : htmlspecialchars($variant['size']); ?>
                                        <?php if ($variant['color']): ?>
                                            <span class="variant-color">• <?php echo htmlspecialchars($variant['color']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="variant-details">
                                        <span><i class="fas fa-cubes"></i> Stock: <?php echo $variant['stock_quantity']; ?></span>
                                        <?php if ($variant['sku']): ?>
                                            <span><i class="fas fa-barcode"></i> <?php echo htmlspecialchars($variant['sku']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="variant-actions">
                                    <a href="?id=<?php echo $product_id; ?>&action=delete_variant&variant_id=<?php echo $variant['variant_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this variant?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Product Statistics -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3><i class="fas fa-chart-line"></i> Product Statistics</h3>
            </div>
            <div class="admin-card-body">
                <div class="stats-list">
                    <div class="stat-row">
                        <span class="stat-label"><i class="fas fa-calendar-plus"></i> Created:</span>
                        <span class="stat-value"><?php echo date('M d, Y', strtotime($product['created_at'])); ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label"><i class="fas fa-calendar-edit"></i> Last Updated:</span>
                        <span class="stat-value"><?php echo date('M d, Y', strtotime($product['updated_at'])); ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label"><i class="fas fa-toggle-on"></i> Status:</span>
                        <span class="stat-value">
                            <span class="status-badge status-<?php echo $product['is_active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </span>
                    </div>
                    <?php if ($product['is_featured']): ?>
                        <div class="stat-row">
                            <span class="stat-label"><i class="fas fa-star"></i> Featured:</span>
                            <span class="stat-value">
                                <span class="badge badge-warning">Yes</span>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="admin-card" style="border-left: 4px solid #dc3545;">
            <div class="admin-card-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Danger Zone</h3>
            </div>
            <div class="admin-card-body">
                <p style="color: #666; margin-bottom: 15px; font-size: 14px;">
                    Deleting this product is permanent and cannot be undone.
                </p>
                <a href="delete-product.php?id=<?php echo $product_id; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this product?')">
                    <i class="fas fa-trash"></i> Delete Product
                </a>
            </div>
        </div>
    </div>
</div>

<style>
/* Product Images Grid */
.product-images-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 15px;
}

.product-image-item {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid #f0f0f0;
    transition: all 0.3s;
}

.product-image-item:hover {
    border-color: var(--primary-color);
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.product-image-item img {
    width: 100%;
    height: 150px;
    object-fit: cover;
    display: block;
}

.primary-badge {
    position: absolute;
    top: 8px;
    left: 8px;
    background: var(--primary-color);
    color: white;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
}

.image-actions {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    gap: 5px;
    padding: 8px;
    opacity: 0;
    transition: opacity 0.3s;
}

.product-image-item:hover .image-actions {
    opacity: 1;
}

.image-actions .btn {
    flex: 1;
}

/* Variants */
.variant-form {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.variants-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.variant-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #fafafa;
    border-radius: 8px;
    border-left: 3px solid var(--primary-color);
}

.variant-info {
    flex: 1;
}

.variant-name {
    font-weight: 600;
    margin-bottom: 6px;
    font-size: 14px;
    color: #333;
}

.variant-color {
    color: #666;
    font-weight: 400;
}

.variant-details {
    display: flex;
    gap: 15px;
    font-size: 12px;
    color: #666;
}

.variant-details span {
    display: flex;
    align-items: center;
    gap: 5px;
}

.variant-actions {
    display: flex;
    gap: 5px;
}

/* Stats List */
.stats-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.stat-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
}

.stat-row:last-child {
    border-bottom: none;
}

.stat-label {
    color: #666;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.stat-label i {
    color: var(--primary-color);
    width: 20px;
}

.stat-value {
    font-weight: 600;
    color: #333;
}
</style>

<?php include 'includes/footer.php'; ?>
<script>
// Dynamic final price calculation
(function(){
    const baseEl = document.getElementById('base_price');
    const discEl = document.getElementById('discount_percentage');
    const finalEl = document.getElementById('final_price_display');
    function recalc(){
        const b = parseFloat(baseEl.value)||0;
        let d = parseFloat(discEl.value)||0;
        if(d<0) d=0; if(d>100) d=100;
        const f = b - (b * d / 100);
        finalEl.textContent = '<?php echo CURRENCY_SYMBOL; ?>' + f.toFixed(2);
    }
    baseEl && baseEl.addEventListener('input', recalc);
    discEl && discEl.addEventListener('input', recalc);
})();
</script>