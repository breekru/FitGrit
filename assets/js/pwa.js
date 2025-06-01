// FitGrit PWA Functionality
// Handles PWA installation, offline detection, and service worker management

class FitGritPWA {
    constructor() {
        this.deferredPrompt = null;
        this.isOnline = navigator.onLine;
        this.installPromptShown = localStorage.getItem('fitgrit_install_prompt_shown') === 'true';
        
        this.init();
    }
    
    init() {
        this.registerServiceWorker();
        this.setupInstallPrompt();
        this.setupOfflineDetection();
        this.setupNotifications();
        this.checkForUpdates();
    }
    
    // Register service worker
    async registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            try {
                const registration = await navigator.serviceWorker.register('/sw.js');
                console.log('FitGrit PWA: Service Worker registered successfully');
                
                // Listen for updates
                registration.addEventListener('updatefound', () => {
                    const newWorker = registration.installing;
                    
                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed') {
                            if (navigator.serviceWorker.controller) {
                                // New update available
                                this.showUpdateNotification();
                            }
                        }
                    });
                });
                
            } catch (error) {
                console.error('FitGrit PWA: Service Worker registration failed:', error);
            }
        }
    }
    
    // Setup PWA install prompt
    setupInstallPrompt() {
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            this.deferredPrompt = e;
            
            // Check if prompt was previously dismissed
            const dismissed = localStorage.getItem('fitgrit_install_prompt_dismissed');
            
            // Show install prompt if not previously dismissed
            if (dismissed !== 'true' && !this.installPromptShown) {
                setTimeout(() => this.showInstallPrompt(), 2000); // Show after 2 seconds
            }
        });
        
        // Listen for app installed
        window.addEventListener('appinstalled', () => {
            console.log('FitGrit PWA: App installed successfully');
            this.hideInstallPrompt();
            this.deferredPrompt = null;
            
            // Track installation
            this.trackEvent('pwa_installed');
        });
    }
    
    // Show install prompt
    showInstallPrompt() {
        const installPrompt = document.getElementById('installPrompt');
        if (installPrompt && this.deferredPrompt && !this.isInstalled()) {
            installPrompt.style.display = 'block';
            // Use setTimeout to ensure the element is displayed before adding the class
            setTimeout(() => {
                installPrompt.classList.add('show');
            }, 10);
        }
    }
    
    // Hide install prompt
    hideInstallPrompt() {
        const installPrompt = document.getElementById('installPrompt');
        if (installPrompt) {
            installPrompt.classList.remove('show');
            setTimeout(() => {
                installPrompt.style.display = 'none';
            }, 300); // Wait for animation to complete
        }
    }
    
    // Install PWA
    async installPWA() {
        if (!this.deferredPrompt) {
            console.log('FitGrit PWA: No install prompt available');
            this.hideInstallPrompt();
            return;
        }
        
        try {
            // Show install prompt
            this.deferredPrompt.prompt();
            
            // Wait for user response
            const { outcome } = await this.deferredPrompt.userChoice;
            
            if (outcome === 'accepted') {
                console.log('FitGrit PWA: User accepted install prompt');
                this.trackEvent('pwa_install_accepted');
            } else {
                console.log('FitGrit PWA: User dismissed install prompt');
                this.trackEvent('pwa_install_dismissed');
            }
            
            this.deferredPrompt = null;
            this.hideInstallPrompt();
            
        } catch (error) {
            console.error('FitGrit PWA: Install failed:', error);
            this.hideInstallPrompt();
        }
    }
    
    // Dismiss install prompt
    dismissInstallPrompt() {
        this.hideInstallPrompt();
        this.installPromptShown = true;
        localStorage.setItem('fitgrit_install_prompt_dismissed', 'true');
        localStorage.setItem('fitgrit_install_prompt_shown', 'true');
        this.trackEvent('pwa_install_dismissed');
    }
    
    // Setup offline detection
    setupOfflineDetection() {
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.showConnectionStatus('back online', 'success');
            this.syncOfflineData();
        });
        
        window.addEventListener('offline', () => {
            this.isOnline = false;
            this.showConnectionStatus('offline', 'warning');
        });
        
        // Check connection periodically
        setInterval(() => {
            this.checkConnection();
        }, 30000); // Check every 30 seconds
    }
    
    // Check internet connection
    async checkConnection() {
        try {
            const response = await fetch('/api/ping.php', {
                method: 'HEAD',
                cache: 'no-cache'
            });
            
            const wasOnline = this.isOnline;
            this.isOnline = response.ok;
            
            if (!wasOnline && this.isOnline) {
                this.showConnectionStatus('connection restored', 'success');
                this.syncOfflineData();
            }
            
        } catch (error) {
            this.isOnline = false;
        }
    }
    
    // Show connection status
    showConnectionStatus(message, type) {
        const statusDiv = document.createElement('div');
        statusDiv.className = `alert alert-${type} connection-status`;
        statusDiv.innerHTML = `
            <span>${type === 'success' ? '✅' : '⚠️'}</span>
            You are ${message}
        `;
        
        document.body.appendChild(statusDiv);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            if (statusDiv.parentNode) {
                statusDiv.remove();
            }
        }, 3000);
    }
    
    // Sync offline data
    async syncOfflineData() {
        if (!this.isOnline) return;
        
        try {
            // Get offline data from localStorage
            const offlineData = this.getOfflineData();
            
            if (offlineData.length === 0) return;
            
            console.log('FitGrit PWA: Syncing offline data...');
            
            for (const item of offlineData) {
                try {
                    const response = await fetch(item.endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(item.data)
                    });
                    
                    if (response.ok) {
                        this.removeOfflineData(item.id);
                    }
                    
                } catch (error) {
                    console.error('FitGrit PWA: Failed to sync item:', error);
                }
            }
            
            if (this.getOfflineData().length === 0) {
                this.showConnectionStatus('all data synced', 'success');
            }
            
        } catch (error) {
            console.error('FitGrit PWA: Sync failed:', error);
        }
    }
    
    // Get offline data from localStorage
    getOfflineData() {
        try {
            const data = localStorage.getItem('fitgrit_offline_data');
            return data ? JSON.parse(data) : [];
        } catch (error) {
            return [];
        }
    }
    
    // Store data for offline sync
    storeOfflineData(endpoint, data) {
        try {
            const offlineData = this.getOfflineData();
            const item = {
                id: Date.now() + Math.random().toString(36).substr(2, 9),
                endpoint: endpoint,
                data: data,
                timestamp: new Date().toISOString()
            };
            
            offlineData.push(item);
            localStorage.setItem('fitgrit_offline_data', JSON.stringify(offlineData));
            
            console.log('FitGrit PWA: Data stored for offline sync');
            
        } catch (error) {
            console.error('FitGrit PWA: Failed to store offline data:', error);
        }
    }
    
    // Remove synced offline data
    removeOfflineData(id) {
        try {
            const offlineData = this.getOfflineData();
            const filteredData = offlineData.filter(item => item.id !== id);
            localStorage.setItem('fitgrit_offline_data', JSON.stringify(filteredData));
        } catch (error) {
            console.error('FitGrit PWA: Failed to remove offline data:', error);
        }
    }
    
    // Setup push notifications
    async setupNotifications() {
        if (!('Notification' in window) || !('serviceWorker' in navigator)) {
            return;
        }
        
        // Check current permission
        if (Notification.permission === 'granted') {
            console.log('FitGrit PWA: Notifications already enabled');
            return;
        }
        
        if (Notification.permission !== 'denied') {
            // Don't ask immediately, wait for user interaction
            this.setupNotificationPrompt();
        }
    }
    
    // Setup notification permission prompt
    setupNotificationPrompt() {
        // Add subtle prompt after user has used the app for a while
        setTimeout(() => {
            if (!localStorage.getItem('fitgrit_notifications_asked')) {
                this.showNotificationPrompt();
            }
        }, 60000); // Wait 1 minute
    }
    
    // Show notification permission prompt
    showNotificationPrompt() {
        const prompt = document.createElement('div');
        prompt.className = 'notification-prompt';
        prompt.innerHTML = `
            <div class="notification-prompt-content">
                <h4>Stay motivated with notifications</h4>
                <p>Get reminders to log your progress and celebrate achievements</p>
                <div class="notification-actions">
                    <button class="btn btn-small btn-outline" onclick="fitgritPWA.dismissNotificationPrompt()">Not now</button>
                    <button class="btn btn-small btn-primary" onclick="fitgritPWA.enableNotifications()">Enable</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(prompt);
    }
    
    // Enable notifications
    async enableNotifications() {
        try {
            const permission = await Notification.requestPermission();
            
            if (permission === 'granted') {
                console.log('FitGrit PWA: Notifications enabled');
                this.trackEvent('notifications_enabled');
                
                // Subscribe to push notifications if supported
                this.subscribeToPush();
            }
            
            localStorage.setItem('fitgrit_notifications_asked', 'true');
            this.dismissNotificationPrompt();
            
        } catch (error) {
            console.error('FitGrit PWA: Failed to enable notifications:', error);
        }
    }
    
    // Dismiss notification prompt
    dismissNotificationPrompt() {
        const prompt = document.querySelector('.notification-prompt');
        if (prompt) {
            prompt.remove();
        }
        localStorage.setItem('fitgrit_notifications_asked', 'true');
    }
    
    // Subscribe to push notifications
    async subscribeToPush() {
        try {
            const registration = await navigator.serviceWorker.ready;
            
            // Check if already subscribed
            const existingSubscription = await registration.pushManager.getSubscription();
            if (existingSubscription) {
                return;
            }
            
            // Subscribe to push notifications
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array('YOUR_VAPID_PUBLIC_KEY') // Replace with actual VAPID key
            });
            
            // Send subscription to server
            await this.sendSubscriptionToServer(subscription);
            
        } catch (error) {
            console.error('FitGrit PWA: Push subscription failed:', error);
        }
    }
    
    // Send subscription to server
    async sendSubscriptionToServer(subscription) {
        try {
            await fetch('/api/push-subscribe.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(subscription)
            });
        } catch (error) {
            console.error('FitGrit PWA: Failed to send subscription to server:', error);
        }
    }
    
    // Utility function for VAPID key conversion
    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');
        
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }
    
    // Check for app updates
    async checkForUpdates() {
        if ('serviceWorker' in navigator) {
            const registration = await navigator.serviceWorker.ready;
            registration.update();
        }
    }
    
    // Show update notification
    showUpdateNotification() {
        const updateDiv = document.createElement('div');
        updateDiv.className = 'update-notification';
        updateDiv.innerHTML = `
            <div class="update-content">
                <strong>App Update Available</strong>
                <p>A new version of FitGrit is ready. Reload to get the latest features.</p>
                <div class="update-actions">
                    <button class="btn btn-small btn-outline" onclick="this.parentElement.parentElement.parentElement.remove()">Later</button>
                    <button class="btn btn-small btn-primary" onclick="window.location.reload()">Update Now</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(updateDiv);
    }
    
    // Track events for analytics
    trackEvent(eventName, data = {}) {
        // Simple event tracking - can be enhanced with analytics service
        console.log('FitGrit PWA Event:', eventName, data);
        
        // Store in localStorage for later analysis
        try {
            const events = JSON.parse(localStorage.getItem('fitgrit_events') || '[]');
            events.push({
                event: eventName,
                data: data,
                timestamp: new Date().toISOString(),
                url: window.location.pathname
            });
            
            // Keep only last 100 events
            if (events.length > 100) {
                events.splice(0, events.length - 100);
            }
            
            localStorage.setItem('fitgrit_events', JSON.stringify(events));
        } catch (error) {
            console.error('FitGrit PWA: Failed to track event:', error);
        }
    }
    
    // Check if app is installed
    isInstalled() {
        return window.matchMedia('(display-mode: standalone)').matches ||
               window.navigator.standalone ||
               document.referrer.includes('android-app://');
    }
    
    // Get app info
    getAppInfo() {
        return {
            isInstalled: this.isInstalled(),
            isOnline: this.isOnline,
            hasServiceWorker: 'serviceWorker' in navigator,
            hasNotifications: 'Notification' in window,
            notificationPermission: 'Notification' in window ? Notification.permission : 'unsupported',
            offlineDataCount: this.getOfflineData().length
        };
    }
}

