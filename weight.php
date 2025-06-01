<?php
// FitGrit Weight Tracking Page
// Comprehensive weight management with charts, goals, and history

define('FITGRIT_ACCESS', true);

// Include core files
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/data-handler.php';
require_once 'includes/auth.php';

// Set page variables
$pageTitle = 'Weight Tracking';
$bodyClass = 'weight-page';
$additionalCSS = ['assets/css/charts.css'];

// Get current user
$currentUser = getCurrentUser();
$userId = $currentUser['id'];
$userPreferences = $currentUser['preferences'] ?? [];
$userProfile = $currentUser['profile'] ?? [];
$weightUnit = $userPreferences['weight_unit'] ?? 'lbs';

// Handle form submissions
$message = '';
$messageType = '';

// Handle quick add parameter
$quickAdd = isset($_GET['quick']) && $_GET['quick'] === 'true';

// Handle weight entry
if ($_POST && isset($_POST['action'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Security token mismatch. Please try again.';
        $messageType = 'error';
    } else {
        switch ($_POST['action']) {
            case 'add_weight':
                $weight = floatval($_POST['weight']);
                $unit = sanitizeInput($_POST['unit'] ?? $weightUnit);
                $date = sanitizeInput($_POST['date'] ?? date('Y-m-d'));
                $notes = sanitizeInput($_POST['notes'] ?? '');
                
                if ($weight > 0 && $weight < 1000) {
                    if (addWeightEntry($userId, $weight, $unit, $date, $notes)) {
                        $message = 'Weight entry added successfully!';
                        $messageType = 'success';
                        
                        // Redirect if quick add
                        if ($quickAdd) {
                            $_SESSION['flash_message'] = $message;
                            $_SESSION['flash_type'] = $messageType;
                            redirect('dashboard.php');
                        }
                    } else {
                        $message = 'Failed to add weight entry. Please try again.';
                        $messageType = 'error';
                    }
                } else {
                    $message = 'Please enter a valid weight between 1 and 999 ' . $unit;
                    $messageType = 'error';
                }
                break;
                
            case 'delete_weight':
                $entryId = sanitizeInput($_POST['entry_id']);
                $filePath = WEIGHT_PATH . $userId . '_weight.json';
                
                if (deleteEntry($filePath, $entryId)) {
                    $message = 'Weight entry deleted successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to delete weight entry.';
                    $messageType = 'error';
                }
                break;
                
            case 'set_goal':
                $goalWeight = floatval($_POST['goal_weight']);
                $goalDate = sanitizeInput($_POST['goal_date']);
                
                if ($goalWeight > 0 && $goalWeight < 1000) {
                    $currentUser['profile']['weight_goal'] = $goalWeight;
                    $currentUser['profile']['goal_date'] = $goalDate;
                    
                    if (saveUserData($userId, $currentUser)) {
                        $message = 'Weight goal set successfully!';
                        $messageType = 'success';
                        $userProfile = $currentUser['profile'];
                    } else {
                        $message = 'Failed to set weight goal.';
                        $messageType = 'error';
                    }
                } else {
                    $message = 'Please enter a valid goal weight.';
                    $messageType = 'error';
                }
                break;
        }
    }
}

// Get weight data
$weightEntries = getWeightData($userId);
$recentEntries = array_slice($weightEntries, 0, 10);

// Calculate statistics
$stats = calculateWeightStats($weightEntries, $userProfile);

// Prepare chart data
$chartData = prepareWeightChartData($weightEntries, 30); // Last 30 days

// Generate CSRF token
$csrfToken = generateCSRFToken();

// Calculate weight statistics
function calculateWeightStats($entries, $profile) {
    $stats = [
        'current' => null,
        'highest' => null,
        'lowest' => null,
        'average' => null,
        'total_entries' => count($entries),
        'goal_weight' => $profile['weight_goal'] ?? null,
        'goal_date' => $profile['goal_date'] ?? null,
        'progress' => null,
        'trend' => 'stable',
        'weekly_change' => 0,
        'monthly_change' => 0,
        'bmi' => null,
        'bmi_category' => '',
        'days_tracking' => 0
    ];
    
    if (empty($entries)) {
        return $stats;
    }
    
    // Sort entries by date (newest first)
    usort($entries, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    $weights = array_column($entries, 'weight');
    $stats['current'] = $weights[0];
    $stats['highest'] = max($weights);
    $stats['lowest'] = min($weights);
    $stats['average'] = round(array_sum($weights) / count($weights), 1);
    
    // Calculate BMI if height is available
    if (!empty($profile['height'])) {
        $stats['bmi'] = calculateBMI($stats['current'], $profile['height']);
        $stats['bmi_category'] = getBMICategory($stats['bmi']);
    }
    
    // Calculate trends and changes
    if (count($entries) > 1) {
        $oneWeekAgo = date('Y-m-d', strtotime('-7 days'));
        $oneMonthAgo = date('Y-m-d', strtotime('-30 days'));
        
        // Weekly change
        $weeklyEntries = array_filter($entries, function($entry) use ($oneWeekAgo) {
            return $entry['date'] >= $oneWeekAgo;
        });
        
        if (count($weeklyEntries) > 1) {
            $weeklyEntries = array_values($weeklyEntries);
            $stats['weekly_change'] = round($weeklyEntries[0]['weight'] - end($weeklyEntries)['weight'], 1);
        }
        
        // Monthly change
        $monthlyEntries = array_filter($entries, function($entry) use ($oneMonthAgo) {
            return $entry['date'] >= $oneMonthAgo;
        });
        
        if (count($monthlyEntries) > 1) {
            $monthlyEntries = array_values($monthlyEntries);
            $stats['monthly_change'] = round($monthlyEntries[0]['weight'] - end($monthlyEntries)['weight'], 1);
        }
        
        // Overall trend
        $recentChange = $weights[0] - $weights[min(4, count($weights) - 1)];
        if ($recentChange > 1) {
            $stats['trend'] = 'up';
        } elseif ($recentChange < -1) {
            $stats['trend'] = 'down';
        }
    }
    
    // Goal progress
    if ($stats['goal_weight']) {
        $startWeight = $stats['highest']; // Assume starting from highest weight
        $currentWeight = $stats['current'];
        $goalWeight = $stats['goal_weight'];
        
        if ($startWeight != $goalWeight) {
            $totalNeeded = abs($startWeight - $goalWeight);
            $achieved = abs($startWeight - $currentWeight);
            $stats['progress'] = min(100, round(($achieved / $totalNeeded) * 100, 1));
        }
    }
    
    // Days tracking
    if (count($entries) > 1) {
        $firstDate = end($entries)['date'];
        $lastDate = $entries[0]['date'];
        $stats['days_tracking'] = (strtotime($lastDate) - strtotime($firstDate)) / (24 * 3600);
    }
    
    return $stats;
}

// Prepare chart data for different time periods
function prepareWeightChartData($entries, $days = 30) {
    $cutoffDate = date('Y-m-d', strtotime("-$days days"));
    
    // Filter entries within the time period
    $filteredEntries = array_filter($entries, function($entry) use ($cutoffDate) {
        return $entry['date'] >= $cutoffDate;
    });
    
    // Sort by date (oldest first for chart)
    usort($filteredEntries, function($a, $b) {
        return strtotime($a['date']) - strtotime($b['date']);
    });
    
    $data = [
        'labels' => [],
        'weights' => [],
        'dates' => []
    ];
    
    foreach ($filteredEntries as $entry) {
        $data['labels'][] = date('M j', strtotime($entry['date']));
        $data['weights'][] = $entry['weight'];
        $data['dates'][] = $entry['date'];
    }
    
    return $data;
}

// Include header
include 'includes/header.php';
?>

<!-- Weight Tracking Content -->
<div class="container weight-container">
    
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1 class="page-title">
                <span class="title-icon">⚖️</span>
                Weight Tracking
            </h1>
            <p class="page-subtitle">Monitor your weight progress and achieve your goals</p>
        </div>
        
        <div class="header-actions">
            <?php if (!$quickAdd): ?>
                <button class="btn btn-outline btn-small" onclick="toggleChartPeriod()">
                    <span class="btn-icon">📊</span>
                    <span class="btn-text">View: <span id="chartPeriodText">30 Days</span></span>
                </button>
            <?php endif; ?>
            
            <button class="btn btn-primary" onclick="showAddWeightModal()">
                <span class="btn-icon">➕</span>
                <span class="btn-text">Add Weight</span>
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
                    <h3 class="card-title">Quick Weight Entry</h3>
                    <a href="weight.php" class="btn btn-outline btn-small">View Full Page</a>
                </div>
                
                <form method="POST" action="" class="weight-form">
                    <input type="hidden" name="action" value="add_weight">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="weight" class="form-label">Weight</label>
                            <div class="input-group">
                                <input type="number" id="weight" name="weight" class="form-control" 
                                       placeholder="Enter weight" required step="0.1" min="1" max="999"
                                       value="<?php echo $stats['current'] ?? ''; ?>">
                                <select name="unit" class="form-control unit-select">
                                    <option value="lbs" <?php echo $weightUnit === 'lbs' ? 'selected' : ''; ?>>lbs</option>
                                    <option value="kg" <?php echo $weightUnit === 'kg' ? 'selected' : ''; ?>>kg</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" id="date" name="date" class="form-control" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes" class="form-label">Notes (Optional)</label>
                        <input type="text" id="notes" name="notes" class="form-control" 
                               placeholder="How are you feeling today?">
                    </div>
                    
                    <div class="form-actions">
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <span class="btn-icon">💾</span>
                            <span class="btn-text">Save Weight</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        
        <!-- Weight Statistics -->
        <div class="weight-stats">
            <div class="stats-grid">
                <div class="stat-card current-weight">
                    <div class="stat-icon">⚖️</div>
                    <div class="stat-content">
                        <div class="stat-value">
                            <?php if ($stats['current']): ?>
                                <?php echo number_format($stats['current'], 1); ?> <?php echo $weightUnit; ?>
                            <?php else: ?>
                                No data
                            <?php endif; ?>
                        </div>
                        <div class="stat-label">Current Weight</div>
                        <?php if ($stats['current'] && $stats['goal_weight']): ?>
                            <div class="stat-sublabel">
                                <?php 
                                $diff = $stats['current'] - $stats['goal_weight'];
                                echo ($diff > 0 ? '+' : '') . number_format($diff, 1) . ' from goal';
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="stat-card weekly-change">
                    <div class="stat-icon">📈</div>
                    <div class="stat-content">
                        <div class="stat-value trend-<?php echo $stats['weekly_change'] > 0 ? 'up' : ($stats['weekly_change'] < 0 ? 'down' : 'stable'); ?>">
                            <?php echo $stats['weekly_change'] > 0 ? '+' : ''; ?><?php echo number_format($stats['weekly_change'], 1); ?> <?php echo $weightUnit; ?>
                        </div>
                        <div class="stat-label">Weekly Change</div>
                        <div class="stat-sublabel">Last 7 days</div>
                    </div>
                </div>
                
                <div class="stat-card monthly-change">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <div class="stat-value trend-<?php echo $stats['monthly_change'] > 0 ? 'up' : ($stats['monthly_change'] < 0 ? 'down' : 'stable'); ?>">
                            <?php echo $stats['monthly_change'] > 0 ? '+' : ''; ?><?php echo number_format($stats['monthly_change'], 1); ?> <?php echo $weightUnit; ?>
                        </div>
                        <div class="stat-label">Monthly Change</div>
                        <div class="stat-sublabel">Last 30 days</div>
                    </div>
                </div>
                
                <div class="stat-card bmi-card">
                    <div class="stat-icon">📋</div>
                    <div class="stat-content">
                        <div class="stat-value">
                            <?php if ($stats['bmi']): ?>
                                <?php echo number_format($stats['bmi'], 1); ?>
                            <?php else: ?>
                                --
                            <?php endif; ?>
                        </div>
                        <div class="stat-label">BMI</div>
                        <div class="stat-sublabel">
                            <?php echo $stats['bmi_category'] ?: 'Set height in profile'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="weight-grid">
            
            <!-- Weight Chart -->
            <div class="chart-section">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Weight Progress</h3>
                        <div class="chart-controls">
                            <button class="btn btn-small btn-outline" onclick="changeChartPeriod(7)" data-period="7">7D</button>
                            <button class="btn btn-small btn-primary" onclick="changeChartPeriod(30)" data-period="30">30D</button>
                            <button class="btn btn-small btn-outline" onclick="changeChartPeriod(90)" data-period="90">90D</button>
                            <button class="btn btn-small btn-outline" onclick="changeChartPeriod(365)" data-period="365">1Y</button>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <?php if (!empty($chartData['weights'])): ?>
                            <div class="chart-container">
                                <canvas id="weightChart" width="400" height="200"></canvas>
                            </div>
                            
                            <div class="chart-summary">
                                <div class="summary-item">
                                    <span class="summary-label">Trend</span>
                                    <span class="summary-value trend-<?php echo $stats['trend']; ?>">
                                        <?php 
                                        echo $stats['trend'] === 'up' ? '↗️ Increasing' : 
                                             ($stats['trend'] === 'down' ? '↘️ Decreasing' : '➡️ Stable');
                                        ?>
                                    </span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Average</span>
                                    <span class="summary-value"><?php echo number_format($stats['average'], 1); ?> <?php echo $weightUnit; ?></span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Range</span>
                                    <span class="summary-value">
                                        <?php echo number_format($stats['lowest'], 1); ?> - <?php echo number_format($stats['highest'], 1); ?> <?php echo $weightUnit; ?>
                                    </span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-icon">📈</div>
                                <h4>No weight data yet</h4>
                                <p>Start tracking your weight to see progress charts and trends</p>
                                <button class="btn btn-primary" onclick="showAddWeightModal()">
                                    Add Your First Entry
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Goal Setting -->
            <div class="goal-section">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Weight Goal</h3>
                        <button class="btn btn-small btn-outline" onclick="showGoalModal()">
                            <?php echo $stats['goal_weight'] ? 'Edit Goal' : 'Set Goal'; ?>
                        </button>
                    </div>
                    
                    <div class="card-body">
                        <?php if ($stats['goal_weight']): ?>
                            <div class="goal-display">
                                <div class="goal-target">
                                    <div class="goal-weight">
                                        <span class="goal-number"><?php echo number_format($stats['goal_weight'], 1); ?></span>
                                        <span class="goal-unit"><?php echo $weightUnit; ?></span>
                                    </div>
                                    <div class="goal-date">
                                        Target: <?php echo date('M j, Y', strtotime($stats['goal_date'])); ?>
                                    </div>
                                </div>
                                
                                <?php if ($stats['progress'] !== null): ?>
                                    <div class="goal-progress">
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $stats['progress']; ?>%"></div>
                                        </div>
                                        <div class="progress-text">
                                            <?php echo number_format($stats['progress'], 1); ?>% Complete
                                        </div>
                                    </div>
                                    
                                    <div class="goal-remaining">
                                        <?php 
                                        $remaining = abs($stats['current'] - $stats['goal_weight']);
                                        echo number_format($remaining, 1) . ' ' . $weightUnit . ' to go';
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="goal-empty">
                                <div class="goal-icon">🎯</div>
                                <h4>Set Your Weight Goal</h4>
                                <p>Having a specific target helps you stay motivated and track progress effectively.</p>
                                <button class="btn btn-primary" onclick="showGoalModal()">
                                    Set Goal
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Recent Entries -->
            <div class="history-section">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Entries</h3>
                        <div class="card-actions">
                            <button class="btn btn-small btn-outline" onclick="exportWeightData()">
                                <span class="btn-icon">📤</span>
                                Export
                            </button>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <?php if (!empty($recentEntries)): ?>
                            <div class="weight-list">
                                <?php foreach ($recentEntries as $entry): ?>
                                    <div class="weight-entry" data-entry-id="<?php echo $entry['id']; ?>">
                                        <div class="entry-date">
                                            <div class="date-main"><?php echo date('M j', strtotime($entry['date'])); ?></div>
                                            <div class="date-year"><?php echo date('Y', strtotime($entry['date'])); ?></div>
                                        </div>
                                        
                                        <div class="entry-content">
                                            <div class="entry-weight">
                                                <span class="weight-value"><?php echo number_format($entry['weight'], 1); ?></span>
                                                <span class="weight-unit"><?php echo $entry['unit']; ?></span>
                                            </div>
                                            
                                            <?php if (!empty($entry['notes'])): ?>
                                                <div class="entry-notes"><?php echo htmlspecialchars($entry['notes']); ?></div>
                                            <?php endif; ?>
                                            
                                            <div class="entry-time">
                                                <?php echo date('g:i A', strtotime($entry['timestamp'])); ?>
                                            </div>
                                        </div>
                                        
                                        <div class="entry-actions">
                                            <button class="btn btn-small btn-outline" onclick="editWeightEntry('<?php echo $entry['id']; ?>')">
                                                <span class="btn-icon">✏️</span>
                                            </button>
                                            <button class="btn btn-small btn-danger" onclick="deleteWeightEntry('<?php echo $entry['id']; ?>')">
                                                <span class="btn-icon">🗑️</span>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php if (count($weightEntries) > 10): ?>
                                <div class="load-more">
                                    <button class="btn btn-outline" onclick="loadMoreEntries()">
                                        Load More Entries (<?php echo count($weightEntries) - 10; ?> remaining)
                                    </button>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-icon">📝</div>
                                <h4>No weight entries yet</h4>
                                <p>Add your first weight entry to start tracking your progress</p>
                                <button class="btn btn-primary" onclick="showAddWeightModal()">
                                    Add Weight Entry
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Add Weight Modal -->
<div class="modal" id="addWeightModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add Weight Entry</h3>
            <button class="modal-close" onclick="hideAddWeightModal()">×</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="" class="weight-form" id="addWeightForm">
                <input type="hidden" name="action" value="add_weight">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="modal_weight" class="form-label">Weight *</label>
                        <div class="input-group">
                            <input type="number" id="modal_weight" name="weight" class="form-control" 
                                   placeholder="Enter weight" required step="0.1" min="1" max="999">
                            <select name="unit" class="form-control unit-select">
                                <option value="lbs" <?php echo $weightUnit === 'lbs' ? 'selected' : ''; ?>>lbs</option>
                                <option value="kg" <?php echo $weightUnit === 'kg' ? 'selected' : ''; ?>>kg</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="modal_date" class="form-label">Date *</label>
                        <input type="date" id="modal_date" name="date" class="form-control" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="modal_notes" class="form-label">Notes</label>
                    <textarea id="modal_notes" name="notes" class="form-control" 
                              placeholder="How are you feeling? Any notes about your progress..." rows="3"></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="hideAddWeightModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="btn-icon">💾</span>
                        <span class="btn-text">Save Entry</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Include JavaScript -->
<script src="assets/js/charts.js"></script>

<script>
// Weight page JavaScript
let currentChartPeriod = 30;
let weightChart = null;

document.addEventListener('DOMContentLoaded', function() {
    initializeWeightPage();
});

function initializeWeightPage() {
    // Initialize weight chart
    <?php if (!empty($chartData['weights'])): ?>
        createWeightChart();
    <?php endif; ?>
    
    // Initialize form validation
    setupFormValidation();
    
    // Initialize goal preview
    setupGoalPreview();
    
    // Setup keyboard shortcuts
    setupWeightShortcuts();
    
    // Auto-focus on weight input if quick add
    <?php if ($quickAdd): ?>
        const weightInput = document.getElementById('weight');
        if (weightInput) {
            weightInput.focus();
            weightInput.select();
        }
    <?php endif; ?>
}

function createWeightChart() {
    const ctx = document.getElementById('weightChart');
    if (!ctx) return;
    
    const chartData = <?php echo json_encode($chartData); ?>;
    const goalWeight = <?php echo $stats['goal_weight'] ?: 'null'; ?>;
    
    const datasets = [{
        label: 'Weight (<?php echo $weightUnit; ?>)',
        data: chartData.weights,
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
    }];
    
    // Add goal line if goal is set
    if (goalWeight) {
        datasets.push({
            label: 'Goal Weight',
            data: new Array(chartData.weights.length).fill(goalWeight),
            borderColor: '#28A745',
            backgroundColor: 'transparent',
            borderWidth: 2,
            borderDash: [5, 5],
            pointRadius: 0,
            pointHoverRadius: 0
        });
    }
    
    weightChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        color: '#E0E0E0',
                        usePointStyle: true
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: 'rgba(44, 44, 44, 0.9)',
                    titleColor: '#FF6B35',
                    bodyColor: '#E0E0E0',
                    borderColor: '#FF6B35',
                    borderWidth: 1
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#E0E0E0',
                        callback: function(value) {
                            return value + ' <?php echo $weightUnit; ?>';
                        }
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
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            }
        }
    });
}

