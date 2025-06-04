<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Profile Header -->
      <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-8">
        <div class="px-6 py-8">
          <div class="flex items-center">
            <!-- Avatar Placeholder -->
            <div class="w-24 h-24 bg-indigo-600 rounded-full flex items-center justify-center">
              <span class="text-2xl font-bold text-white">
                {{ authStore.user?.Username?.charAt(0).toUpperCase() || '?' }}
              </span>
            </div>
            
            <!-- User Info -->
            <div class="ml-6">
              <h1 class="text-3xl font-bold text-gray-900">
                {{ authStore.user?.Username || 'Unknown User' }}
              </h1>
              <p class="text-gray-600">{{ authStore.user?.Email || 'No email' }}</p>
              <div class="mt-2 flex items-center space-x-4 text-sm text-gray-500">
                <span class="capitalize">{{ authStore.user?.Role || 'user' }}</span>
                <span>•</span>
                <span class="capitalize">{{ authStore.user?.Status || 'active' }}</span>
                <span>•</span>
                <span>Member since {{ formatDate(authStore.user?.created_at) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- User Stats -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6 text-center">
          <div class="text-3xl font-bold text-indigo-600">{{ userStats.totalPosts }}</div>
          <div class="text-gray-600">Total Posts</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6 text-center">
          <div class="text-3xl font-bold text-green-600">{{ userStats.publishedPosts }}</div>
          <div class="text-gray-600">Published Posts</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6 text-center">
          <div class="text-3xl font-bold text-yellow-600">{{ userStats.draftPosts }}</div>
          <div class="text-gray-600">Draft Posts</div>
        </div>
      </div>

      <!-- User Posts -->
      <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <header class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
          <h2 class="text-xl font-bold text-gray-900">My Posts</h2>
          <router-link 
            to="/create" 
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
          >
            Create New Post
          </router-link>
        </header>

        <!-- Loading State -->
        <div v-if="postStore.loading" class="flex justify-center items-center h-32">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
          <span class="ml-3 text-gray-600">Loading posts...</span>
        </div>

        <!-- Posts List -->
        <div v-else-if="userPosts.length > 0" class="divide-y divide-gray-200">
          <div 
            v-for="post in userPosts" 
            :key="post.PID"
            class="px-6 py-4 hover:bg-gray-50 transition-colors duration-200"
          >
            <div class="flex justify-between items-start">
              <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">
                  <router-link 
                    :to="`/posts/${post.PID}`"
                    class="hover:text-indigo-600 transition-colors duration-200"
                  >
                    {{ post.Title }}
                  </router-link>
                </h3>
                <p class="text-gray-600 text-sm mb-2">
                  {{ truncateContent(post.Content, 120) }}
                </p>
                <div class="flex items-center text-xs text-gray-500 space-x-4">
                  <span>{{ formatDate(post.created_at) }}</span>
                  <span v-if="post.updated_at !== post.created_at">
                    Updated {{ formatDate(post.updated_at) }}
                  </span>
                  <span class="capitalize">{{ post.status || 'published' }}</span>
                </div>
              </div>
              
              <!-- Action Buttons -->
              <div class="flex space-x-2 ml-4">
                <router-link 
                  :to="`/edit/${post.PID}`"
                  class="text-indigo-600 hover:text-indigo-800 text-sm font-medium transition-colors duration-200"
                >
                  Edit
                </router-link>
                <button 
                  @click="deletePost(post)"
                  class="text-red-600 hover:text-red-800 text-sm font-medium transition-colors duration-200"
                >
                  Delete
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- No Posts State -->
        <div v-else class="text-center py-12">
          <div class="text-gray-400 mb-4">
            <svg class="mx-auto h-16 w-16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <h3 class="text-lg font-medium text-gray-900 mb-2">No posts yet</h3>
          <p class="text-gray-600 mb-6">Start sharing your thoughts by creating your first post.</p>
          <router-link 
            to="/create" 
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-md text-sm font-medium transition-colors duration-200"
          >
            Create Your First Post
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { usePostStore } from '@/stores/post.js'
import { useAuthStore } from '@/stores/auth.js'

const router = useRouter()
const postStore = usePostStore()
const authStore = useAuthStore()

const userPosts = computed(() => {
  return postStore.posts.filter(post => 
    authStore.user && post.UID === authStore.user.UID
  )
})

const userStats = computed(() => {
  const posts = userPosts.value
  return {
    totalPosts: posts.length,
    publishedPosts: posts.filter(post => post.status !== 'draft').length,
    draftPosts: posts.filter(post => post.status === 'draft').length
  }
})

const formatDate = (dateString) => {
  if (!dateString) return 'Unknown date'
  
  try {
    const date = new Date(dateString)
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    })
  } catch (error) {
    return 'Invalid date'
  }
}

const truncateContent = (content, maxLength = 120) => {
  if (!content) return ''
  if (content.length <= maxLength) return content
  return content.substring(0, maxLength) + '...'
}

const deletePost = async (post) => {
  if (confirm(`Are you sure you want to delete "${post.Title}"? This action cannot be undone.`)) {
    const success = await postStore.deletePost(post.PID)
    if (success) {
      // Refresh posts after deletion
      await loadUserPosts()
    }
  }
}

const loadUserPosts = async () => {
  // Fetch all posts and filter on the client side for user's posts
  await postStore.fetchPosts()
}

// Redirect to login if not authenticated
onMounted(async () => {
  if (!authStore.isLoggedIn) {
    router.push('/login')
    return
  }

  await loadUserPosts()
})
</script>
