<template>
  <button
    @click="handleRefresh"
    :disabled="isLoading"
    :class="[
      'inline-flex items-center justify-center transition-all duration-200 font-medium',
      'focus:outline-none focus:ring-2 focus:ring-offset-2',
      size === 'small' ? 'px-3 py-2 text-sm rounded-md' : 'px-4 py-2 text-base rounded-lg',
      variant === 'primary' 
        ? 'bg-indigo-600 hover:bg-indigo-700 text-white focus:ring-indigo-500 disabled:bg-indigo-400' 
        : 'bg-gray-100 hover:bg-gray-200 text-gray-700 focus:ring-gray-500 disabled:bg-gray-50 disabled:text-gray-400',
      isLoading ? 'cursor-not-allowed' : 'cursor-pointer',
      'w-full sm:w-auto' // Full width on mobile, auto on desktop
    ]"
    :title="isLoading ? 'Refreshing...' : 'Refresh content'"
  >    <!-- Loading spinner -->
    <LoadingSpinner 
      v-if="isLoading"
      :size="size === 'small' ? 'small' : 'medium'"
      color="current"
      :aria-hidden="true"
      class="mr-2"
    />
    
    <!-- Refresh icon -->
    <svg 
      v-else
      :class="[
        'mr-2',
        size === 'small' ? 'h-4 w-4' : 'h-5 w-5'
      ]"
      fill="none" 
      viewBox="0 0 24 24" 
      stroke="currentColor"
    >
      <path 
        stroke-linecap="round" 
        stroke-linejoin="round" 
        stroke-width="2" 
        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" 
      />
    </svg>

    <!-- Button text - responsive -->
    <span class="hidden sm:inline">
      {{ isLoading ? 'Refreshing...' : 'Refresh' }}
    </span>
    <span class="sm:hidden">
      {{ isLoading ? 'Refreshing...' : (showMobileText ? 'Refresh' : '') }}
    </span>
  </button>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'

const props = defineProps({
  isLoading: {
    type: Boolean,
    default: false
  },
  variant: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'secondary'].includes(value)
  },
  size: {
    type: String,
    default: 'medium',
    validator: (value) => ['small', 'medium'].includes(value)
  },
  showMobileText: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['refresh'])

const handleRefresh = () => {
  if (!props.isLoading) {
    emit('refresh')
  }
}
</script>
