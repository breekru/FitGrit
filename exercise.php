<?php
// FitGrit Exercise Tracking Page
// Comprehensive workout logging with activity tracking and statistics

define('FITGRIT_ACCESS', true);

// Include core files
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/data-handler.php';
require_once 'includes/auth.php';

// Set page variables
$pageTitle = 'Exercise Tracking';
$bodyClass = 'exercise-page';
$additionalCSS = ['assets/css/charts.css'];

// Get current user
$currentUser = getCurrentUser();
$userId = $currentUser['id'];
$userPreferences = $currentUser['preferences'] ?? [];

// Handle form submissions
$message = '';
$messageType = '';

// Handle quick add parameter
$quickAdd = isset($_GET['quick']) && $_GET['quick'] === 'true';

// Common exercise types with calorie estimates (per minute for average person)
$exerciseTypes = [
    'running' => ['name' => 'Running', 'icon' => '🏃', 'calories_per_min' => 12, 'category' => 'cardio'],
    'walking' => ['name' => 'Walking', 'icon' => '🚶', 'calories_per_min' => 4, 'category' => 'cardio'],
    'cycling' => ['name' => 'Cycling', 'icon' => '🚴', 'calories_per_min' => 8, 'category' => 'cardio'],
    'swimming' => ['name' => 'Swimming', 'icon' => '🏊', 'calories_per_min' => 10, 'category' => 'cardio'],
    'weight_lifting' => ['name' => 'Weight Lifting', 'icon' => '🏋️', 'calories_per_min' => 6, 'category' => 'strength'],
    'yoga' => ['name' => 'Yoga', 'icon' => '🧘', 'calories_per_min' => 3, 'category' => 'flexibility'],
    'hiit' => ['name' => 'HIIT', 'icon' => '💥', 'calories_per_min' => 15, 'category' => 'cardio'],
    'pilates' => ['name' => 'Pilates', 'icon' => '🤸', 'calories_per_min' => 4, 'category' => 'flexibility'],
    'basketball' => ['name' => 'Basketball', 'icon' => '⛹️', 'calories_per_min' => 9, 'category' => 'sports'],
    'tennis' => ['name' => 'Tennis', 'icon' => '🎾', 'calories_per_min' => 8, 'category' => 'sports'],
    'soccer' => ['name' => 'Soccer', 'icon' => '⚽', 'calories_per_min' => 10, 'category' => 'sports'],
    'dancing' => ['name' => 'Dancing', 'icon' => '💃', 'calories_per_min' => 5, 'category' => 'cardio'],
    'hiking' => ['name' => 'Hiking', 'icon' => '🥾', 'calories_per_min' => 6, 'category' => 'cardio'],
    'climbing' => ['name' => 'Rock Climbing', 'icon' => '🧗', 'calories_per_min' => 11, 'category' => 'strength'],
    'boxing' => ['name' => 'Boxing', 'icon' => '🥊', 'calories_per_min' => 13, 'category' => 'cardio'],
    'other' => ['name' => 'Other', 'icon' => '🏃', 'calories_per_min' => 6, 'category' => 'general']
];

