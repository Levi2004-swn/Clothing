/**
 * main.js - Main JavaScript File
 * For LibreTranslate Translation System
 * 
 * Place this file as: assets/js/main.js
 */

(function() {
    'use strict';
    
    // ============================================
    // INITIALIZATION
    // ============================================
    
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🚀 LibreTranslate System Initialized');
        
        // Initialize all features
        initLanguageSelector();
        initSearchBar();
        initMobileMenu();
    initCartUpdates();
        initFormValidation();
        initTooltips();
        initNewsletter();
        
        // Log current language
        const currentLang = getCurrentLanguage();
        console.log('🌍 Current Language:', currentLang);
    });
    
    // ============================================
    // LANGUAGE SELECTOR
    // ============================================
    
    function initLanguageSelector() {
        const languageBtn = document.querySelector('.language-btn');
        const languageDropdown = document.getElementById('language-dropdown');
        
        if (!languageBtn || !languageDropdown) return;
        
        // Toggle dropdown
        languageBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            languageDropdown.classList.toggle('active');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!languageDropdown.contains(e.target) && e.target !== languageBtn) {
                languageDropdown.classList.remove('active');
            }
        });
        
        // Language search functionality
        const searchInput = document.getElementById('language-search-input');
        if (searchInput) {
            searchInput.addEventListener('input', filterLanguages);
        }
        
        console.log('✅ Language selector initialized');
    }
    
    function filterLanguages() {
        const searchInput = document.getElementById('language-search-input');
        const searchValue = searchInput.value.toLowerCase();
        const languageOptions = document.querySelectorAll('.language-option');
        
        languageOptions.forEach(function(option) {
            const text = option.textContent.toLowerCase();
            if (text.includes(searchValue)) {
                option.style.display = 'flex';
            } else {
                option.style.display = 'none';
            }
        });
    }
    
    function changeLanguage(langCode, langName, flag) {
        console.log('🌐 Changing language to:', langCode);
        
        // Show loading overlay
        const loadingOverlay = document.getElementById('translation-loading');
        if (loadingOverlay) {
            loadingOverlay.classList.add('show');
        }
        
        // Set language via AJAX
        fetch('ajax/set-language.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'lang=' + encodeURIComponent(langCode)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('✅ Language changed successfully');
                
                // Reload page to apply translations
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('lang', langCode);
                window.location.href = currentUrl.toString();
            } else {
                console.error('❌ Failed to change language:', data.message);
                alert('Failed to change language. Please try again.');
                if (loadingOverlay) {
                    loadingOverlay.classList.remove('show');
                }
            }
        })
        .catch(error => {
            console.error('❌ Error:', error);
            alert('Failed to change language. Please try again.');
            if (loadingOverlay) {
                loadingOverlay.classList.remove('show');
            }
        });
        
        return false;
    }
    
    function getCurrentLanguage() {
        // Get from cookie
        const cookies = document.cookie.split(';');
        for (let cookie of cookies) {
            const [name, value] = cookie.trim().split('=');
            if (name === 'site_language') {
                return value;
            }
        }
        
        // Get from URL
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('lang')) {
            return urlParams.get('lang');
        }
        
        // Default
        return 'en';
    }
    
    // Make changeLanguage available globally
    window.changeLanguage = changeLanguage;
    window.filterLanguages = filterLanguages;

    // ============================================
    // NEWSLETTER SUBSCRIBE (Index + Footer)
    // ============================================
    function initNewsletter(){
        // Footer form handler
        var footerForm = document.querySelector('.newsletter-form');
        if (footerForm) {
            footerForm.addEventListener('submit', function(e){
                e.preventDefault();
                var input = footerForm.querySelector('input[type="email"]');
                var email = (input && input.value || '').trim();
                subscribeAjax(email, function(ok, msg){
                    // Basic UX: alert; keep workflow minimal
                    alert(msg || (ok ? 'Subscribed!' : 'Failed to subscribe'));
                    if (ok && input) input.value = '';
                });
            });
        }

        // Expose global for index button
        window.subscribeNewsletter = function(){
            var input = document.getElementById('newsletter-email');
            var email = (input && input.value || '').trim();
            subscribeAjax(email, function(ok, msg){
                alert(msg || (ok ? 'Subscribed!' : 'Failed to subscribe'));
                if (ok && input) input.value = '';
            });
        };
    }

    function subscribeAjax(email, cb){
        if (!email) return cb(false, 'Please enter a valid email address');
        // Simple validation
        var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!re.test(email)) return cb(false, 'Please enter a valid email address');
        fetch('ajax/subscribe-newsletter.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'email=' + encodeURIComponent(email)
        }).then(function(r){ return r.json(); })
          .then(function(data){ cb(!!data.success, data.message); })
          .catch(function(){ cb(false, 'Failed to subscribe. Please try again.'); });
    }
    
    // ============================================
    // SEARCH BAR
    // ============================================
    
    function initSearchBar() {
        const searchInput = document.getElementById('search-input');
        const searchSuggestions = document.getElementById('search-suggestions');
        
        if (!searchInput) return;
        
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length < 2) {
                if (searchSuggestions) {
                    searchSuggestions.classList.remove('active');
                }
                return;
            }
            
            searchTimeout = setTimeout(function() {
                fetchSearchSuggestions(query);
            }, 300);
        });
        
        // Close suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (searchSuggestions && !searchInput.contains(e.target)) {
                searchSuggestions.classList.remove('active');
            }
        });
        
        console.log('✅ Search bar initialized');
    }
    
    function fetchSearchSuggestions(query) {
        // This would make an AJAX call to your search endpoint
        // For now, it's a placeholder
        console.log('🔍 Searching for:', query);
        
        // Example implementation:
        /*
        fetch('ajax/search-suggestions.php?q=' + encodeURIComponent(query))
            .then(response => response.json())
            .then(data => {
                displaySearchSuggestions(data.suggestions);
            })
            .catch(error => {
                console.error('Search error:', error);
            });
        */
    }
    
    function displaySearchSuggestions(suggestions) {
        const searchSuggestions = document.getElementById('search-suggestions');
        if (!searchSuggestions) return;
        
        if (suggestions.length === 0) {
            searchSuggestions.classList.remove('active');
            return;
        }
        
        let html = '';
        suggestions.forEach(function(suggestion) {
            html += '<a href="' + suggestion.url + '" class="suggestion-item">';
            html += '<i class="fas fa-search"></i> ';
            html += suggestion.text;
            html += '</a>';
        });
        
        searchSuggestions.innerHTML = html;
        searchSuggestions.classList.add('active');
    }
    
    // ============================================
    // MOBILE MENU
    // ============================================
    
    function initMobileMenu() {
        const menuToggle = document.querySelector('.menu-toggle');
        const mainNav = document.querySelector('.main-nav');
        
        if (!menuToggle) return;
        
        menuToggle.addEventListener('click', function() {
            mainNav.classList.toggle('active');
            this.classList.toggle('active');
        });
        
        console.log('✅ Mobile menu initialized');
    }
    
    // ============================================
    // CART UPDATES
    // ============================================
    
    function initCartUpdates() {
        // Update cart count dynamically
        const addToCartButtons = document.querySelectorAll('.add-to-cart');
        
        addToCartButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const productId = this.dataset.productId;
                addToCart(productId, this);
            });
        });
    }
    
    // Update quantity for a cart item (used by cart.php inline onclick)
    function updateCartQuantity(cartItemId, quantity) {
        // Guard
        if (!cartItemId) return;

        const params = new URLSearchParams();
        params.set('cart_item_id', cartItemId);
        params.set('quantity', quantity);

        fetch('ajax/update-cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString()
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Update the row in-place
                var row = document.querySelector('tr[data-cart-item-id="' + cartItemId + '"]');
                if (row) {
                    var input = row.querySelector('.cart-qty-input');
                    if (input) input.value = quantity;
                    var price = parseFloat(row.getAttribute('data-price')) || 0;
                    var lineTotalEl = row.querySelector('.cart-line-total');
                    var osTmp = document.getElementById('order-summary');
                    var currency = (osTmp && osTmp.dataset && osTmp.dataset.currency) ? osTmp.dataset.currency : '';
                    var lineTotal = price * quantity;
                    if (lineTotalEl) {
                        lineTotalEl.textContent = currency + (Number(lineTotal).toFixed(2));
                    }
                }
                // Recalc summary and cart count
                recalcCartSummaryAndCount();
                showToast(data.message || 'Cart updated', 'success');
            } else if (data.auth_required) {
                showAuthPrompt(data.message || 'Please log in or register to continue', data.login_url, data.register_url);
            } else {
                showNotification(data.message || 'Failed to update cart', 'error');
            }
        })
        .catch(err => {
            console.error('updateCartQuantity error:', err);
            showNotification('An error occurred', 'error');
        });
    }

    // Remove a cart item (used by cart.php inline onclick)
    function removeFromCart(cartItemId) {
        if (!cartItemId) return;
        const params = new URLSearchParams();
        params.set('cart_item_id', cartItemId);

        fetch('ajax/remove-from-cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString()
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Remove row from DOM
                var row = document.querySelector('tr[data-cart-item-id="' + cartItemId + '"]');
                if (row && row.parentNode) {
                    row.parentNode.removeChild(row);
                }
                // If no items left, replace with empty-state
                var anyRows = document.querySelector('tr[data-cart-item-id]');
                if (!anyRows) {
                    // Try to target the current page content container, not header containers
                    var contentContainer = (row && row.closest('.container')) || document.querySelector('main.main-content .container');
                    if (contentContainer) {
                        contentContainer.innerHTML = '<div class="card" style="text-align: center; padding: 60px 20px;"><i class="fas fa-shopping-cart" style="font-size: 80px; color: #ddd; margin-bottom: 20px;"></i><h3 style="margin-bottom: 10px;">Your cart is empty</h3><p style="color: #666; margin-bottom: 30px;">Add some products to get started!</p><a href="category.php" class="btn btn-primary">Start Shopping</a></div>';
                    }
                    // Ensure cart badge resets to 0
                    updateCartCount(0);
                } else {
                    recalcCartSummaryAndCount();
                }
                showToast(data.message || 'Item removed from cart', 'success');
            } else if (data.auth_required) {
                showAuthPrompt(data.message || 'Please log in or register to continue', data.login_url, data.register_url);
            } else {
                showNotification(data.message || 'Failed to remove item', 'error');
            }
        })
        .catch(err => {
            console.error('removeFromCart error:', err);
            showNotification('An error occurred', 'error');
        });
    }

    function addToCart(productId, arg2, arg3) {
        console.log('🛒 Adding product to cart:', productId);

        // Support multiple call signatures:
        // 1) addToCart(productId, triggerEl)
        // 2) addToCart(productId, variantId, quantity)
        var variantId = null;
        var quantity = 1;
        var triggerEl = null;

        if (arg2 && typeof arg2 === 'object' && arg2.nodeType === 1) {
            // Signature 1
            triggerEl = arg2;
        } else if (typeof arg2 !== 'undefined') {
            // Signature 2: variantId provided
            var parsed = parseInt(arg2, 10);
            if (!isNaN(parsed)) variantId = parsed;
        }
        if (typeof arg3 !== 'undefined') {
            var q = parseInt(arg3, 10);
            if (!isNaN(q) && q > 0) quantity = q;
        }

        // If variant not explicitly provided, try to detect required variant selection (size/color)
        if (!variantId) {
            const variantRadios = document.querySelectorAll('input[name="variant_id"]');
            if (variantRadios && variantRadios.length > 0) {
                const checked = document.querySelector('input[name="variant_id"]:checked');
                if (!checked) {
                    showNotification('Please choose size and color', 'error');
                    return;
                }
                variantId = parseInt(checked.value, 10) || null;
            } else {
                // If page uses selects for size/color, verify they are chosen; server still expects variant_id
                var sizeSelect = document.querySelector('select[name="size"]');
                var colorSelect = document.querySelector('select[name="color"]');
                var sizeOk = !sizeSelect || (sizeSelect && sizeSelect.value);
                var colorOk = !colorSelect || (colorSelect && colorSelect.value);
                if (!sizeOk || !colorOk) {
                    showNotification('Please choose size and color', 'error');
                    return;
                }
                // If we don't have a concrete variant_id from the DOM, proceed without; server will validate
            }
        }

        const params = new URLSearchParams();
        params.set('product_id', productId);
        if (variantId) params.set('variant_id', variantId);
        // Always include size/color when available so server can derive and validate non-existing combinations
        var sizeSelect2 = document.querySelector('select[name="size"]');
        var colorSelect2 = document.querySelector('select[name="color"]');
        if (sizeSelect2 && sizeSelect2.value) params.set('size', sizeSelect2.value);
        if (colorSelect2 && colorSelect2.value) params.set('color', colorSelect2.value);
        if (quantity) params.set('quantity', quantity);

        fetch('ajax/add-to-cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: params.toString()
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCartCount(data.cart_count);
                showToast('Added to cart', 'success');
            } else if (data.variant_required) {
                // Prefer server-provided message if available (e.g., non-existing combo or custom text)
                showNotification(data.message || 'Please choose size and color', 'error');
            } else if (data.auth_required) {
                showAuthPrompt(data.message || 'Please log in or register to continue', data.login_url, data.register_url);
            } else {
                showNotification(data.message || 'Failed to add product to cart', 'error');
            }
        })
        .catch(error => {
            console.error('Cart error:', error);
            showNotification('An error occurred', 'error');
        });
    }
    
    function updateCartCount(count) {
        const cartBadge = document.getElementById('cart-count');
        if (cartBadge) {
            cartBadge.textContent = count;
            
            // Animate the badge
            cartBadge.style.transform = 'scale(1.3)';
            setTimeout(function() {
                cartBadge.style.transform = 'scale(1)';
            }, 200);
        }
    }
    
    // ============================================
    // NOTIFICATIONS
    // ============================================
    
        function showNotification(message, type) {
            // Remove existing notice if present
            var existing = document.querySelector('.notice-modal-overlay');
            if (existing) existing.remove();

            var overlay = document.createElement('div');
            overlay.className = 'notice-modal-overlay';
            overlay.setAttribute('role', 'dialog');
            overlay.setAttribute('aria-modal', 'true');

            var box = document.createElement('div');
            box.className = 'notice-modal notice-' + (type || 'info');

            var header = document.createElement('div');
            header.className = 'notice-modal-header';

            var icon = document.createElement('div');
            icon.className = 'icon';
            var glyph = 'i';
            switch (type) {
                case 'success': glyph = '✓'; break;
                case 'error': glyph = '✕'; break;
                case 'warning': glyph = '!'; break;
                default: glyph = 'i';
            }
            icon.textContent = glyph;

            var title = document.createElement('div');
            title.className = 'title';
            var titleText = 'Notice';
            if (type === 'success') titleText = 'Success';
            else if (type === 'error') titleText = 'Error';
            else if (type === 'warning') titleText = 'Warning';
            else titleText = 'Notice';
            title.textContent = titleText;

            var closeBtn = document.createElement('button');
            closeBtn.className = 'notice-modal-close';
            closeBtn.setAttribute('aria-label', 'Close');
            closeBtn.innerHTML = '&times;';

            header.appendChild(icon);
            header.appendChild(title);
            header.appendChild(closeBtn);

            var body = document.createElement('div');
            body.className = 'notice-modal-body';
            body.innerHTML = message || '';

            box.appendChild(header);
            box.appendChild(body);
            overlay.appendChild(box);

            function close() {
                overlay.remove();
                document.body.style.overflow = '';
            }
            overlay.addEventListener('click', function(e){ if (e.target === overlay) close(); });
            closeBtn.addEventListener('click', close);
            document.addEventListener('keydown', function esc(e){
                if (e.key === 'Escape') {
                    close();
                    document.removeEventListener('keydown', esc);
                }
            });

            document.body.appendChild(overlay);
            document.body.style.overflow = 'hidden';
    }
    
    // Make showNotification available globally
    window.showNotification = showNotification;

    // Lightweight toast (non-blocking) for smooth UX on success
    function showToast(message, type) {
        var container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        var toast = document.createElement('div');
        toast.className = 'toast toast-' + (type || 'info');
        var icon = 'i';
        if (type === 'success') icon = '✓';
        else if (type === 'error') icon = '✕';
        else if (type === 'warning') icon = '!';
        toast.innerHTML = '<span class="icon">' + icon + '</span><span>' + (message || '') + '</span>';
        container.appendChild(toast);
        // Auto hide
        setTimeout(function(){
            if (toast && toast.parentNode) {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-4px)';
                setTimeout(function(){ toast.remove(); }, 200);
            }
        }, 2200);
    }
    window.showToast = showToast;

    // ============================================
    // AUTH NOTICE PROMPT
    // ============================================
    function showAuthPrompt(message, loginUrl, registerUrl) {
        // If one already exists, remove it first
        const existing = document.getElementById('auth-prompt-overlay');
        if (existing) existing.remove();

        const overlay = document.createElement('div');
        overlay.id = 'auth-prompt-overlay';
        overlay.className = 'auth-prompt-overlay';

        const box = document.createElement('div');
        box.className = 'auth-prompt-box';
        box.innerHTML = `
            <div class="auth-prompt-header">
                <span>Notice</span>
                <button class="auth-prompt-close" aria-label="Close">&times;</button>
            </div>
            <div class="auth-prompt-body">
                <p>${message || 'Please log in or register to continue'}</p>
                <div class="auth-prompt-actions">
                    <a class="btn btn-primary" href="${loginUrl || 'login.php'}">Login</a>
                    <a class="btn btn-secondary" href="${registerUrl || 'register.php'}">Register</a>
                </div>
            </div>
        `;

        overlay.appendChild(box);
        document.body.appendChild(overlay);

        overlay.addEventListener('click', function(e){
            if (e.target === overlay || e.target.classList.contains('auth-prompt-close')) {
                overlay.remove();
            }
        });
        document.addEventListener('keydown', function escClose(ev){
            if (ev.key === 'Escape') {
                overlay.remove();
                document.removeEventListener('keydown', escClose);
            }
        });
    }
    window.showAuthPrompt = showAuthPrompt;

    // ============================================
    // WISHLIST HANDLERS (basic add/remove)
    // ============================================
    // Toggle-aware addToWishlist: if the trigger element already appears "hearted",
    // perform a remove instead. Visuals are updated optimistically and reverted on error.
    function addToWishlist(productId, triggerEl) {
        if (!productId) return Promise.resolve({ success: false, message: 'Invalid product' });

        // detect current state from triggerEl if available
        try {
            var icon = triggerEl && triggerEl.querySelector ? triggerEl.querySelector('i') : null;
            var isFilled = icon && icon.classList.contains('fas');
        } catch (e) {
            var icon = null, isFilled = false;
        }

        if (isFilled) {
            // Already filled -> remove
            // Update UI optimistically
            if (icon) {
                icon.classList.remove('fas');
                icon.classList.add('far');
                icon.style.color = '#bbb';
            }
            if (triggerEl && triggerEl.classList) triggerEl.classList.remove('active', 'btn-primary');
            return removeFromWishlist(productId, triggerEl);
        }

        // Not in wishlist -> add
        if (icon) {
            // optimistic visual feedback
            icon.classList.remove('far');
            icon.classList.add('fas');
            icon.style.color = '#e74c3c';
        }
        if (triggerEl && triggerEl.classList) triggerEl.classList.add('active', 'btn-primary');

        const params = new URLSearchParams();
        params.set('product_id', productId);

        return fetch('ajax/add-to-wishlist.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString()
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast(data.message || 'Added to wishlist', 'success');
                return data;
            } else if (data.auth_required) {
                // revert visuals and prompt auth
                try {
                    if (icon) {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                        icon.style.color = '#bbb';
                    }
                    if (triggerEl && triggerEl.classList) triggerEl.classList.remove('active', 'btn-primary');
                } catch (e) {}
                showAuthPrompt(data.message || 'Please log in or register to continue', data.login_url, data.register_url);
                return data;
            } else {
                // revert optimistic UI
                try {
                    if (icon) {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                        icon.style.color = '#bbb';
                    }
                    if (triggerEl && triggerEl.classList) triggerEl.classList.remove('active', 'btn-primary');
                } catch (e) {}
                showNotification(data.message || 'Failed to add to wishlist', 'error');
                return data;
            }
        })
        .catch(err => {
            console.error('addToWishlist error:', err);
            // revert optimistic UI
            try {
                if (icon) {
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                    icon.style.color = '#bbb';
                }
                if (triggerEl && triggerEl.classList) triggerEl.classList.remove('active', 'btn-primary');
            } catch (e) {}
            showNotification('An error occurred', 'error');
            return { success: false, error: true };
        });
    }
    window.addToWishlist = addToWishlist;

    function removeFromWishlist(productId, triggerEl) {
        if (!productId) return Promise.resolve({ success: false, message: 'Invalid product' });
        const params = new URLSearchParams();
        params.set('product_id', productId);

        return fetch('ajax/remove-from-wishlist.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString()
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Remove the wishlist item row/card if present
                var item = document.querySelector('[data-wishlist-product-id="' + productId + '"]') ||
                           document.querySelector('.product-card[data-product-id="' + productId + '"]');
                if (item && item.parentNode) item.parentNode.removeChild(item);
                // Update any product card button/icon visuals
                try {
                    var icon = triggerEl && triggerEl.querySelector ? triggerEl.querySelector('i') : null;
                    if (!icon) {
                        // try to find any button on the page for this product
                        var btn = document.querySelector('button[data-product-id="' + productId + '"]');
                        icon = btn ? btn.querySelector('i') : null;
                        triggerEl = triggerEl || btn;
                    }
                    if (icon) {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                        icon.style.color = '#bbb';
                    }
                    if (triggerEl && triggerEl.classList) triggerEl.classList.remove('active', 'btn-primary');
                } catch (e) {}
                // Update wishlist count and show empty state if needed
                try {
                    var grid = document.getElementById('wishlist-grid') || document.querySelector('.product-grid');
                    var remaining = grid ? grid.querySelectorAll('.product-card').length : 0;
                    var countEl = document.getElementById('wishlist-count');
                    if (countEl) {
                        var label = remaining === 1 ? ' item' : ' items';
                        countEl.textContent = remaining + label;
                    }
                    if (grid && remaining === 0) {
                        var empty = document.createElement('div');
                        empty.className = 'card';
                        empty.style.textAlign = 'center';
                        empty.style.padding = '60px 20px';
                        empty.innerHTML = '<i class="fas fa-heart" style="font-size: 80px; color: #ddd; margin-bottom: 20px;"></i>' +
                                          '<h3 style="margin-bottom: 10px;">Your Wishlist is Empty</h3>' +
                                          '<p style="color: #666; margin-bottom: 30px;">Save items you love by clicking the heart icon!</p>' +
                                          '<a href="category.php" class="btn btn-primary">Start Shopping</a>';
                        grid.parentNode.replaceChild(empty, grid);
                    }
                } catch (e) {}
                showToast(data.message || 'Removed from wishlist', 'success');
                return data;
            } else if (data.auth_required) {
                showAuthPrompt(data.message || 'Please log in or register to continue', data.login_url, data.register_url);
                return data;
            } else {
                showNotification(data.message || 'Failed to remove from wishlist', 'error');
                return data;
            }
        })
        .catch(err => {
            console.error('removeFromWishlist error:', err);
            showNotification('An error occurred', 'error');
            return { success: false, error: true };
        });
    }
    window.removeFromWishlist = removeFromWishlist;

    // Expose cart helpers used by inline handlers across pages
    window.addToCart = addToCart;
    window.updateCartQuantity = updateCartQuantity;
    window.removeFromCart = removeFromCart;

    // Bind cart and wishlist remove button events (delegated)
    document.addEventListener('click', function(e){
        var btn = e.target.closest('.cart-qty-btn');
        if (btn) {
            e.preventDefault();
            var id = btn.dataset.itemId;
            var row = document.querySelector('tr[data-cart-item-id="' + id + '"]');
            var input = row ? row.querySelector('.cart-qty-input') : null;
            var current = input ? parseInt(input.value, 10) || 1 : 1;
            var action = btn.dataset.action;
            var newQty = action === 'qty-dec' ? Math.max(1, current - 1) : current + 1;
            var stockAttr = row ? row.getAttribute('data-stock') : null;
            var stock = stockAttr ? parseInt(stockAttr, 10) : null;
            if (stock && !isNaN(stock)) {
                newQty = Math.min(newQty, stock);
            }
            updateCartQuantity(id, newQty);
        }
        var rbtn = e.target.closest('.cart-remove-btn');
        if (rbtn) {
            e.preventDefault();
            removeFromCart(rbtn.dataset.itemId);
        }
        var wbtn = e.target.closest('.wishlist-remove-btn');
        if (wbtn) {
            e.preventDefault();
            e.stopPropagation();
            removeFromWishlist(wbtn.dataset.productId, wbtn);
        }
    });

    function recalcCartSummaryAndCount() {
        // Sum line totals based on row data
        var rows = Array.from(document.querySelectorAll('tr[data-cart-item-id]'));
        var subtotal = 0;
        var itemCount = 0;
        rows.forEach(function(row){
            var price = parseFloat(row.getAttribute('data-price')) || 0;
            var qtyEl = row.querySelector('.cart-qty-input');
            var qtyVal = qtyEl ? qtyEl.value : '0';
            var qty = parseInt(qtyVal, 10) || 0;
            subtotal += price * qty;
            itemCount += qty;
        });
        var os = document.getElementById('order-summary');
        if (os) {
            var taxRate = parseFloat(os.dataset.taxRate) || 0;
            var shippingFee = parseFloat(os.dataset.shippingFee) || 0;
            var freeThresh = parseFloat(os.dataset.freeShippingThreshold) || 0;
            var currency = os.dataset.currency || '';
            var tax = (subtotal * taxRate) / 100;
            var shipping = subtotal >= freeThresh && freeThresh > 0 ? 0 : shippingFee;
            var total = subtotal + tax + shipping;
            var ss = document.getElementById('summary-subtotal');
            var st = document.getElementById('summary-tax');
            var sh = document.getElementById('summary-shipping');
            var stot = document.getElementById('summary-total');
            if (ss) ss.textContent = currency + Number(subtotal).toFixed(2);
            if (st) st.textContent = currency + Number(tax).toFixed(2);
            if (sh) {
                sh.textContent = shipping === 0 ? 'FREE' : (currency + Number(shipping).toFixed(2));
                // Reflect free shipping style similar to initial PHP render
                sh.style.color = shipping === 0 ? '#4caf50' : '#333';
            }
            if (stot) stot.textContent = currency + Number(total).toFixed(2);

            // Update "add more to get free shipping" hint
            var hint = document.getElementById('free-shipping-hint');
            var hintAmt = document.getElementById('free-shipping-remaining');
            if (hint && freeThresh > 0) {
                var remain = Math.max(0, freeThresh - subtotal);
                if (remain > 0) {
                    hint.style.display = '';
                    if (hintAmt) hintAmt.textContent = currency + Number(remain).toFixed(2);
                } else {
                    hint.style.display = 'none';
                }
            }
        }
        // Update cart badge count
        updateCartCount(itemCount);
    }
    
    // ============================================
    // FORM VALIDATION
    // ============================================
    
    function initFormValidation() {
        const forms = document.querySelectorAll('form[data-validate]');
        
        forms.forEach(function(form) {
            form.addEventListener('submit', function(e) {
                if (!validateForm(this)) {
                    e.preventDefault();
                }
            });
        });
    }
    
    function validateForm(form) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(function(field) {
            if (!field.value.trim()) {
                markFieldAsInvalid(field);
                isValid = false;
            } else {
                markFieldAsValid(field);
            }
        });
        
        // Email validation
        const emailFields = form.querySelectorAll('input[type="email"]');
        emailFields.forEach(function(field) {
            if (field.value && !isValidEmail(field.value)) {
                markFieldAsInvalid(field);
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    function markFieldAsInvalid(field) {
        field.style.borderColor = '#ef4444';
        field.classList.add('invalid');
    }
    
    function markFieldAsValid(field) {
        field.style.borderColor = '#10b981';
        field.classList.remove('invalid');
    }
    
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    // ============================================
    // TOOLTIPS
    // ============================================
    
    function initTooltips() {
        const tooltipElements = document.querySelectorAll('[data-tooltip]');
        
        tooltipElements.forEach(function(element) {
            element.addEventListener('mouseenter', function() {
                showTooltip(this);
            });
            
            element.addEventListener('mouseleave', function() {
                hideTooltip();
            });
        });
    }
    
    function showTooltip(element) {
        const text = element.dataset.tooltip;
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = text;
        tooltip.id = 'active-tooltip';
        
        document.body.appendChild(tooltip);
        
        // Position tooltip
        const rect = element.getBoundingClientRect();
        tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
        tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
        
        setTimeout(function() {
            tooltip.classList.add('show');
        }, 10);
    }
    
    function hideTooltip() {
        const tooltip = document.getElementById('active-tooltip');
        if (tooltip) {
            tooltip.classList.remove('show');
            setTimeout(function() {
                tooltip.remove();
            }, 200);
        }
    }
    
    // ============================================
    // SMOOTH SCROLL
    // ============================================
    
    document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href === '#') return;
            
            e.preventDefault();
            const target = document.querySelector(href);
            
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // ============================================
    // LAZY LOADING IMAGES
    // ============================================
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll('img.lazy').forEach(function(img) {
            imageObserver.observe(img);
        });
    }
    
    // ============================================
    // UTILITY FUNCTIONS
    // ============================================
    
    // Debounce function
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = function() {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Get cookie value
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
    }
    
    // Set cookie
    function setCookie(name, value, days) {
        let expires = "";
        if (days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "") + expires + "; path=/";
    }
    
    // Make utility functions available globally
    window.getCookie = getCookie;
    window.setCookie = setCookie;
    window.debounce = debounce;
    
    // ============================================
    // CONSOLE STYLING
    // ============================================
    
    console.log('%c🔓 LibreTranslate Translation System', 'color: #667eea; font-size: 20px; font-weight: bold;');
    console.log('%c✅ 100% FREE - No API Key Required', 'color: #10b981; font-size: 14px;');
    console.log('%c🌍 35+ Languages Supported', 'color: #3b82f6; font-size: 14px;');
    
})();

