<?php
require_once 'config.php';

if (!is_logged_in()) {
    header('Location: ' . SITE_URL . '/login.php');
    exit;
}

$page_title = "My Addresses - " . SITE_NAME;
$user_id = get_user_id();
$error = '';
$success = '';

// Handle address addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_address'])) {
    $full_name = clean_input($_POST['full_name']);
    $phone = clean_input($_POST['phone']);
    $address_line1 = clean_input($_POST['address_line1']);
    $address_line2 = clean_input($_POST['address_line2']);
    $city = clean_input($_POST['city']);
    $state = clean_input($_POST['state']);
    $postal_code = clean_input($_POST['postal_code']);
    $country = clean_input($_POST['country']);
    $address_type = clean_input($_POST['address_type']);
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    
    if (empty($full_name) || empty($phone) || empty($address_line1) || empty($city) || empty($postal_code)) {
        $error = "Please fill in all required fields";
    } else {
        // If setting as default, unset other defaults
        if ($is_default) {
            $conn->query("UPDATE user_addresses SET is_default = 0 WHERE user_id = $user_id");
        }
        
        $stmt = $conn->prepare("INSERT INTO user_addresses (user_id, full_name, phone, address_line1, address_line2, city, state, postal_code, country, address_type, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssssssi", $user_id, $full_name, $phone, $address_line1, $address_line2, $city, $state, $postal_code, $country, $address_type, $is_default);
        
        if ($stmt->execute()) {
            $success = "Address added successfully!";
        } else {
            $error = "Failed to add address";
        }
    }
}