// Handle exercise entry
if ($_POST && isset($_POST['action'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Security token mismatch. Please try again.';
        $messageType = 'error';
    } else {
        switch ($_POST['action']) {
            case 'add_exercise':
                $exerciseType = sanitizeInput($_POST['exercise_type']);
                $customExercise = sanitizeInput($_POST['custom_exercise'] ?? '');
                $duration = intval($_POST['duration']);
                $calories = intval($_POST['calories'] ?? 0);
                $date = sanitizeInput($_POST['date'] ?? date('Y-m-d'));
                $notes = sanitizeInput($_POST['notes'] ?? '');
                $intensity = sanitizeInput($_POST['intensity'] ?? 'moderate');
                
                // Determine exercise name
                if ($exerciseType === 'other' && !empty($customExercise)) {
                    $exerciseName = $customExercise;
                } elseif (isset($exerciseTypes[$exerciseType])) {
                    $exerciseName = $exerciseTypes[$exerciseType]['name'];
                } else {
                    $exerciseName = 'Exercise';
                }
                
                // Calculate calories if not provided
                if ($calories <= 0 && isset($exerciseTypes[$exerciseType])) {
                    $baseCalories = $exerciseTypes[$exerciseType]['calories_per_min'] * $duration;
                    
                    // Adjust for intensity
                    $intensityMultiplier = [
                        'low' => 0.8,
                        'moderate' => 1.0,
                        'high' => 1.3,
                        'extreme' => 1.6
                    ];
                    
                    $calories = round($baseCalories * ($intensityMultiplier[$intensity] ?? 1.0));
                }
                
                if ($duration > 0 && $duration <= 600) { // Max 10 hours
                    if (addExerciseEntry($userId, $exerciseName, $duration, $calories, $date, $notes)) {
                        $message = 'Exercise logged successfully!';
                        $messageType = 'success';
                        
                        // Redirect if quick add
                        if ($quickAdd) {
                            $_SESSION['flash_message'] = $message;
                            $_SESSION['flash_type'] = $messageType;
                            redirect('dashboard.php');
                        }
                    } else {
                        $message = 'Failed to log exercise. Please try again.';
                        $messageType = 'error';
                    }
                } else {
                    $message = 'Please enter a valid duration between 1 and 600 minutes.';
                    $messageType = 'error';
                }
                break;
                
            case 'delete_exercise':
                $entryId = sanitizeInput($_POST['entry_id']);
                $filePath = EXERCISE_PATH . $userId . '_exercise.json';
                
                if (deleteEntry($filePath, $entryId)) {
                    $message = 'Exercise entry deleted successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to delete exercise entry.';
                    $messageType = 'error';
                }
                break;
        }
    }
}

// Get exercise data
$exerciseEntries = getExerciseData($userId);
$recentEntries = array_slice($exerciseEntries, 0, 15);

// Calculate statistics
$stats = calculateExerciseStats($exerciseEntries);

// Prepare chart data
$weeklyChartData = prepareExerciseChartData($exerciseEntries, 'weekly');
$monthlyChartData = prepareExerciseChartData($exerciseEntries, 'monthly');

// Generate CSRF token
$csrfToken = generateCSRFToken();

// Calculate exercise statistics
function calculateExerciseStats($entries) {
    $stats = [
        'total_workouts' => count($entries),
        'total_minutes' => 0,
        'total_calories' => 0,
        'average_duration' => 0,
        'average_calories' => 0,
        'this_week_minutes' => 0,
        'this_week_workouts' => 0,
        'this_month_minutes' => 0,
        'this_month_workouts' => 0,
        'current_streak' => 0,
        'longest_streak' => 0,
        'favorite_exercise' => '',
        'most_active_day' => '',
        'weekly_goal_progress' => 0, // Progress towards 150 minutes per week
        'calories_per_minute' => 0
    ];
    
    if (empty($entries)) {
        return $stats;
    }
    
    // Sort entries by date (newest first)
    usort($entries, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    $today = date('Y-m-d');
    $thisWeek = date('Y-m-d', strtotime('-7 days'));
    $thisMonth = date('Y-m-d', strtotime('-30 days'));
    
    $exerciseFrequency = [];
    $dayFrequency = [];
    
    foreach ($entries as $entry) {
        $stats['total_minutes'] += $entry['duration'];
        $stats['total_calories'] += $entry['calories'];
        
        // Count exercise types
        $exerciseType = $entry['exercise'];
        $exerciseFrequency[$exerciseType] = ($exerciseFrequency[$exerciseType] ?? 0) + 1;
        
        // Count days of week
        $dayOfWeek = date('w', strtotime($entry['date']));
        $dayFrequency[$dayOfWeek] = ($dayFrequency[$dayOfWeek] ?? 0) + 1;
        
        // This week stats
        if ($entry['date'] >= $thisWeek) {
            $stats['this_week_minutes'] += $entry['duration'];
            $stats['this_week_workouts']++;
        }
        
        // This month stats
        if ($entry['date'] >= $thisMonth) {
            $stats['this_month_minutes'] += $entry['duration'];
            $stats['this_month_workouts']++;
        }
    }
    
    // Calculate averages
    if ($stats['total_workouts'] > 0) {
        $stats['average_duration'] = round($stats['total_minutes'] / $stats['total_workouts'], 1);
        $stats['average_calories'] = round($stats['total_calories'] / $stats['total_workouts'], 1);
    }
    
    if ($stats['total_minutes'] > 0) {
        $stats['calories_per_minute'] = round($stats['total_calories'] / $stats['total_minutes'], 1);
    }
    
    // Find favorite exercise
    if (!empty($exerciseFrequency)) {
        $stats['favorite_exercise'] = array_keys($exerciseFrequency, max($exerciseFrequency))[0];
    }
    
    // Find most active day
    if (!empty($dayFrequency)) {
        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $mostActiveDay = array_keys($dayFrequency, max($dayFrequency))[0];
        $stats['most_active_day'] = $dayNames[$mostActiveDay];
    }
    
    // Calculate streaks
    $stats['current_streak'] = calculateCurrentStreak($entries);
    $stats['longest_streak'] = calculateLongestStreak($entries);
    
    // Weekly goal progress (150 minutes recommended)
    $stats['weekly_goal_progress'] = min(100, round(($stats['this_week_minutes'] / 150) * 100, 1));
    
    return $stats;
}

function calculateCurrentStreak($entries) {
    if (empty($entries)) return 0;
    
    $streak = 0;
    $currentDate = new DateTime();
    $exerciseDates = array_unique(array_column($entries, 'date'));
    rsort($exerciseDates); // Sort newest first
    
    foreach ($exerciseDates as $date) {
        $exerciseDate = new DateTime($date);
        $diffDays = $currentDate->diff($exerciseDate)->days;
        
        if ($diffDays <= $streak + 1) {
            $streak++;
            $currentDate = $exerciseDate;
        } else {
            break;
        }
    }
    
    return $streak;
}

function calculateLongestStreak($entries) {
    if (empty($entries)) return 0;
    
    $exerciseDates = array_unique(array_column($entries, 'date'));
    sort($exerciseDates);
    
    $longestStreak = 0;
    $currentStreak = 1;
    
    for ($i = 1; $i < count($exerciseDates); $i++) {
        $prevDate = new DateTime($exerciseDates[$i - 1]);
        $currentDate = new DateTime($exerciseDates[$i]);
        $diffDays = $prevDate->diff($currentDate)->days;
        
        if ($diffDays <= 1) {
            $currentStreak++;
        } else {
            $longestStreak = max($longestStreak, $currentStreak);
            $currentStreak = 1;
        }
    }
    
    return max($longestStreak, $currentStreak);
}

// Prepare chart data for different time periods
function prepareExerciseChartData($entries, $type = 'weekly') {
    $data = ['labels' => [], 'minutes' => [], 'calories' => []];
    
    if ($type === 'weekly') {
        // Last 7 days
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $data['labels'][] = date('M j', strtotime($date));
            
            $dayMinutes = 0;
            $dayCalories = 0;
            
            foreach ($entries as $entry) {
                if ($entry['date'] === $date) {
                    $dayMinutes += $entry['duration'];
                    $dayCalories += $entry['calories'];
                }
            }
            
            $data['minutes'][] = $dayMinutes;
            $data['calories'][] = $dayCalories;
        }
    } else {
        // Last 4 weeks
        for ($i = 3; $i >= 0; $i--) {
            $weekStart = date('Y-m-d', strtotime("-" . ($i + 1) . " weeks monday"));
            $weekEnd = date('Y-m-d', strtotime("-$i weeks sunday"));
            
            $data['labels'][] = date('M j', strtotime($weekStart));
            
            $weekMinutes = 0;
            $weekCalories = 0;
            
            foreach ($entries as $entry) {
                if ($entry['date'] >= $weekStart && $entry['date'] <= $weekEnd) {
                    $weekMinutes += $entry['duration'];
                    $weekCalories += $entry['calories'];
                }
            }
            
            $data['minutes'][] = $weekMinutes;
            $data['calories'][] = $weekCalories;
        }
    }
    
    return $data;
}

// Include header
include 'includes/header.php';
?>

<!-- Exercise Tracking Content -->
<div class="container exercise-container">
    
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1 class="page-title">
                <span class="title-icon">💪</span>
                Exercise Tracking
            </h1>
            <p class="page-subtitle">Log workouts, track progress, and stay motivated</p>
        </div>
        
        <div class="header-actions">
            <?php if (!$quickAdd): ?>
                <button class="btn btn-outline btn-small" onclick="toggleChartView()">
                    <span class="btn-icon">📊</span>
                    <span class="btn-text">View: <span id="chartViewText">Weekly</span></span>
                </button>
            <?php endif; ?>
            
            <button class="btn btn-primary" onclick="showAddExerciseModal()">
                <span class="btn-icon">➕</span>
                <span class="btn-text">Log Workout</span>
            </button>
        </div>
    </div>

    <!-- Display messages -->
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <span class="alert-icon">
                <?php echo $messageType === 'success' ? '✅' : '❌'; ?>
            </span>
            <span class="alert-message"><?php echo htmlspecialchars($message); ?></span>
            <button class="alert-close" onclick="this.parentElement.remove()">×</button>
        </div>
    <?php endif; ?>

    <?php if ($quickAdd): ?>
        <!-- Quick Add Form -->
        <div class="quick-add-section">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Quick Exercise Log</h3>
                    <a href="exercise.php" class="btn btn-outline btn-small">View Full Page</a>
                </div>
                
                <form method="POST" action="" class="exercise-form">
                    <input type="hidden" name="action" value="add_exercise">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="exercise_type" class="form-label">Exercise Type</label>
                            <select id="exercise_type" name="exercise_type" class="form-control" required onchange="updateCalorieEstimate()">
                                <option value="">Select exercise...</option>
                                <?php foreach ($exerciseTypes as $key => $exercise): ?>
                                    <option value="<?php echo $key; ?>" data-calories="<?php echo $exercise['calories_per_min']; ?>">
                                        <?php echo $exercise['icon'] . ' ' . $exercise['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" id="customExerciseGroup" style="display: none;">
                            <label for="custom_exercise" class="form-label">Custom Exercise</label>
                            <input type="text" id="custom_exercise" name="custom_exercise" class="form-control" 
                                   placeholder="Enter exercise name">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="duration" class="form-label">Duration (minutes)</label>
                            <input type="number" id="duration" name="duration" class="form-control" 
                                   placeholder="30" required min="1" max="600" onchange="updateCalorieEstimate()">
                        </div>
                        
                        <div class="form-group">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" id="date" name="date" class="form-control" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="intensity" class="form-label">Intensity</label>
                            <select id="intensity" name="intensity" class="form-control" onchange="updateCalorieEstimate()">
                                <option value="low">🟢 Low</option>
                                <option value="moderate" selected>🟡 Moderate</option>
                                <option value="high">🟠 High</option>
                                <option value="extreme">🔴 Extreme</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="calories" class="form-label">Calories Burned</label>
                            <input type="number" id="calories" name="calories" class="form-control" 
                                   placeholder="Auto-calculated" min="0">
                            <small class="form-help">Leave empty for auto-calculation</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes" class="form-label">Notes (Optional)</label>
                        <input type="text" id="notes" name="notes" class="form-control" 
                               placeholder="How did the workout feel?">
                    </div>
                    
                    <div class="form-actions">
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <span class="btn-icon">💾</span>
                            <span class="btn-text">Log Exercise</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        
        <!-- Exercise Statistics -->
        <div class="exercise-stats">
            <div class="stats-grid">
                <div class="stat-card weekly-minutes">
                    <div class="stat-icon">⏱️</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['this_week_minutes']; ?> min</div>
                        <div class="stat-label">This Week</div>
                        <div class="stat-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $stats['weekly_goal_progress']; ?>%"></div>
                            </div>
                            <div class="progress-text"><?php echo $stats['weekly_goal_progress']; ?>% of 150 min goal</div>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card total-workouts">
                    <div class="stat-icon">🏃</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['total_workouts']; ?></div>
                        <div class="stat-label">Total Workouts</div>
                        <div class="stat-sublabel">
                            <?php if ($stats['average_duration'] > 0): ?>
                                Avg: <?php echo $stats['average_duration']; ?> min
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card calories-burned">
                    <div class="stat-icon">🔥</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($stats['total_calories']); ?></div>
                        <div class="stat-label">Calories Burned</div>
                        <div class="stat-sublabel">
                            <?php if ($stats['calories_per_minute'] > 0): ?>
                                <?php echo $stats['calories_per_minute']; ?> cal/min
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card current-streak">
                    <div class="stat-icon">🔥</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['current_streak']; ?> days</div>
                        <div class="stat-label">Current Streak</div>
                        <div class="stat-sublabel">
                            Best: <?php echo $stats['longest_streak']; ?> days
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="exercise-grid">
            
            <!-- Exercise Chart -->
            <div class="chart-section">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Activity Overview</h3>
                        <div class="chart-controls">
                            <button class="btn btn-small btn-primary" onclick="switchChartType('minutes')" data-chart="minutes">Minutes</button>
                            <button class="btn btn-small btn-outline" onclick="switchChartType('calories')" data-chart="calories">Calories</button>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <?php if (!empty($weeklyChartData['minutes']) && array_sum($weeklyChartData['minutes']) > 0): ?>
                            <div class="chart-container">
                                <canvas id="exerciseChart" width="400" height="200"></canvas>
                            </div>
                            
                            <div class="chart-summary">
                                <div class="summary-item">
                                    <span class="summary-label">This Week</span>
                                    <span class="summary-value"><?php echo $stats['this_week_minutes']; ?> min</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Workouts</span>
                                    <span class="summary-value"><?php echo $stats['this_week_workouts']; ?></span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Favorite</span>
                                    <span class="summary-value"><?php echo $stats['favorite_exercise'] ?: 'None yet'; ?></span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-icon">📊</div>
                                <h4>No exercise data yet</h4>
                                <p>Start logging workouts to see activity charts and progress</p>
                                <button class="btn btn-primary" onclick="showAddExerciseModal()">
                                    Log Your First Workout
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Exercise Types -->
            <div class="types-section">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Quick Log</h3>
                    </div>
                    
                    <div class="card-body">
                        <div class="exercise-types-grid">
                            <?php 
                            $popularTypes = ['running', 'walking', 'cycling', 'weight_lifting', 'yoga', 'hiit'];
                            foreach ($popularTypes as $type): 
                                $exercise = $exerciseTypes[$type];
                            ?>
                                <button class="exercise-type-btn" onclick="quickLogExercise('<?php echo $type; ?>')">
                                    <div class="type-icon"><?php echo $exercise['icon']; ?></div>
                                    <div class="type-name"><?php echo $exercise['name']; ?></div>
                                    <div class="type-calories"><?php echo $exercise['calories_per_min']; ?> cal/min</div>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        
                        <button class="btn btn-outline btn-block" onclick="showAddExerciseModal()">
                            <span class="btn-icon">➕</span>
                            <span class="btn-text">Log Custom Exercise</span>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Recent Workouts -->
            <div class="history-section">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Workouts</h3>
                        <div class="card-actions">
                            <button class="btn btn-small btn-outline" onclick="exportExerciseData()">
                                <span class="btn-icon">📤</span>
                                Export
                            </button>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <?php if (!empty($recentEntries)): ?>
                            <div class="exercise-list">
                                <?php foreach ($recentEntries as $entry): ?>
                                    <div class="exercise-entry" data-entry-id="<?php echo $entry['id']; ?>">
                                        <div class="entry-date">
                                            <div class="date-main"><?php echo date('M j', strtotime($entry['date'])); ?></div>
                                            <div class="date-year"><?php echo date('Y', strtotime($entry['date'])); ?></div>
                                        </div>
                                        
                                        <div class="entry-exercise">
                                            <div class="exercise-icon">
                                                <?php
                                                // Find matching exercise icon
                                                $icon = '🏃'; // Default
                                                foreach ($exerciseTypes as $type) {
                                                    if (stripos($entry['exercise'], $type['name']) !== false) {
                                                        $icon = $type['icon'];
                                                        break;
                                                    }
                                                }
                                                echo $icon;
                                                ?>
                                            </div>
                                            <div class="exercise-info">
                                                <div class="exercise-name"><?php echo htmlspecialchars($entry['exercise']); ?></div>
                                                <div class="exercise-duration"><?php echo $entry['duration']; ?> minutes</div>
                                            </div>
                                        </div>
                                        
                                        <div class="entry-stats">
                                            <?php if ($entry['calories'] > 0): ?>
                                                <div class="stat-item calories">
                                                    <span class="stat-icon">🔥</span>
                                                    <span class="stat-value"><?php echo $entry['calories']; ?> cal</span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="entry-time">
                                                <?php echo date('g:i A', strtotime($entry['timestamp'])); ?>
                                            </div>
                                        </div>
                                        
                                        <div class="entry-actions">
                                            <button class="btn btn-small btn-outline" onclick="editExerciseEntry('<?php echo $entry['id']; ?>')">
                                                <span class="btn-icon">✏️</span>
                                            </button>
                                            <button class="btn btn-small btn-danger" onclick="deleteExerciseEntry('<?php echo $entry['id']; ?>')">
                                                <span class="btn-icon">🗑️</span>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($entry['notes'])): ?>
                                        <div class="entry-notes">
                                            <span class="notes-icon">💭</span>
                                            <span class="notes-text"><?php echo htmlspecialchars($entry['notes']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php if (count($exerciseEntries) > 15): ?>
                                <div class="load-more">
                                    <button class="btn btn-outline" onclick="loadMoreEntries()">
                                        Load More Workouts (<?php echo count($exerciseEntries) - 15; ?> remaining)
                                    </button>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-icon">💪</div>
                                <h4>No workouts logged yet</h4>
                                <p>Start logging your exercises to track progress and stay motivated</p>
                                <button class="btn btn-primary" onclick="showAddExerciseModal()">
                                    Log Your First Workout
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Add Exercise Modal -->
<div class="modal" id="addExerciseModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Log Exercise</h3>
            <button class="modal-close" onclick="hideAddExerciseModal()">×</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="" class="exercise-form" id="addExerciseForm">
                <input type="hidden" name="action" value="add_exercise">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <div class="form-group">
                    <label for="modal_exercise_type" class="form-label">Exercise Type *</label>
                    <select id="modal_exercise_type" name="exercise_type" class="form-control" required onchange="updateModalCalorieEstimate()">
                        <option value="">Select exercise...</option>
                        <?php foreach ($exerciseTypes as $key => $exercise): ?>
                            <option value="<?php echo $key; ?>" data-calories="<?php echo $exercise['calories_per_min']; ?>">
                                <?php echo $exercise['icon'] . ' ' . $exercise['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" id="modalCustomExerciseGroup" style="display: none;">
                    <label for="modal_custom_exercise" class="form-label">Custom Exercise Name *</label>
                    <input type="text" id="modal_custom_exercise" name="custom_exercise" class="form-control" 
                           placeholder="Enter exercise name">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="modal_duration" class="form-label">Duration (minutes) *</label>
                        <input type="number" id="modal_duration" name="duration" class="form-control" 
                               placeholder="30" required min="1" max="600" onchange="updateModalCalorieEstimate()">
                    </div>
                    
                    <div class="form-group">
                        <label for="modal_date" class="form-label">Date *</label>
                        <input type="date" id="modal_date" name="date" class="form-control" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="modal_intensity" class="form-label">Intensity</label>
                        <select id="modal_intensity" name="intensity" class="form-control" onchange="updateModalCalorieEstimate()">
                            <option value="low">🟢 Low - Light effort</option>
                            <option value="moderate" selected>🟡 Moderate - Some effort</option>
                            <option value="high">🟠 High - Hard effort</option>
                            <option value="extreme">🔴 Extreme - Max effort</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="modal_calories" class="form-label">Calories Burned</label>
                        <input type="number" id="modal_calories" name="calories" class="form-control" 
                               placeholder="Auto-calculated" min="0">
                        <small class="form-help">Leave empty for auto-calculation</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="modal_notes" class="form-label">Workout Notes</label>
                    <textarea id="modal_notes" name="notes" class="form-control" 
                              placeholder="How did the workout feel? Any observations..." rows="3"></textarea>
                </div>
                
                <div class="calorie-estimate" id="calorieEstimate" style="display: none;">
                    <div class="estimate-content">
                        <span class="estimate-icon">🔥</span>
                        <span class="estimate-text">Estimated: <span id="estimatedCalories">0</span> calories</span>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="hideAddExerciseModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="btn-icon">💾</span>
                        <span class="btn-text">Log Exercise</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Quick Log Modal -->
<div class="modal" id="quickLogModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="quickLogTitle">Quick Log Exercise</h3>
            <button class="modal-close" onclick="hideQuickLogModal()">×</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="" class="exercise-form" id="quickLogForm">
                <input type="hidden" name="action" value="add_exercise">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" id="quick_exercise_type" name="exercise_type" value="">
                
                <div class="quick-exercise-display" id="quickExerciseDisplay">
                    <!-- Will be populated by JavaScript -->
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="quick_duration" class="form-label">Duration (minutes) *</label>
                        <input type="number" id="quick_duration" name="duration" class="form-control" 
                               placeholder="30" required min="1" max="600" onchange="updateQuickCalorieEstimate()">
                    </div>
                    
                    <div class="form-group">
                        <label for="quick_intensity" class="form-label">How hard was it?</label>
                        <select id="quick_intensity" name="intensity" class="form-control" onchange="updateQuickCalorieEstimate()">
                            <option value="low">🟢 Easy</option>
                            <option value="moderate" selected>🟡 Moderate</option>
                            <option value="high">🟠 Hard</option>
                            <option value="extreme">🔴 Very Hard</option>
                        </select>
                    </div>
                </div>
                
                <div class="quick-calorie-display" id="quickCalorieDisplay">
                    <!-- Will be populated by JavaScript -->
                </div>
                
                <input type="hidden" id="quick_date" name="date" value="<?php echo date('Y-m-d'); ?>">
                <input type="hidden" id="quick_calories" name="calories" value="">
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="hideQuickLogModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="btn-icon">💾</span>
                        <span class="btn-text">Log Workout</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Include JavaScript -->
<script src="assets/js/charts.js"></script>

<script>
// Exercise page JavaScript
let currentChartType = 'minutes';
let currentChartView = 'weekly';
let exerciseChart = null;

// Exercise types data for JavaScript
const exerciseTypes = <?php echo json_encode($exerciseTypes); ?>;

document.addEventListener('DOMContentLoaded', function() {
    initializeExercisePage();
});

function initializeExercisePage() {
    // Initialize exercise chart
    <?php if (!empty($weeklyChartData['minutes']) && array_sum($weeklyChartData['minutes']) > 0): ?>
        createExerciseChart();
    <?php endif; ?>
    
    // Initialize form handlers
    setupFormHandlers();
    
    // Setup keyboard shortcuts
    setupExerciseShortcuts();
    
    // Auto-focus on exercise type if quick add
    <?php if ($quickAdd): ?>
        const exerciseSelect = document.getElementById('exercise_type');
        if (exerciseSelect) {
            exerciseSelect.focus();
        }
    <?php endif; ?>
}

function createExerciseChart() {
    const ctx = document.getElementById('exerciseChart');
    if (!ctx) return;
    
    const weeklyData = <?php echo json_encode($weeklyChartData); ?>;
    
    exerciseChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: weeklyData.labels,
            datasets: [{
                label: 'Minutes',
                data: weeklyData.minutes,
                backgroundColor: '#28A745',
                borderColor: '#28A745',
                borderWidth: 1,
                borderRadius: 4,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(44, 44, 44, 0.9)',
                    titleColor: '#28A745',
                    bodyColor: '#E0E0E0',
                    borderColor: '#28A745',
                    borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            if (currentChartType === 'minutes') {
                                return context.parsed.y + ' minutes';
                            } else {
                                return context.parsed.y + ' calories';
                            }
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#E0E0E0'
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#E0E0E0'
                    }
                }
            }
        }
    });
}

