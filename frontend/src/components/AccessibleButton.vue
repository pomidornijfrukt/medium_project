<template>
  <div
    :id="buttonId"
    ref="buttonRef"
    :class="buttonClasses"
    :aria-label="ariaLabel"
    :aria-describedby="describedBy"
    :aria-pressed="pressed"
    :aria-expanded="expanded"
    :disabled="disabled"
    :tabindex="tabindex"
    role="button"
    @click="handleClick"
    @keydown="handleKeydown"
    @focus="handleFocus"
    @blur="handleBlur"
  >
    <span v-if="loading" class="sr-only">Loading</span>
    <slot :loading="loading" :focused="focused" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAccessibility } from '@/composables/useAccessibility'

const props = defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'secondary', 'danger', 'ghost'].includes(value)
  },
  size: {
    type: String,
    default: 'medium',
    validator: (value) => ['small', 'medium', 'large'].includes(value)
  },
  disabled: {
    type: Boolean,
    default: false
  },
  loading: {
    type: Boolean,
    default: false
  },
  pressed: {
    type: Boolean,
    default: null
  },
  expanded: {
    type: Boolean,
    default: null
  },
  ariaLabel: {
    type: String,
    default: null
  },
  describedBy: {
    type: String,
    default: null
  },
  tabindex: {
    type: [String, Number],
    default: 0
  }
})

const emit = defineEmits(['click', 'focus', 'blur'])

const { announce, generateId } = useAccessibility()
const buttonRef = ref(null)
const focused = ref(false)
const buttonId = ref(`button-${generateId()}`)

const buttonClasses = computed(() => {
  const base = 'inline-flex items-center justify-center font-medium rounded-md transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2'
  
  const variants = {
    primary: 'bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-indigo-500',
    secondary: 'bg-gray-100 text-gray-900 hover:bg-gray-200 focus:ring-gray-500',
    danger: 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
    ghost: 'bg-transparent text-gray-600 hover:bg-gray-100 focus:ring-gray-500'
  }
  
  const sizes = {
    small: 'px-3 py-1.5 text-sm',
    medium: 'px-4 py-2 text-sm',
    large: 'px-6 py-3 text-base'
  }
  
  const states = []
  if (props.disabled || props.loading) {
    states.push('opacity-50 cursor-not-allowed')
  }
  
  return [
    base,
    variants[props.variant],
    sizes[props.size],
    ...states
  ].join(' ')
})

const handleClick = (event) => {
  if (props.disabled || props.loading) {
    event.preventDefault()
    return
  }
  
  emit('click', event)
  
  // Announce action for screen readers if needed
  if (props.loading) {
    announce('Action is processing, please wait')
  }
}

const handleKeydown = (event) => {
  // Handle Enter and Space as clicks for accessibility
  if (event.key === 'Enter' || event.key === ' ') {
    event.preventDefault()
    handleClick(event)
  }
}

const handleFocus = (event) => {
  focused.value = true
  emit('focus', event)
}

const handleBlur = (event) => {
  focused.value = false
  emit('blur', event)
}

onMounted(() => {
  // Ensure button has proper ARIA attributes
  const button = buttonRef.value
  if (button && !button.getAttribute('aria-label') && !button.textContent.trim()) {
    console.warn('AccessibleButton: Button should have visible text or aria-label')
  }
})
</script>

<style scoped>
/* Screen reader only class */
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .inline-flex {
    border: 2px solid currentColor;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .transition-all {
    transition: none !important;
  }
}
</style>
