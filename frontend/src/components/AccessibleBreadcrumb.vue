<template>
  <nav 
    v-if="breadcrumbs.length > 1"
    :aria-label="ariaLabel"
    class="mb-6"
  >
    <ol 
      class="flex items-center space-x-2 text-sm"
      role="list"
    >
      <li 
        v-for="(breadcrumb, index) in breadcrumbs"
        :key="breadcrumb.path || index"
        class="flex items-center"
        :class="{ 'text-gray-500': index < breadcrumbs.length - 1, 'text-gray-900 font-medium': index === breadcrumbs.length - 1 }"
      >
        <!-- Home icon for first item if it's home -->
        <svg 
          v-if="index === 0 && breadcrumb.isHome"
          class="w-4 h-4 mr-1"
          fill="currentColor"
          viewBox="0 0 20 20"
          aria-hidden="true"
        >
          <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
        </svg>
        
        <!-- Breadcrumb link or text -->
        <router-link
          v-if="index < breadcrumbs.length - 1 && breadcrumb.path"
          :to="breadcrumb.path"
          class="hover:text-indigo-600 transition-colors duration-200"
          :aria-label="`Go to ${breadcrumb.label}`"
        >
          {{ breadcrumb.label }}
        </router-link>
        
        <span
          v-else
          :aria-current="index === breadcrumbs.length - 1 ? 'page' : undefined"
        >
          {{ breadcrumb.label }}
        </span>
        
        <!-- Separator -->
        <svg 
          v-if="index < breadcrumbs.length - 1"
          class="w-4 h-4 mx-2 text-gray-400"
          fill="currentColor"
          viewBox="0 0 20 20"
          aria-hidden="true"
        >
          <path 
            fill-rule="evenodd" 
            d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" 
            clip-rule="evenodd"
          />
        </svg>
      </li>
    </ol>
  </nav>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const props = defineProps({
  breadcrumbs: {
    type: Array,
    default: () => []
  },
  ariaLabel: {
    type: String,
    default: 'Breadcrumb navigation'
  },
  autoGenerate: {
    type: Boolean,
    default: false
  }
})

const route = useRoute()

// Auto-generate breadcrumbs from route if enabled
const computedBreadcrumbs = computed(() => {
  if (props.breadcrumbs.length > 0) {
    return props.breadcrumbs
  }

  if (!props.autoGenerate) {
    return []
  }

  // Generate breadcrumbs from current route
  const pathSegments = route.path.split('/').filter(segment => segment)
  const breadcrumbs = [
    { label: 'Home', path: '/', isHome: true }
  ]

  let currentPath = ''
  pathSegments.forEach((segment, index) => {
    currentPath += `/${segment}`
    
    // Convert segment to readable label
    let label = segment.charAt(0).toUpperCase() + segment.slice(1)
    label = label.replace(/-/g, ' ').replace(/_/g, ' ')
    
    // Don't add path for the last segment (current page)
    const isLast = index === pathSegments.length - 1
    
    breadcrumbs.push({
      label,
      path: isLast ? undefined : currentPath,
      isHome: false
    })
  })

  return breadcrumbs
})

// Use computed breadcrumbs
const breadcrumbs = computed(() => computedBreadcrumbs.value)
</script>