function switchChartType(type) {
    currentChartType = type;
    
    // Update button states
    document.querySelectorAll('[data-chart]').forEach(btn => {
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-outline');
    });
    
    const activeBtn = document.querySelector(`[data-chart="${type}"]`);
    if (activeBtn) {
        activeBtn.classList.remove('btn-outline');
        activeBtn.classList.add('btn-primary');
    }
    
    // Update chart data
    if (exerciseChart) {
        const weeklyData = <?php echo json_encode($weeklyChartData); ?>;
        
        if (type === 'minutes') {
            exerciseChart.data.datasets[0].data = weeklyData.minutes;
            exerciseChart.data.datasets[0].label = 'Minutes';
            exerciseChart.data.datasets[0].backgroundColor = '#28A745';
            exerciseChart.data.datasets[0].borderColor = '#28A745';
        } else {
            exerciseChart.data.datasets[0].data = weeklyData.calories;
            exerciseChart.data.datasets[0].label = 'Calories';
            exerciseChart.data.datasets[0].backgroundColor = '#FF6B35';
            exerciseChart.data.datasets[0].borderColor = '#FF6B35';
        }
        
        exerciseChart.update('active');
    }
}

function toggleChartView() {
    currentChartView = currentChartView === 'weekly' ? 'monthly' : 'weekly';
    
    const periodText = document.getElementById('chartViewText');
    if (periodText) {
        periodText.textContent = currentChartView === 'weekly' ? 'Weekly' : 'Monthly';
    }
    
    // In a full implementation, this would fetch new data
    showToast(`Switched to ${currentChartView} view`, 'info');
}

