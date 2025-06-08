<template>
  <nav 
    :aria-label="ariaLabel"
    :class="navigationClasses"
  >
    <!-- Skip links for keyboard users -->
    <div class="sr-only">
      <a 
        href="#main-content" 
        class="skip-link focus:not-sr-only focus:absolute focus:top-0 focus:left-0 focus:z-50 focus:px-4 focus:py-2 focus:bg-indigo-600 focus:text-white focus:no-underline"
        @click="skipToMain"
      >
        Skip to main content
      </a>
    </div>

    <!-- Mobile menu button -->
    <div v-if="responsive" class="md:hidden">
      <AccessibleButton
        variant="ghost"
        size="small"
        :aria-expanded="mobileMenuOpen"
        aria-controls="mobile-menu"
        aria-label="Toggle navigation menu"
        @click="toggleMobileMenu"
      >
        <svg 
          v-if="!mobileMenuOpen"
          class="w-6 h-6" 
          fill="none" 
          stroke="currentColor" 
          viewBox="0 0 24 24"
          aria-hidden="true"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
        <svg 
          v-else
          class="w-6 h-6" 
          fill="none" 
          stroke="currentColor" 
          viewBox="0 0 24 24"
          aria-hidden="true"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </AccessibleButton>
    </div>

    <!-- Navigation menu -->
    <div 
      :id="responsive ? 'mobile-menu' : undefined"
      :class="menuClasses"
      :aria-hidden="responsive && !mobileMenuOpen"
    >
      <ul 
        :class="listClasses"
        role="menubar"
        :aria-orientation="orientation"
      >
        <li 
          v-for="(item, index) in items"
          :key="item.id || item.label"
          :class="itemClasses"
          role="none"
        >
          <!-- Dropdown menu item -->
          <div v-if="item.children && item.children.length > 0" class="relative">
            <AccessibleButton
              :variant="getItemVariant(item)"
              :size="size"
              :aria-expanded="item.expanded || false"
              :aria-haspopup="true"
              :aria-controls="`submenu-${index}`"
              role="menuitem"
              :tabindex="getTabIndex(index)"
              :class="buttonClasses"
              @click="toggleSubmenu(item, index)"
              @keydown="handleKeydown($event, index, 'parent')"
            >
              {{ item.label }}
              <svg 
                class="w-4 h-4 ml-1 transition-transform duration-200"
                :class="{ 'rotate-180': item.expanded }"
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
                aria-hidden="true"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </AccessibleButton>
            
            <!-- Submenu -->
            <ul 
              v-if="item.expanded"
              :id="`submenu-${index}`"
              :class="submenuClasses"
              role="menu"
              :aria-labelledby="`menuitem-${index}`"
            >
              <li 
                v-for="(child, childIndex) in item.children"
                :key="child.id || child.label"
                role="none"
              >
                <router-link
                  v-if="child.to"
                  :to="child.to"
                  :class="submenuItemClasses"
                  role="menuitem"
                  :tabindex="-1"
                  @click="handleSubmenuClick(child)"
                  @keydown="handleKeydown($event, childIndex, 'child', index)"
                >
                  {{ child.label }}
                </router-link>
                <button
                  v-else
                  :class="submenuItemClasses"
                  role="menuitem"
                  :tabindex="-1"
                  @click="handleSubmenuClick(child)"
                  @keydown="handleKeydown($event, childIndex, 'child', index)"
                >
                  {{ child.label }}
                </button>
              </li>
            </ul>
          </div>

          <!-- Regular menu item -->
          <router-link
            v-else-if="item.to"
            :to="item.to"
            :class="linkClasses"
            role="menuitem"
            :tabindex="getTabIndex(index)"
            :aria-current="isCurrentPage(item.to) ? 'page' : undefined"
            @click="handleItemClick(item)"
            @keydown="handleKeydown($event, index, 'link')"
          >
            <component 
              v-if="item.icon"
              :is="item.icon"
              class="w-4 h-4 mr-2"
              aria-hidden="true"
            />
            {{ item.label }}
          </router-link>
          
          <!-- Button menu item -->
          <AccessibleButton
            v-else
            :variant="getItemVariant(item)"
            :size="size"
            :class="buttonClasses"
            role="menuitem"
            :tabindex="getTabIndex(index)"
            @click="handleItemClick(item)"
            @keydown="handleKeydown($event, index, 'button')"
          >
            <component 
              v-if="item.icon"
              :is="item.icon"
              class="w-4 h-4 mr-2"
              aria-hidden="true"
            />
            {{ item.label }}
          </AccessibleButton>
        </li>
      </ul>
    </div>

    <!-- Overlay for mobile menu -->
    <div 
      v-if="responsive && mobileMenuOpen"
      class="fixed inset-0 bg-black bg-opacity-50 z-40 md:hidden"
      @click="closeMobileMenu"
      aria-hidden="true"
    ></div>
  </nav>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAccessibility } from '@/composables/useAccessibility'
