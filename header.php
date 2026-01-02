<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Clothing Store'; ?></title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 448 512%22><path fill=%22%23f53d2d%22 d=%22M160 112c0-35.3 28.7-64 64-64s64 28.7 64 64v48H160V112zm-48 48H48c-26.5 0-48 21.5-48 48v192c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V208c0-26.5-21.5-48-48-48h-64v-48C336 50.1 285.9 0 224 0S112 50.1 112 112v48z%22/></svg>" />
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/notice-modal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Google Translate Styling */
        .google-translate {
            display: inline-block;
            margin-right: 15px;
            vertical-align: middle;
        }

        #google_translate_element {
            display: inline-block;
        }

        /* Hide Google Translate banner */
        .goog-te-banner-frame.skiptranslate {
            display: none !important;
        }

        body {
            top: 0 !important;
        }

        /* Style the translate dropdown */
        .goog-te-gadget {
            font-family: inherit !important;
            font-size: 13px !important;
        }

        .goog-te-gadget-simple {
            background-color: transparent !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            padding: 5px 10px !important;
            border-radius: 4px !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
        }

        .goog-te-gadget-simple:hover {
            background-color: rgba(255, 255, 255, 0.1) !important;
            border-color: rgba(255, 255, 255, 0.5) !important;
        }

        .goog-te-gadget-icon {
            display: none !important;
        }

        .goog-te-menu-value {
            color: #fff !important;
        }

        .goog-te-menu-value span {
            color: #fff !important;
        }

        .goog-te-menu-value span:hover {
            color: #ffd700 !important;
        }

        /* Remove the "Powered by" text */
        .goog-logo-link {
            display: none !important;
        }

        .goog-te-gadget span {
            display: none !important;
        }

        #goog-gt-tt {
            display: none !important;
        }

        .goog-te-balloon-frame {
            display: none !important;
        }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="container">
            <div class="top-bar-left">
                <a href="<?php echo SITE_URL; ?>/admin/login.php"><i class="fas fa-store"></i> Seller Centre</a>
                <a href="#"><i class="fas fa-download"></i> Download</a>
                <a href="#" class="follow-link">
                    Follow us on
                    <i class="fab fa-facebook"></i>
                    <i class="fab fa-instagram"></i>
                </a>
            </div>
            <div class="top-bar-right">
                <!-- Google Translate Widget -->
                <div class="google-translate">
                    <i class="fas fa-language"></i>
                    <div id="google_translate_element"></div>
                </div>
                
                <?php if (is_logged_in()): ?>
                <div class="user-notifications">
                    <a href="#" id="userNotifToggle">
                        <i class="fas fa-bell"></i>
                        <span class="notif-badge" id="userNotifCount" style="display:none;">0</span>
                        <span class="notif-label">Notifications</span>
                    </a>
                    <div class="dropdown-menu" id="userNotifMenu">
                        <div class="notif-list" id="userNotifList"></div>
                        <div class="notif-empty" id="userNotifEmpty">No notifications</div>
                    </div>
                </div>
                <?php else: ?>
                <a href="#"><i class="fas fa-bell"></i> Notifications</a>
                <?php endif; ?>
                <a href="<?php echo SITE_URL; ?>/help.php"><i class="fas fa-question-circle"></i> Help</a>
                <?php if (is_logged_in()): ?>
                    <div class="user-dropdown">
                        <a href="#"><i class="fas fa-user-circle"></i> 
                            <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Account'); ?>
                        </a>
                        <div class="dropdown-menu">
                            <a href="<?php echo SITE_URL; ?>/profile.php">My Account</a>
                            <a href="<?php echo SITE_URL; ?>/orders.php">My Orders</a>
                            <a href="<?php echo SITE_URL; ?>/wishlist.php">My Wishlist</a>
                            <a href="<?php echo SITE_URL; ?>/wallet.php">My Wallet</a>
                            <a href="<?php echo SITE_URL; ?>/logout.php">Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/register.php">Sign Up</a>
                    <span class="divider">|</span>
                    <a href="<?php echo SITE_URL; ?>/login.php">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Header -->
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <!-- Logo -->
                <div class="logo">
                    <a href="<?php echo SITE_URL; ?>/index.php">
                        <i class="fas fa-shopping-bag"></i>
                        <span><?php echo SITE_NAME; ?></span>
                    </a>
                </div>

                <!-- Search Bar -->
                <div class="search-bar">
                    <!-- Route search submissions to category.php with the 'search' query param, which the page expects -->
                    <form action="<?php echo SITE_URL; ?>/category.php" method="GET">
                        <input type="text" name="search" placeholder="Search for products..." id="search-input" autocomplete="off">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                    <div class="search-suggestions" id="search-suggestions"></div>
                </div>

                <!-- Cart Icon -->
                <div class="header-cart">
                    <a href="<?php echo SITE_URL; ?>/cart.php">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-badge" id="cart-count">
                            <?php
                            // Get cart count
                            $cart_count = 0;
                            if (is_logged_in()) {
                                $user_id = get_user_id();
                                $result = $conn->query("SELECT SUM(ci.quantity) as count FROM cart c 
                                                       JOIN cart_items ci ON c.cart_id = ci.cart_id 
                                                       WHERE c.user_id = $user_id");
                            } else {
                                $session_id = get_session_id();
                                $result = $conn->query("SELECT SUM(ci.quantity) as count FROM cart c 
                                                       JOIN cart_items ci ON c.cart_id = ci.cart_id 
                                                       WHERE c.session_id = '$session_id'");
                            }
                            if ($result && $row = $result->fetch_assoc()) {
                                $cart_count = $row['count'] ?? 0;
                            }
                            echo $cart_count;
                            ?>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="main-nav">
        <div class="container">
            <ul class="nav-menu">
                <li><a href="<?php echo SITE_URL; ?>/index.php">Home</a></li>
                <li class="has-dropdown">
                    <!-- Men segment: clicking submenu triggers cross-category keyword search excluding women & kids -->
                    <a href="<?php echo SITE_URL; ?>/category.php?cat=men">Men <i class="fas fa-chevron-down"></i></a>
                    <div class="dropdown-menu">
                        <a href="<?php echo SITE_URL; ?>/category.php?cat=men&sub=shirts">Shirts</a>
                        <a href="<?php echo SITE_URL; ?>/category.php?cat=men&sub=pants">Pants</a>
                        <a href="<?php echo SITE_URL; ?>/category.php?cat=men&sub=shoes">Shoes</a>
                    </div>
                </li>
                <li class="has-dropdown">
                    <!-- Women segment: clicking submenu triggers cross-category keyword search excluding men & kids -->
                    <a href="<?php echo SITE_URL; ?>/category.php?cat=women">Women <i class="fas fa-chevron-down"></i></a>
                    <div class="dropdown-menu">
                        <a href="<?php echo SITE_URL; ?>/category.php?cat=women&sub=dresses">Dresses</a>
                        <a href="<?php echo SITE_URL; ?>/category.php?cat=women&sub=tops">Tops</a>
                        <a href="<?php echo SITE_URL; ?>/category.php?cat=women&sub=shoes">Shoes</a>
                    </div>
                </li>
                <li><a href="<?php echo SITE_URL; ?>/category.php?cat=kids">Kids</a></li>
                <li><a href="<?php echo SITE_URL; ?>/category.php?cat=accessories">Accessories</a></li>
                <li><a href="<?php echo SITE_URL; ?>/deals.php" class="deals-link"><i class="fas fa-tag"></i> Deals</a></li>
            </ul>
        </div>
    </nav>

    <!-- Search overlay for smooth focus effect -->
    <div class="search-overlay" id="search-overlay" aria-hidden="true"></div>

    <!-- Main Content -->
    <main class="main-content">

    <!-- Google Translate Script -->
    <script type="text/javascript">
    function googleTranslateElementInit() {
        new google.translate.TranslateElement({
            pageLanguage: 'en',
            includedLanguages: 'en,es,fr,de,it,pt,ja,ko,zh-CN,zh-TW,ar,hi,ru,nl,pl,tr,th,vi,id,ms,tl',
            layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
            autoDisplay: false,
            multilanguagePage: true
        }, 'google_translate_element');
    }
    </script>
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
    
    <!-- Dropdown click-to-toggle for user menu and nav (improves hover-only behavior) -->
    <script>
    (function(){
        function closeAll(selector){
            document.querySelectorAll(selector).forEach(function(el){ el.classList.remove('open'); });
        }

        function setupDropdown(containerSelector, toggleSelector, menuSelector){
            // Toggle on click of the trigger link/button
            document.addEventListener('click', function(e){
                var trigger = e.target.closest(toggleSelector);
                var container = e.target.closest(containerSelector);

                // Clicked on a trigger inside a container
                if (trigger && container) {
                    // Prevent jumping to top when href="#"
                    if (trigger.tagName === 'A' && trigger.getAttribute('href') === '#') {
                        e.preventDefault();
                    }
                    // Close other open dropdowns of same type
                    document.querySelectorAll(containerSelector).forEach(function(el){ if (el !== container) el.classList.remove('open'); });
                    container.classList.toggle('open');
                    return;
                }

                // Clicked outside any open container: close all
                if (!e.target.closest(containerSelector)) {
                    closeAll(containerSelector);
                }
            });

            // Close with ESC
            document.addEventListener('keydown', function(e){
                if (e.key === 'Escape') closeAll(containerSelector);
            });
        }

        // Storefront user dropdown
        setupDropdown('.user-dropdown', '.user-dropdown > a', '.user-dropdown .dropdown-menu');
        // Optional: main nav dropdowns can also be made clickable
        setupDropdown('.has-dropdown', '.has-dropdown > a', '.has-dropdown .dropdown-menu');
        // Storefront notifications dropdown
        setupDropdown('.user-notifications', '.user-notifications > a', '.user-notifications .dropdown-menu');
    })();
    </script>

    <!-- Search interactions: smooth fade focus + empty submit smooth refresh -->
    <script>
    (function(){
    var input = document.getElementById('search-input');
    var overlay = document.getElementById('search-overlay');
    var form = document.querySelector('.search-bar form');

        if (!input || !form || !overlay) return;

        // Simple input-only activation restores original behavior
        var activate = function(){ document.body.classList.add('search-active'); };
        var deactivate = function(){ document.body.classList.remove('search-active'); };
        input.addEventListener('focus', activate);
        // Deactivate overlay on blur (slight delay to allow clicks inside suggestions)
        input.addEventListener('blur', function(){ setTimeout(deactivate, 120); });

        // Clicking the overlay closes the search focus state
        overlay.addEventListener('click', function(){
            deactivate();
            input.blur();
        });

        // Close with ESC
        document.addEventListener('keydown', function(e){
            if (e.key === 'Escape') {
                deactivate();
                input.blur();
            }
        });

        // Intercept submit: if empty, do a smooth refresh on current page
        form.addEventListener('submit', function(e){
            var q = (input.value || '').trim();
            if (!q) {
                e.preventDefault();
                // Smooth fade, then reload same page
                document.body.classList.add('page-fade-out');
                setTimeout(function(){
                    // Reload current URL without changing location (preserve path + query/hash)
                    window.location.reload();
                }, 180);
            } else {
                // Close overlay before navigating to results
                deactivate();
            }
        });
    })();
    </script>

    <!-- Customer Notifications (returns + outstanding orders) -->
    <script>
    (function(){
        var loggedIn = <?php echo json_encode(is_logged_in()); ?>;
        if (!loggedIn) return;

        var countEl = document.getElementById('userNotifCount');
        var listEl = document.getElementById('userNotifList');
        var emptyEl = document.getElementById('userNotifEmpty');
        if (!countEl || !listEl || !emptyEl) return;

        function render(items){
            var count = Array.isArray(items) ? items.length : 0;
            if (count > 0) {
                countEl.textContent = String(count);
                countEl.style.display = 'inline-block';
                emptyEl.style.display = 'none';
                listEl.innerHTML = items.map(function(it){
                    var kind = (it.type || 'return').toLowerCase();
                    var icon, color;
                    if (kind === 'order') {
                        // Choose a unique icon per order state for clarity
                        var pay = (it.payment_status || '').toLowerCase();
                        var ost = (it.order_status || '').toLowerCase();
                        if (ost === 'cancelled') {
                            // Match rejected return request icon
                            icon = 'fa-times-circle';
                            color = '#c62828'; // red
                        } else if (pay && pay !== 'paid') {
                            // Payment pending or failed
                            icon = 'fa-credit-card';
                            color = '#d97706'; // amber
                        } else if (ost && ost !== 'delivered') {
                            // Paid but not delivered yet
                            icon = 'fa-truck';
                            color = '#2563eb'; // blue
                        } else {
                            // Fallback (should be rare because completed orders usually not shown)
                            icon = 'fa-circle-info';
                            color = '#6b7280'; // gray
                        }
                    } else {
                        icon = it.status === 'approved' ? 'fa-check-circle' : (it.status === 'completed' ? 'fa-box' : 'fa-times-circle');
                        color = it.status === 'approved' ? '#2e7d32' : (it.status === 'completed' ? '#1565c0' : '#c62828');
                    }
                    var href = it.href || '#';
                    var msg = it.message || '';
                    var time = it.updated_at || '';
                    return `
                        <a class="notif-item" href="${href}">
                            <div class="notif-icon" style="background:${color}20"><i class="fas ${icon}" style="color:${color}"></i></div>
                            <div class="notif-content">
                                <div class="notif-text">${msg}</div>
                                <div class="notif-time">${time}</div>
                            </div>
                        </a>
                    `;
                }).join('');
            } else {
                countEl.style.display = 'none';
                listEl.innerHTML = '';
                emptyEl.style.display = 'block';
            }
        }

        function fetchNotifs(){
            var url = '<?php echo SITE_URL; ?>/ajax/get-user-notifications.php';
            fetch(url, { headers: { 'Accept': 'application/json' }})
                .then(function(res){ return res.json(); })
                .then(function(data){ if (data && data.success) render(data.items || []); })
                .catch(function(){ /* silent */ });
        }

        // Initial and polling
        fetchNotifs();
        setInterval(fetchNotifs, 30000);
    })();
    </script>

    <style>
    /* Minimal styles for user notifications dropdown */
    .user-notifications { position: relative; display: inline-block; margin-right: 10px; }
    .user-notifications > a { color: #fff; text-decoration: none; padding: 6px 10px; display: inline-flex; align-items: center; gap: 6px; }
    .user-notifications .notif-badge { background: #ff3b30; color: #fff; font-size: 11px; border-radius: 10px; padding: 2px 6px; line-height: 1; margin-left: 2px; }
    .user-notifications .dropdown-menu { position: absolute; right: 0; top: 100%; background: #fff; min-width: 320px; max-width: 360px; box-shadow: 0 6px 18px rgba(0,0,0,0.15); border-radius: 8px; padding: 10px 0; display: none; z-index: 1000; }
    .user-notifications.open .dropdown-menu { display: block; }
    .user-notifications .notif-empty { color: #666; padding: 16px; text-align: center; font-size: 14px; }
    .user-notifications .notif-item { display: flex; gap: 10px; padding: 10px 14px; color: #333; text-decoration: none; }
    .user-notifications .notif-item:hover { background: #f9f9f9; }
    .user-notifications .notif-icon { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: #eee; }
    .user-notifications .notif-content { flex: 1; }
    .user-notifications .notif-text { font-size: 14px; line-height: 1.4; }
    .user-notifications .notif-time { font-size: 12px; color: #777; margin-top: 2px; }
    </style>