function showAddExerciseModal() {
    const modal = document.getElementById('addExerciseModal');
    modal.classList.add('show');
    
    // Focus on exercise type
    setTimeout(() => {
        const exerciseSelect = document.getElementById('modal_exercise_type');
        if (exerciseSelect) {
            exerciseSelect.focus();
        }
    }, 100);
}

function hideAddExerciseModal() {
    const modal = document.getElementById('addExerciseModal');
    modal.classList.remove('show');
    
    // Reset form
    const form = document.getElementById('addExerciseForm');
    if (form) form.reset();
    
    // Hide custom exercise group
    const customGroup = document.getElementById('modalCustomExerciseGroup');
    if (customGroup) customGroup.style.display = 'none';
    
    // Hide calorie estimate
    const estimate = document.getElementById('calorieEstimate');
    if (estimate) estimate.style.display = 'none';
}

function quickLogExercise(exerciseType) {
    const exercise = exerciseTypes[exerciseType];
    if (!exercise) return;
    
    // Set up quick log modal
    document.getElementById('quickLogTitle').textContent = `Quick Log: ${exercise.name}`;
    document.getElementById('quick_exercise_type').value = exerciseType;
    
    // Display exercise info
    const display = document.getElementById('quickExerciseDisplay');
    display.innerHTML = `
        <div class="quick-exercise-info">
            <div class="exercise-icon">${exercise.icon}</div>
            <div class="exercise-details">
                <div class="exercise-name">${exercise.name}</div>
                <div class="exercise-category">${exercise.category} • ${exercise.calories_per_min} cal/min</div>
            </div>
        </div>
    `;
    
    // Show modal
    const modal = document.getElementById('quickLogModal');
    modal.classList.add('show');
    
    // Focus on duration
    setTimeout(() => {
        const durationInput = document.getElementById('quick_duration');
        if (durationInput) {
            durationInput.focus();
            durationInput.select();
        }
    }, 100);
}

