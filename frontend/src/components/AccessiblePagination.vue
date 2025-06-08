<template>
  <nav 
    v-if="totalPages > 1"
    class="flex justify-center"
    :aria-label="ariaLabel"
    role="navigation"
  >
    <div class="flex items-center space-x-1 sm:space-x-2">
      <!-- Previous Button -->
      <AccessibleButton
        @click="goToPage(currentPage - 1)"
        :disabled="currentPage === 1 || disabled"
        variant="secondary"
        size="small"
        class="flex items-center px-2 sm:px-3 py-2"
        :aria-label="`Go to previous page, page ${currentPage - 1}`"
      >
        <svg 
          class="h-4 w-4 sm:mr-1" 
          fill="none" 
          viewBox="0 0 24 24" 
          stroke="currentColor"
          aria-hidden="true"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        <span class="hidden sm:inline">Previous</span>
        <span class="sr-only">Previous page</span>
      </AccessibleButton>

      <!-- Page Numbers (Desktop) -->
      <div class="hidden sm:flex items-center space-x-1" role="group" aria-label="Page numbers">
        <template v-for="page in visiblePages" :key="`page-${page}`">
          <span 
            v-if="page === 'ellipsis-start' || page === 'ellipsis-end'"
            class="px-3 py-2 text-gray-500"
            aria-hidden="true"
          >
            ...
          </span>
          <AccessibleButton
            v-else
            @click="goToPage(page)"
            :variant="page === currentPage ? 'primary' : 'secondary'"
            size="small"
            :class="[
              'px-3 py-2 min-w-[40px]',
              page === currentPage 
                ? 'bg-indigo-600 text-white border-indigo-600' 
                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
            ]"
            :aria-label="page === currentPage ? `Current page, page ${page}` : `Go to page ${page}`"
            :aria-current="page === currentPage ? 'page' : false"
            :disabled="disabled"
          >
            {{ page }}
          </AccessibleButton>
        </template>
      </div>

      <!-- Page Indicator (Mobile) -->
      <div class="sm:hidden flex items-center px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md">
        <span aria-live="polite" aria-label="Current pagination status">
          {{ currentPage }} / {{ totalPages }}
        </span>
      </div>

      <!-- Next Button -->
      <AccessibleButton
        @click="goToPage(currentPage + 1)"
        :disabled="currentPage === totalPages || disabled"
        variant="secondary"
        size="small"
        class="flex items-center px-2 sm:px-3 py-2"
        :aria-label="`Go to next page, page ${currentPage + 1}`"
      >
        <span class="hidden sm:inline">Next</span>
        <span class="sr-only">Next page</span>
        <svg 
          class="h-4 w-4 sm:ml-1" 
          fill="none" 
          viewBox="0 0 24 24" 
          stroke="currentColor"
          aria-hidden="true"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </AccessibleButton>
    </div>

    <!-- Additional Navigation Options -->
    <div v-if="showJumpTo && totalPages > 10" class="ml-4 flex items-center space-x-2">
      <label for="page-jump" class="text-sm text-gray-600 whitespace-nowrap">
        Go to:
      </label>
      <AccessibleInput
        id="page-jump"
        v-model="jumpToValue"
        type="number"
        :min="1"
        :max="totalPages"
        hide-label
        class="w-16 text-center"
        :aria-label="`Jump to page, enter a page number between 1 and ${totalPages}`"
        @keydown.enter="handleJumpTo"
        :disabled="disabled"
      />
      <AccessibleButton
        @click="handleJumpTo"
        variant="secondary"
        size="small"
        :disabled="!isValidJumpValue || disabled"
        aria-label="Jump to specified page"
      >
        Go
      </AccessibleButton>
    </div>

    <!-- Screen Reader Summary -->
    <div class="sr-only" aria-live="polite" aria-atomic="true">
      {{ screenReaderSummary }}
    </div>
  </nav>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import AccessibleButton from './AccessibleButton.vue'
import AccessibleInput from './AccessibleInput.vue'

