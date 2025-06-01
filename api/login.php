<?php
// FitGrit Login API Endpoint
define('FITGRIT_ACCESS', true);

// Include core files
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/data-handler.php';
require_once '../includes/auth.php';

// Set JSON response header
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(apiResponse(false, 'Only POST requests allowed'));
}

// Check if it's an AJAX request
if (!isAjaxRequest()) {
    sendJsonResponse(apiResponse(false, 'AJAX requests only'));
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (!isset($input['email']) || !isset($input['password'])) {
        sendJsonResponse(apiResponse(false, 'Email and password are required'));
    }
    
    // Verify CSRF token
    if (!isset($input['csrf_token']) || !verifyCSRFToken($input['csrf_token'])) {
        sendJsonResponse(apiResponse(false, 'Security token mismatch'));
    }
    
    $email = sanitizeInput($input['email']);
    $password = $input['password'];
    $rememberMe = isset($input['remember_me']) && $input['remember_me'];
    
    // Validate input
    if (empty($email) || empty($password)) {
        sendJsonResponse(apiResponse(false, 'Email and password cannot be empty'));
    }
    
    if (!validateEmail($email)) {
        sendJsonResponse(apiResponse(false, 'Invalid email format'));
    }
    
    // Attempt authentication
    $result = authenticateUser($email, $password, $rememberMe);
    
    if ($result['success']) {
        // Get user data for response
        $userData = getCurrentUser();
        $responseData = [
            'user_id' => $result['data']['user_id'],
            'user_name' => $result['data']['user_name'],
            'redirect_url' => 'dashboard.php'
        ];
        
        sendJsonResponse(apiResponse(true, 'Login successful', $responseData));
    } else {
        sendJsonResponse(apiResponse(false, $result['message']));
    }
    
} catch (Exception $e) {
    logActivity('Login API error: ' . $e->getMessage(), 'ERROR');
    sendJsonResponse(apiResponse(false, 'An unexpected error occurred'));
}
?>