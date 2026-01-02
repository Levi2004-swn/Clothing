<?php
require_once 'config.php';

$page_title = "Contact Us - " . SITE_NAME;
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = clean_input($_POST['name']);
    $email = clean_input($_POST['email']);
    $subject = clean_input($_POST['subject']);
    $message = clean_input($_POST['message']);
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $subject, $message);
        
        if ($stmt->execute()) {
            $success = "Thank you for contacting us! We'll get back to you within 24 hours.";
            $_POST = [];
        } else {
            $error = "Failed to send message. Please try again.";
        }
    }
}

include 'header.php';
?>

<div class="container" style="margin-top: 40px; margin-bottom: 60px;">
    <h2 style="text-align: center; margin-bottom: 40px;">Contact Us</h2>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; max-width: 1000px; margin: 0 auto;">
        <div class="card">
            <h3 style="margin-bottom: 20px;">Send Us a Message</h3>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Your Name *</label>
                    <input type="text" name="name" class="form-control" required 
                           value="<?php echo $_POST['name'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Your Email *</label>
                    <input type="email" name="email" class="form-control" required 
                           value="<?php echo $_POST['email'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Subject *</label>
                    <select name="subject" class="form-control" required>
                        <option value="">Select Subject</option>
                        <option value="Order Inquiry">Order Inquiry</option>
                        <option value="Product Question">Product Question</option>
                        <option value="Shipping Issue">Shipping Issue</option>
                        <option value="Return/Refund">Return/Refund</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Message *</label>
                    <textarea name="message" class="form-control" rows="6" required><?php echo $_POST['message'] ?? ''; ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary btn-full">
                    <i class="fas fa-paper-plane"></i> Send Message
                </button>
            </form>
        </div>

        <div>
            <div class="card">
                <h3 style="margin-bottom: 20px;">Get in Touch</h3>
                
                <div style="display: grid; gap: 25px;">
                    <div style="display: flex; gap: 15px;">
                        <div style="width: 50px; height: 50px; background: #f53d2d; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fas fa-map-marker-alt" style="color: white; font-size: 20px;"></i>
                        </div>
                        <div>
                            <strong style="display: block; margin-bottom: 5px;">Visit Us</strong>
                            <p style="color: #666; line-height: 1.6; margin: 0;">
                                123 Fashion Street<br>
                                New York, NY 10001<br>
                                United States
                            </p>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 15px;">
                        <div style="width: 50px; height: 50px; background: #f53d2d; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fas fa-phone" style="color: white; font-size: 20px;"></i>
                        </div>
                        <div>
                            <strong style="display: block; margin-bottom: 5px;">Call Us</strong>
                            <p style="color: #666; line-height: 1.6; margin: 0;">
                                +1 (555) 123-4567<br>
                                Mon-Fri: 9AM - 6PM EST
                            </p>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 15px;">
                        <div style="width: 50px; height: 50px; background: #f53d2d; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fas fa-envelope" style="color: white; font-size: 20px;"></i>
                        </div>
                        <div>
                            <strong style="display: block; margin-bottom: 5px;">Email Us</strong>
                            <p style="color: #666; line-height: 1.6; margin: 0;">
                                support@clothingstore.com
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-top: 20px;">
                <h3 style="margin-bottom: 20px;">FAQ</h3>
                
                <details style="border: 1px solid #e5e5e5; border-radius: 4px; padding: 15px; margin-bottom: 10px;">
                    <summary style="cursor: pointer; font-weight: 600;">How do I track my order?</summary>
                    <p style="margin-top: 10px; color: #666;">
                        You can track your order from the "My Orders" section in your account.
                    </p>
                </details>
                
                <details style="border: 1px solid #e5e5e5; border-radius: 4px; padding: 15px; margin-bottom: 10px;">
                    <summary style="cursor: pointer; font-weight: 600;">What is your return policy?</summary>
                    <p style="margin-top: 10px; color: #666;">
                        We offer a 30-day return policy for all unused items with tags attached.
                    </p>
                </details>
                
                <details style="border: 1px solid #e5e5e5; border-radius: 4px; padding: 15px;">
                    <summary style="cursor: pointer; font-weight: 600;">Do you offer international shipping?</summary>
                    <p style="margin-top: 10px; color: #666;">
                        Yes! We ship to over 100 countries worldwide.
                    </p>
                </details>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>