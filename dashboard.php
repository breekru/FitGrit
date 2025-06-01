<?php
// FitGrit Dashboard
// Main dashboard showing user's fitness overview and recent activity

define('FITGRIT_ACCESS', true);

// Include core files
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/data-handler.php';
require_once 'includes/auth.php';

// Set page variables
$pageTitle = 'Dashboard';
$bodyClass = 'dashboard-page';
$additionalCSS = ['assets/css/charts.css'];

// Get current user
$currentUser = getCurrentUser();
$userId = $currentUser['id'];

// Date ranges for analysis
$today = date('Y-m-d');
$thisWeek = date('Y-m-d', strtotime('-7 days'));
$thisMonth = date('Y-m-d', strtotime('-30 days'));
$thisYear = date('Y-m-d', strtotime('-365 days'));

// Get recent data
$recentWeight = getWeightData($userId, 10); // Last 10 entries
$recentExercise = getExerciseData($userId, 7); // Last 7 entries
$todayFood = getFoodData($userId, $today);
$recentRecipes = getRecipes($userId);

// Calculate statistics
$stats = calculateDashboardStats($userId, $recentWeight, $recentExercise, $todayFood);

// Get user's goals and preferences
$userPreferences = $currentUser['preferences'] ?? [];
$userProfile = $currentUser['profile'] ?? [];
$weightUnit = $userPreferences['weight_unit'] ?? 'lbs';

// Calculate BMI if height is available
$currentBMI = null;
$bmiCategory = '';
if (!empty($userProfile['height']) && !empty($recentWeight)) {
    $currentWeight = $recentWeight[0]['weight'];
    $height = $userProfile['height'];
    $currentBMI = calculateBMI($currentWeight, $height);
    $bmiCategory = getBMICategory($currentBMI);
}

// Prepare chart data
$weightChartData = prepareWeightChartData($recentWeight);
$exerciseChartData = prepareExerciseChartData($recentExercise);

// Function to calculate dashboard statistics
function calculateDashboardStats($userId, $weightData, $exerciseData, $foodData) {
    $today = date('Y-m-d');
    $thisWeek = date('Y-m-d', strtotime('-7 days'));
    
    // Weight statistics
    $currentWeight = !empty($weightData) ? $weightData[0]['weight'] : null;
    $previousWeight = count($weightData) > 1 ? $weightData[1]['weight'] : null;
    $weightChange = ($currentWeight && $previousWeight) ? $currentWeight - $previousWeight : 0;
    
    // Exercise statistics
    $todayExercise = array_filter($exerciseData, function($entry) use ($today) {
        return $entry['date'] === $today;
    });
    
    $weeklyExercise = array_filter($exerciseData, function($entry) use ($thisWeek) {
        return $entry['date'] >= $thisWeek;
    });
    
    $todayMinutes = array_sum(array_column($todayExercise, 'duration'));
    $weeklyMinutes = array_sum(array_column($weeklyExercise, 'duration'));
    $todayCalories = array_sum(array_column($todayExercise, 'calories'));
    
    // Food statistics
    $todayCaloriesFood = array_sum(array_column($foodData, 'calories'));
    $todayMeals = count($foodData);
    
    // Streaks
    $exerciseStreak = calculateExerciseStreak($exerciseData);
    $weightLogStreak = calculateWeightLogStreak($userId);
    
    return [
        'weight' => [
            'current' => $currentWeight,
            'change' => $weightChange,
            'trend' => $weightChange > 0 ? 'up' : ($weightChange < 0 ? 'down' : 'stable')
        ],
        'exercise' => [
            'today_minutes' => $todayMinutes,
            'weekly_minutes' => $weeklyMinutes,
            'today_calories' => $todayCalories,
            'streak' => $exerciseStreak
        ],
        'food' => [
            'calories' => $todayCaloriesFood,
            'meals' => $todayMeals
        ],
        'streaks' => [
            'exercise' => $exerciseStreak,
            'weight_log' => $weightLogStreak
        ]
    ];
}

function calculateExerciseStreak($exerciseData) {
    if (empty($exerciseData)) return 0;
    
    $streak = 0;
    $currentDate = new DateTime();
    
    while ($currentDate >= new DateTime('-30 days')) {
        $dateStr = $currentDate->format('Y-m-d');
        $hasExercise = false;
        
        foreach ($exerciseData as $entry) {
            if ($entry['date'] === $dateStr) {
                $hasExercise = true;
                break;
            }
        }
        
        if ($hasExercise) {
            $streak++;
            $currentDate->modify('-1 day');
        } else {
            break;
        }
    }
    
    return $streak;
}

