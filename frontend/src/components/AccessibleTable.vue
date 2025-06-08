<template>
  <div class="overflow-hidden">
    <!-- Table Caption -->
    <div v-if="caption" class="sr-only">{{ caption }}</div>
    
    <!-- Table Controls -->
    <div v-if="showControls" class="mb-4 flex flex-wrap items-center justify-between gap-4">
      <!-- Search -->
      <div v-if="searchable" class="flex-1 min-w-64">
        <AccessibleInput
          v-model="searchQuery"
          type="search"
          label="Search table"
          hide-label
          :placeholder="`Search ${entityName || 'items'}...`"
          :aria-label="`Search ${entityName || 'items'} in table`"
          @input="handleSearch"
        />
      </div>
      
      <!-- Sort Controls -->
      <div v-if="sortable" class="flex items-center space-x-2">
        <label for="sort-select" class="text-sm font-medium text-gray-700">Sort by:</label>
        <select
          id="sort-select"
          v-model="currentSort"
          @change="handleSort"
          class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
          :aria-label="`Sort ${entityName || 'table'} by column`"
        >
          <option v-for="column in sortableColumns" :key="column.key" :value="column.key">
            {{ column.label }}
          </option>
        </select>
        <AccessibleButton
          variant="ghost"
          size="small"
          @click="toggleSortDirection"
          :aria-label="`Sort ${currentSortDirection === 'asc' ? 'descending' : 'ascending'}`"
          :aria-pressed="currentSortDirection === 'desc'"
        >
          <svg 
            class="w-4 h-4"
            :class="{ 'rotate-180': currentSortDirection === 'desc' }"
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
            aria-hidden="true"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
          </svg>
        </AccessibleButton>
      </div>
      
      <!-- Results Info -->
      <div class="text-sm text-gray-600" aria-live="polite" aria-atomic="true">
        {{ filteredData.length }} of {{ data.length }} {{ entityName || 'items' }}
      </div>
    </div>

    <!-- Table Container with Responsive Scroll -->
    <div class="overflow-x-auto border border-gray-200 rounded-lg">
      <table 
        class="min-w-full divide-y divide-gray-200"
        :aria-label="ariaLabel || `${entityName || 'Data'} table`"
        :aria-describedby="tableDescriptionId"
        role="table"
      >
        <!-- Table Description for Screen Readers -->
        <caption v-if="description" :id="tableDescriptionId" class="sr-only">
          {{ description }}
        </caption>
        
        <!-- Table Header -->
        <thead class="bg-gray-50" role="rowgroup">
          <tr role="row">
            <!-- Selection Column -->
            <th 
              v-if="selectable"
              scope="col"
              class="relative px-6 py-3 text-left"
              role="columnheader"
            >
              <input
                type="checkbox"
                :checked="allSelected"
                :indeterminate="someSelected"
                @change="toggleSelectAll"
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                :aria-label="allSelected ? 'Deselect all items' : 'Select all items'"
              />
            </th>
            
            <!-- Data Columns -->
            <th
              v-for="column in columns"
              :key="column.key"
              scope="col"
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              role="columnheader"
              :aria-sort="getSortAriaValue(column.key)"
            >
              <button
                v-if="column.sortable && sortable"
                @click="setSortColumn(column.key)"
                class="group inline-flex items-center space-x-1 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 rounded"
                :aria-label="`Sort by ${column.label}`"
              >
                <span>{{ column.label }}</span>
                <svg 
                  class="w-4 h-4 opacity-0 group-hover:opacity-100"
                  :class="{
                    'opacity-100': currentSort === column.key,
                    'rotate-180': currentSort === column.key && currentSortDirection === 'desc'
                  }"
                  fill="none" 
                  stroke="currentColor" 
                  viewBox="0 0 24 24"
                  aria-hidden="true"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                </svg>
              </button>
              <span v-else>{{ column.label }}</span>
            </th>
            
            <!-- Actions Column -->
            <th 
              v-if="actions && actions.length > 0"
              scope="col"
              class="relative px-6 py-3"
              role="columnheader"
            >
              <span class="sr-only">Actions</span>
            </th>
          </tr>
        </thead>
        
        <!-- Table Body -->
        <tbody class="bg-white divide-y divide-gray-200" role="rowgroup">
          <!-- Loading State -->
          <tr v-if="loading" role="row">
            <td :colspan="totalColumns" class="px-6 py-12 text-center" role="gridcell">
              <div class="flex items-center justify-center">
                <LoadingSpinner size="large" color="indigo" class="mr-3" :aria-hidden="true" />
                <span class="text-gray-600">{{ loadingText || 'Loading...' }}</span>
              </div>
            </td>
          </tr>
          
          <!-- Empty State -->
          <tr v-else-if="filteredData.length === 0" role="row">
            <td :colspan="totalColumns" class="px-6 py-12 text-center text-gray-500" role="gridcell">
              <div class="text-gray-400 mb-2">
                <slot name="empty-icon">
                  <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </slot>
              </div>
              <p class="text-lg font-medium text-gray-900 mb-1">
                {{ emptyTitle || `No ${entityName || 'items'} found` }}
              </p>
              <p class="text-gray-600">
                {{ emptyMessage || `There are no ${entityName || 'items'} to display.` }}
              </p>
            </td>
          </tr>
          
          <!-- Data Rows -->
          <tr 
            v-else
            v-for="(item, index) in filteredData" 
            :key="getRowKey(item, index)"
            class="hover:bg-gray-50 transition-colors duration-150"
            role="row"
            :aria-rowindex="index + 1"
          >
            <!-- Selection Cell -->
            <td v-if="selectable" class="relative px-6 py-4" role="gridcell">
              <input
                type="checkbox"
                :checked="isSelected(item)"
                @change="toggleSelection(item)"
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                :aria-label="`Select ${getRowLabel(item)}`"
              />
            </td>
            
            <!-- Data Cells -->
            <td
              v-for="column in columns"
              :key="column.key"
              class="px-6 py-4 whitespace-nowrap"
              role="gridcell"
              :class="column.cellClass"
            >
              <!-- Custom Column Slot -->
              <slot 
                v-if="$slots[`column-${column.key}`]"
                :name="`column-${column.key}`" 
                :item="item" 
                :value="getCellValue(item, column.key)"
                :index="index"
              />
              
              <!-- Default Cell Content -->
              <span v-else :class="getColumnClasses(column)">
                {{ formatCellValue(getCellValue(item, column.key), column) }}
              </span>
            </td>
            
            <!-- Actions Cell -->
            <td 
              v-if="actions && actions.length > 0"
              class="relative px-6 py-4 text-right"
              role="gridcell"
            >
              <div class="flex items-center justify-end space-x-2">
                <AccessibleButton
                  v-for="action in actions"
                  :key="action.key"
                  :variant="action.variant || 'ghost'"
                  :size="action.size || 'small'"
                  @click="handleAction(action, item, index)"
                  :disabled="action.disabled && action.disabled(item)"
                  :aria-label="`${action.label} ${getRowLabel(item)}`"
                >
                  <component v-if="action.icon" :is="action.icon" class="w-4 h-4" aria-hidden="true" />
                  <span v-if="action.showLabel">{{ action.label }}</span>
                </AccessibleButton>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <!-- Pagination -->
    <div v-if="pagination && filteredData.length > 0" class="mt-4">
      <slot name="pagination" :data="filteredData" :total="data.length" />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useAccessibility } from '@/composables/useAccessibility'
