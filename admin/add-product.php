<?php
require_once 'config.php';
$page_title = "Add New Product";

if (!has_permission('inventory_manager')) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Get categories
$categories = [];
$result = $conn->query("SELECT * FROM categories ORDER BY category_name");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_name = clean_input($_POST['product_name']);
    $sku = clean_input($_POST['sku']);
    $category_id = intval($_POST['category_id']);
    $brand = clean_input($_POST['brand']);
    $description = clean_input($_POST['description']);
    $base_price = floatval($_POST['base_price']);
    $discount_percentage = floatval($_POST['discount_percentage']);
    $final_price = $base_price - ($base_price * $discount_percentage / 100);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_trending = isset($_POST['is_trending']) ? 1 : 0;
    
    if (empty($product_name) || empty($sku) || $base_price <= 0) {
        $error = "Please fill in all required fields";
    } else {
        // Check if SKU exists
        $stmt = $conn->prepare("SELECT product_id FROM products WHERE sku = ?");
        $stmt->bind_param("s", $sku);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = "SKU already exists";
        } else {
            // Generate a unique, non-empty slug for the product to satisfy UNIQUE index on products.slug
            $base_slug = strtolower(trim($product_name));
            // Replace all non-alphanumeric with hyphens, collapse repeats
            $base_slug = preg_replace('/[^a-z0-9]+/i', '-', $base_slug);
            $base_slug = trim($base_slug, '-');
            if ($base_slug === '') {
                // Fallback: hash-based slug if name produced empty after sanitization
                $base_slug = substr(sha1($product_name . microtime(true)), 0, 10);
            }
            $slug = $base_slug;
            $i = 2;
            $checkSlugStmt = $conn->prepare("SELECT 1 FROM products WHERE slug = ? LIMIT 1");
            while (true) {
                $checkSlugStmt->bind_param('s', $slug);
                $checkSlugStmt->execute();
                $checkSlugStmt->store_result();
                if ($checkSlugStmt->num_rows === 0) { // slug free
                    break;
                }
                $slug = $base_slug . '-' . $i;
                $i++;
                // Small safety cap (unlikely to hit) to avoid infinite loop
                if ($i > 500) { break; }
            }
            $checkSlugStmt->close();

            $stmt = $conn->prepare("INSERT INTO products (product_name, slug, sku, category_id, brand, description, 
                                   base_price, discount_percentage, final_price, is_featured, is_trending) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssissdddii", $product_name, $slug, $sku, $category_id, $brand, $description, 
                             $base_price, $discount_percentage, $final_price, $is_featured, $is_trending);
            
            if ($stmt->execute()) {
                $product_id = $stmt->insert_id;
                
                // Handle image upload
                if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
                    $upload_dir = '../uploads/products/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_ext = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                    
                    if (in_array($file_ext, $allowed)) {
                        $new_filename = 'product_' . $product_id . '_' . time() . '.' . $file_ext;
                        $upload_path = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                            $image_url = 'uploads/products/' . $new_filename;
                            $conn->query("INSERT INTO product_images (product_id, image_url, is_primary) 
                                         VALUES ($product_id, '$image_url', 1)");
                        }
                    }
                }
                
                // Add variants (allow empty size for free-size products)
                if (isset($_POST['variants'])) {
                    foreach ($_POST['variants'] as $variant) {
                        $size = isset($variant['size']) ? clean_input($variant['size']) : '';
                        $color = isset($variant['color']) ? clean_input($variant['color']) : '';
                        $stock = isset($variant['stock']) ? intval($variant['stock']) : 0;

                        // Insert when at least one meaningful attribute is provided
                        // Allow empty size so admin can create free-size variants
                        if ($size !== '' || $color !== '' || $stock > 0) {
                            $stmt = $conn->prepare("INSERT INTO product_variants (product_id, size, color, stock_quantity) 
                                                   VALUES (?, ?, ?, ?)");
                            $stmt->bind_param("issi", $product_id, $size, $color, $stock);
                            $stmt->execute();
                        }
                    }
                }
                
                log_admin_activity('add_product', "Product: $product_name");
                
                $success = "Product added successfully!";
                header('Location: products.php?success=added');
                exit;
            } else {
                $error = "Failed to add product";
            }
        }
    }
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="admin-header">
    <h1>Add New Product</h1>
    <div class="admin-header-actions">
        <a href="products.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Products
        </a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <div class="admin-grid-2">
        <!-- Left Column -->
        <div>
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>Basic Information</h3>
                </div>
                <div class="admin-card-body">
                    <div class="form-group">
                        <label>Product Name *</label>
                        <input type="text" name="product_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>SKU *</label>
                        <input type="text" name="sku" class="form-control" required 
                               placeholder="e.g., SHIRT-001">
                    </div>
                    
                    <div class="form-group">
                        <label>Category *</label>
                        <select name="category_id" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>">
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Brand</label>
                        <input type="text" name="brand" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="6"></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Variants -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>Product Variants</h3>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="addVariant()">
                        <i class="fas fa-plus"></i> Add Variant
                    </button>
                </div>
                <div class="admin-card-body">
                    <div id="variants-container">
                        <div class="variant-row">
                            <input type="text" name="variants[0][size]" placeholder="Size (optional, leave blank for Free Size)" class="form-control">
                            <input type="text" name="variants[0][color]" placeholder="Color" class="form-control">
                            <input type="number" name="variants[0][stock]" placeholder="Stock" class="form-control" min="0">
                            <button type="button" class="btn btn-danger btn-sm" onclick="removeVariant(this)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Column -->
        <div>
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>Pricing</h3>
                </div>
                <div class="admin-card-body">
                    <div class="form-group">
                        <label>Base Price *</label>
                        <input type="number" name="base_price" class="form-control" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Discount Percentage</label>
                        <input type="number" name="discount_percentage" class="form-control" step="0.01" min="0" max="100" value="0">
                    </div>

                    <div class="form-group">
                        <label>Final Price</label>
                        <div class="form-control" style="background:#f9fafb; color:#111827;">
                            $ <span id="finalPriceDisplay">0.00</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>Product Image</h3>
                </div>
                <div class="admin-card-body">
                    <div class="form-group">
                        <label>Upload Image</label>
                        <input type="file" name="product_image" class="form-control" accept="image/*">
                        <small class="text-muted">Accepted formats: JPG, PNG, WEBP. Max 5MB</small>
                    </div>
                    <div id="image-preview"></div>
                </div>
            </div>
            
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>Product Options</h3>
                </div>
                <div class="admin-card-body">
                    <div class="form-check">
                        <input type="checkbox" name="is_featured" id="is_featured" value="1">
                        <label for="is_featured">Featured Product</label>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" name="is_trending" id="is_trending" value="1">
                        <label for="is_trending">Trending Product</label>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-full">
                <i class="fas fa-save"></i> Add Product
            </button>
        </div>
    </div>