function calculateWeightLogStreak($userId) {
    $weightData = getWeightData($userId, 30);
    if (empty($weightData)) return 0;
    
    $streak = 0;
    $currentDate = new DateTime();
    
    while ($currentDate >= new DateTime('-30 days')) {
        $dateStr = $currentDate->format('Y-m-d');
        $hasEntry = false;
        
        foreach ($weightData as $entry) {
            if ($entry['date'] === $dateStr) {
                $hasEntry = true;
                break;
            }
        }
        
        if ($hasEntry) {
            $streak++;
            $currentDate->modify('-1 day');
        } else {
            break;
        }
    }
    
    return $streak;
}

function prepareWeightChartData($weightData) {
    $data = ['labels' => [], 'values' => []];
    
    // Reverse to show oldest first
    $weightData = array_reverse($weightData);
    
    foreach ($weightData as $entry) {
        $data['labels'][] = date('M j', strtotime($entry['date']));
        $data['values'][] = $entry['weight'];
    }
    
    return $data;
}

function prepareExerciseChartData($exerciseData) {
    $last7Days = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $last7Days[$date] = 0;
    }
    
    foreach ($exerciseData as $entry) {
        if (isset($last7Days[$entry['date']])) {
            $last7Days[$entry['date']] += $entry['duration'];
        }
    }
    
    return [
        'labels' => array_map(function($date) {
            return date('M j', strtotime($date));
        }, array_keys($last7Days)),
        'values' => array_values($last7Days)
    ];
}

// Include header
include 'includes/header.php';
?>