import AccessibleInput from './AccessibleInput.vue'
import AccessibleButton from './AccessibleButton.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'

const props = defineProps({
  data: {
    type: Array,
    required: true
  },
  columns: {
    type: Array,
    required: true
  },
  caption: {
    type: String,
    default: ''
  },
  description: {
    type: String,
    default: ''
  },
  ariaLabel: {
    type: String,
    default: ''
  },
  entityName: {
    type: String,
    default: 'items'
  },
  loading: {
    type: Boolean,
    default: false
  },
  loadingText: {
    type: String,
    default: 'Loading...'
  },
  emptyTitle: {
    type: String,
    default: ''
  },
  emptyMessage: {
    type: String,
    default: ''
  },
  searchable: {
    type: Boolean,
    default: true
  },
  sortable: {
    type: Boolean,
    default: true
  },
  selectable: {
    type: Boolean,
    default: false
  },
  pagination: {
    type: Boolean,
    default: false
  },
  actions: {
    type: Array,
    default: () => []
  },
  rowKey: {
    type: String,
    default: 'id'
  },
  showControls: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['selection-change', 'action', 'sort', 'search'])

const { announce } = useAccessibility()

// Reactive state
const searchQuery = ref('')
const currentSort = ref('')
const currentSortDirection = ref('asc')
const selectedItems = ref(new Set())

// Generate unique ID for table description
const tableDescriptionId = computed(() => `table-desc-${Math.random().toString(36).substr(2, 9)}`)

// Computed properties
const sortableColumns = computed(() => 
  props.columns.filter(col => col.sortable !== false)
)

const totalColumns = computed(() => {
  let count = props.columns.length
  if (props.selectable) count++
  if (props.actions && props.actions.length > 0) count++
  return count
})

const allSelected = computed(() => 
  props.data.length > 0 && selectedItems.value.size === props.data.length
)

const someSelected = computed(() => 
  selectedItems.value.size > 0 && selectedItems.value.size < props.data.length
)

const filteredData = computed(() => {
  let result = [...props.data]
  
  // Apply search filter
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase().trim()
    result = result.filter(item => 
      props.columns.some(column => {
        const value = getCellValue(item, column.key)
        return String(value).toLowerCase().includes(query)
      })
    )
  }
  
  // Apply sorting
  if (currentSort.value) {
    result.sort((a, b) => {
      const aValue = getCellValue(a, currentSort.value)
      const bValue = getCellValue(b, currentSort.value)
      
      let comparison = 0
      if (aValue < bValue) comparison = -1
      if (aValue > bValue) comparison = 1
      
      return currentSortDirection.value === 'desc' ? -comparison : comparison
    })
  }
  
  return result
})