function hideQuickLogModal() {
    const modal = document.getElementById('quickLogModal');
    modal.classList.remove('show');
    
    // Reset form
    const form = document.getElementById('quickLogForm');
    if (form) form.reset();
}

function updateCalorieEstimate() {
    const exerciseType = document.getElementById('exercise_type').value;
    const duration = parseInt(document.getElementById('duration').value) || 0;
    const intensity = document.getElementById('intensity').value;
    
    updateCalorieEstimateCommon(exerciseType, duration, intensity, 'calories');
    
    // Show/hide custom exercise field
    const customGroup = document.getElementById('customExerciseGroup');
    if (customGroup) {
        customGroup.style.display = exerciseType === 'other' ? 'block' : 'none';
    }
}

function updateModalCalorieEstimate() {
    const exerciseType = document.getElementById('modal_exercise_type').value;
    const duration = parseInt(document.getElementById('modal_duration').value) || 0;
    const intensity = document.getElementById('modal_intensity').value;
    
    updateCalorieEstimateCommon(exerciseType, duration, intensity, 'modal_calories');
    
    // Show/hide custom exercise field
    const customGroup = document.getElementById('modalCustomExerciseGroup');
    if (customGroup) {
        customGroup.style.display = exerciseType === 'other' ? 'block' : 'none';
        
        // Make custom exercise required if other is selected
        const customInput = document.getElementById('modal_custom_exercise');
        if (customInput) {
            customInput.required = exerciseType === 'other';
        }
    }
    
    // Show calorie estimate
    const estimate = document.getElementById('calorieEstimate');
    const estimatedCalories = document.getElementById('estimatedCalories');
    
    if (estimate && estimatedCalories && exerciseType && duration > 0) {
        const calories = calculateCalories(exerciseType, duration, intensity);
        estimatedCalories.textContent = calories;
        estimate.style.display = 'block';
    } else if (estimate) {
        estimate.style.display = 'none';
    }
}