<!-- Dashboard Content -->
<div class="container dashboard-container">
    <!-- Welcome Section -->
    <div class="welcome-section">
        <div class="welcome-content">
            <h1>Welcome back, <?php echo htmlspecialchars($currentUser['first_name']); ?>! 👋</h1>
            <p class="welcome-subtitle">Here's your fitness progress overview for today</p>
        </div>
        
        <!-- Quick Stats Cards -->
        <div class="quick-stats">
            <div class="stat-card weight-stat">
                <div class="stat-icon">⚖️</div>
                <div class="stat-content">
                    <div class="stat-value">
                        <?php if ($stats['weight']['current']): ?>
                            <?php echo number_format($stats['weight']['current'], 1); ?> <?php echo $weightUnit; ?>
                        <?php else: ?>
                            --
                        <?php endif; ?>
                    </div>
                    <div class="stat-label">Current Weight</div>
                    <?php if ($stats['weight']['change'] != 0): ?>
                        <div class="stat-change <?php echo $stats['weight']['trend']; ?>">
                            <?php echo $stats['weight']['change'] > 0 ? '+' : ''; ?>
                            <?php echo number_format($stats['weight']['change'], 1); ?> <?php echo $weightUnit; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="stat-card exercise-stat">
                <div class="stat-icon">💪</div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $stats['exercise']['today_minutes']; ?> min</div>
                    <div class="stat-label">Today's Exercise</div>
                    <?php if ($stats['exercise']['today_calories'] > 0): ?>
                        <div class="stat-change positive">
                            <?php echo number_format($stats['exercise']['today_calories']); ?> calories
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="stat-card food-stat">
                <div class="stat-icon">🍎</div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo number_format($stats['food']['calories']); ?></div>
                    <div class="stat-label">Calories Today</div>
                    <div class="stat-change neutral">
                        <?php echo $stats['food']['meals']; ?> meals logged
                    </div>
                </div>
            </div>
            
            <div class="stat-card streak-stat">
                <div class="stat-icon">🔥</div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo max($stats['streaks']['exercise'], $stats['streaks']['weight_log']); ?></div>
                    <div class="stat-label">Day Streak</div>
                    <div class="stat-change positive">Keep it up!</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Dashboard Grid -->
    <div class="dashboard-grid">
        
        <!-- Weight Progress Chart -->
        <div class="dashboard-card weight-chart-card">
            <div class="card-header">
                <h3 class="card-title">Weight Progress</h3>
                <div class="card-actions">
                    <button class="btn btn-small btn-outline" onclick="location.href='weight.php'">
                        View All
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($weightChartData['values'])): ?>
                    <div class="chart-container">
                        <canvas id="weightChart" width="400" height="200"></canvas>
                    </div>
                    
                    <!-- BMI Information -->
                    <?php if ($currentBMI): ?>
                        <div class="bmi-info">
                            <div class="bmi-value">
                                <span class="bmi-number"><?php echo number_format($currentBMI, 1); ?></span>
                                <span class="bmi-label">BMI</span>
                            </div>
                            <div class="bmi-category <?php echo strtolower(str_replace(' ', '-', $bmiCategory)); ?>">
                                <?php echo htmlspecialchars($bmiCategory); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">⚖️</div>
                        <h4>No weight data yet</h4>
                        <p>Start tracking your weight to see progress charts</p>
                        <a href="weight.php?quick=true" class="btn btn-primary">Log Weight</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Exercise Activity Chart -->
        <div class="dashboard-card exercise-chart-card">
            <div class="card-header">
                <h3 class="card-title">Weekly Exercise</h3>
                <div class="card-actions">
                    <button class="btn btn-small btn-outline" onclick="location.href='exercise.php'">
                        View All
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($exerciseChartData['values']) && array_sum($exerciseChartData['values']) > 0): ?>
                    <div class="chart-container">
                        <canvas id="exerciseChart" width="400" height="200"></canvas>
                    </div>
                    
                    <div class="exercise-summary">
                        <div class="summary-item">
                            <span class="summary-label">This Week</span>
                            <span class="summary-value"><?php echo $stats['exercise']['weekly_minutes']; ?> min</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Streak</span>
                            <span class="summary-value"><?php echo $stats['streaks']['exercise']; ?> days</span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">💪</div>
                        <h4>No exercise logged</h4>
                        <p>Start logging workouts to track your activity</p>
                        <a href="exercise.php?quick=true" class="btn btn-primary">Log Exercise</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Today's Food Log -->
        <div class="dashboard-card food-log-card">
            <div class="card-header">
                <h3 class="card-title">Today's Nutrition</h3>
                <div class="card-actions">
                    <button class="btn btn-small btn-outline" onclick="location.href='food.php'">
                        View All
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($todayFood)): ?>
                    <div class="food-summary">
                        <div class="calorie-circle">
                            <div class="calorie-number"><?php echo number_format($stats['food']['calories']); ?></div>
                            <div class="calorie-label">calories</div>
                        </div>
                        
                        <div class="meal-breakdown">
                            <?php
                            $mealTotals = [];
                            foreach ($todayFood as $entry) {
                                $meal = $entry['meal'];
                                if (!isset($mealTotals[$meal])) {
                                    $mealTotals[$meal] = 0;
                                }
                                $mealTotals[$meal] += $entry['calories'];
                            }
                            ?>
                            
                            <?php foreach (['breakfast', 'lunch', 'dinner', 'snack'] as $meal): ?>
                                <?php if (isset($mealTotals[$meal])): ?>
                                    <div class="meal-item">
                                        <span class="meal-name"><?php echo ucfirst($meal); ?></span>
                                        <span class="meal-calories"><?php echo number_format($mealTotals[$meal]); ?> cal</span>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="recent-foods">
                        <h4>Recent Items</h4>
                        <?php foreach (array_slice($todayFood, 0, 3) as $food): ?>
                            <div class="food-item">
                                <span class="food-name"><?php echo htmlspecialchars($food['food']); ?></span>
                                <span class="food-calories"><?php echo number_format($food['calories']); ?> cal</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">🍎</div>
                        <h4>No food logged today</h4>
                        <p>Track your meals to monitor nutrition</p>
                        <a href="food.php?quick=true" class="btn btn-primary">Log Food</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Activity Feed -->
        <div class="dashboard-card activity-feed-card">
            <div class="card-header">
                <h3 class="card-title">Recent Activity</h3>
            </div>
            <div class="card-body">
                <div class="activity-feed">
                    <?php
                    $recentActivities = [];
                    
                    // Add weight entries
                    foreach (array_slice($recentWeight, 0, 3) as $entry) {
                        $recentActivities[] = [
                            'type' => 'weight',
                            'icon' => '⚖️',
                            'text' => 'Logged weight: ' . number_format($entry['weight'], 1) . ' ' . $weightUnit,
                            'time' => $entry['timestamp'],
                            'date' => $entry['date']
                        ];
                    }
                    
                    // Add exercise entries
                    foreach (array_slice($recentExercise, 0, 3) as $entry) {
                        $recentActivities[] = [
                            'type' => 'exercise',
                            'icon' => '💪',
                            'text' => $entry['exercise'] . ' for ' . $entry['duration'] . ' minutes',
                            'time' => $entry['timestamp'],
                            'date' => $entry['date']
                        ];
                    }
                    
                    // Sort by timestamp
                    usort($recentActivities, function($a, $b) {
                        return strtotime($b['time']) - strtotime($a['time']);
                    });
                    
                    $recentActivities = array_slice($recentActivities, 0, 5);
                    ?>
                    
                    <?php if (!empty($recentActivities)): ?>
                        <?php foreach ($recentActivities as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon"><?php echo $activity['icon']; ?></div>
                                <div class="activity-content">
                                    <div class="activity-text"><?php echo htmlspecialchars($activity['text']); ?></div>
                                    <div class="activity-time"><?php echo formatDateTime($activity['time'], 'M j, g:i A'); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state small">
                            <div class="empty-icon">📝</div>
                            <h4>No recent activity</h4>
                            <p>Start logging to see your activity here</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="dashboard-card quick-actions-card">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div class="card-body">
                <div class="quick-actions-grid">
                    <a href="weight.php?quick=true" class="quick-action-btn">
                        <div class="action-icon">⚖️</div>
                        <div class="action-text">Log Weight</div>
                    </a>
                    
                    <a href="exercise.php?quick=true" class="quick-action-btn">
                        <div class="action-icon">💪</div>
                        <div class="action-text">Add Exercise</div>
                    </a>
                    
                    <a href="food.php?quick=true" class="quick-action-btn">
                        <div class="action-icon">🍎</div>
                        <div class="action-text">Log Meal</div>
                    </a>
                    
                    <a href="recipes.php?add=true" class="quick-action-btn">
                        <div class="action-icon">📝</div>
                        <div class="action-text">Add Recipe</div>
                    </a>
                    
                    <a href="profile.php" class="quick-action-btn">
                        <div class="action-icon">⚙️</div>
                        <div class="action-text">Settings</div>
                    </a>
                    
                    <a href="#" onclick="showMotivation()" class="quick-action-btn">
                        <div class="action-icon">🎯</div>
                        <div class="action-text">Motivation</div>
                    </a>
                </div>
            </div>
        </div>
        
    </div>
</div>

<!-- Motivation Modal -->
<div class="modal" id="motivationModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Your Fitness Journey 🏆</h3>
            <button class="modal-close" onclick="hideMotivation()">×</button>
        </div>
        <div class="modal-body">
            <div class="motivation-content">
                <div class="progress-highlights">
                    <?php if ($stats['streaks']['exercise'] > 0): ?>
                        <div class="highlight-item">
                            <span class="highlight-icon">🔥</span>
                            <span class="highlight-text"><?php echo $stats['streaks']['exercise']; ?> day exercise streak!</span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($stats['weight']['change'] < 0): ?>
                        <div class="highlight-item">
                            <span class="highlight-icon">📉</span>
                            <span class="highlight-text">Lost <?php echo abs($stats['weight']['change']); ?> <?php echo $weightUnit; ?> since last weigh-in!</span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($stats['exercise']['weekly_minutes'] >= 150): ?>
                        <div class="highlight-item">
                            <span class="highlight-icon">⭐</span>
                            <span class="highlight-text">Met weekly exercise goal!</span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="motivational-quote">
                    <blockquote id="motivationQuote">
                        "The groundwork for all happiness is good health."
                    </blockquote>
                    <cite>- Leigh Hunt</cite>
                </div>
                
                <div class="next-goal">
                    <h4>Your Next Goal</h4>
                    <p id="nextGoalText">Keep logging consistently for better insights!</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Charts JavaScript -->
<script src="assets/js/charts.js"></script>

<script>
// Dashboard JavaScript
document.addEventListener('DOMContentLoaded', function() {
    initializeDashboard();
});

function initializeDashboard() {
    // Initialize charts
    <?php if (!empty($weightChartData['values'])): ?>
        createWeightChart();
    <?php endif; ?>
    
    <?php if (!empty($exerciseChartData['values']) && array_sum($exerciseChartData['values']) > 0): ?>
        createExerciseChart();
    <?php endif; ?>
    
    // Auto-refresh data every 5 minutes
    setInterval(refreshDashboardData, 300000);
    
    // Setup keyboard shortcuts
    setupDashboardShortcuts();
}

function createWeightChart() {
    const ctx = document.getElementById('weightChart').getContext('2d');
    const chartData = <?php echo json_encode($weightChartData); ?>;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [{
                label: 'Weight (<?php echo $weightUnit; ?>)',
                data: chartData.values,
                borderColor: '#FF6B35',
                backgroundColor: 'rgba(255, 107, 53, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#FF6B35',
                pointBorderColor: '#FFFFFF',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#E0E0E0'
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#E0E0E0'
                    }
                }
            }
        }
    });
}

