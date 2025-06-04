<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth.js'
import { usePostStore } from '@/stores/post.js'
import { useTagStore } from '@/stores/tag.js'

// Store instances
const authStore = useAuthStore()
const postStore = usePostStore()
const tagStore = useTagStore()

// Local state
const searchQuery = ref('')
const isLoading = ref(true)

// Methods
const fetchPosts = async (page = 1) => {
  try {
    await postStore.fetchPosts(page, searchQuery.value)
  } catch (error) {
    console.error('Error fetching posts:', error)
  }
}

const fetchTags = async () => {
  try {
    await tagStore.fetchTags()
  } catch (error) {
    console.error('Error fetching tags:', error)
  }
}

const changePage = async (page) => {
  if (page >= 1 && page <= postStore.pagination.lastPage) {
    await fetchPosts(page)
  }
}

const truncateContent = (content, maxLength = 200) => {
  if (!content) return ''
  return content.length > maxLength ? content.substring(0, maxLength) + '...' : content
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const searchPosts = async () => {
  await fetchPosts(1)
}

// Load data on component mount
onMounted(async () => {
  isLoading.value = true
  await Promise.all([
    fetchPosts(),
    fetchTags()
  ])
  isLoading.value = false
})
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Hero Section -->
    <div class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="text-center">
          <h1 class="text-5xl font-bold text-gray-900 mb-6">Welcome to Our Forum</h1>
          <p class="text-xl text-gray-600 mb-8 max-w-3xl mx-auto">
            A place to share your thoughts, discover new ideas, and connect with our growing community. 
            Join conversations, ask questions, and be part of something bigger.
          </p>
          
          <!-- Action Buttons -->
          <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
            <div v-if="authStore.isLoggedIn" class="text-center">
              <h2 class="text-2xl font-semibold text-gray-800 mb-4">
                Welcome back, {{ authStore.user?.Username }}! 👋
              </h2>
              <p class="text-gray-600 mb-6">Ready to share something with the community?</p>
              <router-link 
                to="/create"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-3 rounded-lg text-lg font-medium transition-colors duration-200 inline-flex items-center"
              >
                <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Create Your First Post
              </router-link>
            </div>
            
            <div v-else class="text-center">
              <p class="text-gray-600 mb-6">Join our community to start participating in discussions</p>
              <div class="space-x-4">
                <router-link 
                  to="/register"
                  class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-3 rounded-lg text-lg font-medium transition-colors duration-200 inline-flex items-center"
                >
                  <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                  </svg>
                  Join Our Community
                </router-link>
                <router-link 
                  to="/login"
                  class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-8 py-3 rounded-lg text-lg font-medium transition-colors duration-200 inline-flex items-center"
                >
                  <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                  </svg>
                  Sign In
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Posts Section -->
        <div class="lg:col-span-2">
          <!-- Search Bar -->
          <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex gap-4">
              <input 
                v-model="searchQuery"
                @keyup.enter="searchPosts"
                type="text"
                placeholder="Search posts..."
                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
              />
              <button 
                @click="searchPosts"
                class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors duration-200"
              >
                Search
              </button>
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="isLoading || postStore.loading" class="text-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto mb-4"></div>
            <p class="text-gray-600">Loading posts...</p>
          </div>

          <!-- Error State -->
          <div v-else-if="postStore.error" class="bg-red-50 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6">
            <strong class="font-bold">Error:</strong>
            <span class="block sm:inline"> {{ postStore.error }}</span>
            <button 
              @click="fetchPosts()"
              class="mt-2 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm transition-colors duration-200"
            >
              Try Again
            </button>
          </div>

          <!-- Posts List -->
          <div v-else-if="postStore.posts.length > 0" class="space-y-6">
            <article 
              v-for="post in postStore.posts" 
              :key="post.PostID"
              class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200 overflow-hidden"
            >
              <div class="p-6">
                <div class="flex justify-between items-start mb-4">
                  <div class="flex-1">
                    <h2 class="text-xl font-bold text-gray-900 mb-2">
                      <router-link 
                        :to="`/posts/${post.PostID}`"
                        class="hover:text-indigo-600 transition-colors duration-200"
                      >
                        {{ post.Topic }}
                      </router-link>
                    </h2>
                    <p class="text-gray-600 mb-4">{{ truncateContent(post.Content) }}</p>
                    
                    <!-- Post Meta -->
                    <div class="flex items-center text-sm text-gray-500 space-x-4">
                      <span>By {{ post.author?.Username || 'Anonymous' }}</span>
                      <span>•</span>
                      <span>{{ formatDate(post.created_at) }}</span>
                      <span v-if="post.updated_at !== post.created_at">
                        • Updated {{ formatDate(post.updated_at) }}
                      </span>
                    </div>
                  </div>
                </div>
                
                <!-- Tags -->
                <div v-if="post.tags && post.tags.length > 0" class="flex flex-wrap gap-2">
                  <span 
                    v-for="tag in post.tags" 
                    :key="tag.TagName"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800"
                  >
                    {{ tag.TagName }}
                  </span>
                </div>
              </div>
            </article>

            <!-- Pagination -->
            <div v-if="postStore.pagination.lastPage > 1" class="flex justify-center">
              <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                <button
                  @click="changePage(postStore.pagination.currentPage - 1)"
                  :disabled="postStore.pagination.currentPage === 1"
                  class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:bg-gray-100 disabled:cursor-not-allowed"
                >
                  Previous
                </button>
                
                <button
                  v-for="page in Math.min(5, postStore.pagination.lastPage)"
                  :key="page"
                  @click="changePage(page)"
                  :class="[
                    'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                    page === postStore.pagination.currentPage
                      ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                      : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                  ]"
                >
                  {{ page }}
                </button>
                
                <button
                  @click="changePage(postStore.pagination.currentPage + 1)"
                  :disabled="postStore.pagination.currentPage === postStore.pagination.lastPage"
                  class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:bg-gray-100 disabled:cursor-not-allowed"
                >
                  Next
                </button>
              </nav>
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
            <p class="text-gray-600 mb-6">Be the first to share something with the community!</p>
            <router-link 
              v-if="authStore.isLoggedIn"
              to="/create" 
              class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-md text-sm font-medium transition-colors duration-200"
            >
              Create First Post
            </router-link>
            <div v-else class="space-x-4">
              <router-link 
                to="/register"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-md text-sm font-medium transition-colors duration-200"
              >
                Join to Post
              </router-link>
              <router-link 
                to="/login"
                class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-3 rounded-md text-sm font-medium transition-colors duration-200"
              >
                Sign In
              </router-link>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1">
          <!-- Popular Tags -->
          <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Popular Tags</h3>
            <div v-if="tagStore.allTags.length > 0" class="flex flex-wrap gap-2">
              <router-link 
                v-for="tag in tagStore.allTags.slice(0, 10)" 
                :key="tag.TagName" 
                :to="`/tags/${tag.TagName}`"
                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800 hover:bg-gray-200 transition-colors duration-200"
              >
                {{ tag.TagName }}
                <span v-if="tag.posts_count" class="ml-1 text-xs text-gray-500">({{ tag.posts_count }})</span>
              </router-link>
            </div>
            <p v-else class="text-gray-500 text-sm">No tags available yet</p>
          </div>

          <!-- Quick Stats -->
          <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Community Stats</h3>
            <div class="space-y-3">
              <div class="flex justify-between items-center">
                <span class="text-gray-600">Total Posts</span>
                <span class="font-semibold text-indigo-600">{{ postStore.pagination.total || 0 }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-gray-600">Total Tags</span>
                <span class="font-semibold text-green-600">{{ tagStore.allTags.length }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
