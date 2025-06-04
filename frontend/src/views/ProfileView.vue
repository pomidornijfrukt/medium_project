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
            <div class="ml-6 flex-1">
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
            
            <!-- Edit Profile Button -->
            <div class="ml-4">
              <button 
                @click="showEditModal = true"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200 flex items-center space-x-2"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                <span>Edit Profile</span>
              </button>
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
          </router-link>        </div>
      </div>
    </div>
    
    <!-- Edit Profile Modal -->
    <div v-if="showEditModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="closeModal">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white" @click.stop>
        <div class="mt-3">
          <!-- Modal Header -->
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">Edit Profile</h3>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>

          <!-- Tab Navigation -->
          <div class="flex border-b border-gray-200 mb-4">
            <button 
              @click="activeTab = 'profile'"
              :class="['px-4 py-2 text-sm font-medium', activeTab === 'profile' ? 'border-b-2 border-indigo-500 text-indigo-600' : 'text-gray-500 hover:text-gray-700']"
            >
              Profile Info
            </button>
            <button 
              @click="activeTab = 'password'"
              :class="['px-4 py-2 text-sm font-medium', activeTab === 'password' ? 'border-b-2 border-indigo-500 text-indigo-600' : 'text-gray-500 hover:text-gray-700']"
            >
              Change Password
            </button>
          </div>

          <!-- Profile Info Tab -->
          <div v-if="activeTab === 'profile'">
            <form @submit.prevent="updateProfile">
              <div class="mb-4">
                <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                <input
                  id="username"
                  v-model="profileForm.username"
                  type="text"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                  placeholder="Enter your username"
                />
              </div>
              
              <div class="mb-6">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                <input
                  id="email"
                  v-model="profileForm.email"
                  type="email"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                  placeholder="Enter your email"
                />
              </div>

              <div class="flex justify-end space-x-3">
                <button 
                  type="button" 
                  @click="closeModal"
                  class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 transition-colors duration-200"
                >
                  Cancel
                </button>
                <button 
                  type="submit"
                  :disabled="authStore.loading"
                  class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
                >
                  {{ authStore.loading ? 'Updating...' : 'Update Profile' }}
                </button>
              </div>
            </form>
          </div>

          <!-- Change Password Tab -->
          <div v-if="activeTab === 'password'">
            <form @submit.prevent="updatePassword">
              <div class="mb-4">
                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                <input
                  id="current_password"
                  v-model="passwordForm.current_password"
                  type="password"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                  placeholder="Enter current password"
                />
              </div>
                <div class="mb-4">
                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                <input
                  id="new_password"
                  v-model="passwordForm.password"
                  type="password"
                  required
                  minlength="8"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                  placeholder="Enter new password (min. 8 characters)"
                />
              </div>
              
              <div class="mb-6">
                <label for="new_password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                <input
                  id="new_password_confirmation"
                  v-model="passwordForm.password_confirmation"
                  type="password"
                  required
                  minlength="8"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                  placeholder="Confirm new password"
                />
              </div>

              <div class="flex justify-end space-x-3">
                <button 
                  type="button" 
                  @click="closeModal"
                  class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 transition-colors duration-200"
                >
                  Cancel
                </button>                <button 
                  type="submit"
                  :disabled="authStore.loading || passwordForm.password !== passwordForm.password_confirmation"
                  class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
                >
                  {{ authStore.loading ? 'Updating...' : 'Update Password' }}
                </button>
              </div>
            </form>
          </div>

          <!-- Error Display -->
          <div v-if="authStore.error" class="mt-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
            {{ authStore.error }}
          </div>

          <!-- Success Message -->
          <div v-if="successMessage" class="mt-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ successMessage }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { usePostStore } from '@/stores/post.js'
import { useAuthStore } from '@/stores/auth.js'

const router = useRouter()
const postStore = usePostStore()
const authStore = useAuthStore()

// Reactive data for edit modal
const showEditModal = ref(false)
const activeTab = ref('profile')
const successMessage = ref('')

// Form data
const profileForm = ref({
  username: '',
  email: ''
})

const passwordForm = ref({
  current_password: '',
  password: '',
  password_confirmation: ''
})

// Watch for authentication state changes
watch(() => authStore.isLoggedIn, (isLoggedIn) => {
  if (!isLoggedIn && authStore.initialized) {
    console.log('🚫 User logged out, redirecting to home...')
    router.push('/')
  }
}, { immediate: false })

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

// Modal and form handling methods
const closeModal = () => {
  showEditModal.value = false
  activeTab.value = 'profile'
  successMessage.value = ''
  authStore.clearError()
  resetForms()
}

const resetForms = () => {
  profileForm.value = {
    username: authStore.user?.Username || '',
    email: authStore.user?.Email || ''
  }
  passwordForm.value = {
    current_password: '',
    password: '',
    password_confirmation: ''
  }
}

const updateProfile = async () => {
  authStore.clearError()
  successMessage.value = ''
  
  const result = await authStore.updateProfile({
    username: profileForm.value.username,
    email: profileForm.value.email
  })
  
  if (result.success) {
    successMessage.value = 'Profile updated successfully!'
    setTimeout(() => {
      closeModal()
    }, 1500)
  }
}

const updatePassword = async () => {
  authStore.clearError()
  successMessage.value = ''
  
  if (passwordForm.value.password !== passwordForm.value.password_confirmation) {
    authStore.error = 'New passwords do not match'
    return
  }
  
  const result = await authStore.updatePassword({
    current_password: passwordForm.value.current_password,
    password: passwordForm.value.password,
    password_confirmation: passwordForm.value.password_confirmation
  })
  
  if (result.success) {
    successMessage.value = result.message || 'Password updated successfully!'
    setTimeout(() => {
      closeModal()
    }, 1500)
  }
}

// Initialize forms when user data is available
watch(() => authStore.user, (newUser) => {
  if (newUser) {
    resetForms()
  }
}, { immediate: true })

// Redirect to login if not authenticated
onMounted(async () => {
  if (!authStore.isLoggedIn) {
    router.push('/login')
    return
  }

  await loadUserPosts()
})
</script>
