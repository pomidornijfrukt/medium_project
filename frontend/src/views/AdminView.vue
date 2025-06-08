<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Admin Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex justify-between items-center">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Admin Panel</h1>
            <p class="text-gray-600 mt-1">Manage users, posts, and forum settings</p>
          </div>
          <div class="flex items-center space-x-4">
            <span class="text-sm text-gray-500">Welcome, {{ authStore.user?.Username }}</span>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
              Administrator
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Admin Navigation -->
    <div class="bg-white shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="flex space-x-8">
          <button 
            @click="activeTab = 'users'"
            :class="[
              'py-4 px-1 border-b-2 font-medium text-sm',
              activeTab === 'users' 
                ? 'border-indigo-500 text-indigo-600' 
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            Users Management
          </button>
          <button 
            @click="activeTab = 'posts'"
            :class="[
              'py-4 px-1 border-b-2 font-medium text-sm',
              activeTab === 'posts' 
                ? 'border-indigo-500 text-indigo-600' 
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            Posts Management
          </button>
        </nav>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">      <!-- Users Management Tab -->
      <div v-if="activeTab === 'users'" class="space-y-6">
        <div class="bg-white shadow rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
              <div>
                <h2 class="text-lg font-medium text-gray-900">Users Management</h2>
                <p class="text-sm text-gray-500">Manage user accounts and permissions</p>
              </div>
              
              <!-- Refresh Button -->
              <div class="flex justify-center sm:justify-end">
                <RefreshButton 
                  @refresh="refreshUsers"
                  :is-loading="adminStore.loading"
                  variant="secondary"
                  size="small"
                  :show-mobile-text="true"
                />
              </div>
            </div>
          </div>
          
          <!-- Users List -->          <div class="overflow-hidden">
            <div v-if="adminStore.loading" class="p-6 text-center">
              <LoadingSpinner size="large" color="indigo" class="mx-auto" aria-label="Loading users" />
              <p class="mt-2 text-gray-500">Loading users...</p>
            </div>
            
            <div v-else-if="adminStore.error" class="p-6 bg-red-50 border border-red-200">
              <p class="text-red-800">{{ adminStore.error }}</p>
              <button 
                @click="loadUsers" 
                class="mt-2 bg-red-600 text-white px-4 py-2 rounded text-sm hover:bg-red-700"
              >
                Try Again
              </button>
            </div>
            
            <table v-else class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="user in adminStore.users" :key="user.UID" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="flex-shrink-0 h-10 w-10">
                        <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                          <span class="text-sm font-medium text-indigo-600">
                            {{ user.Username.charAt(0).toUpperCase() }}
                          </span>
                        </div>
                      </div>
                      <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900">{{ user.Username }}</div>
                        <div class="text-sm text-gray-500">{{ user.Email }}</div>
                      </div>
                    </div>
                  </td>                  <td class="px-6 py-4 whitespace-nowrap">
                    <Tag
                      :label="user.Role || 'member'"
                      size="medium"
                      :variant="user.Role === 'admin' ? 'red' : user.Role === 'moderator' ? 'yellow' : 'green'"
                    />
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <Tag
                      :label="user.Status"
                      size="medium"
                      :variant="user.Status === 'active' ? 'green' : user.Status === 'banned' ? 'red' : 'yellow'"
                    />
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ formatDate(user.created_at) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                    <button 
                      @click="selectUser(user)"
                      class="text-indigo-600 hover:text-indigo-900"
                    >
                      Manage
                    </button>
                    <button 
                      @click="viewUserPosts(user)"
                      class="text-blue-600 hover:text-blue-900"
                    >
                      Posts
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>      <!-- Posts Management Tab -->
      <div v-if="activeTab === 'posts'" class="space-y-6">
        <div class="bg-white shadow rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
              <div>
                <h2 class="text-lg font-medium text-gray-900">Posts Management</h2>
                <p class="text-sm text-gray-500">Manage all forum posts</p>
              </div>
              
              <!-- Refresh Button -->
              <div class="flex justify-center sm:justify-end">
                <RefreshButton 
                  @refresh="refreshPosts"
                  :is-loading="adminStore.loadingPosts"
                  variant="secondary"
                  size="small"
                  :show-mobile-text="true"
                />
              </div>
            </div>
          </div>
            <div class="p-6">
            <div v-if="adminStore.loadingPosts" class="text-center py-8">
              <LoadingSpinner size="large" color="indigo" class="mx-auto" aria-label="Loading posts" />
              <p class="mt-2 text-gray-500">Loading posts...</p>
            </div>
            
            <div v-else class="space-y-4">
              <div v-for="post in adminStore.allPosts" :key="post.PostID" 
                   class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                <div class="flex justify-between items-start">
                  <div class="flex-1">
                    <h3 class="text-lg font-medium text-gray-900">{{ post.Topic }}</h3>
                    <p class="text-sm text-gray-600 mt-1">{{ truncateContent(post.Content) }}</p>
                    <div class="flex items-center mt-2 text-sm text-gray-500 space-x-4">
                      <span>By {{ post.author?.Username }}</span>
                      <span>{{ formatDate(post.created_at) }}</span>
                      <span :class="[
                        'px-2 py-1 rounded text-xs',
                        post.Status === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                      ]">
                        {{ post.Status }}
                      </span>
                    </div>
                  </div>                  <div class="flex space-x-2">
                    <router-link 
                      :to="`/posts/${post.PostID}/edit`"
                      class="text-indigo-600 hover:text-indigo-900 text-sm"
                    >
                      Edit
                    </router-link>
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
          </div>
        </div>
      </div>
    </div>

    <!-- User Management Modal -->
    <UserManageModal 
      v-if="showUserModal"
      :user="selectedUser"
      @close="closeUserModal"
      @role-updated="handleRoleUpdate"
    />

    <!-- User Posts Modal -->
    <UserPostsModal 
      v-if="showPostsModal"
      :user="selectedUser"
      :posts="adminStore.userPosts"
      :loading="adminStore.loadingPosts"
      @close="closePostsModal"
      @delete-post="handleDeletePost"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useAdminStore } from '@/stores/admin'
