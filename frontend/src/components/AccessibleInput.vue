<template>
  <div class="w-full">
    <label 
      v-if="label"
      :for="inputId"
      class="block text-sm font-medium text-gray-700 mb-2"
      :class="{ 'sr-only': hideLabel }"
    >
      {{ label }}
      <span v-if="required" class="text-red-500 ml-1" aria-label="required">*</span>
    </label>
    
    <div class="relative">
      <input
        :id="inputId"
        ref="inputElement"
        :value="modelValue"
        :type="type"
        :required="required"
        :disabled="disabled"
        :readonly="readonly"
        :minlength="minlength"
        :maxlength="maxlength"
        :min="min"
        :max="max"
        :step="step"
        :pattern="pattern"
        :placeholder="placeholder"
        :autocomplete="autocomplete"
        :aria-describedby="describedBy"
        :aria-invalid="hasError"
        :aria-required="required"
        :class="inputClasses"
        @input="handleInput"
        @blur="handleBlur"
        @focus="handleFocus"
        @keydown="handleKeydown"
      />
      
      <!-- Icon slot -->
      <div v-if="$slots.icon" class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
        <slot name="icon" />
      </div>
      
      <!-- Action button slot (e.g., show/hide password) -->
      <div v-if="$slots.action" class="absolute inset-y-0 right-0 pr-3 flex items-center">
        <slot name="action" />
      </div>
    </div>
    
    <!-- Help text -->
    <div 
      v-if="helpText && !hasError"
      :id="`${inputId}-help`"
      class="mt-1 text-xs text-gray-500"
    >
      {{ helpText }}
    </div>
    
    <!-- Error message -->
    <div 
      v-if="hasError && errorMessage"
      :id="`${inputId}-error`"
      class="mt-1 text-xs text-red-600"
      role="alert"
      aria-live="polite"
    >
      {{ errorMessage }}
    </div>
    
    <!-- Live region for screen reader announcements -->
    <div
      v-if="announcement"
      class="sr-only"
      aria-live="polite"
      aria-atomic="true"
    >
      {{ announcement }}
    </div>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, watch } from 'vue'
import { useAccessibility } from '@/composables/useAccessibility'

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: ''
  },
  type: {
    type: String,
    default: 'text',
    validator: (value) => [
      'text', 'email', 'password', 'number', 'tel', 'url', 'search',
      'date', 'time', 'datetime-local', 'month', 'week'
    ].includes(value)
  },
  label: {
    type: String,
    default: ''
  },
  hideLabel: {
    type: Boolean,
    default: false
  },
  placeholder: {
    type: String,
    default: ''
  },
  helpText: {
    type: String,
    default: ''
  },
  errorMessage: {
    type: String,
    default: ''
  },
  required: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  },
  readonly: {
    type: Boolean,
    default: false
  },
  minlength: {
    type: [String, Number],
    default: undefined
  },
  maxlength: {
    type: [String, Number],
    default: undefined
  },
  min: {
    type: [String, Number],
    default: undefined
  },
  max: {
    type: [String, Number],
    default: undefined
  },
  step: {
    type: [String, Number],
    default: undefined
  },
  pattern: {
    type: String,
    default: undefined
  },
  autocomplete: {
    type: String,
    default: undefined
  },
  size: {
    type: String,
    default: 'medium',
    validator: (value) => ['small', 'medium', 'large'].includes(value)
  },
  variant: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'error', 'success'].includes(value)
  }
})

const emit = defineEmits(['update:modelValue', 'focus', 'blur', 'input', 'keydown'])

const { announce, validateForm } = useAccessibility()

// Refs
const inputElement = ref(null)
const announcement = ref('')

// Generate unique ID for input
const inputId = computed(() => `input-${Math.random().toString(36).substr(2, 9)}`)

// Check if input has error
const hasError = computed(() => Boolean(props.errorMessage))

// Compute described by IDs
const describedBy = computed(() => {
  const ids = []
  if (props.helpText && !hasError.value) {
    ids.push(`${inputId.value}-help`)
  }
  if (hasError.value) {
    ids.push(`${inputId.value}-error`)
  }
  return ids.length > 0 ? ids.join(' ') : undefined
})

// Compute input classes
const inputClasses = computed(() => {
  const baseClasses = [
    'w-full px-3 py-2 border rounded-md transition-colors duration-200',
    'focus:outline-none focus:ring-2 focus:border-transparent',
    'disabled:bg-gray-100 disabled:cursor-not-allowed',
    'placeholder:text-gray-400'
  ]

  // Size classes
  const sizeClasses = {
    small: 'text-sm py-1.5',
    medium: 'text-base py-2',
    large: 'text-lg py-3'
  }

  // Variant classes
  const variantClasses = {
    default: 'border-gray-300 focus:ring-indigo-500',
    error: 'border-red-300 text-red-900 placeholder:text-red-300 focus:ring-red-500',
    success: 'border-green-300 text-green-900 placeholder:text-green-300 focus:ring-green-500'
  }

  // Icon spacing
  if (props.$slots?.icon) {
    baseClasses.push('pl-10')
  }
  if (props.$slots?.action) {
    baseClasses.push('pr-10')
  }

  return [
    ...baseClasses,
    sizeClasses[props.size],
    variantClasses[hasError.value ? 'error' : props.variant]
  ].join(' ')
})

// Event handlers
const handleInput = (event) => {
  emit('update:modelValue', event.target.value)
  emit('input', event)
}

const handleFocus = (event) => {
  emit('focus', event)
}

const handleBlur = (event) => {
  emit('blur', event)
  validateInput()
}

const handleKeydown = (event) => {
  emit('keydown', event)
}

// Input validation
const validateInput = () => {
  if (!inputElement.value) return

  const element = inputElement.value
  const isValid = element.checkValidity()
  
  if (!isValid) {
    announceValidationError()
  }
}

const announceValidationError = () => {
  if (props.errorMessage) {
    announcement.value = `Error: ${props.errorMessage}`
    // Clear announcement after it's been read
    setTimeout(() => {
      announcement.value = ''
    }, 1000)
  }
}

// Focus management
const focus = () => {
  nextTick(() => {
    inputElement.value?.focus()
  })
}

const blur = () => {
  inputElement.value?.blur()
}

const select = () => {
  inputElement.value?.select()
}

// Watch for error changes to announce them
watch(() => props.errorMessage, (newError, oldError) => {
  if (newError && newError !== oldError) {
    announceValidationError()
  }
})

// Expose methods for parent components
defineExpose({
  focus,
  blur,
  select,
  element: inputElement
})
</script>