import AccessibleButton from './AccessibleButton.vue'

const props = defineProps({
  items: {
    type: Array,
    required: true
  },
  ariaLabel: {
    type: String,
    default: 'Main navigation'
  },
  orientation: {
    type: String,
    default: 'horizontal',
    validator: (value) => ['horizontal', 'vertical'].includes(value)
  },
  responsive: {
    type: Boolean,
    default: true
  },
  size: {
    type: String,
    default: 'medium',
    validator: (value) => ['small', 'medium', 'large'].includes(value)
  },
  variant: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'pills', 'underline'].includes(value)
  }
})

const emit = defineEmits(['item-click', 'submenu-toggle'])

const route = useRoute()
const router = useRouter()
const { announce, manageFocus } = useAccessibility()

// Reactive state
const mobileMenuOpen = ref(false)
const currentFocusIndex = ref(0)

// Computed classes
const navigationClasses = computed(() => [
  'relative',
  props.responsive ? 'w-full' : ''
])

const menuClasses = computed(() => {
  const base = ['transition-all duration-300 ease-in-out']
  
  if (props.responsive) {
    return [
      ...base,
      'md:block',
      mobileMenuOpen.value 
        ? 'block fixed top-16 left-0 right-0 bg-white shadow-lg z-50 p-4'
        : 'hidden'
    ]
  }
  
  return base
})

const listClasses = computed(() => {
  const base = ['flex']
  const orientationClasses = {
    horizontal: 'flex-row space-x-1',
    vertical: 'flex-col space-y-1'
  }
  
  if (props.responsive) {
    return [
      ...base,
      'md:flex-row md:space-x-1 md:space-y-0',
      'flex-col space-y-1'
    ]
  }
  
  return [...base, orientationClasses[props.orientation]]
})

const itemClasses = computed(() => ['relative'])

const linkClasses = computed(() => {
  const base = [
    'flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200',
    'focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2'
  ]
  
  const variantClasses = {
    default: 'text-gray-700 hover:text-indigo-600 hover:bg-gray-100',
    pills: 'text-gray-700 hover:text-white hover:bg-indigo-600',
    underline: 'text-gray-700 hover:text-indigo-600 border-b-2 border-transparent hover:border-indigo-600'
  }
  
  return [...base, variantClasses[props.variant]]
})

const buttonClasses = computed(() => [
  'w-full justify-start md:w-auto md:justify-center'
])

const submenuClasses = computed(() => [
  'absolute top-full left-0 mt-1 bg-white shadow-lg rounded-md border border-gray-200 py-1 z-50',
  'min-w-48'
])

const submenuItemClasses = computed(() => [
  'block w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-indigo-600',
  'focus:outline-none focus:bg-gray-100 focus:text-indigo-600'
])

// Helper methods
const getItemVariant = (item) => {
  if (isCurrentPage(item.to)) {
    return 'primary'
  }
  return 'ghost'
}

const getTabIndex = (index) => {
  return index === currentFocusIndex.value ? 0 : -1
}

const isCurrentPage = (to) => {
  if (!to) return false
  return route.path === to
}

// Event handlers
const toggleMobileMenu = () => {
  mobileMenuOpen.value = !mobileMenuOpen.value
  
  if (mobileMenuOpen.value) {
    announce('Navigation menu opened')
    nextTick(() => {
      manageFocus(document.querySelector('[role="menubar"] [role="menuitem"]'))
    })
  } else {
    announce('Navigation menu closed')
  }
}

const closeMobileMenu = () => {
  mobileMenuOpen.value = false
  announce('Navigation menu closed')
}

