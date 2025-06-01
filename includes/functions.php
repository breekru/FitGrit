<?php
// FitGrit Core Functions
// Essential utility functions for the application

// Prevent direct access
if (!defined('FITGRIT_ACCESS')) {
    die('Direct access not permitted');
}

/**
 * Generate a secure random string
 * @param int $length Length of the string
 * @return string Random string
 */
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Generate a unique user ID
 * @return string Unique user ID
 */
function generateUserId() {
    return 'user_' . time() . '_' . generateRandomString(8);
}

/**
 * Generate a unique session ID
 * @return string Unique session ID
 */
function generateSessionId() {
    return 'sess_' . time() . '_' . generateRandomString(16);
}

/**
 * Sanitize input data
 * @param mixed $data Input data
 * @return mixed Sanitized data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    if (is_string($data)) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    
    return $data;
}

/**
 * Validate email address
 * @param string $email Email to validate
 * @return bool True if valid
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength
 * @param string $password Password to validate
 * @return array Result with 'valid' boolean and 'message' string
 */
function validatePassword($password) {
    $result = ['valid' => false, 'message' => ''];
    
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $result['message'] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long';
        return $result;
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $result['message'] = 'Password must contain at least one uppercase letter';
        return $result;
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $result['message'] = 'Password must contain at least one lowercase letter';
        return $result;
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $result['message'] = 'Password must contain at least one number';
        return $result;
    }
    
    $result['valid'] = true;
    $result['message'] = 'Password is valid';
    return $result;
}

/**
 * Hash password securely
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password against hash
 * @param string $password Plain text password
 * @param string $hash Stored hash
 * @return bool True if password matches
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Log activity for debugging and security
 * @param string $message Log message
 * @param string $level Log level (INFO, WARNING, ERROR)
 * @param string $userId User ID (optional)
 */
function logActivity($message, $level = 'INFO', $userId = null) {
    $timestamp = date('Y-m-d H:i:s');
    $userInfo = $userId ? " [User: $userId]" : '';
    $logEntry = "[$timestamp] [$level]$userInfo $message" . PHP_EOL;
    
    $logFile = DATA_PATH . 'activity.log';
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Format date for display
 * @param string $date Date string
 * @param string $format Display format
 * @return string Formatted date
 */
function formatDate($date, $format = 'M j, Y') {
    return date($format, strtotime($date));
}

/**
 * Format datetime for display
 * @param string $datetime Datetime string
 * @param string $format Display format
 * @return string Formatted datetime
 */
function formatDateTime($datetime, $format = 'M j, Y g:i A') {
    return date($format, strtotime($datetime));
}

/**
 * Calculate BMI
 * @param float $weight Weight in pounds
 * @param float $height Height in inches
 * @return float BMI value
 */
function calculateBMI($weight, $height) {
    if ($height <= 0) return 0;
    return round(($weight / ($height * $height)) * 703, 1);
}

/**
 * Get BMI category
 * @param float $bmi BMI value
 * @return string BMI category
 */
function getBMICategory($bmi) {
    if ($bmi < 18.5) return 'Underweight';
    if ($bmi < 25) return 'Normal weight';
    if ($bmi < 30) return 'Overweight';
    return 'Obese';
}

/**
 * Convert weight units
 * @param float $weight Weight value
 * @param string $from Source unit (lbs or kg)
 * @param string $to Target unit (lbs or kg)
 * @return float Converted weight
 */
function convertWeight($weight, $from, $to) {
    if ($from === $to) return $weight;
    
    if ($from === 'lbs' && $to === 'kg') {
        return round($weight * 0.453592, 1);
    }
    
    if ($from === 'kg' && $to === 'lbs') {
        return round($weight * 2.20462, 1);
    }
    
    return $weight;
}

/**
 * Convert height units
 * @param float $height Height value
 * @param string $from Source unit (inches or cm)
 * @param string $to Target unit (inches or cm)
 * @return float Converted height
 */
function convertHeight($height, $from, $to) {
    if ($from === $to) return $height;
    
    if ($from === 'inches' && $to === 'cm') {
        return round($height * 2.54, 1);
    }
    
    if ($from === 'cm' && $to === 'inches') {
        return round($height * 0.393701, 1);
    }
    
    return $height;
}

/**
 * Generate response array for API endpoints
 * @param bool $success Success status
 * @param string $message Response message
 * @param mixed $data Response data (optional)
 * @return array Response array
 */
function apiResponse($success, $message, $data = null) {
    $response = [
        'success' => $success,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    return $response;
}

/**
 * Send JSON response and exit
 * @param array $response Response array
 */
function sendJsonResponse($response) {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

/**
 * Check if request is AJAX
 * @return bool True if AJAX request
 */
function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Generate CSRF token
 * @return string CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateRandomString(32);
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token Token to verify
 * @return bool True if valid
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Redirect to a page
 * @param string $url URL to redirect to
 * @param int $statusCode HTTP status code
 */
function redirect($url, $statusCode = 302) {
    header("Location: $url", true, $statusCode);
    exit;
}

/**
 * Check if user is on mobile device
 * @return bool True if mobile
 */
function isMobile() {
    return preg_match('/Mobile|Android|iPhone|iPad/', $_SERVER['HTTP_USER_AGENT'] ?? '');
}
?>