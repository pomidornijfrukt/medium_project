<template>
  <div 
    class="flex flex-col items-center justify-center py-8"
    :class="containerClass"
    role="status"
    aria-live="polite"
    :aria-label="ariaLabel"
  >
    <!-- Loading Spinner -->
    <div 
      class="relative"
      aria-hidden="true"
    >
      <div 
        class="animate-spin rounded-full border-b-2"
        :class="[
          size === 'small' ? 'h-6 w-6' : size === 'large' ? 'h-16 w-16' : 'h-10 w-10',
          spinnerColorClass
        ]"
      ></div>
      <div 
        v-if="showProgress && progress !== null"
        class="absolute inset-0 flex items-center justify-center"
      >
        <span 
          class="text-xs font-medium"
          :class="textColorClass"
        >
          {{ Math.round(progress) }}%
        </span>
      </div>
    </div>

    <!-- Loading Message -->
    <div class="mt-4 text-center max-w-sm">
      <p 
        class="font-medium"
        :class="[
          size === 'small' ? 'text-sm' : size === 'large' ? 'text-lg' : 'text-base',
          textColorClass
        ]"
      >
        {{ message }}
      </p>
      <p 
        v-if="description"
        class="mt-1 text-sm opacity-75"
        :class="textColorClass"
      >
        {{ description }}
      </p>
    </div>

    <!-- Estimated Time -->
    <div 
      v-if="estimatedTime && showEstimatedTime"
      class="mt-2 text-xs opacity-60"
      :class="textColorClass"
      aria-live="polite"
    >
      Estimated time: {{ estimatedTime }}
    </div>

    <!-- Cancel Button -->
    <div v-if="cancellable && onCancel" class="mt-4">
      <AccessibleButton
        @click="onCancel"
        variant="secondary"
        size="small"
        :disabled="cancelDisabled"
        aria-label="Cancel current operation"
      >
        <svg 
          class="h-4 w-4 mr-1" 
          fill="none" 
          viewBox="0 0 24 24" 
          stroke="currentColor"
          aria-hidden="true"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
        Cancel
      </AccessibleButton>
    </div>

    <!-- Screen Reader Only Progress Updates -->
    <div class="sr-only" aria-live="assertive" aria-atomic="true">
      <span v-if="progress !== null && showProgress">
        Loading progress: {{ Math.round(progress) }} percent complete
      </span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import AccessibleButton from './AccessibleButton.vue'

const props = defineProps({
  // Content props
  message: {
    type: String,
    default: 'Loading...'
  },
  description: {
    type: String,
    default: null
  },
  ariaLabel: {
    type: String,
    default: null
  },

  // Appearance props
  size: {
    type: String,
    default: 'medium',
    validator: (value) => ['small', 'medium', 'large'].includes(value)
  },
  variant: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'secondary', 'success', 'warning', 'danger'].includes(value)
  },
  containerClass: {
    type: String,
    default: ''
  },

  // Progress props
  progress: {
    type: Number,
    default: null,
    validator: (value) => value === null || (value >= 0 && value <= 100)
  },
  showProgress: {
    type: Boolean,
    default: false
  },
  estimatedTime: {
    type: String,
    default: null
  },
  showEstimatedTime: {
    type: Boolean,
    default: true
  },

  // Interaction props
  cancellable: {
    type: Boolean,
    default: false
  },
  cancelDisabled: {
    type: Boolean,
    default: false
  },
  onCancel: {
    type: Function,
    default: null
  }
})

// Computed classes based on variant
const spinnerColorClass = computed(() => {
  const variants = {
    primary: 'border-indigo-600',
    secondary: 'border-gray-600',
    success: 'border-green-600',
    warning: 'border-yellow-600',
    danger: 'border-red-600'
  }
  return variants[props.variant] || variants.primary
})

const textColorClass = computed(() => {
  const variants = {
    primary: 'text-indigo-700',
    secondary: 'text-gray-700',
    success: 'text-green-700',
    warning: 'text-yellow-700',
    danger: 'text-red-700'
  }
  return variants[props.variant] || variants.primary
})

// Default aria label
const computedAriaLabel = computed(() => {
  if (props.ariaLabel) return props.ariaLabel
  if (props.progress !== null && props.showProgress) {
    return `${props.message} ${Math.round(props.progress)} percent complete`
  }
  return props.message
})
</script>

<style scoped>
/* Reduce motion for users who prefer it */
@media (prefers-reduced-motion: reduce) {
  .animate-spin {
    animation: spin 3s linear infinite;
  }
}

/* Custom spin animation for better accessibility */
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