const toggleSubmenu = (item, index) => {
  item.expanded = !item.expanded
  emit('submenu-toggle', { item, index, expanded: item.expanded })
  
  if (item.expanded) {
    announce(`${item.label} submenu opened`)
    nextTick(() => {
      const submenu = document.querySelector(`#submenu-${index} [role="menuitem"]`)
      if (submenu) {
        manageFocus(submenu)
      }
    })
  } else {
    announce(`${item.label} submenu closed`)
  }
}

const handleItemClick = (item) => {
  emit('item-click', item)
  
  if (item.action && typeof item.action === 'function') {
    item.action()
  }
  
  if (props.responsive) {
    closeMobileMenu()
  }
}

const handleSubmenuClick = (item) => {
  emit('item-click', item)
  
  if (item.action && typeof item.action === 'function') {
    item.action()
  }
  
  // Close all submenus when an item is clicked
  props.items.forEach(parentItem => {
    if (parentItem.children) {
      parentItem.expanded = false
    }
  })
  
  if (props.responsive) {
    closeMobileMenu()
  }
}

const skipToMain = () => {
  const mainContent = document.getElementById('main-content')
  if (mainContent) {
    manageFocus(mainContent)
  }
}

// Keyboard navigation
const handleKeydown = (event, index, type, parentIndex = null) => {
  const { key } = event
  
  switch (key) {
    case 'ArrowRight':
      if (props.orientation === 'horizontal' || type === 'parent') {
        event.preventDefault()
        navigateToNext()
      }
      break
      
    case 'ArrowLeft':
      if (props.orientation === 'horizontal' || type === 'parent') {
        event.preventDefault()
        navigateToPrevious()
      }
      break
      
    case 'ArrowDown':
      event.preventDefault()
      if (type === 'parent') {
        const item = props.items[index]
        if (item.children && item.children.length > 0) {
          toggleSubmenu(item, index)
        }
      } else if (props.orientation === 'vertical') {
        navigateToNext()
      }
      break
      
    case 'ArrowUp':
      event.preventDefault()
      if (props.orientation === 'vertical' || type === 'child') {
        navigateToPrevious()
      }
      break
      
    case 'Escape':
      event.preventDefault()
      if (type === 'child' && parentIndex !== null) {
        // Close submenu and focus parent
        const parentItem = props.items[parentIndex]
        parentItem.expanded = false
        nextTick(() => {
          const parentButton = document.querySelector(`[aria-controls="submenu-${parentIndex}"]`)
          if (parentButton) {
            manageFocus(parentButton)
          }
        })
      } else if (props.responsive && mobileMenuOpen.value) {
        closeMobileMenu()
      }
      break
      
    case 'Enter':
    case ' ':
      event.preventDefault()
      event.target.click()
      break
      
    case 'Home':
      event.preventDefault()
      currentFocusIndex.value = 0
      focusCurrentItem()
      break
      
    case 'End':
      event.preventDefault()
      currentFocusIndex.value = props.items.length - 1
      focusCurrentItem()
      break
  }
}

const navigateToNext = () => {
  currentFocusIndex.value = (currentFocusIndex.value + 1) % props.items.length
  focusCurrentItem()
}

const navigateToPrevious = () => {
  currentFocusIndex.value = currentFocusIndex.value === 0 
    ? props.items.length - 1 
    : currentFocusIndex.value - 1
  focusCurrentItem()
}

const focusCurrentItem = () => {
  nextTick(() => {
    const items = document.querySelectorAll('[role="menubar"] > li [role="menuitem"]')
    const currentItem = items[currentFocusIndex.value]
    if (currentItem) {
      manageFocus(currentItem)
    }
  })
}

// Close mobile menu on route change
const closeOnRouteChange = () => {
  if (props.responsive && mobileMenuOpen.value) {
    closeMobileMenu()
  }
}

// Handle clicks outside to close submenus
const handleClickOutside = (event) => {
  const nav = event.target.closest('nav')
  if (!nav) {
    // Close all submenus
    props.items.forEach(item => {
      if (item.children) {
        item.expanded = false
      }
    })
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  router.afterEach(closeOnRouteChange)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
.skip-link {
  position: absolute;
  left: -9999px;
  top: auto;
  width: 1px;
  height: 1px;
  overflow: hidden;
}

.skip-link:focus {
  position: fixed;
  left: 0;
  top: 0;
  width: auto;
  height: auto;
  overflow: visible;
}
</style>
