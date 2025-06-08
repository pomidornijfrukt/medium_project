/**
 * PWA Composable
 * Handles Progressive Web App functionality including offline support,
 * installation prompts, and network status monitoring
 */

import { ref, reactive, onMounted, onUnmounted } from 'vue'

export function usePWA() {
  // State
  const isOnline = ref(navigator.onLine)
  const isInstallable = ref(false)
  const isInstalled = ref(false)
  const updateAvailable = ref(false)
  const installPrompt = ref(null)
  
  // Network quality information
  const networkInfo = reactive({
    type: 'unknown',
    effectiveType: '4g',
    downlink: 10,
    rtt: 100
  })

  // Installation management
  const handleInstallPrompt = (event) => {
    event.preventDefault()
    installPrompt.value = event
    isInstallable.value = true
  }

  const installApp = async () => {
    if (!installPrompt.value) return false

    try {
      const result = await installPrompt.value.prompt()
      const outcome = await result.userChoice
      
      if (outcome === 'accepted') {
        isInstalled.value = true
        isInstallable.value = false
        installPrompt.value = null
        return true
      }
      return false
    } catch (error) {
      console.error('Installation failed:', error)
      return false
    }
  }

  const dismissInstallPrompt = () => {
    isInstallable.value = false
    installPrompt.value = null
    localStorage.setItem('pwa-install-dismissed', 'true')
  }

  // Network status monitoring
  const updateNetworkStatus = () => {
    isOnline.value = navigator.onLine
    
    // Get network connection info if available
    if ('connection' in navigator) {
      const connection = navigator.connection
      networkInfo.type = connection.type || 'unknown'
      networkInfo.effectiveType = connection.effectiveType || '4g'
      networkInfo.downlink = connection.downlink || 10
      networkInfo.rtt = connection.rtt || 100
    }
  }

  // Service Worker management
  const registerServiceWorker = async () => {
    if (!('serviceWorker' in navigator)) {
      console.warn('Service Workers not supported')
      return false
    }

    try {
      const registration = await navigator.serviceWorker.register('/sw.js')
      console.log('Service Worker registered:', registration)

      // Check for updates
      registration.addEventListener('updatefound', () => {
        const newWorker = registration.installing
        if (newWorker) {
          newWorker.addEventListener('statechange', () => {
            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
              updateAvailable.value = true
            }
          })
        }
      })

      return true
    } catch (error) {
      console.error('Service Worker registration failed:', error)
      return false
    }
  }

  const updateServiceWorker = async () => {
    if (!('serviceWorker' in navigator)) return

    try {
      const registration = await navigator.serviceWorker.getRegistration()
      if (registration && registration.waiting) {
        registration.waiting.postMessage({ type: 'SKIP_WAITING' })
        window.location.reload()
      }
    } catch (error) {
      console.error('Service Worker update failed:', error)
    }
  }

  // Offline data management
  const offlineQueue = ref([])

  const queueRequest = (request) => {
    offlineQueue.value.push({
      id: Date.now(),
      url: request.url,
      method: request.method,
      data: request.data,
      timestamp: new Date()
    })
    localStorage.setItem('pwa-offline-queue', JSON.stringify(offlineQueue.value))
  }

  const processOfflineQueue = async () => {
    if (!isOnline.value || offlineQueue.value.length === 0) return

    const queue = [...offlineQueue.value]
    offlineQueue.value = []

    for (const request of queue) {
      try {
        // Attempt to replay the request
        await fetch(request.url, {
          method: request.method,
          headers: {
            'Content-Type': 'application/json',
          },
          body: request.data ? JSON.stringify(request.data) : undefined
        })
        
        console.log('Offline request synced:', request.url)
      } catch (error) {
        console.error('Failed to sync offline request:', error)
        // Re-queue failed requests
        offlineQueue.value.push(request)
      }
    }

    localStorage.setItem('pwa-offline-queue', JSON.stringify(offlineQueue.value))
  }

  // Background sync simulation for unsupported browsers
  const scheduleBackgroundSync = (action, data) => {
    if ('serviceWorker' in navigator && 'sync' in window.ServiceWorkerRegistration.prototype) {
      // Use background sync if available
      navigator.serviceWorker.ready.then(registration => {
        return registration.sync.register('background-sync')
      })
    } else {
      // Fallback: queue for when online
      queueRequest({
        url: '/api/sync',
        method: 'POST',
        data: { action, data }
      })
    }
  }

  // App update notifications
  const showUpdateNotification = () => {
    return {
      title: 'New version available',
      message: 'A new version of the app is ready. Restart to update.',
      actions: [
        {
          label: 'Update Now',
          action: updateServiceWorker
        },
        {
          label: 'Later',
          action: () => {
            updateAvailable.value = false
          }
        }
      ]
    }
  }

  // PWA capabilities detection
  const getPWACapabilities = () => {
    return {
      standalone: window.matchMedia('(display-mode: standalone)').matches,
      serviceWorker: 'serviceWorker' in navigator,
      notification: 'Notification' in window,
      push: 'PushManager' in window,
      backgroundSync: 'serviceWorker' in navigator && 'sync' in window.ServiceWorkerRegistration.prototype,
      periodicSync: 'serviceWorker' in navigator && 'periodicSync' in window.ServiceWorkerRegistration.prototype,
      webShare: 'share' in navigator,
      installPrompt: 'beforeinstallprompt' in window
    }
  }

  // Share functionality
  const shareContent = async (data) => {
    if (navigator.share) {
      try {
        await navigator.share(data)
        return true
      } catch (error) {
        console.log('Sharing failed or was cancelled:', error)
        return false
      }
    } else {
      // Fallback to clipboard
      try {
        await navigator.clipboard.writeText(data.url || data.text || '')
        return true
      } catch (error) {
        console.error('Clipboard write failed:', error)
        return false
      }
    }
  }

  // Device-specific features
  const requestNotificationPermission = async () => {
    if (!('Notification' in window)) return 'unsupported'

    if (Notification.permission === 'default') {
      const permission = await Notification.requestPermission()
      return permission
    }

    return Notification.permission
  }

  const showNotification = (title, options = {}) => {
    if (Notification.permission === 'granted') {
      return new Notification(title, {
        icon: '/icons/icon-192x192.png',
        badge: '/icons/icon-96x96.png',
        tag: 'forum-notification',
        renotify: true,
        ...options
      })
    }
    return null
  }

  // Performance monitoring
  const trackPWAMetrics = () => {
    // Track Time to Interactive
    if ('PerformanceObserver' in window) {
      const observer = new PerformanceObserver((list) => {
        for (const entry of list.getEntries()) {
          if (entry.entryType === 'navigation') {
            console.log('Navigation timing:', {
              loadComplete: entry.loadEventEnd - entry.loadEventStart,
              domInteractive: entry.domInteractive - entry.fetchStart,
              firstContentfulPaint: entry.domContentLoadedEventEnd - entry.fetchStart
            })
          }
        }
      })
      observer.observe({ entryTypes: ['navigation'] })
    }

    // Track installation analytics
    if (isInstalled.value) {
      console.log('PWA installed - tracking analytics')
      // Here you could send analytics about PWA usage
    }
  }

  // Event listeners setup
  onMounted(() => {
    // Network status
    window.addEventListener('online', updateNetworkStatus)
    window.addEventListener('offline', updateNetworkStatus)
    
    // Connection change
    if ('connection' in navigator) {
      navigator.connection.addEventListener('change', updateNetworkStatus)
    }

    // Install prompt
    window.addEventListener('beforeinstallprompt', handleInstallPrompt)
    
    // App installed
    window.addEventListener('appinstalled', () => {
      isInstalled.value = true
      isInstallable.value = false
    })

    // Online/offline queue processing
    window.addEventListener('online', processOfflineQueue)

    // Check if already dismissed
    const dismissed = localStorage.getItem('pwa-install-dismissed')
    if (dismissed) {
      isInstallable.value = false
    }

    // Load queued requests
    const savedQueue = localStorage.getItem('pwa-offline-queue')
    if (savedQueue) {
      try {
        offlineQueue.value = JSON.parse(savedQueue)
      } catch (error) {
        console.error('Failed to parse offline queue:', error)
      }
    }

    // Initialize
    updateNetworkStatus()
    registerServiceWorker()
    trackPWAMetrics()
  })

  onUnmounted(() => {
    window.removeEventListener('online', updateNetworkStatus)
    window.removeEventListener('offline', updateNetworkStatus)
    window.removeEventListener('beforeinstallprompt', handleInstallPrompt)
    
    if ('connection' in navigator) {
      navigator.connection.removeEventListener('change', updateNetworkStatus)
    }
  })

  return {
    // State
    isOnline,
    isInstallable,
    isInstalled,
    updateAvailable,
    networkInfo,
    offlineQueue,

    // Methods
    installApp,
    dismissInstallPrompt,
    updateServiceWorker,
    queueRequest,
    processOfflineQueue,
    scheduleBackgroundSync,
    showUpdateNotification,
    getPWACapabilities,
    shareContent,
    requestNotificationPermission,
    showNotification,
    trackPWAMetrics
  }
}
