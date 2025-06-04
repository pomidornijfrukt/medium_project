import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import { useAuthStore } from './auth.js'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:6969/api'

export const useAdminStore = defineStore('admin', () => {
  // State
  const users = ref([])
  const selectedUser = ref(null)
  const userPosts = ref([])
  const allPosts = ref([])
  const roles = ref([])
  const stats = ref({})
  const loading = ref(false)
  const loadingPosts = ref(false)
  const error = ref(null)

  // Get auth store for token access
  const authStore = useAuthStore()

  // Actions
  const fetchUsers = async () => {
    try {
      loading.value = true
      error.value = null
      
      console.log('Fetching users from admin API...')
      const response = await fetch(`${API_BASE_URL}/admin/users`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        }
      })
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      const data = await response.json()
      console.log('Users fetched successfully:', data)
      
      if (data.success) {
        users.value = data.users || []
        return { success: true, data: data.users }
      } else {
        throw new Error(data.message || 'Failed to fetch users')
      }
    } catch (err) {
      console.error('Error fetching users:', err)
      error.value = err.message
      users.value = []
      return { success: false, error: err.message }
    } finally {
      loading.value = false
    }
  }

  const fetchAllPosts = async () => {
    try {
      loadingPosts.value = true
      error.value = null

      console.log('Fetching all posts from admin API...')
      const response = await fetch(`${API_BASE_URL}/admin/posts`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        }
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const data = await response.json()
      console.log('Posts fetched successfully:', data)

      if (data.success) {
        allPosts.value = data.posts || []
        return { success: true, data: data.posts }
      } else {
        throw new Error(data.message || 'Failed to fetch posts')
      }
    } catch (err) {
      console.error('Error fetching posts:', err)
      error.value = err.message
      allPosts.value = []
      return { success: false, error: err.message }
    } finally {
      loadingPosts.value = false
    }
  }
  const fetchUserDetails = async (userId) => {
    try {
      loadingPosts.value = true
      error.value = null

      console.log(`Fetching user details for: ${userId}`)
      const response = await fetch(`${API_BASE_URL}/admin/users/${userId}`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        }
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const data = await response.json()
      console.log('User details fetched successfully:', data)

      if (data.success) {
        selectedUser.value = data.user
        userPosts.value = data.posts || []
        return { success: true, user: data.user, posts: data.posts }
      } else {
        throw new Error(data.message || 'Failed to fetch user details')
      }
    } catch (err) {
      console.error('Error fetching user details:', err)
      error.value = err.message
      userPosts.value = []
      return { success: false, error: err.message }
    } finally {
      loadingPosts.value = false
    }
  }

  const fetchUserPosts = async (userId) => {
    try {
      loadingPosts.value = true
      error.value = null

      console.log(`Fetching posts for user: ${userId}`)
      const response = await fetch(`${API_BASE_URL}/admin/users/${userId}`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        }
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const data = await response.json()
      console.log('User posts fetched successfully:', data)

      if (data.success) {
        userPosts.value = data.posts || []
        return { success: true, posts: data.posts }
      } else {
        throw new Error(data.message || 'Failed to fetch user posts')
      }
    } catch (err) {
      console.error('Error fetching user posts:', err)
      error.value = err.message
      userPosts.value = []
      return { success: false, error: err.message }
    } finally {
      loadingPosts.value = false
    }
  }

  const updateUserRole = async (userId, newRole) => {
    try {
      loading.value = true
      error.value = null

      console.log(`Updating user ${userId} role to: ${newRole}`)
      const response = await fetch(`${API_BASE_URL}/admin/users/${userId}/role`, {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ role: newRole })
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const data = await response.json()
      console.log('User role updated successfully:', data)

      if (data.success) {
        // Update the user in the local state
        const userIndex = users.value.findIndex(u => u.UID === userId)
        if (userIndex !== -1) {
          users.value[userIndex].Role = newRole
        }
        return { success: true, data: data.user }
      } else {
        throw new Error(data.message || 'Failed to update user role')
      }
    } catch (err) {
      console.error('Error updating user role:', err)
      error.value = err.message
      return { success: false, error: err.message }
    } finally {
      loading.value = false
    }
  }

  const updateUserStatus = async (userId, newStatus) => {
    try {
      loading.value = true
      error.value = null

      console.log(`Updating user ${userId} status to: ${newStatus}`)
      const response = await fetch(`${API_BASE_URL}/admin/users/${userId}/status`, {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ status: newStatus })
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const data = await response.json()
      console.log('User status updated successfully:', data)

      if (data.success) {
        // Update the user in the local state
        const userIndex = users.value.findIndex(u => u.UID === userId)
        if (userIndex !== -1) {
          users.value[userIndex].Status = newStatus
        }
        return { success: true, data: data.user }
      } else {
        throw new Error(data.message || 'Failed to update user status')
      }
    } catch (err) {
      console.error('Error updating user status:', err)
      error.value = err.message
      return { success: false, error: err.message }
    } finally {
      loading.value = false
    }
  }

  const deletePost = async (postId) => {
    try {
      loading.value = true
      error.value = null

      console.log(`Deleting post: ${postId}`)
      const response = await fetch(`${API_BASE_URL}/admin/posts/${postId}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        }
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const data = await response.json()
      console.log('Post deleted successfully:', data)

      if (data.success) {
        // Remove the post from local state
        allPosts.value = allPosts.value.filter(p => p.PostID !== postId)
        userPosts.value = userPosts.value.filter(p => p.PostID !== postId)
        return { success: true }
      } else {
        throw new Error(data.message || 'Failed to delete post')
      }
    } catch (err) {
      console.error('Error deleting post:', err)
      error.value = err.message
      return { success: false, error: err.message }
    } finally {
      loading.value = false
    }
  }

  const fetchDashboardStats = async () => {
    try {
      loading.value = true
      error.value = null

      console.log('Fetching dashboard stats...')
      const response = await fetch(`${API_BASE_URL}/admin/stats`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        }
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const data = await response.json()
      console.log('Dashboard stats fetched successfully:', data)

      if (data.success) {
        stats.value = data.stats || {}
        return { success: true, data: data.stats }
      } else {
        throw new Error(data.message || 'Failed to fetch dashboard stats')
      }
    } catch (err) {
      console.error('Error fetching dashboard stats:', err)
      error.value = err.message
      return { success: false, error: err.message }
    } finally {
      loading.value = false
    }
  }

  const fetchRoles = async () => {
    try {
      loading.value = true
      error.value = null

      console.log('Fetching available roles...')
      const response = await fetch(`${API_BASE_URL}/admin/roles`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        }
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const data = await response.json()
      console.log('Roles fetched successfully:', data)

      if (data.success) {
        roles.value = data.roles || []
        return { success: true, data: data.roles }
      } else {
        throw new Error(data.message || 'Failed to fetch roles')
      }
    } catch (err) {
      console.error('Error fetching roles:', err)
      error.value = err.message
      return { success: false, error: err.message }
    } finally {
      loading.value = false
    }
  }

  const clearError = () => {
    error.value = null
  }

  const setSelectedUser = (user) => {
    selectedUser.value = user
  }

  return {
    // State
    users,
    selectedUser,
    userPosts,
    allPosts,
    roles,
    stats,
    loading,
    loadingPosts,
    error,
      // Actions
    fetchUsers,
    fetchAllPosts,
    fetchUserDetails,
    fetchUserPosts,
    updateUserRole,
    updateUserStatus,
    deletePost,
    fetchDashboardStats,
    fetchRoles,
    clearError,
    setSelectedUser
  }
})