// Handle address deletion
if (isset($_GET['delete'])) {
    $address_id = intval($_GET['delete']);

    // Wrap in a transaction so we can safely detach references then delete
    $conn->begin_transaction();
    try {
        // 1) Detach from any orders that reference this address
        //    (shipping_address_id is nullable per schema, so this is safe)
        $stmt = $conn->prepare("UPDATE orders SET shipping_address_id = NULL WHERE shipping_address_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $address_id, $user_id);
        $stmt->execute();
        $stmt->close();

        // 2) Check if this address is currently the default
        $was_default = 0;
        $stmt = $conn->prepare("SELECT is_default FROM user_addresses WHERE address_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $address_id, $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $was_default = (int)($row['is_default'] ?? 0);
        }
        $stmt->close();

        // 3) Delete the address itself
        $stmt = $conn->prepare("DELETE FROM user_addresses WHERE address_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $address_id, $user_id);
        $stmt->execute();
        $deleted = $stmt->affected_rows > 0;
        $stmt->close();

        // 4) If it was default and there are other addresses, promote one to default
        if ($deleted && $was_default) {
            $stmt = $conn->prepare("SELECT address_id FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC LIMIT 1");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($next = $res->fetch_assoc()) {
                $new_default_id = (int)$next['address_id'];
                // Ensure only this one is default
                $conn->query("UPDATE user_addresses SET is_default = 0 WHERE user_id = " . (int)$user_id);
                $stmt2 = $conn->prepare("UPDATE user_addresses SET is_default = 1 WHERE address_id = ? AND user_id = ?");
                $stmt2->bind_param("ii", $new_default_id, $user_id);
                $stmt2->execute();
                $stmt2->close();
            }
            $stmt->close();
        }

        $conn->commit();
        $success = "Address deleted successfully!";

        // Optional: redirect to clear the query string and avoid accidental repeats
        header('Location: ' . SITE_URL . '/addresses.php?success=deleted');
        exit;
    } catch (Throwable $e) {
        $conn->rollback();
        $error = "Could not delete address. Please try again.";
    }
}

// Handle set default
if (isset($_GET['set_default'])) {
    $address_id = intval($_GET['set_default']);
    $conn->query("UPDATE user_addresses SET is_default = 0 WHERE user_id = $user_id");
    $stmt = $conn->prepare("UPDATE user_addresses SET is_default = 1 WHERE address_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $address_id, $user_id);
    if ($stmt->execute()) {
        $success = "Default address updated!";
    }
}

// Get all addresses
$addresses = [];
$result = $conn->query("SELECT * FROM user_addresses WHERE user_id = $user_id ORDER BY is_default DESC, created_at DESC");
while ($row = $result->fetch_assoc()) {
    $addresses[] = $row;
}

include 'header.php';
?>

<div class="container" style="margin-top: 20px;">
    <h2 style="margin-bottom: 30px;">My Addresses</h2>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
        <!-- Address List -->
        <div>
            <h3 style="margin-bottom: 20px; font-size: 18px; font-weight: 600;">Saved Addresses</h3>
            
            <?php if (empty($addresses)): ?>
                <div class="card" style="text-align: center; padding: 40px;">
                    <i class="fas fa-map-marker-alt" style="font-size: 48px; color: #ddd; margin-bottom: 15px;"></i>
                    <p style="color: #666;">No addresses saved yet</p>
                </div>
            <?php else: ?>
                <?php foreach ($addresses as $address): ?>
                    <div class="card" style="margin-bottom: 15px; position: relative;">
                        <?php if ($address['is_default']): ?>
                            <span style="position: absolute; top: 15px; right: 15px; background: #f53d2d; color: white; padding: 3px 10px; border-radius: 3px; font-size: 11px; font-weight: 600;">
                                DEFAULT
                            </span>
                        <?php endif; ?>
                        
                        <div style="margin-bottom: 10px;">
                            <strong style="font-size: 16px;"><?php echo htmlspecialchars($address['full_name']); ?></strong>
                            <span style="background: #f5f5f5; padding: 2px 8px; border-radius: 3px; font-size: 12px; margin-left: 8px;">
                                <?php echo strtoupper($address['address_type']); ?>
                            </span>
                        </div>
                        
                        <div style="color: #666; line-height: 1.6; margin-bottom: 15px;">
                            <?php echo htmlspecialchars($address['address_line1']); ?><br>
                            <?php if ($address['address_line2']): ?>
                                <?php echo htmlspecialchars($address['address_line2']); ?><br>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($address['city']); ?>, <?php echo htmlspecialchars($address['state']); ?> <?php echo htmlspecialchars($address['postal_code']); ?><br>
                            <?php echo htmlspecialchars($address['country']); ?><br>
                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($address['phone']); ?>
                        </div>
                        
                        <div style="display: flex; gap: 10px; padding-top: 15px; border-top: 1px solid #e5e5e5;">
                            <?php if (!$address['is_default']): ?>
                                <a href="?set_default=<?php echo $address['address_id']; ?>" class="btn btn-secondary" style="padding: 8px 15px; font-size: 14px;">
                                    Set as Default
                                </a>
                            <?php endif; ?>
                            <a href="?delete=<?php echo $address['address_id']; ?>" 
                               onclick="return confirm('Are you sure you want to delete this address?')" 
                               class="btn btn-secondary" style="padding: 8px 15px; font-size: 14px;">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Add New Address Form -->
        <div>
            <div class="card">
                <h3 style="margin-bottom: 20px; font-size: 18px; font-weight: 600;">Add New Address</h3>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Phone Number *</label>
                        <input type="tel" name="phone" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Address Line 1 *</label>
                        <input type="text" name="address_line1" class="form-control" required 
                               placeholder="House number, street name">
                    </div>
                    
                    <div class="form-group">
                        <label>Address Line 2</label>
                        <input type="text" name="address_line2" class="form-control" 
                               placeholder="Apartment, suite, unit, etc. (optional)">
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>City *</label>
                            <input type="text" name="city" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>State/Province</label>
                            <input type="text" name="state" class="form-control">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Postal Code *</label>
                            <input type="text" name="postal_code" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Country</label>
                            <input type="text" name="country" class="form-control" value="United States">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Address Type</label>
                        <select name="address_type" class="form-control">
                            <option value="home">Home</option>
                            <option value="office">Office</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; cursor: pointer;">
                            <input type="checkbox" name="is_default" style="margin-right: 8px;">
                            Set as default address
                        </label>
                    </div>
                    
                    <button type="submit" name="add_address" class="btn btn-primary btn-full">
                        <i class="fas fa-plus"></i> Add Address
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>