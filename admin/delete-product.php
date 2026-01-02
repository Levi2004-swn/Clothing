<?php
require_once 'config.php';
require_admin_login();

// Only inventory managers (or higher) can delete products
if (!has_permission('inventory_manager')) {
    header('Location: products.php?error=unauthorized');
    exit;
}

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($product_id <= 0) {
    header('Location: products.php?error=invalid');
    exit;
}

// Helper: safely unlink if file exists
function __safe_unlink(string $path): void {
    try {
        if ($path !== '' && file_exists($path)) { @unlink($path); }
    } catch (Throwable $e) { /* ignore */ }
}

$redirect = 'products.php';

$conn->begin_transaction();
try {
    // Verify product exists
    $stmt = $conn->prepare('SELECT product_id, product_name FROM products WHERE product_id = ?');
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    if (!$product) {
        $conn->rollback();
        header('Location: ' . $redirect . '?error=not_found');
        exit;
    }

    // Check if any variants of this product are referenced by order_items
    $stmt = $conn->prepare('SELECT COUNT(*) AS cnt FROM order_items oi 
                            JOIN product_variants pv ON pv.variant_id = oi.variant_id 
                            WHERE pv.product_id = ?');
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $ref = $stmt->get_result()->fetch_assoc();
    $variants_in_use = intval($ref['cnt'] ?? 0) > 0;

    if ($variants_in_use) {
        // Product has sales history: mark inactive, preserve images and data; no deletion of media.
        $stmt = $conn->prepare('UPDATE products SET is_active = 0, updated_at = NOW() WHERE product_id = ?');
        $stmt->bind_param('i', $product_id);
        $stmt->execute();

        // Clean wishlist entries (so users don't keep it in active wishlists)
        $stmt = $conn->prepare('DELETE FROM wishlist WHERE product_id = ?');
        $stmt->bind_param('i', $product_id);
        $stmt->execute();

        // Remove any cart items referencing its variants
        $conn->query('DELETE ci FROM cart_items ci 
                      JOIN product_variants pv ON pv.variant_id = ci.variant_id 
                      WHERE pv.product_id = ' . (int)$product_id);

        // Prevent future sales by zeroing stock (retain variant rows for history)
        $stmt = $conn->prepare('UPDATE product_variants SET stock_quantity = 0 WHERE product_id = ?');
        $stmt->bind_param('i', $product_id);
        $stmt->execute();

        log_admin_activity('delete_product_blocked', 'Product (sales history; set inactive): ' . $product['product_name'] . ' (ID ' . $product_id . ')');
        $conn->commit();
        header('Location: ' . $redirect . '?error=cannot_delete');
        exit;
    }

    // No referenced variants -> proceed with hard delete of product and related records (but keep sales data)

    // Delete cart items for this product's variants
    $conn->query('DELETE ci FROM cart_items ci 
                  JOIN product_variants pv ON pv.variant_id = ci.variant_id 
                  WHERE pv.product_id = ' . (int)$product_id);

    // Delete wishlist entries for this product
    $conn->query('DELETE FROM wishlist WHERE product_id = ' . (int)$product_id);

    // Delete product reviews
    if ($conn->query('DELETE FROM product_reviews WHERE product_id = ' . (int)$product_id) === false) {
        // ignore if table not present
    }

    // Delete product images (files + records)
    $imgs = $conn->query('SELECT image_url FROM product_images WHERE product_id = ' . (int)$product_id);
    if ($imgs) {
        while ($img = $imgs->fetch_assoc()) {
            __safe_unlink(__DIR__ . '/../' . $img['image_url']);
        }
    }
    $conn->query('DELETE FROM product_images WHERE product_id = ' . (int)$product_id);

    // Delete variants (only if not referenced; join ensures we only attempt unreferenced)
    // Double-check safety: remove those not present in order_items
    $conn->query('DELETE pv FROM product_variants pv 
                  LEFT JOIN order_items oi ON oi.variant_id = pv.variant_id 
                  WHERE pv.product_id = ' . (int)$product_id . ' AND oi.variant_id IS NULL');

    // If any variants remain (should not, since none referenced), force delete product_variants now
    $left = $conn->query('SELECT COUNT(*) AS c FROM product_variants WHERE product_id = ' . (int)$product_id);
    $left_count = $left ? intval($left->fetch_assoc()['c'] ?? 0) : 0;
    if ($left_count > 0) {
        // Safety: cannot fully delete; mark inactive and preserve images.
        $stmt = $conn->prepare('UPDATE products SET is_active = 0, updated_at = NOW() WHERE product_id = ?');
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
        log_admin_activity('delete_product_blocked', 'Product (variants residual; set inactive): ' . $product['product_name'] . ' (ID ' . $product_id . ')');
        $conn->commit();
        header('Location: ' . $redirect . '?error=cannot_delete');
        exit;
    }

    // Finally, delete the product
    $stmt = $conn->prepare('DELETE FROM products WHERE product_id = ?');
    $stmt->bind_param('i', $product_id);
    $stmt->execute();

    log_admin_activity('delete_product', 'Product: ' . $product['product_name'] . ' (ID ' . $product_id . ')');
    $conn->commit();
    header('Location: ' . $redirect . '?success=deleted');
    exit;
} catch (Throwable $e) {
    $conn->rollback();
    header('Location: ' . $redirect . '?error=delete_failed');
    exit;
}
?>
