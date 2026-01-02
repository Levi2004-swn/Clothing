<?php
require_once 'config.php';
require_admin_login();

// Get order ID
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$order_id) {
    header('Location: orders.php');
    exit;
}

// Fetch order details
$stmt = $conn->prepare("SELECT o.*, u.first_name, u.last_name, u.email, u.phone
                       FROM orders o
                       JOIN users u ON o.user_id = u.user_id
                       WHERE o.order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: orders.php');
    exit;
}

// Fetch order items
$order_items = [];
$stmt = $conn->prepare("SELECT oi.*, p.product_name, pi.image_url, pv.size, pv.color
                       FROM order_items oi
                       JOIN products p ON oi.product_id = p.product_id
                       LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                       LEFT JOIN product_variants pv ON oi.variant_id = pv.variant_id
                       WHERE oi.order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $order_items[] = $row;
}

// Calculate values with fallbacks
$subtotal = isset($order['subtotal']) ? floatval($order['subtotal']) : 0;
$discount = isset($order['discount_amount']) ? floatval($order['discount_amount']) : 0;
$tax = isset($order['tax_amount']) ? floatval($order['tax_amount']) : 0;
$shipping = isset($order['shipping_fee']) ? floatval($order['shipping_fee']) : 0;
$total = isset($order['total_amount']) ? floatval($order['total_amount']) : 0;

