<?php
require_once 'config.php';
require_admin_login(); // Protect this page

$page_title = "Content Management";

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_message_status':
                $message_id = intval($_POST['message_id']);
                $status = $_POST['status'];
                $stmt = $conn->prepare("UPDATE contact_messages SET status = ? WHERE message_id = ?");
                $stmt->bind_param("si", $status, $message_id);
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Message status updated successfully";
                } else {
                    $_SESSION['error'] = "Failed to update message status";
                }
                $stmt->close();
                header("Location: content.php?tab=messages");
                exit;
                
            case 'delete_message':
                $message_id = intval($_POST['message_id']);
                $stmt = $conn->prepare("DELETE FROM contact_messages WHERE message_id = ?");
                $stmt->bind_param("i", $message_id);
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Message deleted successfully";
                } else {
                    $_SESSION['error'] = "Failed to delete message";
                }
                $stmt->close();
                header("Location: content.php?tab=messages");
                exit;
                
            case 'delete_subscriber':
                $subscriber_id = intval($_POST['subscriber_id']);
                $stmt = $conn->prepare("DELETE FROM newsletter_subscribers WHERE subscriber_id = ?");
                $stmt->bind_param("i", $subscriber_id);
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Subscriber deleted successfully";
                } else {
                    $_SESSION['error'] = "Failed to delete subscriber";
                }
                $stmt->close();
                header("Location: content.php?tab=subscribers");
                exit;
                
            case 'update_review_status':
                $review_id = intval($_POST['review_id']);
                $stmt = $conn->prepare("DELETE FROM product_reviews WHERE review_id = ?");
                $stmt->bind_param("i", $review_id);
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Review deleted successfully";
                } else {
                    $_SESSION['error'] = "Failed to delete review";
                }
                $stmt->close();
                header("Location: content.php?tab=reviews");
                exit;
        }
    }
}

// Get active tab
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'messages';

// Get statistics
$stats = [];

// Contact Messages Stats
$result = $conn->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_messages,
    SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read_messages,
    SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END) as replied_messages
    FROM contact_messages");
$stats['messages'] = $result->fetch_assoc();

// Newsletter Subscribers Stats
$result = $conn->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_subscribers
    FROM newsletter_subscribers");
$stats['subscribers'] = $result->fetch_assoc();

