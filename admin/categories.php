<?php
require_once 'config.php';
require_admin_login();

$page_title = "Categories Management";
$success = '';
$error = '';

// Handle category deletion
if (isset($_GET['delete']) && has_permission('inventory_manager')) {
    $category_id = intval($_GET['delete']);
    
    // Check if category has products
    $check = $conn->query("SELECT COUNT(*) as count FROM products WHERE category_id = $category_id");
    $count = $check->fetch_assoc()['count'];
    
    if ($count > 0) {
        $error = "Cannot delete category with existing products. Please reassign or delete products first.";
    } else {
        $conn->query("DELETE FROM categories WHERE category_id = $category_id");
        log_admin_activity('delete_category', "Deleted category ID: $category_id");
        $success = "Category deleted successfully!";
    }
}

// Handle category add/edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category_name = clean_input($_POST['category_name']);
    $description = clean_input($_POST['description']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    
    if (empty($category_name)) {
        $error = "Category name is required";
    } else {
        if ($category_id > 0) {
            // Update existing category (FIXED - removed updated_at)
            $stmt = $conn->prepare("UPDATE categories SET category_name = ?, description = ?, is_active = ? WHERE category_id = ?");
            $stmt->bind_param("ssii", $category_name, $description, $is_active, $category_id);
            
            if ($stmt->execute()) {
                log_admin_activity('update_category', "Updated category: $category_name");
                $success = "Category updated successfully!";
            } else {
                $error = "Failed to update category";
            }
        } else {
            // Add new category
            $stmt = $conn->prepare("INSERT INTO categories (category_name, description, is_active) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $category_name, $description, $is_active);
            
            if ($stmt->execute()) {
                log_admin_activity('add_category', "Added new category: $category_name");
                $success = "Category added successfully!";
            } else {
                $error = "Failed to add category";
            }
        }
    }
}

// Get all categories with product count
$categories = [];
$result = $conn->query("SELECT c.*, 
                       (SELECT COUNT(*) FROM products WHERE category_id = c.category_id) as product_count
                       FROM categories c 
                       ORDER BY c.category_name");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

// Get category for editing if ID is provided
$edit_category = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM categories WHERE category_id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_category = $stmt->get_result()->fetch_assoc();
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="admin-header">
    <h1><i class="fas fa-folder"></i> Categories Management</h1>
    <div class="admin-header-actions">
        <button type="button" class="btn btn-primary" onclick="openModal()">
            <i class="fas fa-plus"></i> Add New Category
        </button>
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

<!-- Categories Grid -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3><i class="fas fa-list"></i> All Categories (<?php echo count($categories); ?>)</h3>
    </div>
    <div class="admin-card-body">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Category Name</th>
                        <th>Description</th>
                        <th>Products</th>
                        <th>Status</th>
                        <th>Created Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px;">
                                <i class="fas fa-folder-open" style="font-size: 48px; color: #ddd; margin-bottom: 10px;"></i>
                                <p style="color: #999;">No categories found</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><strong>#<?php echo $category['category_id']; ?></strong></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($category['category_name']); ?></strong>
                                </td>
                                <td>
                                    <?php 
                                    $desc = $category['description'] ?? 'No description';
                                    echo htmlspecialchars(substr($desc, 0, 50)) . (strlen($desc) > 50 ? '...' : ''); 
                                    ?>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo $category['product_count']; ?> products
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $category['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $category['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($category['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="#" 
                                           class="action-btn" 
                                           title="Edit"
                                           onclick="openEditModal(<?php echo htmlspecialchars(json_encode($category)); ?>); return false;">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if (has_permission('inventory_manager')): ?>
                                            <a href="?delete=<?php echo $category['category_id']; ?>" 
                                               class="action-btn delete" 
                                               title="Delete"
                                               onclick="return confirm('Are you sure you want to delete this category?')">
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

<!-- Category Modal -->
<div id="categoryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">
                <i class="fas fa-folder-plus"></i> Add New Category
            </h3>
            <button type="button" class="close-modal" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" action="" id="categoryForm">
            <input type="hidden" name="category_id" id="category_id" value="">
            
            <div class="modal-body">
                <div class="form-group">
                    <label><i class="fas fa-tag"></i> Category Name *</label>
                    <input type="text" 
                           name="category_name" 
                           id="category_name" 
                           class="form-control" 
                           placeholder="e.g., Men's Clothing, Women's Shoes" 
                           required>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-align-left"></i> Description</label>
                    <textarea name="description" 
                              id="description" 
                              class="form-control" 
                              rows="4"
                              placeholder="Brief description of this category..."></textarea>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 400;">
                        <input type="checkbox" 
                               name="is_active" 
                               id="is_active" 
                               checked
                               style="width: 18px; height: 18px;">
                        <span><i class="fas fa-toggle-on"></i> Active (Visible on store)</span>
                    </label>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="fas fa-save"></i> Add Category
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.6);
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.3s;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-content {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    animation: slideUp 0.3s;
}

@keyframes slideUp {
    from {
        transform: translateY(50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-header {
    padding: 24px;
    border-bottom: 1px solid #e5e5e5;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    font-size: 20px;
    color: #333;
    display: flex;
    align-items: center;
    gap: 10px;
}

.modal-header h3 i {
    color: var(--primary-color);
}

.close-modal {
    background: none;
    border: none;
    font-size: 24px;
    color: #999;
    cursor: pointer;
    transition: color 0.3s;
    padding: 5px;
}

.close-modal:hover {
    color: var(--danger-color);
}

.modal-body {
    padding: 24px;
}

.modal-footer {
    padding: 20px 24px;
    border-top: 1px solid #e5e5e5;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 6px;
}

.action-btn {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    border: 1px solid #ddd;
    background: white;
    color: #666;
    transition: all 0.3s;
    text-decoration: none;
    cursor: pointer;
}

.action-btn:hover {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

.action-btn.delete:hover {
    background: var(--danger-color);
    border-color: var(--danger-color);
}

/* Responsive */
@media (max-width: 768px) {
    .modal-content {
        width: 95%;
        max-height: 95vh;
    }
    
    .modal-header,
    .modal-body,
    .modal-footer {
        padding: 16px;
    }
}
</style>

<script>
// Open modal for adding new category
function openModal() {
    document.getElementById('categoryModal').style.display = 'flex';
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-folder-plus"></i> Add New Category';
    document.getElementById('categoryForm').reset();
    document.getElementById('category_id').value = '';
    document.getElementById('is_active').checked = true;
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Add Category';
}

// Open modal for editing category
function openEditModal(category) {
    document.getElementById('categoryModal').style.display = 'flex';
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Category';
    document.getElementById('category_id').value = category.category_id;
    document.getElementById('category_name').value = category.category_name;
    document.getElementById('description').value = category.description || '';
    document.getElementById('is_active').checked = category.is_active == 1;
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Update Category';
}

// Close modal
function closeModal() {
    document.getElementById('categoryModal').style.display = 'none';
    document.getElementById('categoryForm').reset();
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('categoryModal');
    if (event.target == modal) {
        closeModal();
    }
}

// Close modal on ESC key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeModal();
    }
});
</script>

<?php include 'includes/footer.php'; ?>