// Accessibility utilities and helpers
import { ref, onMounted, onUnmounted, nextTick } from 'vue'

/**
 * Generate unique ID for accessibility purposes
 * @param {string} prefix - Optional prefix for the ID
 * @returns {string} - Unique ID
 */
const generateId = (prefix = 'accessibility') => {
  return `${prefix}-${Math.random().toString(36).substr(2, 9)}`
}

/**
 * WCAG 2.1 AA Accessibility Composable
 * Provides comprehensive utilities for keyboard navigation, screen reader support,
 * focus management, color contrast compliance, and user preferences
 */
export function useAccessibility() {
  // State
  const announcements = ref([])
  const focusHistory = ref([])
  const isHighContrast = ref(false)
  const prefersReducedMotion = ref(false)
  const fontSize = ref('medium')

  /**
   * Announce message to screen readers
   * @param {string} message - Message to announce
   * @param {string} priority - 'polite' or 'assertive'
   */
  const announce = (message, priority = 'polite') => {
    if (!message) return

    const announcement = {
      id: Date.now(),
      message,
      priority
    }

    announcements.value.push(announcement)

    // Remove announcement after it's been read
    setTimeout(() => {
      announcements.value = announcements.value.filter(
        a => a.id !== announcement.id
      )
    }, 1000)
  }

  // Enhanced Screen Reader Announcements (WCAG 4.1.3)
  const announceEnhanced = (message, priority = 'polite') => {
    const announcement = {
      id: Date.now(),
      message,
      priority,
      timestamp: new Date()
    }
    
    announcements.value.push(announcement)
    
    // Create or get live region for screen readers
    const liveRegion = document.getElementById('live-region') || createLiveRegion()
    liveRegion.setAttribute('aria-live', priority)
    liveRegion.textContent = message
    
    // Clear after announcement
    setTimeout(() => {
      liveRegion.textContent = ''
      announcements.value = announcements.value.filter(a => a.id !== announcement.id)
    }, priority === 'assertive' ? 3000 : 1500)
  }

  // Create invisible live region for screen reader announcements
  const createLiveRegion = () => {
    const liveRegion = document.createElement('div')
    liveRegion.id = 'live-region'
    liveRegion.setAttribute('aria-live', 'polite')
    liveRegion.setAttribute('aria-atomic', 'true')
    liveRegion.style.cssText = `
      position: absolute;
      left: -10000px;
      width: 1px;
      height: 1px;
      overflow: hidden;
    `
    document.body.appendChild(liveRegion)
    return liveRegion
  }

  /**
   * Manage focus for modal dialogs and components
   * @param {HTMLElement} container - Container element
   * @param {boolean} trapFocus - Whether to trap focus within container
   */
  const useFocusManagement = (container, trapFocus = true) => {
    let previouslyFocusedElement = null

    const focusableSelectors = [
      'button:not([disabled])',
      'input:not([disabled])',
      'select:not([disabled])',
      'textarea:not([disabled])',
      'a[href]',
      '[tabindex]:not([tabindex="-1"])',
      '[contenteditable="true"]'
    ].join(', ')

    const getFocusableElements = () => {
      if (!container) return []
      return Array.from(container.querySelectorAll(focusableSelectors))
        .filter(el => {
          const style = window.getComputedStyle(el)
          return style.display !== 'none' && style.visibility !== 'hidden'
        })
    }

    const trapFocusHandler = (e) => {
      if (!trapFocus || !container) return

      const focusableElements = getFocusableElements()
      if (focusableElements.length === 0) return

      const firstFocusable = focusableElements[0]
      const lastFocusable = focusableElements[focusableElements.length - 1]

      if (e.key === 'Tab') {
        if (e.shiftKey) {
          // Shift + Tab
          if (document.activeElement === firstFocusable) {
            e.preventDefault()
            lastFocusable.focus()
          }
        } else {
          // Tab
          if (document.activeElement === lastFocusable) {
            e.preventDefault()
            firstFocusable.focus()
          }
        }
      }

      if (e.key === 'Escape') {
        e.preventDefault()
        restoreFocus()
      }
    }

    const setInitialFocus = () => {
      previouslyFocusedElement = document.activeElement

      nextTick(() => {
        if (container) {
          const focusableElements = getFocusableElements()
          if (focusableElements.length > 0) {
            focusableElements[0].focus()
          } else {
            container.focus()
          }
        }
      })
    }

    const restoreFocus = () => {
      if (previouslyFocusedElement && typeof previouslyFocusedElement.focus === 'function') {
        previouslyFocusedElement.focus()
      }
    }

    const startFocusManagement = () => {
      document.addEventListener('keydown', trapFocusHandler)
      setInitialFocus()
    }

    const stopFocusManagement = () => {
      document.removeEventListener('keydown', trapFocusHandler)
      restoreFocus()
    }

    return {
      startFocusManagement,
      stopFocusManagement,
      setInitialFocus,
      restoreFocus
    }
  }

  // Enhanced Focus Management (WCAG 2.4.3, 2.4.7)
  const enhancedFocusManagement = {
    trap: (container) => {
      const focusableElements = container.querySelectorAll(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
      )
      
      if (focusableElements.length === 0) return
      
      const firstElement = focusableElements[0]
      const lastElement = focusableElements[focusableElements.length - 1]
      
      const handleTab = (e) => {
        if (e.key === 'Tab') {
          if (e.shiftKey) {
            if (document.activeElement === firstElement) {
              e.preventDefault()
              lastElement.focus()
            }
          } else {
            if (document.activeElement === lastElement) {
              e.preventDefault()
              firstElement.focus()
            }
          }
        }
      }
      
      container.addEventListener('keydown', handleTab)
      firstElement.focus()
      
      return () => container.removeEventListener('keydown', handleTab)
    },
    
    save: () => {
      focusHistory.value.push(document.activeElement)
    },
    
    restore: () => {
      const lastFocused = focusHistory.value.pop()
      if (lastFocused && typeof lastFocused.focus === 'function') {
        nextTick(() => lastFocused.focus())
      }
    },
    
    setToElement: (selector) => {
      nextTick(() => {
        const element = document.querySelector(selector)
        if (element) {
          element.focus()
        }
      })
    }
  }

  /**
   * Keyboard navigation helper
   * @param {Array} items - Array of items to navigate
   * @param {Function} onSelect - Callback when item is selected
   */
  const useKeyboardNavigation = (items, onSelect) => {
    const currentIndex = ref(-1)

    const handleKeydown = (e) => {
      switch (e.key) {
        case 'ArrowDown':
          e.preventDefault()
          currentIndex.value = Math.min(currentIndex.value + 1, items.value.length - 1)
          break
        case 'ArrowUp':
          e.preventDefault()
          currentIndex.value = Math.max(currentIndex.value - 1, 0)
          break
        case 'Home':
          e.preventDefault()
          currentIndex.value = 0
          break
        case 'End':
          e.preventDefault()
          currentIndex.value = items.value.length - 1
          break
        case 'Enter':
        case ' ':
          e.preventDefault()
          if (currentIndex.value >= 0 && onSelect) {
            onSelect(items.value[currentIndex.value], currentIndex.value)
          }
          break
        case 'Escape':
          currentIndex.value = -1
          break
      }
    }

    return {
      currentIndex,
      handleKeydown
    }
  }

  // User Preferences Detection (WCAG 1.4.3, 1.4.6, 2.3.3)
  const detectUserPreferences = () => {
    // High contrast
    isHighContrast.value = window.matchMedia('(prefers-contrast: high)').matches
    
    // Reduced motion
    prefersReducedMotion.value = window.matchMedia('(prefers-reduced-motion: reduce)').matches
    
    // Font size preference
    const savedFontSize = localStorage.getItem('forum-font-size')
    if (savedFontSize) {
      fontSize.value = savedFontSize
      applyFontSize(savedFontSize)
    }
  }

  // Font Size Management (WCAG 1.4.4)
  const setFontSize = (size) => {
    fontSize.value = size
    localStorage.setItem('forum-font-size', size)
    applyFontSize(size)
    announceEnhanced(`Font size changed to ${size}`)
  }

  const applyFontSize = (size) => {
    const root = document.documentElement
    const sizes = {
      small: '14px',
      medium: '16px',
      large: '18px',
      xlarge: '20px'
    }
    root.style.fontSize = sizes[size] || sizes.medium
  }

  // Color Contrast Utilities (WCAG 1.4.3, 1.4.6)
  const checkContrast = (foreground, background) => {
    const getRGB = (color) => {
      const div = document.createElement('div')
      div.style.color = color
      document.body.appendChild(div)
      const rgb = window.getComputedStyle(div).color
      document.body.removeChild(div)
      return rgb.match(/\d+/g).map(Number)
    }
    
    const getLuminance = (rgb) => {
      const [r, g, b] = rgb.map(val => {
        val = val / 255
        return val <= 0.03928 ? val / 12.92 : Math.pow((val + 0.055) / 1.055, 2.4)
      })
      return 0.2126 * r + 0.7152 * g + 0.0722 * b
    }
    
    const fg = getRGB(foreground)
    const bg = getRGB(background)
    const fgLum = getLuminance(fg)
    const bgLum = getLuminance(bg)
    
    const contrast = (Math.max(fgLum, bgLum) + 0.05) / (Math.min(fgLum, bgLum) + 0.05)
    
    return {
      ratio: contrast,
      aa: contrast >= 4.5,
      aaa: contrast >= 7
    }
  }

  // Form Accessibility Helpers (WCAG 3.3.1, 3.3.2)
  const formAccessibility = {
    validateField: (field, rules = []) => {
      const errors = []
      const value = field.value || ''
      
      rules.forEach(rule => {
        if (!rule.test(value)) {
          errors.push(rule.message)
        }
      })
      
      // Update field accessibility attributes
      if (errors.length > 0) {
        field.setAttribute('aria-invalid', 'true')
        field.setAttribute('aria-describedby', `${field.id}-error`)
        announceEnhanced(`Validation error: ${errors.join(', ')}`, 'assertive')
      } else {
        field.setAttribute('aria-invalid', 'false')
        field.removeAttribute('aria-describedby')
      }
      
      return errors
    },
    
    associateLabels: (container) => {
      const inputs = container.querySelectorAll('input, select, textarea')
      inputs.forEach(input => {
        if (!input.getAttribute('aria-label') && !input.getAttribute('aria-labelledby')) {
          const label = container.querySelector(`label[for="${input.id}"]`)
          if (label) {
            input.setAttribute('aria-labelledby', label.id || `label-${input.id}`)
            if (!label.id) label.id = `label-${input.id}`
          }
        }
      })
    }
  }

  // Heading Structure Validation (WCAG 1.3.1)
  const validateHeadingStructure = () => {
    const headings = Array.from(document.querySelectorAll('h1, h2, h3, h4, h5, h6'))
    const issues = []
    
    if (headings.length === 0) {
      issues.push('No headings found on page')
      return issues
    }
    
    // Check for h1
    const h1Count = headings.filter(h => h.tagName === 'H1').length
    if (h1Count === 0) {
      issues.push('No H1 heading found')
    } else if (h1Count > 1) {
      issues.push('Multiple H1 headings found')
    }
    
    // Check heading hierarchy
    let previousLevel = 0
    headings.forEach((heading, index) => {
      const level = parseInt(heading.tagName.substring(1))
      
      if (index === 0 && level !== 1) {
        issues.push('First heading should be H1')
      }
      
      if (level > previousLevel + 1) {
        issues.push(`Heading level skipped: ${heading.tagName} after H${previousLevel}`)
      }
      
      previousLevel = level
    })
    
    return issues
  }

  // Initialize accessibility features
  onMounted(() => {
    detectUserPreferences()
    createLiveRegion()
    
    // Listen for preference changes
    const contrastMedia = window.matchMedia('(prefers-contrast: high)')
    const motionMedia = window.matchMedia('(prefers-reduced-motion: reduce)')
    
    contrastMedia.addEventListener('change', (e) => {
      isHighContrast.value = e.matches
    })
    
    motionMedia.addEventListener('change', (e) => {
      prefersReducedMotion.value = e.matches
    })
  })

  // Keyboard handlers for common accessibility patterns
  const keyboardHandlers = {
    // Tab navigation helper
    setupTabNavigation: (containerSelector, itemSelector) => {
      const container = document.querySelector(containerSelector)
      if (!container) return

      const items = container.querySelectorAll(itemSelector)
      
      const handleKeyDown = (event) => {
        const currentIndex = Array.from(items).indexOf(event.target)
        
        switch (event.key) {
          case 'ArrowRight':
          case 'ArrowDown':
            event.preventDefault()
            const nextIndex = (currentIndex + 1) % items.length
            items[nextIndex].focus()
            break
          case 'ArrowLeft':
          case 'ArrowUp':
            event.preventDefault()
            const prevIndex = currentIndex === 0 ? items.length - 1 : currentIndex - 1
            items[prevIndex].focus()
            break
          case 'Home':
            event.preventDefault()
            items[0].focus()
            break
          case 'End':
            event.preventDefault()
            items[items.length - 1].focus()
            break
        }
      }

      items.forEach(item => {
        item.addEventListener('keydown', handleKeyDown)
      })

      return () => {
        items.forEach(item => {
          item.removeEventListener('keydown', handleKeyDown)
        })
      }
    },

    // List navigation helper
    setupListNavigation: (listSelector, options = {}) => {
      const list = document.querySelector(listSelector)
      if (!list) return

      const {
        itemSelector = '[role="listitem"], li',
        announceItems = true,
        wrap = true
      } = options

      const items = list.querySelectorAll(itemSelector)
      
      const handleKeyDown = (event) => {
        if (!['ArrowUp', 'ArrowDown', 'Home', 'End'].includes(event.key)) return
        
        event.preventDefault()
        const currentIndex = Array.from(items).indexOf(event.target)
        let newIndex = currentIndex

        switch (event.key) {
          case 'ArrowDown':
            newIndex = wrap ? (currentIndex + 1) % items.length : Math.min(currentIndex + 1, items.length - 1)
            break
          case 'ArrowUp':
            newIndex = wrap ? (currentIndex === 0 ? items.length - 1 : currentIndex - 1) : Math.max(currentIndex - 1, 0)
            break
          case 'Home':
            newIndex = 0
            break
          case 'End':
            newIndex = items.length - 1
            break
        }

        items[newIndex].focus()
        
        if (announceItems) {
          announceEnhanced(`Item ${newIndex + 1} of ${items.length}`)
        }
      }

      items.forEach(item => {
        item.addEventListener('keydown', handleKeyDown)
      })

      return () => {
        items.forEach(item => {
          item.removeEventListener('keydown', handleKeyDown)
        })
      }
    }
  }

  onUnmounted(() => {
    // Clean up live region
    const liveRegion = document.getElementById('live-region')
    if (liveRegion) {
      liveRegion.remove()
    }
  })

  // Helper Functions
  /**
   * Alias for announce function for backward compatibility
   * @param {string} message - Message to announce to screen readers
   * @param {string} priority - 'polite' or 'assertive'
   */
  const announceToScreenReader = (message, priority = 'polite') => {
    announceEnhanced(message, priority)
  }

  /**
   * Focus an element by selector or element reference
   * @param {string|HTMLElement} element - CSS selector or DOM element
   */
  const focusElement = (element) => {
    nextTick(() => {
      let target = element
      
      if (typeof element === 'string') {
        target = document.querySelector(element)
      }
      
      if (target && typeof target.focus === 'function') {
        target.focus()
      }
    })
  }

  return {
    // State
    isHighContrast,
    prefersReducedMotion,
    fontSize,
    announcements,
    
    // Enhanced Methods
    announce: announceEnhanced,
    manageFocus: enhancedFocusManagement,
    handleKeyboard: keyboardHandlers,
    setFontSize,
    checkContrast,
    formAccessibility,
    validateHeadingStructure,
    
    // Helper Functions
    announceToScreenReader,
    focusElement,
    
    // Original methods (preserved for compatibility)
    generateId,
    useFocusManagement
  }
}