// Reviews Stats
$result = $conn->query("SELECT 
    COUNT(*) as total_reviews,
    AVG(rating) as avg_rating,
    SUM(CASE WHEN is_verified_purchase = 1 THEN 1 ELSE 0 END) as verified_reviews
    FROM product_reviews");
$stats['reviews'] = $result->fetch_assoc();

// Get contact messages
$contact_messages = [];
if ($active_tab === 'messages') {
    $result = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
    while ($row = $result->fetch_assoc()) {
        $contact_messages[] = $row;
    }
}

// Get newsletter subscribers
$subscribers = [];
if ($active_tab === 'subscribers') {
    $result = $conn->query("SELECT * FROM newsletter_subscribers ORDER BY subscribed_at DESC");
    while ($row = $result->fetch_assoc()) {
        $subscribers[] = $row;
    }
}

// Get product reviews
$reviews = [];
if ($active_tab === 'reviews') {
    $result = $conn->query("SELECT r.*, u.first_name, u.last_name, u.email, p.product_name 
                           FROM product_reviews r
                           JOIN users u ON r.user_id = u.user_id
                           JOIN products p ON r.product_id = p.product_id
                           ORDER BY r.created_at DESC");
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="admin-header">
    <h1>Content Management</h1>
    <div class="admin-header-actions">
        <span class="last-update">Last updated: <?php echo date('M d, Y - g:i A'); ?></span>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <?php 
        echo $_SESSION['success']; 
        unset($_SESSION['success']);
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-error">
        <?php 
        echo $_SESSION['error']; 
        unset($_SESSION['error']);
        ?>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card bg-blue">
        <div class="stat-icon">
            <i class="fas fa-envelope"></i>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?php echo number_format($stats['messages']['total'] ?? 0); ?></div>
            <div class="stat-label">Total Messages</div>
            <div class="stat-detail"><?php echo $stats['messages']['new_messages'] ?? 0; ?> new</div>
        </div>
    </div>
    
    <div class="stat-card bg-green">
        <div class="stat-icon">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?php echo number_format($stats['subscribers']['total'] ?? 0); ?></div>
            <div class="stat-label">Newsletter Subscribers</div>
            <div class="stat-detail"><?php echo $stats['subscribers']['active_subscribers'] ?? 0; ?> active</div>
        </div>
    </div>
    
    <div class="stat-card bg-purple">
        <div class="stat-icon">
            <i class="fas fa-star"></i>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?php echo number_format($stats['reviews']['total_reviews'] ?? 0); ?></div>
            <div class="stat-label">Product Reviews</div>
            <div class="stat-detail"><?php echo number_format($stats['reviews']['avg_rating'] ?? 0, 1); ?> avg rating</div>
        </div>
    </div>
    
    <div class="stat-card bg-orange">
        <div class="stat-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?php echo number_format($stats['reviews']['verified_reviews'] ?? 0); ?></div>
            <div class="stat-label">Verified Reviews</div>
        </div>
    </div>
</div>

<!-- Tab Navigation -->
<div class="admin-tabs">
    <a href="content.php?tab=messages" class="admin-tab <?php echo $active_tab === 'messages' ? 'active' : ''; ?>">
        <i class="fas fa-envelope"></i> Contact Messages
        <?php if ($stats['messages']['new_messages'] > 0): ?>
            <span class="badge"><?php echo $stats['messages']['new_messages']; ?></span>
        <?php endif; ?>
    </a>
    <a href="content.php?tab=subscribers" class="admin-tab <?php echo $active_tab === 'subscribers' ? 'active' : ''; ?>">
        <i class="fas fa-users"></i> Newsletter Subscribers
    </a>
    <a href="content.php?tab=reviews" class="admin-tab <?php echo $active_tab === 'reviews' ? 'active' : ''; ?>">
        <i class="fas fa-star"></i> Product Reviews
    </a>
</div>

<!-- Contact Messages Tab -->
<?php if ($active_tab === 'messages'): ?>
<div class="admin-card">
    <div class="admin-card-header">
        <h3>Contact Form Messages</h3>
    </div>
    <div class="admin-card-body">
        <?php if (empty($contact_messages)): ?>
            <div class="empty-state">
                <i class="fas fa-envelope" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                <p>No contact messages yet</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contact_messages as $message): ?>
                            <tr class="<?php echo $message['status'] === 'new' ? 'highlight-row' : ''; ?>">
                                <td><?php echo $message['message_id']; ?></td>
                                <td><?php echo htmlspecialchars($message['name']); ?></td>
                                <td><?php echo htmlspecialchars($message['email']); ?></td>
                                <td><?php echo htmlspecialchars($message['subject'] ?? 'No subject'); ?></td>
                                <td>
                                    <div class="message-preview" title="<?php echo htmlspecialchars($message['message']); ?>">
                                        <?php echo htmlspecialchars(substr($message['message'], 0, 50)) . (strlen($message['message']) > 50 ? '...' : ''); ?>
                                    </div>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update_message_status">
                                        <input type="hidden" name="message_id" value="<?php echo $message['message_id']; ?>">
                                        <select name="status" class="status-select" onchange="this.form.submit()">
                                            <option value="new" <?php echo $message['status'] === 'new' ? 'selected' : ''; ?>>New</option>
                                            <option value="read" <?php echo $message['status'] === 'read' ? 'selected' : ''; ?>>Read</option>
                                            <option value="replied" <?php echo $message['status'] === 'replied' ? 'selected' : ''; ?>>Replied</option>
                                        </select>
                                    </form>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($message['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>" class="btn btn-sm btn-primary" title="Reply">
                                            <i class="fas fa-reply"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-info" onclick="viewMessage(<?php echo $message['message_id']; ?>)" title="View Full Message">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this message?');">
                                            <input type="hidden" name="action" value="delete_message">
                                            <input type="hidden" name="message_id" value="<?php echo $message['message_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Newsletter Subscribers Tab -->
<?php if ($active_tab === 'subscribers'): ?>
<div class="admin-card">
    <div class="admin-card-header">
        <h3>Newsletter Subscribers</h3>
        <button class="btn btn-primary" onclick="exportSubscribers()">
            <i class="fas fa-download"></i> Export List
        </button>
    </div>
    <div class="admin-card-body">
        <?php if (empty($subscribers)): ?>
            <div class="empty-state">
                <i class="fas fa-users" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                <p>No newsletter subscribers yet</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Subscribed Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subscribers as $subscriber): ?>
                            <tr>
                                <td><?php echo $subscriber['subscriber_id']; ?></td>
                                <td><?php echo htmlspecialchars($subscriber['email']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $subscriber['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $subscriber['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($subscriber['subscribed_at'])); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this subscriber?');">
                                        <input type="hidden" name="action" value="delete_subscriber">
                                        <input type="hidden" name="subscriber_id" value="<?php echo $subscriber['subscriber_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Product Reviews Tab -->
<?php if ($active_tab === 'reviews'): ?>
<div class="admin-card">
    <div class="admin-card-header">
        <h3>Product Reviews</h3>
    </div>
    <div class="admin-card-body">
        <?php if (empty($reviews)): ?>
            <div class="empty-state">
                <i class="fas fa-star" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                <p>No product reviews yet</p>
            </div>
        <?php else: ?>
            <div class="reviews-list">
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div class="review-product">
                                <strong><?php echo htmlspecialchars($review['product_name']); ?></strong>
                            </div>
                            <div class="review-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'star-filled' : 'star-empty'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="review-meta">
                            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></span>
                            <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($review['email']); ?></span>
                            <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                            <?php if ($review['is_verified_purchase']): ?>
                                <span class="verified-badge"><i class="fas fa-check-circle"></i> Verified Purchase</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($review['review_title']): ?>
                            <h4 class="review-title"><?php echo htmlspecialchars($review['review_title']); ?></h4>
                        <?php endif; ?>
                        <div class="review-text">
                            <?php echo nl2br(htmlspecialchars($review['review_text'])); ?>
                        </div>
                        <div class="review-actions">
                            <a href="../product.php?id=<?php echo $review['product_id']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                <i class="fas fa-external-link-alt"></i> View Product
                            </a>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this review?');">
                                <input type="hidden" name="action" value="update_review_status">
                                <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash"></i> Delete Review
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Message View Modal -->
<div id="messageModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeMessageModal()">&times;</span>
        <h2>Message Details</h2>
        <div id="messageContent"></div>
    </div>
</div>

<style>
.admin-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 30px;
    border-bottom: 2px solid #e0e0e0;
}

.admin-tab {
    padding: 15px 25px;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    color: #666;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.admin-tab:hover {
    color: #333;
    background: #f8f9fa;
}

.admin-tab.active {
    color: #2563eb;
    border-bottom-color: #2563eb;
    font-weight: 600;
}

.admin-tab .badge {
    background: #ef4444;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: bold;
}

.message-preview {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.highlight-row {
    background-color: #fffbeb !important;
}

.status-select {
    padding: 5px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    cursor: pointer;
}

.action-buttons {
    display: flex;
    gap: 5px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.review-card {
    background: #f8f9fa;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.review-rating {
    display: flex;
    gap: 2px;
}

.star-filled {
    color: #fbbf24;
}

.star-empty {
    color: #e5e7eb;
}

.review-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    color: #666;
    font-size: 14px;
    margin-bottom: 15px;
}

.verified-badge {
    color: #10b981;
    font-weight: 600;
}

.review-title {
    margin-bottom: 10px;
    color: #333;
}

.review-text {
    margin-bottom: 15px;
    line-height: 1.6;
}

.review-actions {
    display: flex;
    gap: 10px;
    padding-top: 15px;
    border-top: 1px solid #e0e0e0;
}

.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fff;
    margin: 5% auto;
    padding: 30px;
    border-radius: 8px;
    width: 80%;
    max-width: 600px;
    position: relative;
}

.close {
    position: absolute;
    right: 20px;
    top: 15px;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    color: #999;
}

.close:hover {
    color: #333;
}

.stat-detail {
    font-size: 12px;
    color: rgba(255,255,255,0.8);
    margin-top: 5px;
}
</style>

<script>
function viewMessage(messageId) {
    // In a real application, you would fetch the full message via AJAX
    // For now, we'll show the message from the table
    const modal = document.getElementById('messageModal');
    modal.style.display = 'block';
    
    // You would fetch message details here
    document.getElementById('messageContent').innerHTML = '<p>Loading message details...</p>';
}

function closeMessageModal() {
    document.getElementById('messageModal').style.display = 'none';
}

function exportSubscribers() {
    // Create CSV export
    window.location.href = 'export-subscribers.php';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('messageModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}
</script>

<?php include 'includes/footer.php'; ?>