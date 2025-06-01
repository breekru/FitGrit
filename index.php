<?php
// FitGrit Landing/Login Page
define('FITGRIT_ACCESS', true);

// Include core files
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/data-handler.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

// Handle login form submission
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']);
    
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token mismatch. Please try again.';
    } else {
        $result = authenticateUser($email, $password, $rememberMe);
        
        if ($result['success']) {
            redirect('dashboard.php');
        } else {
            $error = $result['message'];
        }
    }
}

// Handle registration form submission
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'register') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $firstName = sanitizeInput($_POST['first_name'] ?? '');
    $lastName = sanitizeInput($_POST['last_name'] ?? '');
    
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token mismatch. Please try again.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $result = registerUser($email, $password, $firstName, $lastName);
        
        if ($result['success']) {
            $success = 'Registration successful! Please log in with your credentials.';
        } else {
            $error = $result['message'];
        }
    }
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="FitGrit - Track your fitness journey, weight loss, exercise, and nutrition goals">
    <meta name="keywords" content="fitness, weight loss, exercise, nutrition, health tracker">
    <meta name="author" content="FitGrit">
    
    <title>FitGrit - Your Personal Fitness Companion</title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#FF6B35">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="FitGrit">
    
    <!-- Links -->
    <link rel="manifest" href="manifest.json">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/icons/icon-32x32.png">
    <link rel="apple-touch-icon" href="assets/images/icons/icon-192x192.png">
    
    <!-- Preload critical resources -->
    <link rel="preload" href="assets/css/style.css" as="style">
    <link rel="preload" href="assets/js/main.js" as="script">
