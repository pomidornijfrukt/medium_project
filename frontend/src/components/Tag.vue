<template>
  <span 
    :class="tagClasses"
    role="status"
    :aria-label="ariaLabel || `Tag: ${label}`"
  >
    <slot name="icon" />
    <span class="text-content">{{ label }}</span>
    <button 
      v-if="removable"
      type="button"
      @click="$emit('remove')"
      class="ml-1.5 -mr-1 flex-shrink-0 rounded-full hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1 focus:bg-indigo-200 transition-colors duration-200"
      :class="[
        size === 'small' ? 'w-3 h-3' : 'w-4 h-4'
      ]"
      :aria-label="`Remove ${label} tag`"
    >
      <svg 
        :class="[
          'w-full h-full',
          size === 'small' ? 'stroke-2' : 'stroke-1.5'
        ]"
        fill="none" 
        stroke="currentColor" 
        viewBox="0 0 24 24"
        aria-hidden="true"
      >
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
      </svg>
    </button>
  </span>
</template>

<script>
import { computed, defineEmits } from 'vue'

export default {
  name: 'Tag',
  props: {
    /**
     * The text content of the tag
     */
    label: {
      type: String,
      required: true
    },
    /**
     * Size variant of the tag
     * @values small, medium, large
     */
    size: {
      type: String,
      default: 'medium',
      validator: (value) => ['small', 'medium', 'large'].includes(value)
    },
    /**
     * Color variant of the tag
     * @values indigo, gray, green, red, yellow, blue, purple
     */
    variant: {
      type: String,
      default: 'indigo',
      validator: (value) => ['indigo', 'gray', 'green', 'red', 'yellow', 'blue', 'purple'].includes(value)
    },
    /**
     * Whether the tag can be removed
     */
    removable: {
      type: Boolean,
      default: false
    },
    /**
     * Whether the tag is interactive (clickable)
     */
    interactive: {
      type: Boolean,
      default: false
    },
    /**
     * Custom aria-label for accessibility
     */
    ariaLabel: {
      type: String,
      default: null
    },
    /**
     * Additional CSS classes
     */
    customClass: {
      type: [String, Array, Object],
      default: ''
    }
  },
  emits: ['remove', 'click'],
  setup(props, { emit }) {
    const tagClasses = computed(() => {
      const baseClasses = [
        'inline-flex items-center rounded-full font-medium transition-colors duration-200',
        'focus-within:ring-2 focus-within:ring-offset-1'
      ]

      // Size classes
      const sizeClasses = {
        small: 'px-2 py-1 text-xs',
        medium: 'px-2.5 py-1 text-xs',
        large: 'px-3 py-1 text-sm'
      }

      // Variant classes
      const variantClasses = {
        indigo: [
          'bg-indigo-100 text-indigo-800',
          props.interactive ? 'hover:bg-indigo-200 cursor-pointer' : '',
          'focus-within:ring-indigo-500'
        ].filter(Boolean),
        gray: [
          'bg-gray-100 text-gray-800',
          props.interactive ? 'hover:bg-gray-200 cursor-pointer' : '',
          'focus-within:ring-gray-500'
        ].filter(Boolean),
        green: [
          'bg-green-100 text-green-800',
          props.interactive ? 'hover:bg-green-200 cursor-pointer' : '',
          'focus-within:ring-green-500'
        ].filter(Boolean),
        red: [
          'bg-red-100 text-red-800',
          props.interactive ? 'hover:bg-red-200 cursor-pointer' : '',
          'focus-within:ring-red-500'
        ].filter(Boolean),
        yellow: [
          'bg-yellow-100 text-yellow-800',
          props.interactive ? 'hover:bg-yellow-200 cursor-pointer' : '',
          'focus-within:ring-yellow-500'
        ].filter(Boolean),
        blue: [
          'bg-blue-100 text-blue-800',
          props.interactive ? 'hover:bg-blue-200 cursor-pointer' : '',
          'focus-within:ring-blue-500'
        ].filter(Boolean),
        purple: [
          'bg-purple-100 text-purple-800',
          props.interactive ? 'hover:bg-purple-200 cursor-pointer' : '',
          'focus-within:ring-purple-500'
        ].filter(Boolean)
      }

      const classes = [
        ...baseClasses,
        sizeClasses[props.size],
        ...variantClasses[props.variant]
      ]

      // Add custom classes
      if (props.customClass) {
        if (typeof props.customClass === 'string') {
          classes.push(props.customClass)
        } else if (Array.isArray(props.customClass)) {
          classes.push(...props.customClass)
        }
      }

      return classes.filter(Boolean).join(' ')
    })

    const handleClick = (event) => {
      if (props.interactive) {
        emit('click', event)
      }
    }

    return {
      tagClasses,
      handleClick
    }
  }
}
</script>

<style scoped>
/* Ensure consistent text rendering */
.text-content {
  line-height: 1.2;
  word-break: break-word;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  span {
    border: 1px solid currentColor;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  span {
    transition: none !important;
  }
}

/* Focus styles for better accessibility */
span:focus-within {
  outline: none;
}

/* Ensure remove button is properly sized */
button svg {
  min-width: 100%;
  min-height: 100%;
}
</style>
