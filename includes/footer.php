<?php
// FitGrit Common Footer
// Reusable footer component for all pages

// Prevent direct access
if (!defined('FITGRIT_ACCESS')) {
    die('Direct access not permitted');
}
?>

    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-left">
                    <div class="footer-logo">
                        <img src="assets/images/logo.png" alt="FitGrit Logo" width="32" height="32">
                        <span>FitGrit</span>
                    </div>
                    <p class="footer-tagline">Your personal fitness companion</p>
                </div>
                
                <div class="footer-center">
                    <div class="footer-stats">
                        <div class="stat-item">
                            <span class="stat-value" id="footerTotalEntries">--</span>
                            <span class="stat-label">Total Entries</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value" id="footerDaysActive">--</span>
                            <span class="stat-label">Days Active</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value" id="footerCurrentStreak">--</span>
                            <span class="stat-label">Current Streak</span>
                        </div>
                    </div>
                </div>
                
                <div class="footer-right">
                    <div class="app-info">
                        <div class="app-status">
                            <span class="status-item">
                                <span class="status-dot" id="connectionDot" title="Connection Status"></span>
                                <span id="connectionText">Online</span>
                            </span>
                            <span class="status-item">
                                <span class="status-dot pwa-dot" id="pwaDot" title="PWA Status"></span>
                                <span id="pwaText">Web App</span>
                            </span>
                        </div>
                        
                        <div class="app-version">
                            <span>v<?php echo APP_VERSION; ?></span>
                            <span class="version-date"><?php echo date('Y'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer Actions -->
            <div class="footer-actions">
                <button class="footer-btn" onclick="showAppInfo()" title="App Information">
                    <span class="btn-icon">ℹ️</span>
                    <span class="btn-text">Info</span>
                </button>
                
                <button class="footer-btn" onclick="exportData()" title="Export Data">
                    <span class="btn-icon">📤</span>
                    <span class="btn-text">Export</span>
                </button>
                
                <button class="footer-btn" onclick="showKeyboardShortcuts()" title="Keyboard Shortcuts">
                    <span class="btn-icon">⌨️</span>
                    <span class="btn-text">Shortcuts</span>
                </button>
                
                <button class="footer-btn" onclick="toggleTheme()" title="Toggle Theme">
                    <span class="btn-icon" id="themeIcon">🌙</span>
                    <span class="btn-text">Theme</span>
                </button>
            </div>
        </div>
    </footer>

    <!-- App Info Modal -->
    <div class="modal" id="appInfoModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>FitGrit App Information</h3>
                <button class="modal-close" onclick="hideAppInfo()">×</button>
            </div>
            <div class="modal-body">
                <div class="app-info-content">
                    <div class="info-section">
                        <h4>Application Details</h4>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Version:</span>
                                <span class="info-value"><?php echo APP_VERSION; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Installation:</span>
                                <span class="info-value" id="installationType">Web Browser</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Storage:</span>
                                <span class="info-value">JSON Files</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Offline Support:</span>
                                <span class="info-value" id="offlineSupport">Available</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="info-section">
                        <h4>Data Overview</h4>
                        <div class="data-overview" id="dataOverview">
                            <div class="overview-item">
                                <span class="overview-icon">⚖️</span>
                                <span class="overview-text">
                                    <span class="overview-count" id="weightCount">--</span>
                                    Weight entries
                                </span>
                            </div>
                            <div class="overview-item">
                                <span class="overview-icon">💪</span>
                                <span class="overview-text">
                                    <span class="overview-count" id="exerciseCount">--</span>
                                    Exercise sessions
                                </span>
                            </div>
                            <div class="overview-item">
                                <span class="overview-icon">🍎</span>
                                <span class="overview-text">
                                    <span class="overview-count" id="foodCount">--</span>
                                    Food entries
                                </span>
                            </div>
                            <div class="overview-item">
                                <span class="overview-icon">📝</span>
                                <span class="overview-text">
                                    <span class="overview-count" id="recipeCount">--</span>
                                    Saved recipes
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="info-section">
                        <h4>Privacy & Security</h4>
                        <div class="privacy-info">
                            <p>🔒 All your data is stored locally on your device</p>
                            <p>🛡️ No data is sent to external servers</p>
                            <p>📱 Works offline without internet connection</p>
                            <p>🔐 Secure password hashing and session management</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Keyboard Shortcuts Modal -->
    <div class="modal" id="shortcutsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Keyboard Shortcuts</h3>
                <button class="modal-close" onclick="hideKeyboardShortcuts()">×</button>
            </div>
            <div class="modal-body">
                <div class="shortcuts-content">
                    <div class="shortcuts-section">
                        <h4>Navigation</h4>
                        <div class="shortcut-list">
                            <div class="shortcut-item">
                                <kbd>Alt + D</kbd>
                                <span>Dashboard</span>
                            </div>
                            <div class="shortcut-item">
                                <kbd>Alt + W</kbd>
                                <span>Weight Tracking</span>
                            </div>
                            <div class="shortcut-item">
                                <kbd>Alt + E</kbd>
                                <span>Exercise Log</span>
                            </div>
                            <div class="shortcut-item">
                                <kbd>Alt + F</kbd>
                                <span>Food Tracking</span>
                            </div>
                            <div class="shortcut-item">
                                <kbd>Alt + R</kbd>
                                <span>Recipes</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="shortcuts-section">
                        <h4>Quick Actions</h4>
                        <div class="shortcut-list">
                            <div class="shortcut-item">
                                <kbd>Ctrl + W</kbd>
                                <span>Quick Log Weight</span>
                            </div>
                            <div class="shortcut-item">
                                <kbd>Ctrl + E</kbd>
                                <span>Quick Log Exercise</span>
                            </div>
                            <div class="shortcut-item">
                                <kbd>Ctrl + F</kbd>
                                <span>Quick Log Food</span>
                            </div>
                            <div class="shortcut-item">
                                <kbd>Ctrl + R</kbd>
                                <span>Add Recipe</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="shortcuts-section">
                        <h4>General</h4>
                        <div class="shortcut-list">
                            <div class="shortcut-item">
                                <kbd>Esc</kbd>
                                <span>Close modals</span>
                            </div>
                            <div class="shortcut-item">
                                <kbd>Enter</kbd>
                                <span>Submit forms</span>
                            </div>
                            <div class="shortcut-item">
                                <kbd>?</kbd>
                                <span>Show this help</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Core JavaScript -->
    <script src="assets/js/main.js"></script>
    <script src="assets/js/pwa.js"></script>
    
    <!-- Additional page-specific JavaScript -->
    <?php if (isset($additionalJS)): ?>
        <?php foreach ($additionalJS as $js): ?>
            <script src="<?php echo htmlspecialchars($js); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <script>
    // Footer functionality
    document.addEventListener('DOMContentLoaded', function() {
        initializeFooter();
        updateFooterStats();
        setupGlobalShortcuts();
    });

    function initializeFooter() {
        // Update connection status
        updateConnectionStatus();
        
        // Update PWA status
        updatePWAStatus();
        
        // Update footer stats every minute
        setInterval(updateFooterStats, 60000);
        
        // Update connection status every 30 seconds
        setInterval(updateConnectionStatus, 30000);
    }

    function updateConnectionStatus() {
        const dot = document.getElementById('connectionDot');
        const text = document.getElementById('connectionText');
        
        if (navigator.onLine) {
            dot.className = 'status-dot online';
            text.textContent = 'Online';
        } else {
            dot.className = 'status-dot offline';
            text.textContent = 'Offline';
        }
    }

    function updatePWAStatus() {
        const dot = document.getElementById('pwaDot');
        const text = document.getElementById('pwaText');
        
        if (fitgritPWA && fitgritPWA.isInstalled()) {
            dot.className = 'status-dot pwa-installed';
            text.textContent = 'Installed';
        } else {
            dot.className = 'status-dot pwa-web';
            text.textContent = 'Web App';
        }
    }

    async function updateFooterStats() {
        try {
            const response = await fetch('/api/footer-stats.php', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                
                document.getElementById('footerTotalEntries').textContent = data.totalEntries || '--';
                document.getElementById('footerDaysActive').textContent = data.daysActive || '--';
                document.getElementById('footerCurrentStreak').textContent = data.currentStreak || '--';
            }
        } catch (error) {
            console.error('Failed to update footer stats:', error);
        }
    }

    function showAppInfo() {
        const modal = document.getElementById('appInfoModal');
        modal.classList.add('show');
        
        // Update installation type
        const installationType = document.getElementById('installationType');
        if (fitgritPWA && fitgritPWA.isInstalled()) {
            installationType.textContent = 'Progressive Web App';
        } else {
            installationType.textContent = 'Web Browser';
        }
        
        // Load data overview
        loadDataOverview();
    }

    function hideAppInfo() {
        const modal = document.getElementById('appInfoModal');
        modal.classList.remove('show');
    }

    async function loadDataOverview() {
        try {
            const response = await fetch('/api/data-overview.php', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                
                document.getElementById('weightCount').textContent = data.weightCount || 0;
                document.getElementById('exerciseCount').textContent = data.exerciseCount || 0;
                document.getElementById('foodCount').textContent = data.foodCount || 0;
                document.getElementById('recipeCount').textContent = data.recipeCount || 0;
            }
        } catch (error) {
            console.error('Failed to load data overview:', error);
        }
    }

    function showKeyboardShortcuts() {
        const modal = document.getElementById('shortcutsModal');
        modal.classList.add('show');
    }

    function hideKeyboardShortcuts() {
        const modal = document.getElementById('shortcutsModal');
        modal.classList.remove('show');
    }

    async function exportData() {
        try {
            const response = await fetch('/api/export-data.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    format: 'json',
                    csrf_token: document.querySelector('meta[name="csrf-token"]').content
                })
            });
            
            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `fitgrit-data-${new Date().toISOString().split('T')[0]}.json`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                
                // Show success message
                showToast('Data exported successfully!', 'success');
            } else {
                showToast('Export failed. Please try again.', 'error');
            }
        } catch (error) {
            console.error('Export failed:', error);
            showToast('Export failed. Please try again.', 'error');
        }
    }

    function toggleTheme() {
        const body = document.body;
        const themeIcon = document.getElementById('themeIcon');
        
        if (body.classList.contains('light-theme')) {
            body.classList.remove('light-theme');
            themeIcon.textContent = '🌙';
            localStorage.setItem('fitgrit_theme', 'dark');
        } else {
            body.classList.add('light-theme');
            themeIcon.textContent = '☀️';
            localStorage.setItem('fitgrit_theme', 'light');
        }
    }

    function setupGlobalShortcuts() {
        document.addEventListener('keydown', function(e) {
            // Show shortcuts on '?' key
            if (e.key === '?' && !e.ctrlKey && !e.altKey && !e.shiftKey) {
                e.preventDefault();
                showKeyboardShortcuts();
            }
            
            // Close modals on Escape
            if (e.key === 'Escape') {
                const modals = document.querySelectorAll('.modal.show');
                modals.forEach(modal => modal.classList.remove('show'));
            }
        });
    }

    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <span class="toast-icon">${type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️'}</span>
            <span class="toast-message">${message}</span>
        `;
        
        document.body.appendChild(toast);
        
        // Show toast
        setTimeout(() => toast.classList.add('show'), 100);
        
        // Hide toast after 3 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // Load saved theme
    const savedTheme = localStorage.getItem('fitgrit_theme');
    if (savedTheme === 'light') {
        document.body.classList.add('light-theme');
        document.getElementById('themeIcon').textContent = '☀️';
    }

    // Close modals when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.classList.remove('show');
        }
    });
    </script>

    <style>
    /* Footer Styles */
    .footer {
        background: var(--darker-grey);
        border-top: 1px solid var(--border-grey);
        padding: var(--spacing-lg) 0 var(--spacing-md);
        margin-top: auto;
    }

    .footer-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: var(--spacing-md);
    }

    .footer-logo {
        display: flex;
        align-items: center;
        gap: var(--spacing-sm);
        color: var(--primary-orange);
        font-weight: 600;
    }

    .footer-tagline {
        font-size: 0.9rem;
        color: #999;
        margin: var(--spacing-xs) 0 0 0;
    }

    .footer-stats {
        display: flex;
        gap: var(--spacing-lg);
    }

    .stat-item {
        text-align: center;
    }

    .stat-value {
        display: block;
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--primary-orange);
    }

    .stat-label {
        font-size: 0.8rem;
        color: #999;
    }

    .app-status {
        display: flex;
        gap: var(--spacing-md);
        margin-bottom: var(--spacing-sm);
    }

    .status-item {
        display: flex;
        align-items: center;
        gap: var(--spacing-xs);
        font-size: 0.9rem;
    }

    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }

    .status-dot.online { background: var(--success-green); }
    .status-dot.offline { background: var(--accent-red); }
    .status-dot.pwa-installed { background: var(--accent-purple); }
    .status-dot.pwa-web { background: var(--warning-yellow); }

    .app-version {
        font-size: 0.8rem;
        color: #999;
        text-align: right;
    }

    .footer-actions {
        display: flex;
        justify-content: center;
        gap: var(--spacing-md);
        padding-top: var(--spacing-md);
        border-top: 1px solid var(--border-grey);
    }

    .footer-btn {
        display: flex;
        align-items: center;
        gap: var(--spacing-xs);
        padding: var(--spacing-xs) var(--spacing-sm);
        background: transparent;
        border: 1px solid var(--border-grey);
        border-radius: var(--radius-sm);
        color: var(--text-light);
        font-size: 0.85rem;
        cursor: pointer;
        transition: all var(--transition-fast);
    }

    .footer-btn:hover {
        background: var(--primary-orange);
        border-color: var(--primary-orange);
        color: var(--white);
    }

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
    }

    .toast.show {
        transform: translateY(0);
        opacity: 1;
    }

    .toast-success { border-left: 4px solid var(--success-green); }
    .toast-error { border-left: 4px solid var(--accent-red); }
    .toast-info { border-left: 4px solid var(--accent-purple); }

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
        max-width: 600px;
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

    .info-section {
        margin-bottom: var(--spacing-lg);
    }

    .info-section h4 {
        color: var(--primary-orange);
        margin-bottom: var(--spacing-md);
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--spacing-md);
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        padding: var(--spacing-sm);
        background: var(--dark-grey);
        border-radius: var(--radius-sm);
    }

    .info-label {
        font-weight: 500;
    }

    .info-value {
        color: var(--primary-orange);
    }

    .shortcuts-section {
        margin-bottom: var(--spacing-lg);
    }

    .shortcut-list {
        display: flex;
        flex-direction: column;
        gap: var(--spacing-sm);
    }

    .shortcut-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: var(--spacing-sm);
        background: var(--dark-grey);
        border-radius: var(--radius-sm);
    }

    .shortcut-item kbd {
        background: var(--border-grey);
        border: 1px solid var(--primary-orange);
        border-radius: 4px;
        padding: 2px 8px;
        font-size: 0.8rem;
        color: var(--primary-orange);
    }

    @media (max-width: 768px) {
        .footer-content {
            flex-direction: column;
            gap: var(--spacing-md);
            text-align: center;
        }

        .footer-stats {
            gap: var(--spacing-md);
        }

        .footer-actions {
            flex-wrap: wrap;
            gap: var(--spacing-sm);
        }

        .footer-btn .btn-text {
            display: none;
        }

        .modal-content {
            width: 95%;
            margin: var(--spacing-md);
        }

        .info-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>

</body>
</html>