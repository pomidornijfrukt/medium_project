<template>
  <div class="w-full">
    <label 
      v-if="label"
      :for="textareaId"
      class="block text-sm font-medium text-gray-700 mb-2"
      :class="{ 'sr-only': hideLabel }"
    >
      {{ label }}
      <span v-if="required" class="text-red-500 ml-1" aria-label="required">*</span>
    </label>
    
    <div class="relative">
      <textarea
        :id="textareaId"
        ref="textareaElement"
        v-model="modelValue"
        :required="required"
        :disabled="disabled"
        :readonly="readonly"
        :minlength="minlength"
        :maxlength="maxlength"
        :rows="rows"
        :cols="cols"
        :placeholder="placeholder"
        :aria-describedby="describedBy"
        :aria-invalid="hasError"
        :aria-required="required"
        :class="textareaClasses"
        @input="handleInput"
        @blur="handleBlur"
        @focus="handleFocus"
        @keydown="handleKeydown"
      />
      
      <!-- Character count -->
      <div 
        v-if="showCharCount && maxlength"
        class="absolute bottom-2 right-2 text-xs text-gray-500 bg-white px-1 rounded"
        :aria-live="characterCountAriaLive"
      >
        {{ characterCount }}/{{ maxlength }}
      </div>
    </div>
    
    <!-- Help text -->
    <div 
      v-if="helpText && !hasError"
      :id="`${textareaId}-help`"
      class="mt-1 text-xs text-gray-500"
    >
      {{ helpText }}
    </div>
    
    <!-- Error message -->
    <div 
      v-if="hasError && errorMessage"
      :id="`${textareaId}-error`"
      class="mt-1 text-xs text-red-600"
      role="alert"
      aria-live="polite"
    >
      {{ errorMessage }}
    </div>
    
    <!-- Character count warning for screen readers -->
    <div 
      v-if="showCharCount && isNearLimit"
      class="sr-only"
      aria-live="polite"
      aria-atomic="true"
    >
      {{ characterCountWarning }}
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
    type: String,
    default: ''
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
  rows: {
    type: [String, Number],
    default: 4
  },
  cols: {
    type: [String, Number],
    default: undefined
  },
  resize: {
    type: String,
    default: 'vertical',
    validator: (value) => ['none', 'both', 'horizontal', 'vertical'].includes(value)
  },
  showCharCount: {
    type: Boolean,
    default: false
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

const { announce } = useAccessibility()

// Refs
const textareaElement = ref(null)
const announcement = ref('')

// Generate unique ID for textarea
const textareaId = computed(() => `textarea-${Math.random().toString(36).substr(2, 9)}`)

// Check if textarea has error
const hasError = computed(() => Boolean(props.errorMessage))

// Character count management
const characterCount = computed(() => props.modelValue.length)

const isNearLimit = computed(() => {
  if (!props.maxlength) return false
  const remaining = props.maxlength - characterCount.value
  return remaining <= Math.min(20, props.maxlength * 0.1)
})

const characterCountAriaLive = computed(() => {
  return isNearLimit.value ? 'polite' : 'off'
})

const characterCountWarning = computed(() => {
  if (!props.maxlength) return ''
  const remaining = props.maxlength - characterCount.value
  if (remaining <= 0) {
    return 'Character limit reached'
  } else if (isNearLimit.value) {
    return `${remaining} characters remaining`
  }
  return ''
})

// Compute described by IDs
const describedBy = computed(() => {
  const ids = []
  if (props.helpText && !hasError.value) {
    ids.push(`${textareaId.value}-help`)
  }
  if (hasError.value) {
    ids.push(`${textareaId.value}-error`)
  }
  return ids.length > 0 ? ids.join(' ') : undefined
})

// Compute textarea classes
const textareaClasses = computed(() => {
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

  // Resize classes
  const resizeClasses = {
    none: 'resize-none',
    both: 'resize',
    horizontal: 'resize-x',
    vertical: 'resize-y'
  }

  return [
    ...baseClasses,
    sizeClasses[props.size],
    variantClasses[hasError.value ? 'error' : props.variant],
    resizeClasses[props.resize]
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
  validateTextarea()
}

const handleKeydown = (event) => {
  emit('keydown', event)
  
  // Handle keyboard shortcuts for accessibility
  if (event.ctrlKey || event.metaKey) {
    switch (event.key) {
      case 'a':
        // Allow Ctrl+A for select all
        break
      case 'c':
        // Allow Ctrl+C for copy
        break
      case 'v':
        // Allow Ctrl+V for paste
        break
      case 'x':
        // Allow Ctrl+X for cut
        break
      case 'z':
        // Allow Ctrl+Z for undo
        break
      case 'y':
        // Allow Ctrl+Y for redo
        break
    }
  }
}

// Textarea validation
const validateTextarea = () => {
  if (!textareaElement.value) return

  const element = textareaElement.value
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
    textareaElement.value?.focus()
  })
}

const blur = () => {
  textareaElement.value?.blur()
}

const select = () => {
  textareaElement.value?.select()
}

// Auto-resize functionality
const autoResize = () => {
  if (!textareaElement.value) return
  
  const element = textareaElement.value
  element.style.height = 'auto'
  element.style.height = `${element.scrollHeight}px`
}

// Watch for error changes to announce them
watch(() => props.errorMessage, (newError, oldError) => {
  if (newError && newError !== oldError) {
    announceValidationError()
  }
})

// Watch for model value changes to auto-resize if needed
watch(() => props.modelValue, () => {
  if (props.resize === 'vertical' || props.resize === 'both') {
    nextTick(autoResize)
  }
})

// Expose methods for parent components
defineExpose({
  focus,
  blur,
  select,
  autoResize,
  element: textareaElement
})
</script>