(function () {
	// Matches common search inputs without relying on specific markup
	const isSearchInput = (el) => {
		return !!el && (
			el.matches('input[type="search"]') ||
			el.matches('.search-input') ||
			el.matches('input[name="q"]') ||
			el.matches('input[name="search"]') ||
			el.matches('#search') ||
			el.closest?.('.header-search')?.querySelector('input') === el
		);
	};

	// Add/remove a CSS flag to suppress page dim overlays while typing in search
    const stripSearchOverlayClasses = () => {
		const cls = ['search-open', 'search-active', 'search-focus', 'overlay-active', 'header-search-open', 'dimmed'];
		cls.forEach(c => {
			document.body.classList.remove(c);
			document.documentElement.classList.remove(c);
		});
	};

	document.addEventListener('focusin', (e) => {
		if (isSearchInput(e.target)) {
			document.body.classList.add('no-search-dim');
			document.documentElement.classList.add('no-search-dim');
            stripSearchOverlayClasses();
		}
    }, true);

	document.addEventListener('focusout', (e) => {
		if (isSearchInput(e.target)) {
			// Defer to allow focus to move within autocomplete lists without flicker
			setTimeout(() => {
				const ae = document.activeElement;
				if (!isSearchInput(ae)) {
					document.body.classList.remove('no-search-dim');
					document.documentElement.classList.remove('no-search-dim');
				}
			}, 0);
		}
    }, true);
    document.addEventListener('keydown', (e) => {
		if (e.key === 'Escape') {
			document.body.classList.remove('no-search-dim');
			document.documentElement.classList.remove('no-search-dim');
		}
	});
})();