<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <!-- Header -->
        <header class="px-6 py-4 border-b border-gray-200">
          <h1 class="text-2xl font-bold text-gray-900">Create New Post</h1>
        </header>

        <!-- Error Display -->
        <div v-if="postStore.error" class="mx-6 mt-4 bg-red-50 border border-red-400 text-red-700 px-4 py-3 rounded">
          {{ postStore.error }}
        </div>

        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="px-6 py-6 space-y-6">
          <!-- Title -->
          <div>
            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
              Title *
            </label>
            <input 
              type="text" 
              id="title"
              v-model="form.title"
              required
              :disabled="postStore.loading"
              placeholder="Enter post title"
              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
            />
          </div>

          <!-- Content -->
          <div>
            <label for="content" class="block text-sm font-medium text-gray-700 mb-2">
              Content *
            </label>
            <textarea 
              id="content"
              v-model="form.content"
              required
              :disabled="postStore.loading"
              placeholder="Write your post content here..."
              rows="12"
              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-gray-100 disabled:cursor-not-allowed resize-y"
            ></textarea>
          </div>

          <!-- Tags -->
          <div>
            <label for="tags" class="block text-sm font-medium text-gray-700 mb-2">
              Tags
            </label>
            <div class="space-y-2">
              <input 
                type="text" 
                v-model="tagInput"
                @keyup.enter="addTag"
                :disabled="postStore.loading"
                placeholder="Type a tag and press Enter"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
              />
              
              <!-- Selected Tags -->
              <div v-if="form.tags.length > 0" class="flex flex-wrap gap-2">
                <span 
                  v-for="(tag, index) in form.tags" 
                  :key="index"
                  class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800"
                >
                  {{ tag }}
                  <button 
                    type="button"
                    @click="removeTag(index)"
                    :disabled="postStore.loading"
                    class="ml-2 text-indigo-600 hover:text-indigo-800 disabled:cursor-not-allowed"
                  >
                    ×
                  </button>
                </span>
              </div>
            </div>
            <p class="mt-1 text-sm text-gray-500">
              Press Enter to add tags. Click × to remove them.
            </p>
          </div>

          <!-- Action Buttons -->
          <div class="flex justify-between items-center pt-4 border-t border-gray-200">
            <router-link 
              to="/" 
              class="text-gray-600 hover:text-gray-800 px-4 py-2 text-sm font-medium transition-colors duration-200"
            >
              Cancel
            </router-link>
            
            <button 
              type="submit" 
              :disabled="postStore.loading || !form.title.trim() || !form.content.trim()"
              class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-md text-sm font-medium transition-colors duration-200 disabled:bg-gray-400 disabled:cursor-not-allowed flex items-center"
            >
              <span v-if="postStore.loading" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></span>
              {{ postStore.loading ? 'Creating...' : 'Create Post' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { usePostStore } from '@/stores/post.js'
import { useAuthStore } from '@/stores/auth.js'

const router = useRouter()
const postStore = usePostStore()
const authStore = useAuthStore()

// Watch for authentication state changes
watch(() => authStore.isLoggedIn, (isLoggedIn) => {
  if (!isLoggedIn && authStore.initialized) {
    console.log('🚫 User logged out, redirecting to home...')
    router.push('/')
  }
}, { immediate: false })

const form = ref({
  title: '',
  content: '',
  tags: []
})

const tagInput = ref('')

const addTag = () => {
  const tag = tagInput.value.trim()
  if (tag && !form.value.tags.includes(tag)) {
    form.value.tags.push(tag)
    tagInput.value = ''
  }
}

const removeTag = (index) => {
  form.value.tags.splice(index, 1)
}

const handleSubmit = async () => {
  const postData = {
    title: form.value.title.trim(),
    content: form.value.content.trim(),
    tags: form.value.tags
  }

  const result = await postStore.createPost(postData)
  
  if (result.success) {
    router.push('/')
  }
}

// Redirect to login if not authenticated
onMounted(() => {
  if (!authStore.isLoggedIn) {
    router.push('/login')
  }
})
</script>
