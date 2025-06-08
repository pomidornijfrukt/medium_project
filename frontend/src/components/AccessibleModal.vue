<template>
  <teleport to="body">
    <transition
      name="modal"
      @enter="onEnter"
      @leave="onLeave"
      @after-enter="onAfterEnter"
      @after-leave="onAfterLeave"
    >
      <div
        v-if="isOpen"
        :id="modalId"
        class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog"
        :aria-modal="true"
        :aria-labelledby="titleId"
        :aria-describedby="descriptionId"
      >
        <!-- Backdrop -->
        <div
          class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
          @click="handleBackdropClick"
        ></div>
        
        <!-- Modal Container -->
        <div class="flex min-h-full items-center justify-center p-4">
          <div
            ref="modalRef"
            :class="modalClasses"
            @click.stop
          >
            <!-- Header -->
            <header v-if="showHeader" class="flex items-center justify-between p-6 border-b border-gray-200">
              <h1
                :id="titleId"
                class="text-lg font-semibold text-gray-900"
              >
                <slot name="title">{{ title }}</slot>
              </h1>
              
              <button
                v-if="showCloseButton"
                ref="closeButtonRef"
                class="p-2 text-gray-400 hover:text-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                :aria-label="closeButtonLabel"
                @click="close"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </header>
            
            <!-- Content -->
            <main :id="descriptionId" class="p-6">
              <slot />
            </main>
            
            <!-- Footer -->
            <footer v-if="$slots.footer" class="flex justify-end space-x-3 p-6 border-t border-gray-200">
              <slot name="footer" :close="close" />
            </footer>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import { useAccessibility } from '@/composables/useAccessibility'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: ''
  },
  size: {
    type: String,
    default: 'medium',
    validator: (value) => ['small', 'medium', 'large', 'xlarge', 'full'].includes(value)
  },
  closeOnBackdrop: {
    type: Boolean,
    default: true
  },
  closeOnEscape: {
    type: Boolean,
    default: true
  },
  showHeader: {
    type: Boolean,
    default: true
  },
  showCloseButton: {
    type: Boolean,
    default: true
  },
  closeButtonLabel: {
    type: String,
    default: 'Close dialog'
  },
  preventBodyScroll: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['close', 'open'])

const { announce, manageFocus, handleKeyboard, generateId } = useAccessibility()

const modalRef = ref(null)
const closeButtonRef = ref(null)
const modalId = ref(`modal-${generateId()}`)
const titleId = ref(`modal-title-${generateId()}`)
const descriptionId = ref(`modal-description-${generateId()}`)

let focusTrap = null
let escapeHandler = null
let previouslyFocusedElement = null

const modalClasses = computed(() => {
  const base = 'relative bg-white rounded-lg shadow-xl transition-all transform'
  
  const sizes = {
    small: 'max-w-sm w-full',
    medium: 'max-w-md w-full',
    large: 'max-w-2xl w-full',
    xlarge: 'max-w-4xl w-full',
    full: 'max-w-none w-full h-full'
  }
  
  return `${base} ${sizes[props.size]}`
})

const close = () => {
  emit('close')
}

const handleBackdropClick = () => {
  if (props.closeOnBackdrop) {
    close()
  }
}

const handleEscape = (event) => {
  if (event.key === 'Escape' && props.closeOnEscape) {
    close()
  }
}

const onEnter = () => {
  // Save currently focused element
  previouslyFocusedElement = document.activeElement
  
  // Prevent body scroll
  if (props.preventBodyScroll) {
    document.body.style.overflow = 'hidden'
  }
  
  // Announce modal opening
  announce(`Dialog opened: ${props.title || 'Modal dialog'}`)
  
  emit('open')
}

const onAfterEnter = () => {
  // Set up focus trap
  if (modalRef.value) {
    focusTrap = manageFocus.trap(modalRef.value)
    
    // Focus the close button or first focusable element
    nextTick(() => {
      if (closeButtonRef.value) {
        closeButtonRef.value.focus()
      } else {
        const firstFocusable = modalRef.value.querySelector(
          'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        )
        if (firstFocusable) {
          firstFocusable.focus()
        }
      }
    })
  }
  
  // Set up escape key handler
  if (props.closeOnEscape) {
    escapeHandler = handleKeyboard.escape(handleEscape)
  }
}

const onLeave = () => {
  // Clean up focus trap
  if (focusTrap) {
    focusTrap()
    focusTrap = null
  }
  
  // Clean up escape handler
  if (escapeHandler) {
    escapeHandler()
    escapeHandler = null
  }
  
  // Restore body scroll
  if (props.preventBodyScroll) {
    document.body.style.overflow = ''
  }
}

const onAfterLeave = () => {
  // Restore focus to previously focused element
  if (previouslyFocusedElement && typeof previouslyFocusedElement.focus === 'function') {
    previouslyFocusedElement.focus()
  }
  
  // Announce modal closing
  announce('Dialog closed')
}

// Watch for prop changes
watch(() => props.isOpen, (isOpen) => {
  if (!isOpen && focusTrap) {
    // Clean up if modal is closed programmatically
    onLeave()
  }
})

// Cleanup on unmount
onUnmounted(() => {
  if (focusTrap) {
    focusTrap()
  }
  if (escapeHandler) {
    escapeHandler()
  }
  if (props.preventBodyScroll) {
    document.body.style.overflow = ''
  }
})
</script>

<style scoped>
/* Modal transitions */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active .relative,
.modal-leave-active .relative {
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.modal-enter-from .relative,
.modal-leave-to .relative {
  transform: scale(0.95);
  opacity: 0;
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .modal-enter-active,
  .modal-leave-active,
  .modal-enter-active .relative,
  .modal-leave-active .relative {
    transition: none !important;
  }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .bg-black {
    background-color: #000000;
  }
  
  .bg-white {
    background-color: #ffffff;
    border: 2px solid #000000;
  }
}
</style>
