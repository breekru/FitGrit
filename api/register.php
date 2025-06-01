<?php
// FitGrit Registration API Endpoint
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
    $requiredFields = ['email', 'password', 'confirm_password', 'first_name', 'last_name'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            sendJsonResponse(apiResponse(false, "Field '$field' is required"));
        }
    }
    
    // Verify CSRF token
    if (!isset($input['csrf_token']) || !verifyCSRFToken($input['csrf_token'])) {
        sendJsonResponse(apiResponse(false, 'Security token mismatch'));
    }
    
    $email = sanitizeInput($input['email']);
    $password = $input['password'];
    $confirmPassword = $input['confirm_password'];
    $firstName = sanitizeInput($input['first_name']);
    $lastName = sanitizeInput($input['last_name']);
    
    // Validate email format
    if (!validateEmail($email)) {
        sendJsonResponse(apiResponse(false, 'Invalid email format'));
    }
    
    // Check password confirmation
    if ($password !== $confirmPassword) {
        sendJsonResponse(apiResponse(false, 'Passwords do not match'));
    }
    
    // Validate password strength
    $passwordValidation = validatePassword($password);
    if (!$passwordValidation['valid']) {
        sendJsonResponse(apiResponse(false, $passwordValidation['message']));
    }
    
    // Validate name fields
    if (strlen($firstName) < 2 || strlen($lastName) < 2) {
        sendJsonResponse(apiResponse(false, 'First and last names must be at least 2 characters'));
    }
    
    if (!preg_match('/^[a-zA-Z\s\-\'\.]+$/', $firstName) || !preg_match('/^[a-zA-Z\s\-\'\.]+$/', $lastName)) {
        sendJsonResponse(apiResponse(false, 'Names can only contain letters, spaces, hyphens, apostrophes, and periods'));
    }
    
    // Attempt registration
    $result = registerUser($email, $password, $firstName, $lastName);
    
    if ($result['success']) {
        $responseData = [
            'user_id' => $result['data']['user_id'],
            'message' => 'Registration successful! You can now log in with your credentials.'
        ];
        
        sendJsonResponse(apiResponse(true, 'Registration successful', $responseData));
    } else {
        sendJsonResponse(apiResponse(false, $result['message']));
    }
    
} catch (Exception $e) {
    logActivity('Registration API error: ' . $e->getMessage(), 'ERROR');
    sendJsonResponse(apiResponse(false, 'An unexpected error occurred during registration'));
}
?>