<?php
// FitGrit Common Header
// Reusable header component for all authenticated pages

// Prevent direct access
if (!defined('FITGRIT_ACCESS')) {
    die('Direct access not permitted');
}

// Ensure user is logged in
requireLogin();

// Get current user data
$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Get user's first name for greeting
$firstName = $currentUser['first_name'] ?? 'User';
$userInitials = strtoupper(substr($firstName, 0, 1) . substr($currentUser['last_name'] ?? 'U', 0, 1));

// Check for any pending offline data
$hasOfflineData = false;
if (isset($_SESSION['offline_data_count']) && $_SESSION['offline_data_count'] > 0) {
    $hasOfflineData = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="FitGrit - Track your fitness journey, weight loss, exercise, and nutrition goals">
    <meta name="author" content="FitGrit">
    
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - FitGrit' : 'FitGrit - Your Personal Fitness Companion'; ?></title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#FF6B35">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="FitGrit">
    
    <!-- Links -->
    <link rel="manifest" href="manifest.json">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/mobile.css">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/icons/icon-32x32.png">
    <link rel="apple-touch-icon" href="assets/images/icons/icon-192x192.png">
    
    <!-- Preload critical resources -->
    <link rel="preload" href="assets/css/style.css" as="style">
    <link rel="preload" href="assets/js/main.js" as="script">
    
    <!-- Chart.js for data visualization -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    
    <!-- Additional page-specific CSS -->
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars($css); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body class="<?php echo isset($bodyClass) ? htmlspecialchars($bodyClass) : 'app-page'; ?>">
    
    <!-- Offline Status Banner -->
    <div id="offlineStatus" class="offline-banner d-none">
        <div class="container">
            <span class="offline-icon">⚠️</span>
            <span class="offline-text">You're offline. Data will sync when connection is restored.</span>
            <?php if ($hasOfflineData): ?>
                <span class="offline-count"><?php echo $_SESSION['offline_data_count']; ?> items pending sync</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="header-left">
                    <!-- Logo -->
                    <div class="logo">
                        <img src="assets/images/logo.png" alt="FitGrit Logo" width="40" height="40">
                        <span>FitGrit</span>
                    </div>
                    
                    <!-- Mobile Menu Toggle -->
                    <button class="mobile-nav-toggle" id="mobileNavToggle" aria-label="Toggle navigation">
                        <span class="hamburger"></span>
                        <span class="hamburger"></span>
                        <span class="hamburger"></span>
                    </button>
                </div>
                
                <!-- Desktop Navigation -->
                <nav class="header-nav">
                    <div class="nav-container" id="navContainer">
                        <?php include 'navigation.php'; ?>
                    </div>
                </nav>
                
                <!-- Header Right - User Menu -->
                <div class="header-right">
                    <!-- Quick Add Button -->
                    <div class="quick-add-dropdown">
                        <button class="btn btn-primary btn-small quick-add-toggle" id="quickAddToggle" title="Quick Add">
                            <span class="plus-icon">+</span>
                            <span class="btn-text d-none d-md-inline">Add</span>
                        </button>
                        
                        <div class="quick-add-menu" id="quickAddMenu">
                            <a href="weight.php?quick=true" class="quick-add-item">
                                <span class="item-icon">⚖️</span>
                                <span>Log Weight</span>
                            </a>
                            <a href="exercise.php?quick=true" class="quick-add-item">
                                <span class="item-icon">💪</span>
                                <span>Log Exercise</span>
                            </a>
                            <a href="food.php?quick=true" class="quick-add-item">
                                <span class="item-icon">🍎</span>
                                <span>Log Food</span>
                            </a>
                            <a href="recipes.php?add=true" class="quick-add-item">
                                <span class="item-icon">📝</span>
                                <span>Add Recipe</span>
                            </a>
                        </div>
                    </div>
                    
                    <!-- User Profile Dropdown -->
                    <div class="user-dropdown">
                        <button class="btn btn-primary btn-small user-toggle" id="userToggle" aria-label="User menu" title="User Menu">
                            <div class="user-avatar-mini">
                                <?php echo htmlspecialchars($userInitials); ?>
                            </div>
                            <span class="user-name d-none d-lg-inline">
                                <?php echo htmlspecialchars($firstName); ?>
                            </span>
                            <span class="dropdown-arrow d-none d-md-inline">▼</span>
                        </button>
                        
                        <div class="user-menu" id="userMenu">
                            <div class="user-info">
                                <div class="user-avatar-large">
                                    <?php echo htmlspecialchars($userInitials); ?>
                                </div>
                                <div class="user-details">
                                    <div class="user-full-name">
                                        <?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?>
                                    </div>
                                    <div class="user-email">
                                        <?php echo htmlspecialchars($currentUser['email']); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="user-menu-divider"></div>
                            
                            <a href="profile.php" class="user-menu-item">
                                <span class="menu-icon">👤</span>
                                <span>Profile Settings</span>
                            </a>
                            
                            <a href="dashboard.php" class="user-menu-item">
                                <span class="menu-icon">📊</span>
                                <span>Dashboard</span>
                            </a>
                            
                            <div class="user-menu-divider"></div>
                            
                            <div class="user-menu-item app-info">
                                <span class="menu-icon">📱</span>
                                <span>App Status</span>
                                <div class="app-status-indicators">
                                    <span class="status-indicator online" id="connectionStatus" title="Online">🟢</span>
                                    <span class="status-indicator pwa" id="pwaStatus" title="PWA">📱</span>
                                    <span class="status-indicator sync" id="syncStatus" title="Synced">✅</span>
                                </div>
                            </div>
                            
                            <div class="user-menu-divider"></div>
                            
                            <a href="logout.php" class="user-menu-item logout">
                                <span class="menu-icon">🚪</span>
                                <span>Sign Out</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <style>
        /* Header Layout Fixes */
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: var(--spacing-md);
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            flex-shrink: 0;
        }
        
        .header-nav {
            flex: 1;
            display: flex;
            justify-content: center;
            min-width: 0; /* Allow flex item to shrink */
        }
        
        .nav-container {
            position: relative;
            max-width: 100%;
            overflow: hidden;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            flex-shrink: 0;
        }
        
        /* Mobile adjustments */
        @media (max-width: 768px) {
            .header-nav {
                display: none;
            }
            
            .header-right .user-name {
                display: none;
            }
        }
        
        /* Tablet adjustments */
        @media (max-width: 1024px) {
            .header-content {
                gap: var(--spacing-sm);
            }
            
            .header-right .btn-text {
                display: none;
            }
        }
    </style>

    <!-- Mobile Navigation Overlay -->
    <div class="mobile-nav" id="mobileNav">
        <div class="mobile-nav-header">
            <div class="logo">
                <img src="assets/images/logo.png" alt="FitGrit Logo" width="40" height="40">
                <span>FitGrit</span>
            </div>
            <button class="mobile-nav-close" id="mobileNavClose">×</button>
        </div>
        
        <nav class="mobile-nav-content">
            <?php include 'navigation.php'; ?>
            
            <!-- Mobile User Info -->
            <div class="mobile-user-info">
                <div class="user-avatar-large">
                    <?php echo htmlspecialchars($userInitials); ?>
                </div>
                <div class="user-details">
                    <div class="user-full-name">
                        <?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?>
                    </div>
                    <div class="user-email">
                        <?php echo htmlspecialchars($currentUser['email']); ?>
                    </div>
                </div>
            </div>
            
            <!-- Mobile Quick Actions -->
            <div class="mobile-quick-actions">
                <h4>Quick Actions</h4>
                <div class="quick-actions-grid">
                    <a href="weight.php?quick=true" class="quick-action-btn">
                        <span class="action-icon">⚖️</span>
                        <span>Weight</span>
                    </a>
                    <a href="exercise.php?quick=true" class="quick-action-btn">
                        <span class="action-icon">💪</span>
                        <span>Exercise</span>
                    </a>
                    <a href="food.php?quick=true" class="quick-action-btn">
                        <span class="action-icon">🍎</span>
                        <span>Food</span>
                    </a>
                    <a href="recipes.php?add=true" class="quick-action-btn">
                        <span class="action-icon">📝</span>
                        <span>Recipe</span>
                    </a>
                </div>
            </div>
        </nav>
        
        <div class="mobile-nav-footer">
            <a href="profile.php" class="btn btn-outline btn-small">Profile</a>
            <a href="logout.php" class="btn btn-secondary btn-small">Sign Out</a>
        </div>
    </div>

    <!-- Mobile Navigation Backdrop -->
    <div class="mobile-nav-backdrop" id="mobileNavBackdrop"></div>

    <!-- Main Content Container -->
    <main class="main-content" id="mainContent">
        
        <!-- Page Loading Indicator -->
        <div class="page-loading" id="pageLoading">
            <div class="loading-spinner"></div>
            <div class="loading-text">Loading...</div>
        </div>

        <!-- Flash Messages -->
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="container">
                <div class="alert alert-<?php echo $_SESSION['flash_type'] ?? 'info'; ?> flash-message">
                    <span class="alert-icon">
                        <?php
                        switch ($_SESSION['flash_type'] ?? 'info') {
                            case 'success': echo '✅'; break;
                            case 'error': echo '❌'; break;
                            case 'warning': echo '⚠️'; break;
                            default: echo 'ℹ️'; break;
                        }
                        ?>
                    </span>
                    <span class="alert-message"><?php echo htmlspecialchars($_SESSION['flash_message']); ?></span>
                    <button class="alert-close" onclick="this.parentElement.remove()">×</button>
                </div>
            </div>
            <?php 
            // Clear flash message after displaying
            unset($_SESSION['flash_message'], $_SESSION['flash_type']); 
            ?>
        <?php endif; ?>

        <!-- CSRF Token for JavaScript -->
        <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">

        <!-- Page Content Starts Here -->
        <?php
        // Hide page loading indicator
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                const pageLoading = document.getElementById("pageLoading");
                if (pageLoading) {
                    pageLoading.style.display = "none";
                }
                
                // Initialize header functionality
                initializeHeaderDropdowns();
                initializeMobileNavigation();
            });
            
            function initializeHeaderDropdowns() {
                // Quick Add Dropdown
                const quickAddToggle = document.getElementById("quickAddToggle");
                const quickAddMenu = document.getElementById("quickAddMenu");
                
                if (quickAddToggle && quickAddMenu) {
                    quickAddToggle.addEventListener("click", function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        toggleDropdown(quickAddMenu);
                        
                        // Close user menu if open
                        const userMenu = document.getElementById("userMenu");
                        if (userMenu && userMenu.classList.contains("show")) {
                            userMenu.classList.remove("show");
                        }
                    });
                }
                
                // User Dropdown
                const userToggle = document.getElementById("userToggle");
                const userMenu = document.getElementById("userMenu");
                
                if (userToggle && userMenu) {
                    userToggle.addEventListener("click", function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        toggleDropdown(userMenu);
                        
                        // Close quick add menu if open
                        const quickAddMenu = document.getElementById("quickAddMenu");
                        if (quickAddMenu && quickAddMenu.classList.contains("show")) {
                            quickAddMenu.classList.remove("show");
                        }
                    });
                }
                
                // Close dropdowns when clicking outside
                document.addEventListener("click", function(e) {
                    const dropdowns = document.querySelectorAll(".quick-add-menu, .user-menu");
                    dropdowns.forEach(dropdown => {
                        if (!dropdown.contains(e.target) && !dropdown.previousElementSibling.contains(e.target)) {
                            dropdown.classList.remove("show");
                        }
                    });
                });
                
                // Close dropdowns on escape key
                document.addEventListener("keydown", function(e) {
                    if (e.key === "Escape") {
                        const dropdowns = document.querySelectorAll(".quick-add-menu, .user-menu");
                        dropdowns.forEach(dropdown => {
                            dropdown.classList.remove("show");
                        });
                    }
                });
            }
            
            function toggleDropdown(dropdown) {
                dropdown.classList.toggle("show");
            }
            
            function initializeMobileNavigation() {
                const mobileNavToggle = document.getElementById("mobileNavToggle");
                const mobileNav = document.getElementById("mobileNav");
                const mobileNavClose = document.getElementById("mobileNavClose");
                const mobileNavBackdrop = document.getElementById("mobileNavBackdrop");
                
                if (mobileNavToggle && mobileNav) {
                    mobileNavToggle.addEventListener("click", function(e) {
                        e.preventDefault();
                        openMobileNav();
                    });
                }
                
                if (mobileNavClose) {
                    mobileNavClose.addEventListener("click", function(e) {
                        e.preventDefault();
                        closeMobileNav();
                    });
                }
                
                if (mobileNavBackdrop) {
                    mobileNavBackdrop.addEventListener("click", function(e) {
                        e.preventDefault();
                        closeMobileNav();
                    });
                }
                
                // Close mobile nav on escape key
                document.addEventListener("keydown", function(e) {
                    if (e.key === "Escape" && mobileNav && mobileNav.classList.contains("active")) {
                        closeMobileNav();
                    }
                });
            }
            
            function openMobileNav() {
                const mobileNav = document.getElementById("mobileNav");
                const mobileNavBackdrop = document.getElementById("mobileNavBackdrop");
                
                if (mobileNav) {
                    mobileNav.classList.add("active");
                    document.body.classList.add("mobile-nav-open");
                }
                
                if (mobileNavBackdrop) {
                    mobileNavBackdrop.classList.add("active");
                }
            }
            
            function closeMobileNav() {
                const mobileNav = document.getElementById("mobileNav");
                const mobileNavBackdrop = document.getElementById("mobileNavBackdrop");
                
                if (mobileNav) {
                    mobileNav.classList.remove("active");
                    document.body.classList.remove("mobile-nav-open");
                }
                
                if (mobileNavBackdrop) {
                    mobileNavBackdrop.classList.remove("active");
                }
            }
        </script>';
        ?>

        <style>
            /* Dropdown Styles */
            .quick-add-dropdown, .user-dropdown {
                position: relative;
            }
            
            .quick-add-menu, .user-menu {
                position: absolute;
                top: 100%;
                right: 0;
                background: var(--light-grey);
                border: 1px solid var(--border-grey);
                border-radius: var(--radius-lg);
                box-shadow: 0 8px 32px var(--shadow);
                z-index: 1000;
                min-width: 200px;
                opacity: 0;
                visibility: hidden;
                transform: translateY(-10px);
                transition: all var(--transition-normal);
                margin-top: var(--spacing-sm);
            }
            
            .quick-add-menu.show, .user-menu.show {
                opacity: 1;
                visibility: visible;
                transform: translateY(0);
            }
            
            .quick-add-item, .user-menu-item {
                display: flex;
                align-items: center;
                gap: var(--spacing-sm);
                padding: var(--spacing-md);
                color: var(--text-light);
                text-decoration: none;
                transition: background var(--transition-fast);
                border-radius: var(--radius-md);
                margin: var(--spacing-xs);
            }
            
            .quick-add-item:hover, .user-menu-item:hover {
                background: var(--border-grey);
                color: var(--primary-orange);
            }
            
            .quick-add-item:first-child, .user-menu-item:first-child {
                margin-top: var(--spacing-sm);
            }
            
            .quick-add-item:last-child, .user-menu-item:last-child {
                margin-bottom: var(--spacing-sm);
            }
            
            .item-icon, .menu-icon {
                font-size: 1.2rem;
                min-width: 24px;
                text-align: center;
            }
            
            .user-info {
                padding: var(--spacing-lg);
                border-bottom: 1px solid var(--border-grey);
                display: flex;
                align-items: center;
                gap: var(--spacing-md);
            }
            
            /* User Profile Button Styling - Match Quick Add */
            .user-toggle {
                /* Reset default button styles */
                background: none;
                border: none;
                padding: 0;
                margin: 0;
                cursor: pointer;
            }
            
            .user-toggle.btn {
                /* Apply consistent button styling */
                display: flex;
                align-items: center;
                gap: var(--spacing-xs);
                padding: var(--spacing-sm) var(--spacing-md);
                background: linear-gradient(135deg, var(--primary-orange), var(--light-orange));
                color: var(--white);
                border-radius: var(--radius-md);
                font-weight: 500;
                transition: all var(--transition-normal);
                box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
                position: relative;
                overflow: hidden;
                min-height: 36px;
            }
            
            .user-toggle.btn::before {
                content: '';
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
                transition: left var(--transition-slow);
            }
            
            .user-toggle.btn:hover::before {
                left: 100%;
            }
            
            .user-toggle.btn:hover {
                background: linear-gradient(135deg, var(--light-orange), var(--primary-orange));
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(255, 107, 53, 0.4);
            }
            
            .user-avatar-mini {
                background: rgba(255, 255, 255, 0.2);
                color: var(--white);
                border-radius: 50%;
                width: 24px;
                height: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 600;
                font-size: 0.75rem;
                border: 1px solid rgba(255, 255, 255, 0.3);
            }
            
            .user-name {
                font-size: 0.9rem;
                font-weight: 500;
            }
            
            .dropdown-arrow {
                font-size: 0.7rem;
                opacity: 0.8;
                transition: transform var(--transition-fast);
            }
            
            .user-toggle.btn:hover .dropdown-arrow {
                transform: translateY(1px);
            }
            
            /* Consistent dropdown positioning */
            .quick-add-dropdown, .user-dropdown {
                position: relative;
            }
            
            /* Make dropdowns consistent */
            .quick-add-menu, .user-menu {
                position: absolute;
                top: 100%;
                right: 0;
                background: var(--light-grey);
                border: 1px solid var(--primary-orange);
                border-radius: var(--radius-lg);
                box-shadow: 0 8px 32px var(--shadow);
                z-index: 1000;
                min-width: 200px;
                opacity: 0;
                visibility: hidden;
                transform: translateY(-10px);
                transition: all var(--transition-normal);
                margin-top: var(--spacing-sm);
                backdrop-filter: blur(10px);
            }
            
            .quick-add-menu.show, .user-menu.show {
                opacity: 1;
                visibility: visible;
                transform: translateY(0);
            }
            
            /* Consistent menu item styling */
            .quick-add-item, .user-menu-item {
                display: flex;
                align-items: center;
                gap: var(--spacing-sm);
                padding: var(--spacing-md);
                color: var(--text-light);
                text-decoration: none;
                transition: all var(--transition-fast);
                border-radius: var(--radius-md);
                margin: var(--spacing-xs);
                position: relative;
                overflow: hidden;
            }
            
            .quick-add-item::before, .user-menu-item::before {
                content: '';
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255, 107, 53, 0.1), transparent);
                transition: left var(--transition-normal);
            }
            
            .quick-add-item:hover::before, .user-menu-item:hover::before {
                left: 100%;
            }
            
            .quick-add-item:hover, .user-menu-item:hover {
                background: linear-gradient(135deg, var(--primary-orange), var(--light-orange));
                color: var(--white);
                transform: translateX(4px);
            }
            
            .quick-add-item:first-child, .user-menu-item:first-child {
                margin-top: var(--spacing-sm);
            }
            
            .quick-add-item:last-child, .user-menu-item:last-child {
                margin-bottom: var(--spacing-sm);
            }
            
            /* Enhanced user info section */
            .user-info {
                padding: var(--spacing-lg);
                border-bottom: 1px solid var(--border-grey);
                display: flex;
                align-items: center;
                gap: var(--spacing-md);
                background: linear-gradient(135deg, rgba(255, 107, 53, 0.1), rgba(255, 138, 91, 0.1));
                border-radius: var(--radius-lg) var(--radius-lg) 0 0;
            }
            
            .user-avatar-large {
                background: linear-gradient(135deg, var(--primary-orange), var(--light-orange));
                color: var(--white);
                border-radius: 50%;
                width: 48px;
                height: 48px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 600;
                font-size: 1.1rem;
                border: 2px solid rgba(255, 255, 255, 0.3);
                box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
            }
            
            .user-details {
                flex: 1;
            }
            
            .user-full-name {
                font-weight: 600;
                color: var(--text-light);
                margin-bottom: var(--spacing-xs);
                font-size: 1.1rem;
            }
            
            .user-email {
                font-size: 0.85rem;
                color: var(--primary-orange);
                opacity: 0.8;
            }
            
            .user-menu-divider {
                height: 1px;
                background: linear-gradient(90deg, transparent, var(--primary-orange), transparent);
                margin: var(--spacing-xs) var(--spacing-md);
                opacity: 0.3;
            }
            
            .app-info {
                cursor: default;
                background: rgba(255, 107, 53, 0.05) !important;
            }
            
            .app-info:hover {
                background: rgba(255, 107, 53, 0.05) !important;
                transform: none !important;
                color: var(--text-light) !important;
            }
            
            .app-info::before {
                display: none !important;
            }
            
            .app-status-indicators {
                display: flex;
                gap: var(--spacing-xs);
                margin-left: auto;
            }
            
            .status-indicator {
                font-size: 0.8rem;
                padding: 2px 4px;
                border-radius: 4px;
                background: rgba(255, 255, 255, 0.1);
            }
            
            .logout {
                color: var(--accent-red) !important;
                border: 1px solid rgba(220, 20, 60, 0.3);
            }
            
            .logout:hover {
                background: linear-gradient(135deg, var(--accent-red), #B8122A) !important;
                color: var(--white) !important;
                border-color: var(--accent-red);
            }
            
            .item-icon, .menu-icon {
                font-size: 1.2rem;
                min-width: 24px;
                text-align: center;
                filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.3));
            }
            
            /* Mobile Navigation Styles */
            .mobile-nav-toggle {
                display: none;
                background: none;
                border: none;
                color: var(--primary-orange);
                cursor: pointer;
                flex-direction: column;
                gap: 3px;
                padding: var(--spacing-xs);
            }
            
            .hamburger {
                width: 20px;
                height: 2px;
                background: var(--primary-orange);
                transition: all var(--transition-normal);
            }
            
            .mobile-nav-toggle.active .hamburger:nth-child(1) {
                transform: rotate(45deg) translate(5px, 5px);
            }
            
            .mobile-nav-toggle.active .hamburger:nth-child(2) {
                opacity: 0;
            }
            
            .mobile-nav-toggle.active .hamburger:nth-child(3) {
                transform: rotate(-45deg) translate(7px, -6px);
            }
            
            .mobile-nav {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100vh;
                background: var(--darker-grey);
                z-index: 2000;
                transform: translateX(-100%);
                transition: transform var(--transition-normal);
                display: flex;
                flex-direction: column;
            }
            
            .mobile-nav.active {
                transform: translateX(0);
            }
            
            .mobile-nav-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: var(--spacing-lg);
                border-bottom: 1px solid var(--border-grey);
            }
            
            .mobile-nav-close {
                background: none;
                border: none;
                color: var(--primary-orange);
                font-size: 2rem;
                cursor: pointer;
                padding: var(--spacing-xs);
                line-height: 1;
            }
            
            .mobile-nav-content {
                flex: 1;
                padding: var(--spacing-lg);
                overflow-y: auto;
            }
            
            .mobile-user-info {
                display: flex;
                align-items: center;
                gap: var(--spacing-md);
                padding: var(--spacing-lg);
                background: var(--light-grey);
                border-radius: var(--radius-lg);
                margin-bottom: var(--spacing-lg);
            }
            
            .mobile-quick-actions h4 {
                color: var(--primary-orange);
                margin-bottom: var(--spacing-md);
            }
            
            .quick-actions-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: var(--spacing-md);
            }
            
            .quick-action-btn {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: var(--spacing-sm);
                padding: var(--spacing-lg);
                background: var(--light-grey);
                border-radius: var(--radius-lg);
                text-decoration: none;
                color: var(--text-light);
                transition: all var(--transition-normal);
            }
            
            .quick-action-btn:hover {
                background: var(--primary-orange);
                color: var(--white);
                transform: translateY(-2px);
            }
            
            .action-icon {
                font-size: 1.5rem;
            }
            
            .mobile-nav-footer {
                padding: var(--spacing-lg);
                border-top: 1px solid var(--border-grey);
                display: flex;
                gap: var(--spacing-md);
            }
            
            .mobile-nav-backdrop {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100vh;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1999;
                opacity: 0;
                visibility: hidden;
                transition: all var(--transition-normal);
            }
            
            .mobile-nav-backdrop.active {
                opacity: 1;
                visibility: visible;
            }
            
            .mobile-nav-open {
                overflow: hidden;
            }
            
            /* Responsive adjustments */
            @media (max-width: 768px) {
                .mobile-nav-toggle {
                    display: flex;
                }
                
                .user-name {
                    display: none;
                }
            }
            
            @media (max-width: 480px) {
                .quick-add-menu, .user-menu {
                    left: 0;
                    right: 0;
                    margin: 0 var(--spacing-md);
                    margin-top: var(--spacing-sm);
                }
            }
        </style>