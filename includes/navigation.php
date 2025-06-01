<?php
// FitGrit Navigation Menu
// Reusable navigation component for all authenticated pages

// Prevent direct access
if (!defined('FITGRIT_ACCESS')) {
    die('Direct access not permitted');
}

// Get current page for active states
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Navigation items with icons and descriptions
$navItems = [
    [
        'page' => 'dashboard',
        'url' => 'dashboard.php',
        'title' => 'Dashboard',
        'icon' => '📊',
        'description' => 'Overview and progress',
        'shortcut' => 'D'
    ],
    [
        'page' => 'weight',
        'url' => 'weight.php',
        'title' => 'Weight',
        'icon' => '⚖️',
        'description' => 'Track weight changes',
        'shortcut' => 'W'
    ],
    [
        'page' => 'exercise',
        'url' => 'exercise.php',
        'title' => 'Exercise',
        'icon' => '💪',
        'description' => 'Log workouts',
        'shortcut' => 'E'
    ],
    [
        'page' => 'food',
        'url' => 'food.php',
        'title' => 'Food',
        'icon' => '🍎',
        'description' => 'Track nutrition',
        'shortcut' => 'F'
    ],
    [
        'page' => 'recipes',
        'url' => 'recipes.php',
        'title' => 'Recipes',
        'icon' => '📝',
        'description' => 'Meal planning',
        'shortcut' => 'R'
    ]
];

// Check if user has data for badge indicators
$currentUserId = getCurrentUserId();
$todayDate = date('Y-m-d');

// Get recent activity counts for badges
$weightEntries = getWeightData($currentUserId, 1);
$todayExercise = getExerciseData($currentUserId, 5);
$todayFood = getFoodData($currentUserId, $todayDate);

// Count today's activities
$todayExerciseCount = count(array_filter($todayExercise, function($entry) use ($todayDate) {
    return $entry['date'] === $todayDate;
}));

$todayFoodCount = count($todayFood);

// Activity indicators
$activityCounts = [
    'weight' => !empty($weightEntries) ? 1 : 0,
    'exercise' => $todayExerciseCount,
    'food' => $todayFoodCount,
    'recipes' => count(getRecipes($currentUserId))
];
?>

<ul class="nav" role="menubar">
    <?php foreach ($navItems as $item): ?>
        <?php 
        $isActive = $currentPage === $item['page'];
        $hasActivity = isset($activityCounts[$item['page']]) && $activityCounts[$item['page']] > 0;
        $activityCount = $activityCounts[$item['page']] ?? 0;
        ?>
        <li class="nav-item" role="none">
            <a href="<?php echo htmlspecialchars($item['url']); ?>" 
               class="nav-link<?php echo $isActive ? ' active' : ''; ?>"
               role="menuitem"
               title="<?php echo htmlspecialchars($item['description']); ?>"
               data-shortcut="<?php echo htmlspecialchars($item['shortcut']); ?>"
               <?php if (!$isActive): ?>
                   data-preload="true"
               <?php endif; ?>>
                
                <span class="nav-icon"><?php echo $item['icon']; ?></span>
                
                <span class="nav-text">
                    <?php echo htmlspecialchars($item['title']); ?>
                </span>
                
                <!-- Activity indicator badge -->
                <?php if ($hasActivity && $item['page'] !== 'dashboard'): ?>
                    <span class="nav-badge" 
                          title="<?php echo $activityCount; ?> <?php echo $item['page'] === 'weight' ? 'recent entry' : 'today'; ?>">
                        <?php if ($activityCount > 9): ?>
                            9+
                        <?php else: ?>
                            <?php echo $activityCount; ?>
                        <?php endif; ?>
                    </span>
                <?php endif; ?>
                
                <!-- Active indicator -->
                <?php if ($isActive): ?>
                    <span class="nav-active-indicator"></span>
                <?php endif; ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<!-- Keyboard shortcut hint (shown on hover) -->
<div class="keyboard-shortcuts-hint" id="keyboardHint">
    <div class="hint-content">
        <h4>Keyboard Shortcuts</h4>
        <div class="shortcuts-list">
            <?php foreach ($navItems as $item): ?>
                <div class="shortcut-item">
                    <kbd>Alt + <?php echo $item['shortcut']; ?></kbd>
                    <span><?php echo $item['title']; ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="hint-footer">
            <small>Hold Alt and press the letter to navigate quickly</small>
        </div>
    </div>
</div>

<style>
/* Navigation Styles */
.nav {
    display: flex;
    list-style: none;
    gap: var(--spacing-xs);
    margin: 0;
    padding: 0;
    align-items: center;
    flex-wrap: nowrap;
    overflow-x: auto;
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* IE/Edge */
}