// Initialize PWA functionality
const fitgritPWA = new FitGritPWA();

// Global functions for use in HTML
window.installPWA = () => fitgritPWA.installPWA();
window.dismissInstallPrompt = () => fitgritPWA.dismissInstallPrompt();

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FitGritPWA;
}

// Add some PWA-specific styles
const pwaStyles = `
<style>
.connection-status {
    position: fixed;
    top: 80px;
    right: 1rem;
    z-index: 1001;
    max-width: 300px;
    animation: slideInRight 0.3s ease;
}

.notification-prompt {
    position: fixed;
    bottom: 1rem;
    left: 1rem;
    right: 1rem;
    background: var(--light-grey);
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    box-shadow: 0 4px 20px var(--shadow);
    z-index: 1000;
    animation: slideUp 0.3s ease;
}

.notification-prompt-content h4 {
    color: var(--primary-orange);
    margin-bottom: var(--spacing-sm);
}

.notification-actions,
.update-actions {
    display: flex;
    gap: var(--spacing-sm);
    margin-top: var(--spacing-md);
    justify-content: flex-end;
}

.update-notification {
    position: fixed;
    top: 80px;
    left: 1rem;
    right: 1rem;
    background: var(--accent-purple);
    color: var(--white);
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    box-shadow: 0 4px 20px var(--shadow);
    z-index: 1001;
    animation: slideDown 0.3s ease;
}

@keyframes slideInRight {
    from { transform: translateX(100%); }
    to { transform: translateX(0); }
}

@keyframes slideUp {
    from { transform: translateY(100%); }
    to { transform: translateY(0); }
}

@keyframes slideDown {
    from { transform: translateY(-100%); }
    to { transform: translateY(0); }
}

@media (max-width: 768px) {
    .notification-actions,
    .update-actions {
        flex-direction: column;
    }
    
    .connection-status {
        left: 1rem;
        right: 1rem;
    }
}
</style>
`;

document.head.insertAdjacentHTML('beforeend', pwaStyles);