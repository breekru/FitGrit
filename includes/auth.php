<?php
// FitGrit Authentication System
// Handles user registration, login, and session management

// Prevent direct access
if (!defined('FITGRIT_ACCESS')) {
    die('Direct access not permitted');
}

/**
 * Register a new user
 * @param string $email User email
 * @param string $password User password
 * @param string $firstName First name
 * @param string $lastName Last name
 * @return array Result with success status and message
 */
function registerUser($email, $password, $firstName, $lastName) {
    // Validate input
    if (empty($email) || empty($password) || empty($firstName) || empty($lastName)) {
        return apiResponse(false, 'All fields are required');
    }
    
    if (!validateEmail($email)) {
        return apiResponse(false, 'Invalid email address');
    }
    
    $passwordValidation = validatePassword($password);
    if (!$passwordValidation['valid']) {
        return apiResponse(false, $passwordValidation['message']);
    }
    
    // Check if email already exists
    if (emailExists($email)) {
        return apiResponse(false, 'Email address already registered');
    }
    
    // Generate unique user ID
    $userId = generateUserId();
    
    // Create user data
    $userData = [
        'id' => $userId,
        'email' => strtolower(trim($email)),
        'password' => hashPassword($password),
        'first_name' => sanitizeInput($firstName),
        'last_name' => sanitizeInput($lastName),
        'created_at' => date('Y-m-d H:i:s'),
        'last_login' => null,
        'is_active' => true,
        'login_attempts' => 0,
        'locked_until' => null,
        'preferences' => [
            'weight_unit' => DEFAULT_WEIGHT_UNIT,
            'height_unit' => DEFAULT_HEIGHT_UNIT,
            'timezone' => DEFAULT_TIMEZONE,
            'notifications' => true
        ],
        'profile' => [
            'height' => null,
            'gender' => null,
            'birth_date' => null,
            'activity_level' => 'moderate',
            'goal' => 'maintain'
        ]
    ];
    
    // Save user data
    if (saveUserData($userId, $userData)) {
        logActivity("New user registered: $email", 'INFO', $userId);
        return apiResponse(true, 'Registration successful', ['user_id' => $userId]);
    } else {
        logActivity("Failed to register user: $email", 'ERROR');
        return apiResponse(false, 'Registration failed. Please try again.');
    }
}

/**
 * Authenticate user login
 * @param string $email User email
 * @param string $password User password
 * @param bool $rememberMe Remember user login
 * @return array Result with success status and message
 */
function authenticateUser($email, $password, $rememberMe = false) {
    // Validate input
    if (empty($email) || empty($password)) {
        return apiResponse(false, 'Email and password are required');
    }
    
    if (!validateEmail($email)) {
        return apiResponse(false, 'Invalid email address');
    }
    
    // Find user by email
    $userId = findUserByEmail($email);
    if (!$userId) {
        logActivity("Login attempt with non-existent email: $email", 'WARNING');
        return apiResponse(false, 'Invalid email or password');
    }
    
    // Get user data
    $userData = getUserData($userId);
    if (!$userData) {
        return apiResponse(false, 'User data not found');
    }
    
    // Check if account is locked
    if (isAccountLocked($userData)) {
        $lockTime = date('g:i A', strtotime($userData['locked_until']));
        return apiResponse(false, "Account locked due to multiple failed login attempts. Try again after $lockTime.");
    }
    
    // Check if account is active
    if (!$userData['is_active']) {
        return apiResponse(false, 'Account is deactivated. Please contact support.');
    }
    
    // Verify password
    if (!verifyPassword($password, $userData['password'])) {
        handleFailedLogin($userId, $userData);
        logActivity("Failed login attempt: $email", 'WARNING', $userId);
        return apiResponse(false, 'Invalid email or password');
    }
    
    // Successful login - reset failed attempts and update last login
    $userData['login_attempts'] = 0;
    $userData['locked_until'] = null;
    $userData['last_login'] = date('Y-m-d H:i:s');
    saveUserData($userId, $userData);
    
    // Create session
    $sessionId = createUserSession($userId, $rememberMe);
    
    logActivity("Successful login: $email", 'INFO', $userId);
    
    return apiResponse(true, 'Login successful', [
        'user_id' => $userId,
        'session_id' => $sessionId,
        'user_name' => $userData['first_name'] . ' ' . $userData['last_name']
    ]);
}

/**
 * Check if email already exists
 * @param string $email Email to check
 * @return bool True if exists
 */
function emailExists($email) {
    $email = strtolower(trim($email));
    $userFiles = glob(USERS_PATH . '*.json');
    
    foreach ($userFiles as $file) {
        $userData = readJsonFile($file);
        if ($userData && isset($userData['email']) && $userData['email'] === $email) {
            return true;
        }
    }
    
    return false;
}

/**
 * Find user ID by email
 * @param string $email Email to search for
 * @return string|false User ID or false if not found
 */
function findUserByEmail($email) {
    $email = strtolower(trim($email));
    $userFiles = glob(USERS_PATH . '*.json');
    
    foreach ($userFiles as $file) {
        $userData = readJsonFile($file);
        if ($userData && isset($userData['email']) && $userData['email'] === $email) {
            return $userData['id'];
        }
    }
    
    return false;
}

/**
 * Check if account is locked
 * @param array $userData User data
 * @return bool True if locked
 */
function isAccountLocked($userData) {
    if (!isset($userData['locked_until']) || !$userData['locked_until']) {
        return false;
    }
    
    return strtotime($userData['locked_until']) > time();
}

/**
 * Handle failed login attempt
 * @param string $userId User ID
 * @param array $userData User data
 */