.nav::-webkit-scrollbar {
    display: none; /* Chrome/Safari */
}

.nav-item {
    position: relative;
    flex-shrink: 0;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--radius-md);
    text-decoration: none;
    color: var(--text-light);
    font-weight: 500;
    transition: all var(--transition-normal);
    position: relative;
    min-height: 40px;
    white-space: nowrap;
    font-size: 0.9rem;
}

.nav-link:hover {
    background: rgba(255, 107, 53, 0.1);
    color: var(--primary-orange);
    transform: translateY(-1px);
}

.nav-link.active {
    background: var(--primary-orange);
    color: var(--white);
    box-shadow: 0 2px 8px rgba(255, 107, 53, 0.3);
}

.nav-link.active:hover {
    background: var(--light-orange);
    transform: translateY(-1px);
}

.nav-icon {
    font-size: 1.1rem;
    min-width: 20px;
    text-align: center;
}

.nav-text {
    font-size: 0.9rem;
    display: block;
}

.nav-badge {
    background: var(--accent-red);
    color: var(--white);
    font-size: 0.7rem;
    font-weight: 600;
    padding: 1px 4px;
    border-radius: 8px;
    min-width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-left: var(--spacing-xs);
    line-height: 1;
}

.nav-link.active .nav-badge {
    background: var(--white);
    color: var(--primary-orange);
}

.nav-active-indicator {
    position: absolute;
    bottom: -2px;
    left: 50%;
    transform: translateX(-50%);
    width: 4px;
    height: 4px;
    background: var(--white);
    border-radius: 50%;
}

/* Keyboard shortcuts hint */
.keyboard-shortcuts-hint {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: var(--darker-grey);
    border: 1px solid var(--primary-orange);
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    box-shadow: 0 8px 32px var(--shadow);
    z-index: 1000;
    display: none;
    min-width: 300px;
}

.keyboard-shortcuts-hint.show {
    display: block;
    animation: fadeInScale 0.2s ease;
}

.hint-content h4 {
    color: var(--primary-orange);
    margin-bottom: var(--spacing-md);
    text-align: center;
}

.shortcuts-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-sm);
}

.shortcut-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--spacing-xs) 0;
}

.shortcut-item kbd {
    background: var(--light-grey);
    border: 1px solid var(--border-grey);
    border-radius: 4px;
    padding: 2px 6px;
    font-size: 0.8rem;
    font-family: monospace;
    color: var(--primary-orange);
}

.hint-footer {
    margin-top: var(--spacing-md);
    text-align: center;
    opacity: 0.7;
}

/* Tablet adjustments */
@media (max-width: 1024px) {
    .nav-text {
        display: none;
    }
    
    .nav-link {
        padding: var(--spacing-xs);
        min-width: 40px;
        justify-content: center;
    }
    
    .nav-icon {
        font-size: 1.2rem;
    }
    
    .nav-badge {
        position: absolute;
        top: -4px;
        right: -4px;
        margin-left: 0;
    }
}

/* Mobile Navigation Styles */
@media (max-width: 768px) {
    .header .nav {
        display: none;
    }
    
    .mobile-nav .nav {
        flex-direction: column;
        gap: var(--spacing-md);
        padding: var(--spacing-lg) 0;
        overflow-x: visible;
    }
    
    .mobile-nav .nav-item {
        width: 100%;
    }
    
    .mobile-nav .nav-link {
        padding: var(--spacing-md) var(--spacing-lg);
        border-radius: var(--radius-lg);
        font-size: 1.1rem;
        justify-content: flex-start;
        width: 100%;
        min-height: 50px;
    }
    
    .mobile-nav .nav-icon {
        font-size: 1.5rem;
        min-width: 32px;
    }
    
    .mobile-nav .nav-text {
        font-size: 1.1rem;
        display: block;
    }
    
    .mobile-nav .nav-badge {
        position: static;
        margin-left: auto;
    }
    
    .keyboard-shortcuts-hint {
        display: none !important;
    }
}

/* Small tablet specific */
@media (max-width: 900px) and (min-width: 769px) {
    .nav {
        gap: 2px;
    }
    
    .nav-link {
        padding: 6px 8px;
        font-size: 0.85rem;
    }
    
    .nav-icon {
        font-size: 1rem;
        min-width: 18px;
    }
}

/* Focus styles for accessibility */
.nav-link:focus-visible {
    outline: 2px solid var(--primary-orange);
    outline-offset: 2px;
}

/* Animation for badge appearance */
.nav-badge {
    animation: badgePop 0.3s ease;
}

