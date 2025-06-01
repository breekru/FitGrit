<?php
// FitGrit Configuration File
// Secure configuration settings for the application

// Prevent direct access
if (!defined('FITGRIT_ACCESS')) {
    die('Direct access not permitted');
}

// Application Settings
define('APP_NAME', 'FitGrit');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/fitgrit'); // Change to your domain
define('DEBUG_MODE', true); // Set to false in production

// File Paths (Data directory should be outside web root for security)
define('DATA_PATH', dirname(__DIR__) . '/data/');
define('USERS_PATH', DATA_PATH . 'users/');
define('WEIGHT_PATH', DATA_PATH . 'weight/');
define('EXERCISE_PATH', DATA_PATH . 'exercise/');
define('FOOD_PATH', DATA_PATH . 'food/');
define('RECIPES_PATH', DATA_PATH . 'recipes/');
define('SESSIONS_PATH', DATA_PATH . 'sessions/');

// Security Settings
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes in seconds

// File Settings
define('MAX_FILE_SIZE', 1048576); // 1MB for any uploads
define('BACKUP_ENABLED', true);
define('BACKUP_RETENTION_DAYS', 30);

// Default User Settings
define('DEFAULT_WEIGHT_UNIT', 'lbs'); // lbs or kg
define('DEFAULT_HEIGHT_UNIT', 'inches'); // inches or cm
define('DEFAULT_TIMEZONE', 'America/New_York');

// Color Scheme (for CSS generation)
define('PRIMARY_ORANGE', '#FF6B35');
define('DARK_GREY', '#2C2C2C');
define('LIGHT_GREY', '#4A4A4A');
define('ACCENT_RED', '#DC143C');
define('ACCENT_PURPLE', '#8A2BE2');
define('SUCCESS_GREEN', '#28A745');
define('WARNING_YELLOW', '#FFC107');
define('WHITE', '#FFFFFF');

// Create data directories if they don't exist
function createDataDirectories() {
    $directories = [
        DATA_PATH,
        USERS_PATH,
        WEIGHT_PATH,
        EXERCISE_PATH,
        FOOD_PATH,
        RECIPES_PATH,
        SESSIONS_PATH
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0755, true)) {
                error_log("Failed to create directory: $dir");
                return false;
            }
            
            // Create .htaccess to prevent direct access
            $htaccess = $dir . '.htaccess';
            file_put_contents($htaccess, "Deny from all\n");
        }
    }
    return true;
}

// Initialize data directories
createDataDirectories();

// Error handling
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', DATA_PATH . 'error.log');
}

// Session configuration
ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.use_strict_mode', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerate session ID periodically for security
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}
?>