function createExerciseChart() {
    const ctx = document.getElementById('exerciseChart').getContext('2d');
    const chartData = <?php echo json_encode($exerciseChartData); ?>;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartData.labels,
            datasets: [{
                label: 'Minutes',
                data: chartData.values,
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

async function refreshDashboardData() {
    try {
        const response = await fetch('/api/dashboard-data.php', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            updateDashboardStats(data);
        }
    } catch (error) {
        console.error('Failed to refresh dashboard data:', error);
    }
}

function updateDashboardStats(data) {
    // Update stat cards with new data
    if (data.stats) {
        const stats = data.stats;
        
        // Update weight
        if (stats.weight && stats.weight.current) {
            const weightValue = document.querySelector('.weight-stat .stat-value');
            if (weightValue) {
                weightValue.textContent = `${stats.weight.current} <?php echo $weightUnit; ?>`;
            }
        }
        
        // Update exercise
        if (stats.exercise) {
            const exerciseValue = document.querySelector('.exercise-stat .stat-value');
            if (exerciseValue) {
                exerciseValue.textContent = `${stats.exercise.today_minutes} min`;
            }
        }
        
        // Update food
        if (stats.food) {
            const foodValue = document.querySelector('.food-stat .stat-value');
            if (foodValue) {
                foodValue.textContent = stats.food.calories;
            }
        }
    }
}

function setupDashboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey || e.metaKey) {
            switch(e.key) {
                case 'w':
                    e.preventDefault();
                    window.location.href = 'weight.php?quick=true';
                    break;
                case 'e':
                    e.preventDefault();
                    window.location.href = 'exercise.php?quick=true';
                    break;
                case 'f':
                    e.preventDefault();
                    window.location.href = 'food.php?quick=true';
                    break;
                case 'r':
                    e.preventDefault();
                    window.location.href = 'recipes.php?add=true';
                    break;
            }
        }
    });
}

