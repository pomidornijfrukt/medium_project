<template>
  <div 
    :id="`reply-${reply.PostID}`"
    class="reply-container"
    :class="{ 'ring-2 ring-indigo-500 ring-opacity-50 rounded-lg': highlightedReplyId === reply.PostID }"
  >
    <!-- Reply Article -->
    <article 
      class="overflow-hidden border border-gray-200 rounded-lg transition-all duration-200"
      :class="[
        depth === 1 ? 'bg-white' : 
        depth === 2 ? 'bg-gray-50' : 
        depth === 3 ? 'bg-gray-100 border-gray-300' : 
        'bg-gray-50 border-gray-300 shadow-sm'
      ]"
    >
      <!-- Reply Header -->
      <header 
        class="border-b transition-colors duration-200"
        :class="[
          depth === 1 ? 'px-6 py-4 bg-gray-50 border-gray-200' :
          depth === 2 ? 'px-5 py-3 bg-gray-100 border-gray-200' :
          depth === 3 ? 'px-4 py-3 bg-gray-200 border-gray-300' :
          'px-3 py-2 bg-gray-300 border-gray-400'
        ]"
      >
        <div class="flex items-start justify-between">
          <div class="flex-1 min-w-0">
            <component 
              :is="depth <= 2 ? 'h4' : 'h6'" 
              :class="[
                'font-semibold text-gray-900 mb-2',
                depth === 1 ? 'text-lg' :
                depth === 2 ? 'text-base' :
                depth === 3 ? 'text-sm' :
                'text-xs'
              ]"
            >
              <router-link 
                :to="getNavigationLink()"
                class="text-indigo-600 hover:text-indigo-800 hover:underline transition-colors duration-200 break-words"
              >
                {{ reply.Topic }}
              </router-link>
            </component>
            <div 
              class="flex items-center flex-wrap gap-x-2 gap-y-1"
              :class="[
                'text-gray-500',
                depth >= 3 ? 'text-xs' : 'text-sm'
              ]"
            >
              <span class="font-medium">{{ reply.author?.Username}}</span>
              <span class="text-gray-300">•</span>
              <time :datetime="reply.created_at">{{ formatDate(reply.created_at) }}</time>
              <template v-if="reply.updated_at !== reply.created_at">
                <span class="text-gray-300">•</span>
                <span class="text-xs">Updated {{ formatDate(reply.updated_at) }}</span>
              </template>
            </div>
          </div>
        </div>
      </header>

      <!-- Reply Content -->
      <div :class="[
        depth === 1 ? 'px-6 py-5' :
        depth === 2 ? 'px-5 py-4' :
        depth === 3 ? 'px-4 py-3' :
        'px-3 py-2'
      ]">
        <p 
          class="text-gray-700 whitespace-pre-wrap leading-relaxed break-words"
          :class="[
            depth >= 3 ? 'text-sm' :
            depth >= 4 ? 'text-xs' :
            'text-base'
          ]"
        >
          {{ reply.Content }}        </p>
      </div>

      <!-- Reply Tags -->
      <div 
        v-if="reply.tags && reply.tags.length > 0" 
        class="border-t transition-colors duration-200"
        :class="[
          depth === 1 ? 'px-6 py-3 bg-gray-50 border-gray-200' :
          depth === 2 ? 'px-5 py-3 bg-gray-100 border-gray-200' :
          depth === 3 ? 'px-4 py-2 bg-gray-200 border-gray-300' :
          'px-3 py-2 bg-gray-300 border-gray-400'
        ]"
      >        <div :class="['flex flex-wrap', depth >= 3 ? 'gap-1' : 'gap-2']">
          <Tag
            v-for="tag in reply.tags" 
            :key="tag.TagName"
            :label="tag.TagName"
            size="small"
            variant="indigo"
            :interactive="false"
          />
        </div>
      </div>

      <!-- Reply Actions -->
      <div 
        class="border-t transition-colors duration-200"
        :class="[
          depth === 1 ? 'px-6 py-3 bg-gray-50 border-gray-200' :
          depth === 2 ? 'px-5 py-3 bg-gray-100 border-gray-200' :
          depth === 3 ? 'px-4 py-2 bg-gray-200 border-gray-300' :
          'px-3 py-2 bg-gray-300 border-gray-400'
        ]"
      >
        <button 
          v-if="authStore.isLoggedIn && nestedReplyForm.parentId !== reply.PostID"
          @click="startNestedReply(reply.PostID)"
          class="font-medium text-indigo-600 hover:text-indigo-800 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-opacity-50 rounded"
          :class="depth >= 3 ? 'text-xs' : 'text-sm'"
        >
          Reply
        </button>
        <button 
          v-else-if="nestedReplyForm.parentId === reply.PostID"
          @click="cancelNestedReply"
          class="font-medium text-gray-600 hover:text-gray-800 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-opacity-50 rounded"
          :class="depth >= 3 ? 'text-xs' : 'text-sm'"
        >
          Cancel Reply
        </button>
        <span 
          v-else 
          :class="[
            'text-gray-500',
            depth >= 3 ? 'text-xs' : 'text-sm'
          ]"
        >
          <RouterLink 
            to="/login" 
            class="text-indigo-600 hover:text-indigo-800 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-opacity-50 rounded"
          >
            Login
          </RouterLink> 
          to reply
        </span>
      </div>
    </article>

    <!-- Reply Form -->
    <Transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0 transform -translate-y-2"
      enter-to-class="opacity-100 transform translate-y-0"
      leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100 transform translate-y-0"
      leave-to-class="opacity-0 transform -translate-y-2"
    >
      <div 
        v-if="nestedReplyForm.parentId === reply.PostID && authStore.isLoggedIn" 
        class="mt-3"
        :class="[
          depth === 1 ? 'ml-8' :
          depth === 2 ? 'ml-6' :
          depth === 3 ? 'ml-4' :
          'ml-2'
        ]"
      >
        <div 
          class="bg-white border rounded-lg shadow-sm"
          :class="[
            depth === 1 ? 'border-gray-200' :
            depth === 2 ? 'border-gray-300' :
            depth === 3 ? 'border-gray-400' :
            'border-gray-500'
          ]"
        >
          <div :class="[
            depth === 1 ? 'p-4' :
            depth === 2 ? 'p-3' :
            depth === 3 ? 'p-3' :
            'p-2'
          ]">
            <component 
              :is="depth >= 3 ? 'h6' : 'h5'"
              class="mb-3 font-semibold text-gray-900"
              :class="[
                depth === 1 ? 'text-base' :
                depth === 2 ? 'text-sm' :
                depth === 3 ? 'text-sm' :
                'text-xs'
              ]"
            >
              Reply to {{ reply.Topic }}
            </component>
            
            <!-- Error Display -->
            <div 
              v-if="nestedReplyForm.error" 
              class="mb-3 text-red-700 border border-red-300 rounded bg-red-50"
              :class="depth >= 3 ? 'px-2 py-1 text-xs' : 'px-3 py-2 text-sm'"
            >
              {{ nestedReplyForm.error }}
            </div>            <form 
              @submit.prevent="handleNestedReplySubmit" 
              :class="[
                'space-y-3',
                depth >= 4 ? 'space-y-2' : 
                depth === 3 ? 'space-y-3' : 
                'space-y-4'
              ]"
            >
              <!-- Title input -->
              <div>
                <label class="sr-only">Reply title</label>
                <input 
                  type="text" 
                  v-model="nestedReplyForm.title"
                  required
                  :disabled="postStore.loading"
                  placeholder="Reply title"
                  class="w-full border border-gray-300 rounded-md shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
                  :class="[
                    depth >= 4 ? 'px-2 py-1 text-xs' :
                    depth === 3 ? 'px-3 py-2 text-sm' :
                    'px-3 py-2 text-base'
                  ]"
                />
              </div>
              
              <!-- Content textarea -->
              <div>
                <label class="sr-only">Reply content</label>
                <textarea 
                  v-model="nestedReplyForm.content"
                  required
                  :disabled="postStore.loading"
                  placeholder="Write your reply..."
                  :rows="depth >= 4 ? 2 : depth === 3 ? 3 : 4"
                  class="w-full border border-gray-300 rounded-md shadow-sm resize-y transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
                  :class="[
                    depth >= 4 ? 'px-2 py-1 text-xs' :
                    depth === 3 ? 'px-3 py-2 text-sm' :
                    'px-3 py-2 text-base'
                  ]"                ></textarea>
              </div>

              <!-- Tags input -->
              <div>
                <label class="sr-only">Reply tags</label>
                <div class="space-y-2">
                  <input
                    type="text"
                    v-model="tagInput"
                    :disabled="postStore.loading"
                    placeholder="Add tags (press Enter to add)"
                    @keydown.enter.prevent="addTag"
                    class="w-full border border-gray-300 rounded-md shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
                    :class="[
                      depth >= 4 ? 'px-2 py-1 text-xs' :
                      depth === 3 ? 'px-3 py-2 text-sm' :
                      'px-3 py-2 text-base'
                    ]"
                  />
                  
                  <!-- Selected Tags -->
                  <div v-if="nestedReplyForm.tags && nestedReplyForm.tags.length > 0" class="flex flex-wrap gap-1">
                    <Tag
                      v-for="(tag, index) in nestedReplyForm.tags"
                      :key="index"
                      :label="tag"
                      size="small"
                      variant="indigo"
                      removable
                      @remove="removeTag(index)"
                    />
                  </div>
                </div>
              </div>

              <!-- Action buttons -->
              <div class="flex items-center justify-between pt-2">
                <button 
                  type="button"
                  @click="cancelNestedReply"
                  class="font-medium text-gray-600 hover:text-gray-800 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-opacity-50 rounded"
                  :class="[
                    depth >= 4 ? 'px-2 py-1 text-xs' :
                    depth === 3 ? 'px-3 py-1 text-xs' :
                    'px-4 py-2 text-sm'
                  ]"
                >
                  Cancel
                </button>
                  <button 
                  type="submit" 
                  :disabled="postStore.loading || !nestedReplyForm.content.trim() || !nestedReplyForm.title.trim()"
                  class="flex items-center font-medium text-white bg-indigo-600 rounded-md transition-all duration-200 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:bg-gray-400 disabled:cursor-not-allowed disabled:hover:bg-gray-400"
                  :class="[
                    depth >= 4 ? 'px-3 py-1 text-xs' :
                    depth === 3 ? 'px-4 py-1 text-xs' :                  'px-6 py-2 text-sm'
                  ]"                >
                  <LoadingSpinner 
                    v-if="postStore.loading" 
                    color="white"
                    :size="depth >= 3 ? 'tiny' : 'small'"
                    :aria-hidden="true"
                    :class="[
                      depth >= 4 ? 'mr-1' :
                      depth === 3 ? 'mr-1' :
                      'mr-2'
                    ]"
                  />
                  {{ postStore.loading ? 'Posting...' : 'Reply' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Nested Replies (Recursive) -->
    <div 
      v-if="reply.nested_replies && reply.nested_replies.length > 0" 
      class="mt-4 space-y-3"
      :class="[
        'relative',
        depth >= 3 ? 'ml-6' : 'ml-8'
      ]"
    >
      <ReplyItem
        v-for="nestedReply in reply.nested_replies"
        :key="nestedReply.PostID"
        :reply="nestedReply"
        :depth="depth + 1"
        :root-post-id="rootPostId"
        :highlighted-reply-id="highlightedReplyId"
        :nested-reply-form="nestedReplyForm"
        :post-store="postStore"
        :auth-store="authStore"
        :format-date="formatDate"
        @start-nested-reply="$emit('start-nested-reply', $event)"
        @cancel-nested-reply="$emit('cancel-nested-reply')"
        @handle-nested-reply-submit="$emit('handle-nested-reply-submit')"
      />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import Tag from '@/components/Tag.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'

// Reactive data for tag input
const tagInput = ref('')

const props = defineProps({
  reply: {
    type: Object,
    required: true
  },
  depth: {
    type: Number,
    default: 1
  },
  rootPostId: {
    type: Number,
    required: true
  },
  highlightedReplyId: {
    type: Number,
    default: null
  },
  nestedReplyForm: {
    type: Object,
    required: true
  },
  postStore: {
    type: Object,
    required: true
  },
  authStore: {
    type: Object,
    required: true
  },
  formatDate: {
    type: Function,
    required: true
  }
})

const emit = defineEmits(['start-nested-reply', 'cancel-nested-reply', 'handle-nested-reply-submit'])

// Methods for handling actions
const startNestedReply = (replyId) => {
  emit('start-nested-reply', replyId)
}

const cancelNestedReply = () => {
  emit('cancel-nested-reply')
}

const handleNestedReplySubmit = () => {
  emit('handle-nested-reply-submit')
}

// Tag management methods
const addTag = () => {
  const tag = tagInput.value.trim()
  if (tag && !props.nestedReplyForm.tags.includes(tag)) {
    props.nestedReplyForm.tags.push(tag)
    tagInput.value = ''
  }
}

const removeTag = (index) => {
  props.nestedReplyForm.tags.splice(index, 1)
}

// Navigation logic
const getNavigationLink = () => {
  // For very deep replies (depth > 3), navigate to root post
  if (props.depth > 3) {
    return `/posts/${props.rootPostId}#reply-${props.reply.PostID}`
  }
  // For shallower replies, navigate to immediate parent
  return `/posts/${props.reply.ParentPostID}#reply-${props.reply.PostID}`
}
</script>

<style scoped>
/* Thread connection lines */
.reply-container {
  position: relative;
}

/* Primary level connection (ml-8) */
.reply-container .ml-8 {
  position: relative;
}

.reply-container .ml-8::before {
  content: '';
  position: absolute;
  left: -20px;
  top: 0;
  bottom: 0;
  width: 2px;
  background: linear-gradient(to bottom, #e5e7eb 0%, #e5e7eb 100%);
  border-radius: 1px;
}

.reply-container .ml-8::after {
  content: '';
  position: absolute;
  left: -20px;
  top: 20px;
  width: 20px;
  height: 2px;
  background-color: #e5e7eb;
  border-radius: 1px;
}

/* Secondary level connection (ml-6) */
.reply-container .ml-6 {
  position: relative;
}

.reply-container .ml-6::before {
  content: '';
  position: absolute;
  left: -15px;
  top: 0;
  bottom: 0;
  width: 1px;
  background: linear-gradient(to bottom, #d1d5db 0%, #d1d5db 100%);
}

.reply-container .ml-6::after {
  content: '';
  position: absolute;
  left: -15px;
  top: 15px;
  width: 15px;
  height: 1px;
  background-color: #d1d5db;
}

/* Tertiary level connection (ml-4) */
.reply-container .ml-4 {
  position: relative;
}

.reply-container .ml-4::before {
  content: '';
  position: absolute;
  left: -12px;
  top: 0;
  bottom: 0;
  width: 1px;
  background: linear-gradient(to bottom, #9ca3af 0%, #9ca3af 100%);
}

.reply-container .ml-4::after {
  content: '';
  position: absolute;
  left: -12px;
  top: 12px;
  width: 12px;
  height: 1px;
  background-color: #9ca3af;
}

/* Deep level connection (ml-2) */
.reply-container .ml-2 {
  position: relative;
}

.reply-container .ml-2::before {
  content: '';
  position: absolute;
  left: -8px;
  top: 0;
  bottom: 0;
  width: 1px;
  background: linear-gradient(to bottom, #6b7280 0%, #6b7280 100%);
}

.reply-container .ml-2::after {
  content: '';
  position: absolute;
  left: -8px;
  top: 8px;
  width: 8px;
  height: 1px;
  background-color: #6b7280;
}

/* Hover effects for better UX */
.reply-container:hover .ml-8::before,
.reply-container:hover .ml-8::after {
  background-color: #9ca3af;
}

.reply-container:hover .ml-6::before,
.reply-container:hover .ml-6::after {
  background-color: #6b7280;
}

.reply-container:hover .ml-4::before,
.reply-container:hover .ml-4::after {
  background-color: #4b5563;
}

.reply-container:hover .ml-2::before,
.reply-container:hover .ml-2::after {
  background-color: #374151;
}

/* Smooth transitions for all elements */
.reply-container * {
  transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 200ms;
}

/* Focus states for accessibility */
.reply-container button:focus,
.reply-container input:focus,
.reply-container textarea:focus,
.reply-container a:focus {
  outline: 2px solid transparent;
  outline-offset: 2px;
}

/* Custom scrollbar for textarea */
.reply-container textarea::-webkit-scrollbar {
  width: 8px;
}

.reply-container textarea::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 4px;
}

.reply-container textarea::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

.reply-container textarea::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