import UserManageModal from '@/components/UserManageModal.vue'
import UserPostsModal from '@/components/UserPostsModal.vue'
import Tag from '@/components/Tag.vue'
import RefreshButton from '@/components/RefreshButton.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'

const router = useRouter()
const authStore = useAuthStore()
const adminStore = useAdminStore()

// Watch for authentication state changes
watch(() => authStore.isLoggedIn, (isLoggedIn) => {
  if (!isLoggedIn && authStore.initialized) {
    console.log('🚫 Admin logged out, redirecting to home...')
    router.push('/')
  }
}, { immediate: false })

const activeTab = ref('users')
const showUserModal = ref(false)
const showPostsModal = ref(false)
const selectedUser = ref(null)

onMounted(async () => {
  await loadUsers()
})

const refreshUsers = async () => {
  await adminStore.fetchUsers()
}

const refreshPosts = async () => {
  await adminStore.fetchAllPosts()
}

const loadUsers = async () => {
  await adminStore.fetchUsers()
}

const loadPosts = async () => {
  await adminStore.fetchAllPosts()
}

const selectUser = (user) => {
  selectedUser.value = user
  adminStore.setSelectedUser(user)
  showUserModal.value = true
}

const viewUserPosts = async (user) => {
  selectedUser.value = user
  adminStore.setSelectedUser(user)
  await adminStore.fetchUserPosts(user.UID)
  showPostsModal.value = true
}

const closeUserModal = () => {
  showUserModal.value = false
  selectedUser.value = null
}

const closePostsModal = () => {
  showPostsModal.value = false
  selectedUser.value = null
}

const handleRoleUpdate = () => {
  // Refresh users list after role update
  loadUsers()
}

const handleDeletePost = async (postId) => {
  await adminStore.deletePost(postId)
}

const deletePost = async (post) => {
  if (confirm(`Are you sure you want to delete the post "${post.Topic}"?`)) {
    await adminStore.deletePost(post.PostID)
  }
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString()
}

const truncateContent = (content) => {
  if (!content) return ''
  return content.length > 150 ? content.substring(0, 150) + '...' : content
}

// Watch for tab changes and load data accordingly
watch(activeTab, (newTab) => {
  if (newTab === 'posts' && adminStore.allPosts.length === 0) {
    loadPosts()
  }
})
</script>