@keyframes badgePop {
    0% { transform: scale(0); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

@keyframes fadeInScale {
    0% { 
        opacity: 0; 
        transform: translate(-50%, -50%) scale(0.9); 
    }
    100% { 
        opacity: 1; 
        transform: translate(-50%, -50%) scale(1); 
    }
}

/* Preload indicator */
.nav-link[data-preload="true"]::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255, 107, 53, 0.1), transparent);
    transform: translateX(-100%);
    transition: transform 0.6s ease;
}

.nav-link[data-preload="true"]:hover::after {
    transform: translateX(100%);
}

/* Scroll fade indicators for horizontal scroll */
.nav-container {
    position: relative;
}

.nav-container::before,
.nav-container::after {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    width: 20px;
    pointer-events: none;
    z-index: 1;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.nav-container::before {
    left: 0;
    background: linear-gradient(to right, var(--darker-grey), transparent);
}

.nav-container::after {
    right: 0;
    background: linear-gradient(to left, var(--darker-grey), transparent);
}

.nav-container.scroll-left::before {
    opacity: 1;
}

.nav-container.scroll-right::after {
    opacity: 1;
}
</style>

<script>
// Navigation JavaScript functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize navigation scroll handling
    initializeNavScroll();
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.altKey && !e.ctrlKey && !e.shiftKey) {
            const shortcuts = {
                'KeyD': 'dashboard.php',
                'KeyW': 'weight.php',
                'KeyE': 'exercise.php',
                'KeyF': 'food.php',
                'KeyR': 'recipes.php'
            };
            
            if (shortcuts[e.code]) {
                e.preventDefault();
                window.location.href = shortcuts[e.code];
            }
        }
        
        // Show keyboard shortcuts hint on Alt key hold
        if (e.altKey && e.type === 'keydown') {
            showKeyboardHint();
        }
    });
    
    document.addEventListener('keyup', function(e) {
        if (!e.altKey) {
            hideKeyboardHint();
        }
    });
    
    // Preload pages on hover
    const navLinks = document.querySelectorAll('.nav-link[data-preload="true"]');
    navLinks.forEach(link => {
        link.addEventListener('mouseenter', function() {
            const href = this.getAttribute('href');
            if (href && !document.querySelector(`link[href="${href}"]`)) {
                const preloadLink = document.createElement('link');
                preloadLink.rel = 'prefetch';
                preloadLink.href = href;
                document.head.appendChild(preloadLink);
            }
        });
    });
    
    // Update activity badges dynamically
    updateActivityBadges();
    
    // Update badges every 30 seconds
    setInterval(updateActivityBadges, 30000);
});

function initializeNavScroll() {
    const navContainer = document.getElementById('navContainer');
    const nav = navContainer ? navContainer.querySelector('.nav') : null;
    
    if (!nav || !navContainer) return;
    
    function updateScrollIndicators() {
        const isScrollable = nav.scrollWidth > nav.clientWidth;
        const isAtStart = nav.scrollLeft <= 5;
        const isAtEnd = nav.scrollLeft >= nav.scrollWidth - nav.clientWidth - 5;
        
        navContainer.classList.toggle('scroll-left', isScrollable && !isAtStart);
        navContainer.classList.toggle('scroll-right', isScrollable && !isAtEnd);
    }
    
    // Update indicators on scroll
    nav.addEventListener('scroll', updateScrollIndicators);
    
    // Update indicators on resize
    window.addEventListener('resize', updateScrollIndicators);
    
    // Initial check
    setTimeout(updateScrollIndicators, 100);
}

function showKeyboardHint() {
    const hint = document.getElementById('keyboardHint');
    if (hint) {
        hint.classList.add('show');
    }
}

function hideKeyboardHint() {
    const hint = document.getElementById('keyboardHint');
    if (hint) {
        hint.classList.remove('show');
    }
}

async function updateActivityBadges() {
    try {
        const response = await fetch('/api/activity-count.php', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            
            // Update badges based on response
            Object.keys(data.counts || {}).forEach(page => {
                const badge = document.querySelector(`.nav-link[href="${page}.php"] .nav-badge`);
                const count = data.counts[page];
                
                if (count > 0) {
                    if (badge) {
                        badge.textContent = count > 9 ? '9+' : count;
                    } else {
                        // Create badge if it doesn't exist
                        const navLink = document.querySelector(`.nav-link[href="${page}.php"]`);
                        if (navLink) {
                            const newBadge = document.createElement('span');
                            newBadge.className = 'nav-badge';
                            newBadge.textContent = count > 9 ? '9+' : count;
                            navLink.appendChild(newBadge);
                        }
                    }
                } else if (badge) {
                    badge.remove();
                }
            });
        }
    } catch (error) {
        console.error('Failed to update activity badges:', error);
    }
}
</script>