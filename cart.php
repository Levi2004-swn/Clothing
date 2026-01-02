<?php
require_once 'config.php';

$page_title = "Shopping Cart - " . SITE_NAME;

// Get cart items
$cart_items = [];
$subtotal = 0;

if (is_logged_in()) {
    $user_id = get_user_id();
    $sql = "SELECT ci.*, p.product_name, p.slug, p.final_price, pi.image_url, pv.size, pv.color, pv.stock_quantity
            FROM cart c
            JOIN cart_items ci ON c.cart_id = ci.cart_id
            JOIN products p ON ci.product_id = p.product_id
            LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
            LEFT JOIN product_variants pv ON ci.variant_id = pv.variant_id
            WHERE c.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
} else {
    $session_id = get_session_id();
    $sql = "SELECT ci.*, p.product_name, p.slug, p.final_price, pi.image_url, pv.size, pv.color, pv.stock_quantity
            FROM cart c
            JOIN cart_items ci ON c.cart_id = ci.cart_id
            JOIN products p ON ci.product_id = p.product_id
            LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
            LEFT JOIN product_variants pv ON ci.variant_id = pv.variant_id
            WHERE c.session_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $session_id);
}

$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $cart_items[] = $row;
    $subtotal += $row['price'] * $row['quantity'];
}

// Get site settings
$settings = [];
$result = $conn->query("SELECT setting_key, setting_value FROM site_settings");
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$tax_rate = floatval($settings['tax_rate'] ?? 10);
$shipping_fee = floatval($settings['shipping_fee'] ?? 5.99);
$free_shipping_threshold = floatval($settings['free_shipping_threshold'] ?? 50);

$tax_amount = ($subtotal * $tax_rate) / 100;
$shipping_amount = $subtotal >= $free_shipping_threshold ? 0 : $shipping_fee;
$total = $subtotal + $tax_amount + $shipping_amount;

$_SESSION['cart_summary'] = [
    'subtotal' => $subtotal,
    'tax_amount' => $tax_amount,
    'shipping_amount' => $shipping_amount,
    'total' => $total
];

include 'header.php';
?>