// Helper methods
const getCellValue = (item, key) => {
  return key.split('.').reduce((obj, k) => obj?.[k], item) ?? ''
}

const formatCellValue = (value, column) => {
  if (column.format && typeof column.format === 'function') {
    return column.format(value)
  }
  return value
}

const getColumnClasses = (column) => {
  const baseClasses = ['text-sm', 'text-gray-900']
  if (column.textClass) {
    baseClasses.push(column.textClass)
  }
  return baseClasses.join(' ')
}

const getRowKey = (item, index) => {
  return item[props.rowKey] || index
}

const getRowLabel = (item) => {
  // Try to get a meaningful label for the row
  const labelColumn = props.columns.find(col => col.key === 'name' || col.key === 'title' || col.key === 'label')
  if (labelColumn) {
    return getCellValue(item, labelColumn.key)
  }
  return `item ${getRowKey(item)}`
}

const getSortAriaValue = (columnKey) => {
  if (currentSort.value !== columnKey) return 'none'
  return currentSortDirection.value === 'asc' ? 'ascending' : 'descending'
}

// Event handlers
const handleSearch = () => {
  emit('search', searchQuery.value)
  const count = filteredData.value.length
  announce(`Search completed. ${count} ${props.entityName || 'items'} found.`)
}

const handleSort = () => {
  emit('sort', { column: currentSort.value, direction: currentSortDirection.value })
  const column = props.columns.find(col => col.key === currentSort.value)
  announce(`Table sorted by ${column?.label} ${currentSortDirection.value === 'asc' ? 'ascending' : 'descending'}`)
}

const setSortColumn = (columnKey) => {
  if (currentSort.value === columnKey) {
    toggleSortDirection()
  } else {
    currentSort.value = columnKey
    currentSortDirection.value = 'asc'
  }
  handleSort()
}

const toggleSortDirection = () => {
  currentSortDirection.value = currentSortDirection.value === 'asc' ? 'desc' : 'asc'
  handleSort()
}

const isSelected = (item) => {
  return selectedItems.value.has(getRowKey(item))
}

const toggleSelection = (item) => {
  const key = getRowKey(item)
  if (selectedItems.value.has(key)) {
    selectedItems.value.delete(key)
  } else {
    selectedItems.value.add(key)
  }
  emit('selection-change', Array.from(selectedItems.value))
  
  const action = selectedItems.value.has(key) ? 'selected' : 'deselected'
  announce(`${getRowLabel(item)} ${action}`)
}

const toggleSelectAll = () => {
  if (allSelected.value) {
    selectedItems.value.clear()
    announce('All items deselected')
  } else {
    props.data.forEach(item => {
      selectedItems.value.add(getRowKey(item))
    })
    announce('All items selected')
  }
  emit('selection-change', Array.from(selectedItems.value))
}

const handleAction = (action, item, index) => {
  emit('action', { action: action.key, item, index })
  announce(`${action.label} action triggered for ${getRowLabel(item)}`)
}

// Watch for data changes to clear invalid selections
watch(() => props.data, () => {
  const validKeys = new Set(props.data.map(item => getRowKey(item)))
  const currentKeys = Array.from(selectedItems.value)
  currentKeys.forEach(key => {
    if (!validKeys.has(key)) {
      selectedItems.value.delete(key)
    }
  })
})
</script>