function showMotivation() {
    const modal = document.getElementById('motivationModal');
    modal.classList.add('show');
    
    // Load random motivational quote
    const quotes = [
        "The groundwork for all happiness is good health. - Leigh Hunt",
        "Health is a state of complete harmony of the body, mind and spirit. - B.K.S. Iyengar",
        "The greatest wealth is to live content with little. - Plato",
        "He who conquers others is strong; He who conquers himself is mighty. - Lao Tzu",
        "The first wealth is health. - Ralph Waldo Emerson"
    ];
    
    const randomQuote = quotes[Math.floor(Math.random() * quotes.length)];
    const [quote, author] = randomQuote.split(' - ');
    
    document.getElementById('motivationQuote').textContent = `"${quote}"`;
    document.querySelector('#motivationModal cite').textContent = `- ${author}`;
    
    // Set next goal based on current stats
    const nextGoals = [
        "Try to log your weight consistently this week!",
        "Aim for 30 minutes of exercise today!",
        "Track all your meals for better insights!",
        "Add a new healthy recipe to your collection!",
        "Maintain your current streak - you're doing great!"
    ];
    
    const randomGoal = nextGoals[Math.floor(Math.random() * nextGoals.length)];
    document.getElementById('nextGoalText').textContent = randomGoal;
}

function hideMotivation() {
    const modal = document.getElementById('motivationModal');
    modal.classList.remove('show');
}

// Close modal when clicking outside
document.getElementById('motivationModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideMotivation();
    }
});
</script>

<?php include 'includes/footer.php'; ?>