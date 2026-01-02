// Toggle Sidebar
function toggleSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    if (sidebar) {
        sidebar.classList.toggle('active');
    }
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(e) {
    const sidebar = document.getElementById('adminSidebar');
    const toggleBtn = document.querySelector('.sidebar-toggle');
    
    if (sidebar && window.innerWidth <= 1024) {
        if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
            sidebar.classList.remove('active');
        }
    }
});

// Confirm before delete
document.querySelectorAll('[data-confirm]').forEach(element => {
    element.addEventListener('click', function(e) {
        if (!confirm(this.dataset.confirm)) {
            e.preventDefault();
        }
    });
});

// Auto-hide alerts
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(alert => {
        alert.style.animation = 'fadeOut 0.5s ease';
        setTimeout(() => alert.remove(), 500);
    });
}, 5000);

// Image preview
const imageInput = document.querySelector('input[type="file"][name="product_image"]');
if (imageInput) {
    imageInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                const preview = document.getElementById('image-preview');
                if (preview) {
                    preview.innerHTML = `<img src="${event.target.result}" style="max-width: 100%; height: auto; border-radius: 8px; margin-top: 15px;">`;
                }
            };
            reader.readAsDataURL(file);
        }
    });
}

// Print function
function printInvoice(orderId) {
    window.open(`print-invoice.php?id=${orderId}`, '_blank', 'width=800,height=600');
}

// Backup database
function backupDatabase() {
    if (confirm('Generate database backup?')) {
        fetch('ajax/backup-database.php')
            .then(response => response.blob())
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `backup_${new Date().getTime()}.sql`;
                a.click();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to generate backup');
            });
    }
}

// Data Tables Enhancement
document.addEventListener('DOMContentLoaded', function() {
    // Add search to tables
    const tables = document.querySelectorAll('.admin-table');
    tables.forEach(table => {
        const tbody = table.querySelector('tbody');
        if (tbody) {
            const rows = tbody.querySelectorAll('tr');
            
            // Add quick search if needed
            const searchInput = table.parentElement.querySelector('.table-search');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(searchTerm) ? '' : 'none';
                    });
                });
            }
        }
    });
});

// Chart.js defaults
if (typeof Chart !== 'undefined') {
    Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
    Chart.defaults.plugins.legend.display = false;
}

// Notification system (reuse from main.js)
function showNotification(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `notification-toast ${type}`;
    
    const icon = type === 'success' ? 'fa-check-circle' : 
                 type === 'error' ? 'fa-exclamation-circle' : 
                 'fa-info-circle';
    
    toast.innerHTML = `
        <div class="notification-icon">
            <i class="fas ${icon}"></i>
        </div>
        <div class="notification-message">${message}</div>
        <button class="notification-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideInRight 0.3s ease reverse';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

console.log('Admin panel initialized');