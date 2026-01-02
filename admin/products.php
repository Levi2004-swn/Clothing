<?php
require_once 'config.php';
$page_title = "Products Management";

// Handle product deletion
if (isset($_GET['delete']) && has_permission('inventory_manager')) {
    $product_id = intval($_GET['delete']);
    $conn->query("UPDATE products SET is_active = 0 WHERE product_id = $product_id");
    log_admin_activity('delete_product', "Product ID: $product_id");
    header('Location: products.php?success=deleted');
    exit;
}

// Get products
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$status = $_GET['status'] ?? '';

$where = ["1=1"];
if ($search) {
    $search_safe = $conn->real_escape_string($search);
    $where[] = "(p.product_name LIKE '%$search_safe%' OR p.sku LIKE '%$search_safe%')";
}
if ($category) {
    $category_safe = intval($category);
    $where[] = "p.category_id = $category_safe";
}
if ($status !== '') {
    $status_safe = intval($status);
    $where[] = "p.is_active = $status_safe";
}

$where_clause = implode(" AND ", $where);

$products = [];
$result = $conn->query("SELECT p.*, c.category_name, pi.image_url,
                       (SELECT SUM(stock_quantity) FROM product_variants WHERE product_id = p.product_id) as total_stock
                       FROM products p
                       LEFT JOIN categories c ON p.category_id = c.category_id
                       LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                       WHERE $where_clause
                       ORDER BY p.created_at DESC");
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

// Get categories for filter
$categories = [];
$result = $conn->query("SELECT * FROM categories ORDER BY category_name");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<?php
// Inline toast logic (non-invasive): check success/error params and emit notification JS.
// success=deleted  -> hard delete (no sales history) => "Product deleted"
// success=added    -> after add-product -> "Product added"
// error=cannot_delete -> product has sales records -> "Product cannot be deleted"
if (isset($_GET['success'])) {
    $succ = $_GET['success'];
    $msg = '';
    if ($succ === 'deleted') { $msg = 'Product deleted'; }
    elseif ($succ === 'added') { $msg = 'Product added'; }
    if ($msg !== '') {
        echo '<script>document.addEventListener("DOMContentLoaded",function(){ if(typeof showNotification==="function"){ showNotification(' . json_encode($msg) . ', "success"); } });</script>';
    }
}
if (isset($_GET['error'])) {
    $err = $_GET['error'];
    $msg = '';
    if ($err === 'cannot_delete') { $msg = 'Product cannot be deleted'; }
    if ($msg !== '') {
        echo '<script>document.addEventListener("DOMContentLoaded",function(){ if(typeof showNotification==="function"){ showNotification(' . json_encode($msg) . ', "error"); } });</script>';
    }
}
?>

<div class="admin-header">
    <h1>Products Management</h1>
    <div class="admin-header-actions">
        <a href="add-product.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Product
        </a>
    </div>
</div>

<!-- Filters -->
<div class="admin-card">
    <form method="GET" class="filter-form">
        <div class="filter-group">
            <input type="text" name="search" placeholder="Search products..." 
                   value="<?php echo htmlspecialchars($search); ?>" class="form-control">
        </div>
        
        <div class="filter-group">
            <select name="category" class="form-control">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['category_id']; ?>" 
                            <?php echo $category == $cat['category_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['category_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="filter-group">
            <select name="status" class="form-control">
                <option value="">All Status</option>
                <option value="1" <?php echo $status === '1' ? 'selected' : ''; ?>>Active</option>
                <option value="0" <?php echo $status === '0' ? 'selected' : ''; ?>>Inactive</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="products.php" class="btn btn-secondary">Clear</a>
    </form>
</div>

<!-- Products Table -->
<div class="admin-card">
    <div class="admin-card-body">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Product Name</th>
                        <th>SKU</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px;">No products found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <img src="../<?php echo $product['image_url'] ?? 'assets/images/no-image.jpg'; ?>" 
                                         alt="Product" class="product-thumbnail">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                                    <?php if ($product['brand']): ?>
                                        <div class="text-muted"><?php echo htmlspecialchars($product['brand']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($product['sku']); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                                <td><?php echo CURRENCY_SYMBOL . number_format($product['final_price'], 2); ?></td>
                                <td>
                                    <span class="stock-badge <?php echo $product['total_stock'] < 10 ? 'low-stock' : ''; ?>">
                                        <?php echo $product['total_stock'] ?? 0; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $product['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit-product.php?id=<?php echo $product['product_id']; ?>" 
                                           class="btn-icon" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="../product.php?id=<?php echo $product['product_id']; ?>" 
                                           target="_blank" class="btn-icon" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if (has_permission('inventory_manager')): ?>
                                            <a href="?delete=<?php echo $product['product_id']; ?>" 
                                               class="btn-icon btn-danger" title="Delete"
                                               onclick="return confirm('Are you sure you want to delete this product?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>