const props = defineProps({
  // Required props
  currentPage: {
    type: Number,
    required: true,
    validator: (value) => value >= 1
  },
  totalPages: {
    type: Number,
    required: true,
    validator: (value) => value >= 1
  },
  totalItems: {
    type: Number,
    default: null
  },
  itemsPerPage: {
    type: Number,
    default: null
  },

  // Configuration props
  maxVisiblePages: {
    type: Number,
    default: 5,
    validator: (value) => value >= 3 && value % 2 === 1 // Must be odd and >= 3
  },
  showJumpTo: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  },
  ariaLabel: {
    type: String,
    default: 'Pagination navigation'
  }
})

const emit = defineEmits(['page-change'])

// Local state
const jumpToValue = ref('')

// Computed properties
const visiblePages = computed(() => {
  const pages = []
  const { currentPage, totalPages, maxVisiblePages } = props
  
  if (totalPages <= maxVisiblePages) {
    // Show all pages if total is less than max visible
    for (let i = 1; i <= totalPages; i++) {
      pages.push(i)
    }
  } else {
    // Calculate range around current page
    const halfVisible = Math.floor(maxVisiblePages / 2)
    let startPage = Math.max(1, currentPage - halfVisible)
    let endPage = Math.min(totalPages, currentPage + halfVisible)
    
    // Adjust range if we're near the start or end
    if (currentPage <= halfVisible) {
      endPage = maxVisiblePages
    } else if (currentPage > totalPages - halfVisible) {
      startPage = totalPages - maxVisiblePages + 1
    }
    
    // Add first page and ellipsis if needed
    if (startPage > 1) {
      pages.push(1)
      if (startPage > 2) {
        pages.push('ellipsis-start')
      }
    }
    
    // Add page numbers
    for (let i = startPage; i <= endPage; i++) {
      pages.push(i)
    }
    
    // Add ellipsis and last page if needed
    if (endPage < totalPages) {
      if (endPage < totalPages - 1) {
        pages.push('ellipsis-end')
      }
      pages.push(totalPages)
    }
  }
  
  return pages
})

const isValidJumpValue = computed(() => {
  const value = parseInt(jumpToValue.value)
  return !isNaN(value) && value >= 1 && value <= props.totalPages && value !== props.currentPage
})

const screenReaderSummary = computed(() => {
  let summary = `Page ${props.currentPage} of ${props.totalPages}`
  
  if (props.totalItems && props.itemsPerPage) {
    const startItem = (props.currentPage - 1) * props.itemsPerPage + 1
    const endItem = Math.min(props.currentPage * props.itemsPerPage, props.totalItems)
    summary += `, showing items ${startItem} to ${endItem} of ${props.totalItems} total`
  }
  
  return summary
})

// Methods
const goToPage = (page) => {
  if (page >= 1 && page <= props.totalPages && page !== props.currentPage && !props.disabled) {
    emit('page-change', page)
  }
}

const handleJumpTo = () => {
  if (isValidJumpValue.value) {
    goToPage(parseInt(jumpToValue.value))
    jumpToValue.value = ''
  }
}

// Keyboard navigation
const handleKeydown = (event) => {
  if (props.disabled) return
  
  switch (event.key) {
    case 'ArrowLeft':
      if (props.currentPage > 1) {
        event.preventDefault()
        goToPage(props.currentPage - 1)
      }
      break
    case 'ArrowRight':
      if (props.currentPage < props.totalPages) {
        event.preventDefault()
        goToPage(props.currentPage + 1)
      }
      break
    case 'Home':
      if (props.currentPage !== 1) {
        event.preventDefault()
        goToPage(1)
      }
      break
    case 'End':
      if (props.currentPage !== props.totalPages) {
        event.preventDefault()
        goToPage(props.totalPages)
      }
      break
  }
}

// Watch for external page changes
watch(() => props.currentPage, () => {
  jumpToValue.value = ''
})
</script>

<style scoped>
/* Ensure focus indicators are visible */
.focus\:z-10:focus {
  z-index: 10;
}
</style>