<div class="container" style="margin-top: 20px;">
    <h2 style="margin-bottom: 30px;">Shopping Cart</h2>

    <?php if (empty($cart_items)): ?>
        <div class="card" style="text-align: center; padding: 60px 20px;">
            <i class="fas fa-shopping-cart" style="font-size: 80px; color: #ddd; margin-bottom: 20px;"></i>
            <h3 style="margin-bottom: 10px;">Your cart is empty</h3>
            <p style="color: #666; margin-bottom: 30px;">Add some products to get started!</p>
            <a href="category.php" class="btn btn-primary">Start Shopping</a>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: 1fr 400px; gap: 30px;">
            <!-- Cart Items -->
            <div>
                <div class="card">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid #e5e5e5;">
                                <th style="padding: 15px; text-align: left; font-weight: 600;">Product</th>
                                <th style="padding: 15px; text-align: center; font-weight: 600;">Price</th>
                                <th style="padding: 15px; text-align: center; font-weight: 600;">Quantity</th>
                                <th style="padding: 15px; text-align: center; font-weight: 600;">Total</th>
                                <th style="padding: 15px; text-align: center; font-weight: 600;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_items as $item): ?>
                                <tr style="border-bottom: 1px solid #e5e5e5;" data-cart-item-id="<?php echo $item['cart_item_id']; ?>" data-price="<?php echo htmlspecialchars($item['price']); ?>" data-stock="<?php echo htmlspecialchars($item['stock_quantity'] ?? 999); ?>">
                                    <td style="padding: 20px;">
                                        <div style="display: flex; gap: 15px; align-items: center;">
                                            <img src="<?php echo $item['image_url'] ?? 'assets/images/no-image.jpg'; ?>" 
                                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                                 style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px;">
                                            <div>
                                                <a href="product.php?id=<?php echo $item['product_id']; ?>" 
                                                   style="font-weight: 600; color: #333; display: block; margin-bottom: 5px;">
                                                    <?php echo htmlspecialchars($item['product_name']); ?>
                                                </a>
                                                <?php if ($item['size'] || $item['color']): ?>
                                                    <div style="font-size: 13px; color: #666;">
                                                        <?php if ($item['size']): ?>
                                                            Size: <strong><?php echo htmlspecialchars($item['size']); ?></strong>
                                                        <?php endif; ?>
                                                        <?php if ($item['color']): ?>
                                                            <?php echo $item['size'] ? ' | ' : ''; ?>
                                                            Color: <strong><?php echo htmlspecialchars($item['color']); ?></strong>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding: 20px; text-align: center; font-weight: 600; color: #f53d2d;">
                                        <?php echo CURRENCY_SYMBOL . number_format($item['price'], 2); ?>
                                    </td>
                                    <td style="padding: 20px;">
                                        <div style="display: flex; align-items: center; gap: 5px; justify-content: center; border: 1px solid #ddd; border-radius: 4px; width: fit-content; margin: 0 auto;">
                                            <button class="cart-qty-btn" data-action="qty-dec" data-item-id="<?php echo $item['cart_item_id']; ?>" type="button" style="background: none; border: none; padding: 8px 12px; cursor: pointer;">-</button>
                                            <input class="cart-qty-input" type="number" value="<?php echo $item['quantity']; ?>" 
                                                   min="1" max="<?php echo $item['stock_quantity'] ?? 999; ?>"
                                                   style="width: 50px; text-align: center; border: none; font-weight: 600;"
                                                   readonly>
                                            <button class="cart-qty-btn" data-action="qty-inc" data-item-id="<?php echo $item['cart_item_id']; ?>" type="button" style="background: none; border: none; padding: 8px 12px; cursor: pointer;">+</button>
                                        </div>
                                    </td>
                                    <td class="cart-line-total" style="padding: 20px; text-align: center; font-weight: 700; color: #333; font-size: 18px;">
                                        <?php echo CURRENCY_SYMBOL . number_format($item['price'] * $item['quantity'], 2); ?>
                                    </td>
                                    <td style="padding: 20px; text-align: center;">
                                        <button class="cart-remove-btn" data-item-id="<?php echo $item['cart_item_id']; ?>" 
                                                style="background: none; border: none; color: #dc3545; cursor: pointer; font-size: 20px;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div style="padding: 20px; background: #f8f8f8; border-top: 1px solid #e5e5e5; display: flex; justify-content: space-between;">
                        <a href="category.php" style="color: #f53d2d; font-weight: 600;">
                            <i class="fas fa-arrow-left"></i> Continue Shopping
                        </a>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div>
                <div id="order-summary" class="card" style="position: sticky; top: 20px;" 
                     data-tax-rate="<?php echo $tax_rate; ?>" 
                     data-shipping-fee="<?php echo $shipping_fee; ?>" 
                     data-free-shipping-threshold="<?php echo $free_shipping_threshold; ?>" 
                     data-currency="<?php echo CURRENCY_SYMBOL; ?>">
                    <h3 style="margin-bottom: 20px; font-size: 20px; font-weight: 600;">Order Summary</h3>

                    <div style="border-top: 1px solid #e5e5e5; padding-top: 20px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                            <span style="color: #666;">Subtotal:</span>
                            <span id="summary-subtotal" style="font-weight: 600;"><?php echo CURRENCY_SYMBOL . number_format($subtotal, 2); ?></span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                            <span style="color: #666;">Tax (<?php echo $tax_rate; ?>%):</span>
                            <span id="summary-tax" style="font-weight: 600;"><?php echo CURRENCY_SYMBOL . number_format($tax_amount, 2); ?></span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                            <span style="color: #666;">Shipping:</span>
                            <span id="summary-shipping" style="font-weight: 600; color: <?php echo $shipping_amount == 0 ? '#4caf50' : '#333'; ?>;">
                                <?php echo $shipping_amount == 0 ? 'FREE' : CURRENCY_SYMBOL . number_format($shipping_amount, 2); ?>
                            </span>
                        </div>

                        <?php
                            $remaining_to_free = ($free_shipping_threshold > 0) ? max(0, $free_shipping_threshold - $subtotal) : 0;
                            $show_free_hint = $free_shipping_threshold > 0 && $remaining_to_free > 0;
                        ?>
                        <div id="free-shipping-hint" 
                             style="background: #fff3cd; padding: 12px; border-radius: 4px; margin: 15px 0; font-size: 13px; text-align: center; <?php echo $show_free_hint ? '' : 'display:none;'; ?>">
                            Add <span id="free-shipping-remaining"><?php echo CURRENCY_SYMBOL . number_format($remaining_to_free, 2); ?></span> more to get <strong>FREE shipping</strong>!
                        </div>

                        <div style="border-top: 2px solid #e5e5e5; margin-top: 15px; padding-top: 15px; display: flex; justify-content: space-between; font-size: 20px; font-weight: 700;">
                            <span>Total:</span>
                            <span id="summary-total" style="color: #f53d2d;"><?php echo CURRENCY_SYMBOL . number_format($total, 2); ?></span>
                        </div>
                    </div>

                    <a href="checkout.php" class="btn btn-primary btn-full" style="margin-top: 20px; padding: 15px; font-size: 16px; font-weight: 600;">
                        Proceed to Checkout
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>