</form>

<script>
let variantCount = 1;

function addVariant() {
    const container = document.getElementById('variants-container');
    const newRow = document.createElement('div');
    newRow.className = 'variant-row';
    newRow.innerHTML = `
    <input type="text" name="variants[${variantCount}][size]" placeholder="Size (optional, blank = Free Size)" class="form-control">
        <input type="text" name="variants[${variantCount}][color]" placeholder="Color" class="form-control">
        <input type="number" name="variants[${variantCount}][stock]" placeholder="Stock" class="form-control" min="0">
        <button type="button" class="btn btn-danger btn-sm" onclick="removeVariant(this)">
            <i class="fas fa-trash"></i>
        </button>
    `;
    container.appendChild(newRow);
    variantCount++;
}

function removeVariant(btn) {
    btn.parentElement.remove();
}

// Pricing live calculation
function recalcFinalPrice() {
    var baseInput = document.querySelector('input[name="base_price"]');
    var discInput = document.querySelector('input[name="discount_percentage"]');
    var outEl = document.getElementById('finalPriceDisplay');
    if (!baseInput || !discInput || !outEl) return;
    var base = parseFloat(baseInput.value);
    var disc = parseFloat(discInput.value);
    if (isNaN(base)) base = 0;
    if (isNaN(disc)) disc = 0;
    if (disc < 0) disc = 0;
    if (disc > 100) disc = 100;
    var final = base - (base * disc / 100);
    if (!isFinite(final)) final = 0;
    outEl.textContent = final.toFixed(2);
}

document.addEventListener('DOMContentLoaded', function(){
    var baseInput = document.querySelector('input[name="base_price"]');
    var discInput = document.querySelector('input[name="discount_percentage"]');
    if (baseInput) baseInput.addEventListener('input', recalcFinalPrice);
    if (discInput) discInput.addEventListener('input', recalcFinalPrice);
    recalcFinalPrice();
});
</script>

<?php include 'includes/footer.php'; ?>