function updateQuickCalorieEstimate() {
    const exerciseType = document.getElementById('quick_exercise_type').value;
    const duration = parseInt(document.getElementById('quick_duration').value) || 0;
    const intensity = document.getElementById('quick_intensity').value;
    
    if (exerciseType && duration > 0) {
        const calories = calculateCalories(exerciseType, duration, intensity);
        document.getElementById('quick_calories').value = calories;
        
        // Display calorie estimate
        const display = document.getElementById('quickCalorieDisplay');
        display.innerHTML = `
            <div class="quick-calorie-info">
                <span class="calorie-icon">🔥</span>
                <span class="calorie-text">Estimated: <strong>${calories} calories</strong></span>
            </div>
        `;
    }
}

function updateCalorieEstimateCommon(exerciseType, duration, intensity, targetFieldId) {
    if (!exerciseType || duration <= 0) return;
    
    const calories = calculateCalories(exerciseType, duration, intensity);
    const targetField = document.getElementById(targetFieldId);
    
    if (targetField && !targetField.value) {
        targetField.placeholder = calories + ' (estimated)';
    }
}

function calculateCalories(exerciseType, duration, intensity) {
    const exercise = exerciseTypes[exerciseType];
    if (!exercise) return 0;
    
    const baseCalories = exercise.calories_per_min * duration;
    
    const intensityMultipliers = {
        'low': 0.8,
        'moderate': 1.0,
        'high': 1.3,
        'extreme': 1.6
    };
    
    return Math.round(baseCalories * (intensityMultipliers[intensity] || 1.0));
}

function setupFormHandlers() {
    const forms = document.querySelectorAll('.exercise-form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const exerciseType = form.querySelector('select[name="exercise_type"]').value;
            const customExercise = form.querySelector('input[name="custom_exercise"]')?.value;
            const duration = parseInt(form.querySelector('input[name="duration"]').value);
            
            // Validate exercise type
            if (!exerciseType) {
                e.preventDefault();
                showToast('Please select an exercise type', 'error');
                return;
            }
            
            // Validate custom exercise name if "other" is selected
            if (exerciseType === 'other' && !customExercise) {
                e.preventDefault();
                showToast('Please enter a custom exercise name', 'error');
                return;
            }
            
            // Validate duration
            if (!duration || duration <= 0 || duration > 600) {
                e.preventDefault();
                showToast('Please enter a valid duration between 1 and 600 minutes', 'error');
                return;
            }
        });
    });
}

async function deleteExerciseEntry(entryId) {
    if (!confirm('Are you sure you want to delete this exercise entry?')) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'delete_exercise');
        formData.append('entry_id', entryId);
        formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
        
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            // Remove entry from DOM
            const entryElement = document.querySelector(`[data-entry-id="${entryId}"]`);
            if (entryElement) {
                entryElement.style.opacity = '0';
                entryElement.style.transform = 'translateX(-100%)';
                setTimeout(() => entryElement.remove(), 300);
            }
            
            showToast('Exercise entry deleted successfully', 'success');
            
            // Refresh page after short delay to update stats
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showToast('Failed to delete entry', 'error');
        }
    } catch (error) {
        console.error('Delete failed:', error);
        showToast('Failed to delete entry', 'error');
    }
}

function editExerciseEntry(entryId) {
    // For now, show the add modal
    showAddExerciseModal();
    showToast('Edit functionality coming soon! For now, add a new entry.', 'info');
}

