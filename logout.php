<?php
// FitGrit Logout Handler
// Securely logs out user and cleans up sessions

define('FITGRIT_ACCESS', true);

// Include core files
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/data-handler.php';
require_once 'includes/auth.php';

// Log the logout attempt
$userId = getCurrentUserId();
if ($userId) {
    logActivity("User logout initiated", 'INFO', $userId);
}

// Perform logout
$logoutSuccess = logoutUser();

// Set flash message
if ($logoutSuccess) {
    // Create a temporary session to store the flash message
    session_start();
    $_SESSION['flash_message'] = 'You have been successfully logged out. See you next time!';
    $_SESSION['flash_type'] = 'success';
    
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);
}

// Redirect to login page
redirect('index.php');
?>