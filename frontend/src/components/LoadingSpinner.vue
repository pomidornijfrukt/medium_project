<template>
  <svg 
    :class="[
      'animate-spin',
      sizeClass,
      colorClass
    ]"
    fill="none" 
    viewBox="0 0 24 24"
    :aria-hidden="ariaHidden"
    :aria-label="ariaLabel"
    role="img"
  >
    <circle 
      class="opacity-25" 
      cx="12" 
      cy="12" 
      r="10" 
      stroke="currentColor" 
      stroke-width="4"
    />
    <path 
      class="opacity-75" 
      fill="currentColor" 
      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
    />
  </svg>
</template>

<script setup>
import { computed, defineProps } from 'vue'

const props = defineProps({
  size: {
    type: String,
    default: 'medium',
    validator: (value) => ['tiny', 'small', 'medium', 'large', 'xl'].includes(value)
  },
  color: {
    type: String,
    default: 'current',
    validator: (value) => ['current', 'white', 'indigo', 'blue', 'green', 'red', 'gray'].includes(value)
  },
  ariaLabel: {
    type: String,
    default: 'Loading'
  },
  ariaHidden: {
    type: Boolean,
    default: false
  }
})

const sizeClass = computed(() => {
  const sizes = {
    tiny: 'h-3 w-3',
    small: 'h-4 w-4',
    medium: 'h-5 w-5',
    large: 'h-8 w-8',
    xl: 'h-12 w-12'
  }
  return sizes[props.size]
})

const colorClass = computed(() => {
  const colors = {
    current: 'text-current',
    white: 'text-white',
    indigo: 'text-indigo-600',
    blue: 'text-blue-600',
    green: 'text-green-600',
    red: 'text-red-600',
    gray: 'text-gray-600'
  }
  return colors[props.color]
})
</script>