async function changeChartPeriod(days) {
    currentChartPeriod = days;
    
    // Update button states
    document.querySelectorAll('[data-period]').forEach(btn => {
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-outline');
    });
    
    const activeBtn = document.querySelector(`[data-period="${days}"]`);
    if (activeBtn) {
        activeBtn.classList.remove('btn-outline');
        activeBtn.classList.add('btn-primary');
    }
    
    // Update chart period text
    const periodText = document.getElementById('chartPeriodText');
    if (periodText) {
        const periodLabels = {
            7: '7 Days',
            30: '30 Days',
            90: '90 Days',
            365: '1 Year'
        };
        periodText.textContent = periodLabels[days] || days + ' Days';
    }
    
    // Fetch new chart data
    try {
        const response = await fetch(`/api/weight-chart.php?period=${days}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            updateChart(data);
        }
    } catch (error) {
        console.error('Failed to update chart:', error);
    }
}

function updateChart(data) {
    if (!weightChart) return;
    
    weightChart.data.labels = data.labels;
    weightChart.data.datasets[0].data = data.weights;
    
    // Update goal line if it exists
    if (weightChart.data.datasets.length > 1) {
        const goalWeight = <?php echo $stats['goal_weight'] ?: 'null'; ?>;
        if (goalWeight) {
            weightChart.data.datasets[1].data = new Array(data.weights.length).fill(goalWeight);
        }
    }
    
    weightChart.update('active');
}

function showAddWeightModal() {
    const modal = document.getElementById('addWeightModal');
    modal.classList.add('show');
    
    // Focus on weight input
    setTimeout(() => {
        const weightInput = document.getElementById('modal_weight');
        if (weightInput) {
            weightInput.focus();
            weightInput.select();
        }
    }, 100);
}

function hideAddWeightModal() {
    const modal = document.getElementById('addWeightModal');
    modal.classList.remove('show');
    
    // Reset form
    const form = document.getElementById('addWeightForm');
    if (form) form.reset();
}

function showGoalModal() {
    const modal = document.getElementById('goalModal');
    modal.classList.add('show');
    
    // Update goal preview
    updateGoalPreview();
}

function hideGoalModal() {
    const modal = document.getElementById('goalModal');
    modal.classList.remove('show');
}

function setupFormValidation() {
    const forms = document.querySelectorAll('.weight-form, .goal-form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const weightInput = form.querySelector('input[name="weight"], input[name="goal_weight"]');
            
            if (weightInput) {
                const weight = parseFloat(weightInput.value);
                if (weight <= 0 || weight >= 1000) {
                    e.preventDefault();
                    showToast('Please enter a valid weight between 1 and 999', 'error');
                    weightInput.focus();
                    return;
                }
            }
        });
    });
}

function setupGoalPreview() {
    const goalWeightInput = document.getElementById('goal_weight');
    const goalDateInput = document.getElementById('goal_date');
    
    if (goalWeightInput && goalDateInput) {
        [goalWeightInput, goalDateInput].forEach(input => {
            input.addEventListener('input', updateGoalPreview);
        });
    }
}

function updateGoalPreview() {
    const goalWeight = parseFloat(document.getElementById('goal_weight').value);
    const goalDate = document.getElementById('goal_date').value;
    const currentWeight = <?php echo $stats['current'] ?: 'null'; ?>;
    const preview = document.getElementById('goalPreview');
    
    if (!goalWeight || !goalDate || !currentWeight || !preview) return;
    
    const difference = currentWeight - goalWeight;
    const daysToGoal = Math.ceil((new Date(goalDate) - new Date()) / (1000 * 60 * 60 * 24));
    const weeklyTarget = difference / (daysToGoal / 7);
    
    let previewHTML = '<div class="goal-calculation">';
    previewHTML += '<h4>Goal Preview</h4>';
    
    if (difference > 0) {
        previewHTML += `<p>📉 You need to lose <strong>${Math.abs(difference).toFixed(1)} <?php echo $weightUnit; ?></strong></p>`;
    } else if (difference < 0) {
        previewHTML += `<p>📈 You need to gain <strong>${Math.abs(difference).toFixed(1)} <?php echo $weightUnit; ?></strong></p>`;
    } else {
        previewHTML += `<p>🎯 You're already at your goal weight!</p>`;
    }
    
    if (daysToGoal > 0 && Math.abs(difference) > 0) {
        previewHTML += `<p>📅 ${daysToGoal} days to reach your goal</p>`;
        previewHTML += `<p>⚖️ Target: ${Math.abs(weeklyTarget).toFixed(1)} <?php echo $weightUnit; ?>/week</p>`;
        
        if (Math.abs(weeklyTarget) > 2) {
            previewHTML += '<p class="warning">⚠️ This may be too aggressive. Consider a more gradual approach.</p>';
        } else if (Math.abs(weeklyTarget) < 0.5) {
            previewHTML += '<p class="success">✅ This is a healthy, sustainable rate.</p>';
        }
    }
    
    previewHTML += '</div>';
    preview.innerHTML = previewHTML;
}

async function deleteWeightEntry(entryId) {
    if (!confirm('Are you sure you want to delete this weight entry?')) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'delete_weight');
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
            
            showToast('Weight entry deleted successfully', 'success');
            
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

function editWeightEntry(entryId) {
    // For now, show the add modal with pre-filled data
    // In a full implementation, this would populate the modal with the entry data
    showAddWeightModal();
    showToast('Edit functionality coming soon! For now, add a new entry.', 'info');
}

async function exportWeightData() {
    try {
        const response = await fetch('/api/export-weight.php', {
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
            a.download = `fitgrit-weight-data-${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            
            showToast('Weight data exported successfully', 'success');
        } else {
            showToast('Export failed. Please try again.', 'error');
        }
    } catch (error) {
        console.error('Export failed:', error);
        showToast('Export failed. Please try again.', 'error');
    }
}

function loadMoreEntries() {
    // Implement load more functionality
    showToast('Load more functionality coming soon!', 'info');
}

function setupWeightShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + W to add weight (if not in input field)
        if ((e.ctrlKey || e.metaKey) && e.key === 'w' && !e.target.matches('input, textarea')) {
            e.preventDefault();
            showAddWeightModal();
        }
        
        // Ctrl/Cmd + G to set goal
        if ((e.ctrlKey || e.metaKey) && e.key === 'g' && !e.target.matches('input, textarea')) {
            e.preventDefault();
            showGoalModal();
        }
        
        // Escape to close modals
        if (e.key === 'Escape') {
            hideAddWeightModal();
            hideGoalModal();
        }
    });
}

function toggleChartPeriod() {
    const periods = [7, 30, 90, 365];
    const currentIndex = periods.indexOf(currentChartPeriod);
    const nextIndex = (currentIndex + 1) % periods.length;
    changeChartPeriod(periods[nextIndex]);
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
/* Weight Page Styles */
.weight-container {
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
    border: 2px solid var(--primary-orange);
}

.input-group {
    display: flex;
    gap: var(--spacing-sm);
}

.input-group .form-control {
    flex: 1;
}

.unit-select {
    flex: 0 0 80px;
}

.input-addon {
    display: flex;
    align-items: center;
    padding: var(--spacing-md);
    background: var(--border-grey);
    border: 2px solid var(--border-grey);
    border-left: none;
    border-radius: 0 var(--radius-md) var(--radius-md) 0;
    color: var(--text-light);
    font-weight: 500;
}

/* Weight Statistics */
.weight-stats {
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
    background: var(--primary-orange);
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px var(--shadow);
}

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
    color: var(--primary-orange);
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

.trend-up { color: var(--accent-red); }
.trend-down { color: var(--success-green); }
.trend-stable { color: #999; }

/* Main Grid */
.weight-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: var(--spacing-xl);
    grid-template-areas: 
        "chart goal"
        "history history";
}

.chart-section {
    grid-area: chart;
}

.goal-section {
    grid-area: goal;
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

/* Goal Display */
.goal-display {
    text-align: center;
}

.goal-target {
    margin-bottom: var(--spacing-lg);
}

.goal-weight {
    display: flex;
    align-items: baseline;
    justify-content: center;
    gap: var(--spacing-sm);
    margin-bottom: var(--spacing-sm);
}

.goal-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--primary-orange);
}

.goal-unit {
    font-size: 1.2rem;
    color: #999;
}

.goal-date {
    color: #999;
    font-size: 0.9rem;
}

.goal-progress {
    margin-bottom: var(--spacing-lg);
}

.progress-bar {
    width: 100%;
    height: 10px;
    background: var(--border-grey);
    border-radius: 5px;
    overflow: hidden;
    margin-bottom: var(--spacing-sm);
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--primary-orange), var(--success-green));
    border-radius: 5px;
    transition: width var(--transition-slow);
}

.progress-text {
    font-size: 0.9rem;
    color: var(--text-light);
    font-weight: 600;
}

.goal-remaining {
    font-size: 1.1rem;
    color: var(--primary-orange);
    font-weight: 600;
}

.goal-empty, .goal-icon {
    text-align: center;
}

.goal-icon {
    font-size: 3rem;
    margin-bottom: var(--spacing-md);
}

/* Weight History */
.weight-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-md);
}

.weight-entry {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    padding: var(--spacing-md);
    background: var(--dark-grey);
    border-radius: var(--radius-lg);
    border: 1px solid var(--border-grey);
    transition: all var(--transition-normal);
}

.weight-entry:hover {
    background: var(--border-grey);
    transform: translateX(4px);
}

.entry-date {
    text-align: center;
    min-width: 60px;
}

.date-main {
    font-weight: 600;
    color: var(--primary-orange);
    font-size: 0.9rem;
}

.date-year {
    font-size: 0.75rem;
    color: #999;
}

.entry-content {
    flex: 1;
}

.entry-weight {
    display: flex;
    align-items: baseline;
    gap: var(--spacing-xs);
    margin-bottom: var(--spacing-xs);
}

.weight-value {
    font-size: 1.3rem;
    font-weight: 600;
    color: var(--text-light);
}

.weight-unit {
    font-size: 0.9rem;
    color: #999;
}

.entry-notes {
    font-size: 0.9rem;
    color: #999;
    margin-bottom: var(--spacing-xs);
    font-style: italic;
}

.entry-time {
    font-size: 0.8rem;
    color: #666;
}

.entry-actions {
    display: flex;
    gap: var(--spacing-xs);
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

/* Goal Calculation Preview */
.goal-calculation {
    background: rgba(255, 107, 53, 0.1);
    border: 1px solid var(--primary-orange);
    border-radius: var(--radius-md);
    padding: var(--spacing-md);
    margin-top: var(--spacing-md);
}

.goal-calculation h4 {
    color: var(--primary-orange);
    margin-bottom: var(--spacing-sm);
}

.goal-calculation .warning {
    color: var(--warning-yellow);
}

.goal-calculation .success {
    color: var(--success-green);
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
    .weight-grid {
        grid-template-columns: 1fr;
        grid-template-areas: 
            "chart"
            "goal"
            "history";
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
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
    
    .chart-summary {
        flex-direction: column;
        gap: var(--spacing-sm);
    }
    
    .weight-entry {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--spacing-sm);
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
}

@media (max-width: 480px) {
    .chart-controls {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .input-group {
        flex-direction: column;
    }
    
    .unit-select {
        flex: 1;
    }
}
</style>

<?php include 'includes/footer.php'; ?> echo $stats['goal_weight'] ?? ''; ?>">
                        <span class="input-addon"><?php echo $weightUnit; ?></span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="goal_date" class="form-label">Target Date *</label>
                    <input type="date" id="goal_date" name="goal_date" class="form-control" 
                           required min="<?php echo date('Y-m-d'); ?>"
                           value="<?php echo $stats['goal_date'] ?? ''; ?>">
                </div>
                
                <div class="goal-preview" id="goalPreview">
                    <!-- Goal preview will be populated by JavaScript -->
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="hideGoalModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="btn-icon">🎯</span>
                        <span class="btn-text"><?php echo $stats['goal_weight'] ? 'Update' : 'Set'; ?> Goal</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>