// If subtotal is 0, calculate from items
if ($subtotal == 0) {
    foreach ($order_items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
}

// If total is 0, calculate it
if ($total == 0) {
    $total = $subtotal - $discount + $tax + $shipping;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $order['order_number']; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
            padding: 20px;
        }

        .invoice-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        /* Header */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 30px;
            border-bottom: 3px solid #ee4d2d;
            margin-bottom: 30px;
        }

        .company-info {
            flex: 1;
        }

        .company-name {
            font-size: 32px;
            font-weight: 700;
            color: #ee4d2d;
            margin-bottom: 10px;
        }

        .company-details {
            font-size: 14px;
            color: #666;
            line-height: 1.8;
        }

        .invoice-title {
            text-align: right;
        }

        .invoice-title h1 {
            font-size: 36px;
            color: #333;
            margin-bottom: 10px;
        }

        .invoice-number {
            font-size: 16px;
            color: #666;
            margin-bottom: 5px;
        }

        .invoice-date {
            font-size: 14px;
            color: #999;
        }

        /* Info Sections */
        .invoice-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        .info-box {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #ee4d2d;
        }

        .info-box h3 {
            font-size: 14px;
            color: #ee4d2d;
            text-transform: uppercase;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .info-box p {
            font-size: 14px;
            color: #666;
            margin: 8px 0;
            line-height: 1.6;
        }

        .info-box strong {
            color: #333;
            font-size: 16px;
        }

        /* Items Table */
        .invoice-items {
            margin-bottom: 30px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .items-table thead {
            background: #f5f5f5;
        }

        .items-table th {
            padding: 12px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
            color: #666;
            font-weight: 600;
            border-bottom: 2px solid #e5e5e5;
        }

        .items-table th:last-child,
        .items-table td:last-child {
            text-align: right;
        }

        .items-table tbody tr {
            border-bottom: 1px solid #f0f0f0;
        }

        .items-table td {
            padding: 15px 12px;
            font-size: 14px;
            color: #333;
        }

        .item-name {
            font-weight: 600;
            color: #333;
        }

        .item-variant {
            font-size: 12px;
            color: #999;
            margin-top: 4px;
        }

        /* Totals */
        .invoice-totals {
            margin-left: auto;
            width: 350px;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 14px;
            color: #666;
        }

        .total-row.subtotal {
            border-bottom: 1px solid #e5e5e5;
        }

        .total-row.discount {
            color: #26aa99;
        }

        .total-row.grand-total {
            border-top: 2px solid #e5e5e5;
            padding-top: 15px;
            margin-top: 10px;
            font-size: 18px;
            font-weight: 700;
            color: #ee4d2d;
        }

        /* Payment Info */
        .payment-info {
            background: #fff3cd;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #ffc107;
            margin-bottom: 30px;
        }

        .payment-info h3 {
            font-size: 14px;
            color: #856404;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .payment-status {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .payment-status.paid {
            background: #d4edda;
            color: #155724;
        }

        .payment-status.pending {
            background: #fff3cd;
            color: #856404;
        }

        /* Footer */
        .invoice-footer {
            text-align: center;
            padding-top: 30px;
            border-top: 2px solid #e5e5e5;
            color: #999;
            font-size: 13px;
        }

        .invoice-footer p {
            margin: 5px 0;
        }

        .invoice-footer strong {
            color: #666;
        }

        /* Notes */
        .invoice-notes {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .invoice-notes h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .invoice-notes p {
            font-size: 13px;
            color: #999;
            line-height: 1.6;
        }

        /* Print Button */
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #ee4d2d;
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            border: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(238, 77, 45, 0.3);
            transition: all 0.3s;
            z-index: 1000;
        }

        .print-button:hover {
            background: #d73211;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(238, 77, 45, 0.4);
        }

        .print-button i {
            margin-right: 8px;
        }

        /* Print Styles */
        @media print {
            body {
                background: white;
                padding: 0;
            }

            .invoice-container {
                box-shadow: none;
                padding: 20px;
            }

            .print-button {
                display: none;
            }

            .invoice-header {
                border-bottom: 2px solid #ee4d2d;
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .invoice-container {
                padding: 20px;
            }

            .invoice-header {
                flex-direction: column;
            }

            .invoice-title {
                text-align: left;
                margin-top: 20px;
            }

            .invoice-info {
                grid-template-columns: 1fr;
            }

            .invoice-totals {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<button class="print-button" onclick="window.print()">
    <i class="fas fa-print"></i> Print Invoice
</button>

<div class="invoice-container">
    <!-- Header -->
    <div class="invoice-header">
        <div class="company-info">
            <div class="company-name">
                <i class="fas fa-shopping-bag"></i> <?php echo SITE_NAME; ?>
            </div>
            <div class="company-details">
                <p><strong>Address:</strong> 123 Fashion Street, Yangon, Myanmar</p>
                <p><strong>Phone:</strong> +95 9 123 456 789</p>
                <p><strong>Email:</strong> contact@clothingstore.com</p>
                <p><strong>Website:</strong> www.clothingstore.com</p>
            </div>
        </div>
        <div class="invoice-title">
            <h1>INVOICE</h1>
            <p class="invoice-number"><strong>#<?php echo $order['order_number']; ?></strong></p>
            <p class="invoice-date">Date: <?php echo date('F d, Y', strtotime($order['created_at'])); ?></p>
        </div>
    </div>

    <!-- Bill To & Ship To -->
    <div class="invoice-info">
        <div class="info-box">
            <h3><i class="fas fa-user"></i> Bill To</h3>
            <p><strong><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></strong></p>
            <p><?php echo htmlspecialchars($order['email']); ?></p>
            <?php if (!empty($order['phone'])): ?>
                <p><?php echo htmlspecialchars($order['phone']); ?></p>
            <?php endif; ?>
        </div>
        <div class="info-box">
            <h3><i class="fas fa-shipping-fast"></i> Ship To</h3>
            <p><strong><?php echo htmlspecialchars($order['shipping_name'] ?? $order['first_name'] . ' ' . $order['last_name']); ?></strong></p>
            <?php if (!empty($order['shipping_address'])): ?>
                <p><?php echo htmlspecialchars($order['shipping_address']); ?></p>
            <?php endif; ?>
            <?php if (!empty($order['shipping_city']) || !empty($order['shipping_state']) || !empty($order['shipping_zip'])): ?>
                <p>
                    <?php echo htmlspecialchars($order['shipping_city'] ?? ''); ?>
                    <?php echo !empty($order['shipping_state']) ? ', ' . htmlspecialchars($order['shipping_state']) : ''; ?>
                    <?php echo !empty($order['shipping_zip']) ? ' ' . htmlspecialchars($order['shipping_zip']) : ''; ?>
                </p>
            <?php endif; ?>
            <?php if (!empty($order['shipping_phone'])): ?>
                <p><?php echo htmlspecialchars($order['shipping_phone']); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Payment Status -->
    <div class="payment-info">
        <h3><i class="fas fa-credit-card"></i> Payment Information</h3>
        <p>
            <strong>Payment Method:</strong> <?php echo ucfirst($order['payment_method']); ?> 
            &nbsp;&nbsp;|&nbsp;&nbsp;
            <strong>Status:</strong> 
            <span class="payment-status <?php echo $order['payment_status']; ?>">
                <?php echo ucfirst($order['payment_status']); ?>
            </span>
            &nbsp;&nbsp;|&nbsp;&nbsp;
            <strong>Order Status:</strong> <?php echo ucfirst($order['order_status']); ?>
        </p>
    </div>

    <!-- Items Table -->
    <div class="invoice-items">
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Item Description</th>
                    <th style="width: 15%;">Unit Price</th>
                    <th style="width: 15%;">Quantity</th>
                    <th style="width: 20%;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order_items as $item): ?>
                    <tr>
                        <td>
                            <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                            <?php if (!empty($item['size']) || !empty($item['color'])): ?>
                                <div class="item-variant">
                                    <?php if (!empty($item['size'])): ?>
                                        Size: <?php echo htmlspecialchars($item['size']); ?>
                                    <?php endif; ?>
                                    <?php if (!empty($item['color'])): ?>
                                        <?php echo !empty($item['size']) ? ' | ' : ''; ?>
                                        Color: <?php echo htmlspecialchars($item['color']); ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo CURRENCY_SYMBOL . number_format($item['price'], 2); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td><strong><?php echo CURRENCY_SYMBOL . number_format($item['price'] * $item['quantity'], 2); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Totals (FIXED) -->
    <div class="invoice-totals">
        <div class="total-row subtotal">
            <span>Subtotal:</span>
            <span><?php echo CURRENCY_SYMBOL . number_format($subtotal, 2); ?></span>
        </div>
        <?php if ($discount > 0): ?>
            <div class="total-row discount">
                <span>Discount:</span>
                <span>-<?php echo CURRENCY_SYMBOL . number_format($discount, 2); ?></span>
            </div>
        <?php endif; ?>
        <?php if ($tax > 0): ?>
            <div class="total-row">
                <span>Tax:</span>
                <span><?php echo CURRENCY_SYMBOL . number_format($tax, 2); ?></span>
            </div>
        <?php endif; ?>
        <?php if ($shipping > 0): ?>
            <div class="total-row">
                <span>Shipping:</span>
                <span><?php echo CURRENCY_SYMBOL . number_format($shipping, 2); ?></span>
            </div>
        <?php endif; ?>
        <div class="total-row grand-total">
            <span>TOTAL:</span>
            <span><?php echo CURRENCY_SYMBOL . number_format($total, 2); ?></span>
        </div>
    </div>

    <!-- Notes -->
    <div class="invoice-notes">
        <h3><i class="fas fa-sticky-note"></i> Notes & Terms</h3>
        <p>Thank you for your business! Payment is due within 15 days. Please make checks payable to <?php echo SITE_NAME; ?>. If you have any questions concerning this invoice, please contact us at contact@clothingstore.com or +95 9 123 456 789.</p>
    </div>

    <!-- Footer -->
    <div class="invoice-footer">
        <p><strong><?php echo SITE_NAME; ?></strong></p>
        <p>This is a computer-generated invoice and does not require a signature.</p>
        <p>Generated on <?php echo date('F d, Y g:i A'); ?> by <?php echo get_admin_username(); ?></p>
    </div>
</div>

<script>
// Print function
function printInvoice() {
    window.print();
}
</script>

</body>
</html>