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
                <?php include 'navigation.php'; ?>
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
                    <button class="user-toggle" id="userToggle" aria-label="User menu">
                        <div class="user-avatar">
                            <?php echo htmlspecialchars($userInitials); ?>
                        </div>
                        <span class="user-name d-none d-md-inline">
                            <?php echo htmlspecialchars($firstName); ?>
                        </span>
                        <span class="dropdown-arrow">▼</span>
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
    </header>

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
            });
        </script>';
        ?>