</head>
<body class="landing-page">
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="logo">
                <img src="assets/images/logo.png" alt="FitGrit Logo" width="40" height="40">
                <span>FitGrit</span>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <div class="landing-hero">
                <div class="row">
                    <!-- Left Column - Hero Content -->
                    <div class="col-6">
                        <div class="hero-content">
                            <h1>Transform Your Fitness Journey</h1>
                            <p class="hero-subtitle">Track weight loss, log exercises, monitor nutrition, and store your favorite recipes - all in one powerful, offline-capable app.</p>
                            
                            <div class="feature-highlights">
                                <div class="feature-item">
                                    <div class="feature-icon">📊</div>
                                    <div class="feature-text">
                                        <h4>Weight Tracking</h4>
                                        <p>Visualize your progress with beautiful charts and graphs</p>
                                    </div>
                                </div>
                                
                                <div class="feature-item">
                                    <div class="feature-icon">💪</div>
                                    <div class="feature-text">
                                        <h4>Exercise Logging</h4>
                                        <p>Record workouts, track calories burned, and monitor activity</p>
                                    </div>
                                </div>
                                
                                <div class="feature-item">
                                    <div class="feature-icon">🍎</div>
                                    <div class="feature-text">
                                        <h4>Nutrition Tracking</h4>
                                        <p>Log meals, track calories, and maintain a balanced diet</p>
                                    </div>
                                </div>
                                
                                <div class="feature-item">
                                    <div class="feature-icon">📱</div>
                                    <div class="feature-text">
                                        <h4>Works Offline</h4>
                                        <p>Access your data anywhere, even without internet connection</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Column - Login/Register Forms -->
                    <div class="col-6">
                        <div class="auth-container">
                            <!-- Display messages -->
                            <?php if ($error): ?>
                                <div class="alert alert-error">
                                    <span>⚠️</span>
                                    <?php echo htmlspecialchars($error); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($success): ?>
                                <div class="alert alert-success">
                                    <span>✅</span>
                                    <?php echo htmlspecialchars($success); ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Login Form -->
                            <div class="auth-form" id="loginForm">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Welcome Back</h3>
                                        <p>Sign in to continue your fitness journey</p>
                                    </div>
                                    
                                    <form method="POST" action="" class="login-form">
                                        <input type="hidden" name="action" value="login">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                        
                                        <div class="form-group">
                                            <label for="login_email" class="form-label">Email Address</label>
                                            <input type="email" id="login_email" name="email" class="form-control" 
                                                   placeholder="Enter your email" required autocomplete="email"
                                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="login_password" class="form-label">Password</label>
                                            <input type="password" id="login_password" name="password" class="form-control" 
                                                   placeholder="Enter your password" required autocomplete="current-password">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="checkbox-label">
                                                <input type="checkbox" name="remember_me" class="checkbox">
                                                <span class="checkmark"></span>
                                                Remember me for 30 days
                                            </label>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary btn-block">
                                            <span class="btn-text">Sign In</span>
                                            <span class="spinner d-none"></span>
                                        </button>
                                    </form>
                                    
                                    <div class="card-footer">
                                        <p>Don't have an account? 
                                            <a href="#" onclick="toggleAuthForm()" class="toggle-link">Create one here</a>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Register Form -->
                            <div class="auth-form d-none" id="registerForm">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Join FitGrit</h3>
                                        <p>Create your account and start tracking today</p>
                                    </div>
                                    
                                    <form method="POST" action="" class="register-form">
                                        <input type="hidden" name="action" value="register">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                        
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label for="first_name" class="form-label">First Name</label>
                                                <input type="text" id="first_name" name="first_name" class="form-control" 
                                                       placeholder="First name" required autocomplete="given-name"
                                                       value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="last_name" class="form-label">Last Name</label>
                                                <input type="text" id="last_name" name="last_name" class="form-control" 
                                                       placeholder="Last name" required autocomplete="family-name"
                                                       value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="register_email" class="form-label">Email Address</label>
                                            <input type="email" id="register_email" name="email" class="form-control" 
                                                   placeholder="Enter your email" required autocomplete="email"
                                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="register_password" class="form-label">Password</label>
                                            <input type="password" id="register_password" name="password" class="form-control" 
                                                   placeholder="Create a strong password" required autocomplete="new-password">
                                            <small class="form-help">Must be at least 8 characters with uppercase, lowercase, and number</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="confirm_password" class="form-label">Confirm Password</label>
                                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                                                   placeholder="Confirm your password" required autocomplete="new-password">
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary btn-block">
                                            <span class="btn-text">Create Account</span>
                                            <span class="spinner d-none"></span>
                                        </button>
                                    </form>
                                    
                                    <div class="card-footer">
                                        <p>Already have an account? 
                                            <a href="#" onclick="toggleAuthForm()" class="toggle-link">Sign in here</a>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- PWA Install Prompt -->
    <div class="install-prompt" id="installPrompt">
        <div class="install-content">
            <strong>Install FitGrit</strong>
            <p>Get the full app experience with offline access and quick shortcuts.</p>
            <div class="install-actions">
                <button class="btn btn-small btn-outline" onclick="dismissInstallPrompt()">Maybe Later</button>
                <button class="btn btn-small btn-primary" onclick="installPWA()">Install App</button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/main.js"></script>
    <script src="assets/js/pwa.js"></script>
    
    <script>
        // Form toggle functionality
        function toggleAuthForm() {
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');
            
            if (loginForm.classList.contains('d-none')) {
                loginForm.classList.remove('d-none');
                registerForm.classList.add('d-none');
            } else {
                loginForm.classList.add('d-none');
                registerForm.classList.remove('d-none');
            }
        }
        
        // Form submission handling
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    const btnText = submitBtn.querySelector('.btn-text');
                    const spinner = submitBtn.querySelector('.spinner');
                    
                    // Show loading state
                    btnText.classList.add('d-none');
                    spinner.classList.remove('d-none');
                    submitBtn.disabled = true;
                });
            });
            
            // Password confirmation validation
            const registerForm = document.querySelector('.register-form');
            if (registerForm) {
                const password = registerForm.querySelector('#register_password');
                const confirmPassword = registerForm.querySelector('#confirm_password');
                
                function validatePasswords() {
                    if (password.value !== confirmPassword.value) {
                        confirmPassword.setCustomValidity('Passwords do not match');
                    } else {
                        confirmPassword.setCustomValidity('');
                    }
                }
                
                password.addEventListener('input', validatePasswords);
                confirmPassword.addEventListener('input', validatePasswords);
            }
        });
    </script>
    
    <style>
        /* Landing page specific styles */
        .landing-page {
            background: linear-gradient(135deg, var(--dark-grey) 0%, var(--darker-grey) 50%, var(--dark-grey) 100%);
            min-height: 100vh;
        }
        
        .landing-hero {
            padding: var(--spacing-xxl) 0;
            min-height: calc(100vh - 80px);
            display: flex;
            align-items: center;
        }
        
        .hero-content h1 {
            font-size: 3.5rem;
            margin-bottom: var(--spacing-lg);
            background: linear-gradient(135deg, var(--primary-orange), var(--light-orange));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hero-subtitle {
            font-size: 1.25rem;
            margin-bottom: var(--spacing-xxl);
            color: var(--text-light);
            line-height: 1.6;
        }
        
        .feature-highlights {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-lg);
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            padding: var(--spacing-md);
            background: rgba(255, 107, 53, 0.1);
            border-radius: var(--radius-lg);
            border-left: 4px solid var(--primary-orange);
        }
        
        .feature-icon {
            font-size: 2rem;
            min-width: 60px;
            text-align: center;
        }
        
        .feature-text h4 {
            color: var(--primary-orange);
            margin-bottom: var(--spacing-xs);
        }
        
        .feature-text p {
            margin: 0;
            font-size: 0.9rem;
            color: var(--text-light);
        }
        
        .auth-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 600px;
        }
        
        .auth-form {
            width: 100%;
            max-width: 400px;
        }
        
        .auth-form .card {
            background: rgba(74, 74, 74, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid var(--primary-orange);
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            cursor: pointer;
            user-select: none;
        }
        
        .checkbox {
            margin: 0;
        }
        
        .form-help {
            display: block;
            margin-top: var(--spacing-xs);
            font-size: 0.875rem;
            color: #999;
        }
        
        .toggle-link {
            color: var(--primary-orange);
            font-weight: 500;
        }
        
        .install-prompt {
            background: linear-gradient(135deg, var(--primary-orange), var(--light-orange));
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .install-content {
            flex: 1;
        }
        
        .install-content strong {
            display: block;
            margin-bottom: var(--spacing-xs);
        }
        
        .install-content p {
            margin: 0;
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .install-actions {
            display: flex;
            gap: var(--spacing-sm);
            margin-top: var(--spacing-sm);
        }
        
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2.5rem;
            }
            
            .landing-hero {
                padding: var(--spacing-lg) 0;
            }
            
            .landing-hero .row {
                flex-direction: column;
            }
            
            .feature-item {
                padding: var(--spacing-sm);
            }
            
            .auth-container {
                margin-top: var(--spacing-xl);
                min-height: auto;
            }
            
            .install-prompt {
                flex-direction: column;
                text-align: center;
            }
            
            .install-actions {
                justify-content: center;
                width: 100%;
            }
        }
    </style>
</body>
</html>