function handleFailedLogin($userId, $userData) {
    $userData['login_attempts'] = ($userData['login_attempts'] ?? 0) + 1;
    
    if ($userData['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
        $userData['locked_until'] = date('Y-m-d H:i:s', time() + LOCKOUT_TIME);
        logActivity("Account locked due to failed login attempts", 'WARNING', $userId);
    }
    
    saveUserData($userId, $userData);
}

/**
 * Create user session
 * @param string $userId User ID
 * @param bool $rememberMe Extended session
 * @return string Session ID
 */
function createUserSession($userId, $rememberMe = false) {
    $sessionId = generateSessionId();
    $expiresAt = $rememberMe ? time() + (30 * 24 * 3600) : time() + SESSION_TIMEOUT; // 30 days or session timeout
    
    $sessionData = [
        'session_id' => $sessionId,
        'user_id' => $userId,
        'created_at' => date('Y-m-d H:i:s'),
        'expires_at' => date('Y-m-d H:i:s', $expiresAt),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'is_active' => true
    ];
    
    // Save session data
    $sessionFile = SESSIONS_PATH . $sessionId . '.json';
    writeJsonFile($sessionFile, $sessionData);
    
    // Set session variables
    $_SESSION['user_id'] = $userId;
    $_SESSION['session_id'] = $sessionId;
    $_SESSION['logged_in'] = true;
    
    // Set cookie if remember me is enabled
    if ($rememberMe) {
        setcookie('fitgrit_session', $sessionId, $expiresAt, '/', '', false, true);
    }
    
    return $sessionId;
}

/**
 * Check if user is logged in
 * @return bool True if logged in
 */
function isLoggedIn() {
    // Check session first
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['user_id'])) {
        return validateSession($_SESSION['session_id']);
    }
    
    // Check cookie
    if (isset($_COOKIE['fitgrit_session'])) {
        return validateSession($_COOKIE['fitgrit_session']);
    }
    
    return false;
}

/**
 * Validate session
 * @param string $sessionId Session ID
 * @return bool True if valid
 */
function validateSession($sessionId) {
    if (!$sessionId) return false;
    
    $sessionFile = SESSIONS_PATH . $sessionId . '.json';
    $sessionData = readJsonFile($sessionFile);
    
    if (!$sessionData || !$sessionData['is_active']) {
        return false;
    }
    
    // Check if session expired
    if (strtotime($sessionData['expires_at']) < time()) {
        destroySession($sessionId);
        return false;
    }
    
    // Update session variables if needed
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['user_id'] = $sessionData['user_id'];
        $_SESSION['session_id'] = $sessionId;
        $_SESSION['logged_in'] = true;
    }
    
    return true;
}

/**
 * Get current user ID
 * @return string|false User ID or false if not logged in
 */
function getCurrentUserId() {
    if (isLoggedIn()) {
        return $_SESSION['user_id'];
    }
    return false;
}

/**
 * Get current user data
 * @return array|false User data or false if not logged in
 */
function getCurrentUser() {
    $userId = getCurrentUserId();
    if ($userId) {
        return getUserData($userId);
    }
    return false;
}

/**
 * Logout user
 * @return bool True on success
 */
function logoutUser() {
    $userId = getCurrentUserId();
    $sessionId = $_SESSION['session_id'] ?? null;
    
    // Log activity
    if ($userId) {
        logActivity("User logged out", 'INFO', $userId);
    }
    
    // Destroy session
    if ($sessionId) {
        destroySession($sessionId);
    }
    
    // Clear session variables
    $_SESSION = [];
    
    // Destroy session
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    
    // Clear cookie
    if (isset($_COOKIE['fitgrit_session'])) {
        setcookie('fitgrit_session', '', time() - 3600, '/');
    }
    
    return true;
}

/**
 * Destroy session
 * @param string $sessionId Session ID
 */
function destroySession($sessionId) {
    $sessionFile = SESSIONS_PATH . $sessionId . '.json';
    if (file_exists($sessionFile)) {
        unlink($sessionFile);
    }
}

/**
 * Clean expired sessions
 */
function cleanExpiredSessions() {
    $sessionFiles = glob(SESSIONS_PATH . '*.json');
    $currentTime = time();
    
    foreach ($sessionFiles as $file) {
        $sessionData = readJsonFile($file);
        if ($sessionData && strtotime($sessionData['expires_at']) < $currentTime) {
            unlink($file);
        }
    }
}

/**
 * Require login - redirect if not authenticated
 * @param string $redirectUrl URL to redirect to if not logged in
 */
function requireLogin($redirectUrl = 'index.php') {
    if (!isLoggedIn()) {
        redirect($redirectUrl);
    }
}

/**
 * Update user password
 * @param string $userId User ID
 * @param string $currentPassword Current password
 * @param string $newPassword New password
 * @return array Result with success status and message
 */
function updatePassword($userId, $currentPassword, $newPassword) {
    $userData = getUserData($userId);
    if (!$userData) {
        return apiResponse(false, 'User not found');
    }
    
    // Verify current password
    if (!verifyPassword($currentPassword, $userData['password'])) {
        return apiResponse(false, 'Current password is incorrect');
    }
    
    // Validate new password
    $passwordValidation = validatePassword($newPassword);
    if (!$passwordValidation['valid']) {
        return apiResponse(false, $passwordValidation['message']);
    }
    
    // Update password
    $userData['password'] = hashPassword($newPassword);
    
    if (saveUserData($userId, $userData)) {
        logActivity("Password updated", 'INFO', $userId);
        return apiResponse(true, 'Password updated successfully');
    } else {
        return apiResponse(false, 'Failed to update password');
    }
}

// Clean expired sessions on load
cleanExpiredSessions();
?>