async function exportExerciseData() {
    try {
        const response = await fetch('/api/export-exercise.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                format: 'csv',
                csrf_token: document.querySelector('meta[name="csrf-token"]').content
            })
        });
        
        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `fitgrit-exercise-data-${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            
            showToast('Exercise data exported successfully', 'success');
        } else {
            showToast('Export failed. Please try again.', 'error');
        }
    } catch (error) {
        console.error('Export failed:', error);
        showToast('Export failed. Please try again.', 'error');
    }
}

function loadMoreEntries() {
    showToast('Load more functionality coming soon!', 'info');
}

function setupExerciseShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + E to add exercise (if not in input field)
        if ((e.ctrlKey || e.metaKey) && e.key === 'e' && !e.target.matches('input, textarea, select')) {
            e.preventDefault();
            showAddExerciseModal();
        }
        
        // Escape to close modals
        if (e.key === 'Escape') {
            hideAddExerciseModal();
            hideQuickLogModal();
        }
    });
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <span class="toast-icon">${type === 'success' ? '✅' : type === 'error' ? '❌' : type === 'warning' ? '⚠️' : 'ℹ️'}</span>
        <span class="toast-message">${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => toast.classList.add('show'), 100);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
    }
});
</script>

<style>
/* Exercise Page Styles */
.exercise-container {
    max-width: 1400px;
    margin: 0 auto;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: var(--spacing-xl);
    padding-bottom: var(--spacing-lg);
    border-bottom: 1px solid var(--border-grey);
}

.header-content h1 {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-sm);
}

.title-icon {
    font-size: 2rem;
}

.page-subtitle {
    color: #999;
    font-size: 1.1rem;
    margin: 0;
}

.header-actions {
    display: flex;
    gap: var(--spacing-md);
    align-items: center;
}

/* Quick Add Section */
.quick-add-section {
    max-width: 600px;
    margin: 0 auto;
}

.quick-add-section .card {
    border: 2px solid var(--success-green);
}

/* Exercise Statistics */
.exercise-stats {
    margin-bottom: var(--spacing-xl);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-lg);
}

.stat-card {
    background: var(--light-grey);
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    border: 1px solid var(--border-grey);
    transition: all var(--transition-normal);
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: var(--success-green);
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px var(--shadow);
}

.weekly-minutes::before { background: var(--success-green); }
.total-workouts::before { background: var(--primary-orange); }
.calories-burned::before { background: var(--accent-red); }
.current-streak::before { background: var(--accent-purple); }

.stat-icon {
    font-size: 2.5rem;
    min-width: 60px;
    text-align: center;
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--success-green);
    margin-bottom: var(--spacing-xs);
}

.stat-label {
    font-size: 0.9rem;
    color: var(--text-light);
    margin-bottom: var(--spacing-xs);
}

.stat-sublabel {
    font-size: 0.8rem;
    color: #999;
}

.stat-progress {
    margin-top: var(--spacing-sm);
}

.progress-bar {
    width: 100%;
    height: 6px;
    background: var(--border-grey);
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: var(--spacing-xs);
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--success-green), var(--primary-orange));
    border-radius: 3px;
    transition: width var(--transition-slow);
}

.progress-text {
    font-size: 0.75rem;
    color: #999;
}

/* Main Grid */
.exercise-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: var(--spacing-xl);
    grid-template-areas: 
        "chart types"
        "history history";
}

.chart-section {
    grid-area: chart;
}

.types-section {
    grid-area: types;
}

.history-section {
    grid-area: history;
}

/* Chart Styles */
.chart-controls {
    display: flex;
    gap: var(--spacing-xs);
}

.chart-container {
    height: 300px;
    margin-bottom: var(--spacing-lg);
}

.chart-summary {
    display: flex;
    justify-content: space-around;
    gap: var(--spacing-md);
    padding-top: var(--spacing-lg);
    border-top: 1px solid var(--border-grey);
}

.summary-item {
    text-align: center;
}

.summary-label {
    display: block;
    font-size: 0.8rem;
    color: #999;
    margin-bottom: var(--spacing-xs);
}

.summary-value {
    font-weight: 600;
    color: var(--text-light);
}

/* Exercise Types Grid */
.exercise-types-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-lg);
}

.exercise-type-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--spacing-xs);
    padding: var(--spacing-md);
    background: var(--dark-grey);
    border: 1px solid var(--border-grey);
    border-radius: var(--radius-lg);
    color: var(--text-light);
    cursor: pointer;
    transition: all var(--transition-normal);
    text-align: center;
}

.exercise-type-btn:hover {
    background: var(--success-green);
    color: var(--white);
    transform: translateY(-2px);
    border-color: var(--success-green);
}

.type-icon {
    font-size: 2rem;
}

.type-name {
    font-weight: 600;
    font-size: 0.9rem;
}

.type-calories {
    font-size: 0.8rem;
    opacity: 0.8;
}

/* Exercise History */
.exercise-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-md);
}

.exercise-entry {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    padding: var(--spacing-md);
    background: var(--dark-grey);
    border-radius: var(--radius-lg);
    border: 1px solid var(--border-grey);
    transition: all var(--transition-normal);
}

.exercise-entry:hover {
    background: var(--border-grey);
    transform: translateX(4px);
}

.entry-date {
    text-align: center;
    min-width: 60px;
}

.date-main {
    font-weight: 600;
    color: var(--success-green);
    font-size: 0.9rem;
}

.date-year {
    font-size: 0.75rem;
    color: #999;
}

.entry-exercise {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    flex: 1;
}

.exercise-icon {
    font-size: 1.5rem;
    min-width: 32px;
    text-align: center;
}

.exercise-info {
    flex: 1;
}

.exercise-name {
    font-weight: 600;
    color: var(--text-light);
    margin-bottom: var(--spacing-xs);
}

.exercise-duration {
    font-size: 0.9rem;
    color: var(--success-green);
}

.entry-stats {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: var(--spacing-xs);
}

.stat-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
}

.stat-item.calories {
    color: var(--accent-red);
}

.stat-icon {
    font-size: 0.9rem;
}

.stat-value {
    font-size: 0.85rem;
    font-weight: 500;
}

.entry-time {
    font-size: 0.8rem;
    color: #666;
}

.entry-actions {
    display: flex;
    gap: var(--spacing-xs);
}

.entry-notes {
    grid-column: 1 / -1;
    margin-top: var(--spacing-sm);
    padding: var(--spacing-sm);
    background: rgba(255, 255, 255, 0.05);
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
}

.notes-icon {
    font-size: 0.9rem;
    opacity: 0.7;
}

.notes-text {
    font-size: 0.85rem;
    color: #999;
    font-style: italic;
}

.load-more {
    text-align: center;
    margin-top: var(--spacing-lg);
    padding-top: var(--spacing-lg);
    border-top: 1px solid var(--border-grey);
}

/* Modal Styles */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1001;
    opacity: 0;
    visibility: hidden;
    transition: all var(--transition-normal);
}

.modal.show {
    opacity: 1;
    visibility: visible;
}

.modal-content {
    background: var(--light-grey);
    border-radius: var(--radius-lg);
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    transform: scale(0.9);
    transition: transform var(--transition-normal);
}

.modal.show .modal-content {
    transform: scale(1);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--spacing-lg);
    border-bottom: 1px solid var(--border-grey);
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: var(--text-light);
    cursor: pointer;
    padding: var(--spacing-xs);
    border-radius: var(--radius-sm);
    transition: background var(--transition-fast);
}

.modal-close:hover {
    background: var(--border-grey);
}

.modal-body {
    padding: var(--spacing-lg);
}

.modal-actions {
    display: flex;
    gap: var(--spacing-md);
    justify-content: flex-end;
    margin-top: var(--spacing-lg);
}

/* Form Styles */
.form-row {
    display: flex;
    gap: var(--spacing-md);
}

.form-row .form-group {
    flex: 1;
}

.form-help {
    display: block;
    margin-top: var(--spacing-xs);
    font-size: 0.8rem;
    color: #999;
}

/* Calorie Estimate Display */
.calorie-estimate {
    background: rgba(40, 167, 69, 0.1);
    border: 1px solid var(--success-green);
    border-radius: var(--radius-md);
    padding: var(--spacing-md);
    margin-top: var(--spacing-md);
    text-align: center;
}

.estimate-content {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-sm);
}

.estimate-icon {
    font-size: 1.2rem;
}

.estimate-text {
    color: var(--success-green);
    font-weight: 600;
}

/* Quick Log Modal Styles */
.quick-exercise-display {
    margin-bottom: var(--spacing-lg);
}

.quick-exercise-info {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    padding: var(--spacing-md);
    background: var(--dark-grey);
    border-radius: var(--radius-lg);
    border: 2px solid var(--success-green);
}

.exercise-details {
    flex: 1;
}

.exercise-category {
    font-size: 0.9rem;
    color: var(--success-green);
    text-transform: capitalize;
}

.quick-calorie-display {
    margin-top: var(--spacing-md);
}

.quick-calorie-info {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-sm);
    padding: var(--spacing-md);
    background: rgba(220, 20, 60, 0.1);
    border: 1px solid var(--accent-red);
    border-radius: var(--radius-md);
}

.calorie-icon {
    font-size: 1.2rem;
}

.calorie-text {
    color: var(--accent-red);
    font-weight: 600;
}

/* Toast Notifications */
.toast {
    position: fixed;
    bottom: var(--spacing-lg);
    right: var(--spacing-lg);
    background: var(--light-grey);
    border-radius: var(--radius-md);
    padding: var(--spacing-md);
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    box-shadow: 0 4px 20px var(--shadow);
    transform: translateY(100px);
    opacity: 0;
    transition: all var(--transition-normal);
    z-index: 1002;
    max-width: 400px;
}

.toast.show {
    transform: translateY(0);
    opacity: 1;
}

.toast-success { border-left: 4px solid var(--success-green); }
.toast-error { border-left: 4px solid var(--accent-red); }
.toast-warning { border-left: 4px solid var(--warning-yellow); }
.toast-info { border-left: 4px solid var(--accent-purple); }

.toast-icon {
    font-size: 1.2rem;
}

.toast-message {
    flex: 1;
}

/* Empty States */
.empty-state {
    text-align: center;
    padding: var(--spacing-xxl);
    color: #999;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: var(--spacing-lg);
}

.empty-state h4 {
    color: var(--text-light);
    margin-bottom: var(--spacing-md);
}

.empty-state p {
    margin-bottom: var(--spacing-lg);
    max-width: 300px;
    margin-left: auto;
    margin-right: auto;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .exercise-grid {
        grid-template-columns: 1fr;
        grid-template-areas: 
            "chart"
            "types"
            "history";
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .exercise-types-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: var(--spacing-lg);
        align-items: stretch;
    }
    
    .header-actions {
        justify-content: center;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .exercise-types-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .chart-summary {
        flex-direction: column;
        gap: var(--spacing-sm);
    }
    
    .exercise-entry {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--spacing-sm);
    }
    
    .entry-exercise {
        width: 100%;
    }
    
    .entry-stats {
        align-items: flex-start;
        width: 100%;
    }
    
    .entry-actions {
        align-self: flex-end;
    }
    
    .modal-content {
        width: 95%;
        margin: var(--spacing-md);
    }
    
    .modal-actions {
        flex-direction: column;
    }
    
    .form-row {
        flex-direction: column;
    }
}

@media (max-width: 480px) {
    .exercise-types-grid {
        grid-template-columns: 1fr;
    }
    
    .chart-controls {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .quick-exercise-info {
        flex-direction: column;
        text-align: center;
    }
}

/* Animation for exercise entries */
.exercise-entry {
    animation: fadeInUp 0.3s ease;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Hover effects for interactive elements */
.exercise-type-btn,
.exercise-entry,
.stat-card {
    position: relative;
    overflow: hidden;
}

.exercise-type-btn::before,
.exercise-entry::before,
.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    transition: left var(--transition-slow);
}

.exercise-type-btn:hover::before,
.exercise-entry:hover::before,
.stat-card:hover::before {
    left: 100%;
}

/* Progress bar animations */
.progress-fill {
    animation: fillProgress 1s ease-out;
}

@keyframes fillProgress {
    from {
        width: 0;
    }
    to {
        width: var(--progress-width, 0%);
    }
}
</style>

<?php include 'includes/footer.php'; ?>