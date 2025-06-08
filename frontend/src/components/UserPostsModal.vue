<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>

      <!-- Modal panel -->
      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
          <div class="sm:flex sm:items-start">
            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
              <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
              <h3 class="text-lg leading-6 font-medium text-gray-900">
                Posts by {{ user?.Username }}
              </h3>
              <p class="text-sm text-gray-500 mt-1">
                Manage and moderate user posts
              </p>
              
              <div class="mt-4">
                <!-- Loading State -->                <div v-if="loading" class="text-center py-8">
                  <LoadingSpinner size="large" color="blue" class="mx-auto mb-2" aria-label="Loading posts" />
                  <p class="text-gray-500">Loading posts...</p>
                </div>

                <!-- No Posts -->
                <div v-else-if="!posts || posts.length === 0" class="text-center py-8">
                  <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  <h3 class="mt-2 text-sm font-medium text-gray-900">No posts</h3>
                  <p class="mt-1 text-sm text-gray-500">This user hasn't created any posts yet.</p>
                </div>

                <!-- Posts List -->
                <div v-else class="space-y-4 max-h-96 overflow-y-auto">
                  <div v-for="post in posts" :key="post.PostID" 
                       class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                    <div class="flex justify-between items-start">
                      <div class="flex-1">
                        <h4 class="text-lg font-medium text-gray-900">{{ post.Topic }}</h4>
                        <p class="text-sm text-gray-600 mt-1">{{ truncateContent(post.Content) }}</p>
                        <div class="flex items-center mt-2 text-sm text-gray-500 space-x-4">
                          <span>{{ formatDate(post.created_at) }}</span>
                          <span :class="[
                            'px-2 py-1 rounded text-xs',
                            post.Status === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                          ]">
                            {{ post.Status }}
                          </span>
                          <span v-if="post.tags && post.tags.length > 0" class="text-xs">
                            Tags: {{ post.tags.map(tag => tag.TagName).join(', ') }}
                          </span>
                        </div>
                      </div>
                      <div class="flex flex-col space-y-2 ml-4">
                        <button 
                          @click="viewPost(post)"
                          class="text-blue-600 hover:text-blue-900 text-sm"
                        >
                          View
                        </button>
                        <button 
                          @click="deletePost(post)"
                          class="text-red-600 hover:text-red-900 text-sm"
                        >
                          Delete
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Error Display -->
                <div v-if="error" class="mt-4 bg-red-50 border border-red-200 rounded-md p-3">
                  <p class="text-sm text-red-800">{{ error }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Modal Actions -->
        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
          <button 
            @click="$emit('close')"
            class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:w-auto sm:text-sm"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import LoadingSpinner from '@/components/LoadingSpinner.vue'

const props = defineProps({
  user: {
    type: Object,
    required: true
  },
  posts: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close', 'delete-post'])

const router = useRouter()
const error = ref(null)

const viewPost = (post) => {
  // Navigate to the post detail page
  router.push(`/posts/${post.PostID}`)
  emit('close')
}

const deletePost = async (post) => {
  if (confirm(`Are you sure you want to delete the post "${post.Topic}"?`)) {
    try {
      emit('delete-post', post.PostID)
    } catch (err) {
      error.value = err.message || 'Failed to delete post'
    }
  }
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString()
}

const truncateContent = (content) => {
  if (!content) return ''
  return content.length > 200 ? content.substring(0, 200) + '...' : content
}
</script>
