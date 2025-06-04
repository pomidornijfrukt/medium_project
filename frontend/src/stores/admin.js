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
  
  // Cache management
  const cacheLoading = ref(false)
  const hasNewPosts = ref(false)
  // Get auth store for token access
  const authStore = useAuthStore()
  
  // Cache management constants
  const CACHE_KEYS = {
    ADMIN_POSTS: 'admin_posts_cache',
    TIMESTAMP: 'admin_posts_timestamp'
  }

  // Cache helper functions
  const savePostsToCache = (posts) => {
    try {
      localStorage.setItem(CACHE_KEYS.ADMIN_POSTS, JSON.stringify(posts))
      localStorage.setItem(CACHE_KEYS.TIMESTAMP, Date.now().toString())
      console.log('🔧 Admin posts saved to cache:', posts.length)
    } catch (error) {
      console.warn('Failed to save admin posts to cache:', error)
    }
  }

  const loadPostsFromCache = () => {
    try {
      const cachedPosts = localStorage.getItem(CACHE_KEYS.ADMIN_POSTS)
      const timestamp = localStorage.getItem(CACHE_KEYS.TIMESTAMP)

      if (cachedPosts && timestamp) {
        const posts = JSON.parse(cachedPosts)
        const cacheAge = Date.now() - parseInt(timestamp)
        
        // Use cache if it's less than 3 minutes old (admin data changes more frequently)
        if (cacheAge < 3 * 60 * 1000) {
          allPosts.value = posts
          console.log('🔧 Loaded admin posts from cache:', posts.length)
          return true
        } else {
          console.log('🔧 Admin cache expired, will fetch fresh data')
          clearPostsCache()
        }
      }
      return false
    } catch (error) {
      console.warn('Failed to load admin posts from cache:', error)
      return false
    }
  }

  const clearPostsCache = () => {
    try {
      localStorage.removeItem(CACHE_KEYS.ADMIN_POSTS)
      localStorage.removeItem(CACHE_KEYS.TIMESTAMP)
      console.log('🔧 Admin posts cache cleared')
    } catch (error) {
      console.warn('Failed to clear admin posts cache:', error)
    }
  }

  const checkForNewPosts = (freshPosts, cachedPosts) => {
    if (freshPosts.length !== cachedPosts.length) return true
    
    // Check if any posts have been updated
    for (let i = 0; i < freshPosts.length; i++) {
      if (!cachedPosts[i]) return true
      if (new Date(freshPosts[i].updated_at) > new Date(cachedPosts[i].updated_at)) return true
    }
    return false
  }

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
  const fetchAllPosts = async (useCache = true) => {
    console.log('🔍 Admin fetchAllPosts called with:', { useCache })
    
    // Try to load from cache first
    if (useCache) {
      console.log('🔍 Attempting to load admin posts from cache...')
      cacheLoading.value = true
      
      // Check if cache exists and is valid
      const cachedPosts = localStorage.getItem(CACHE_KEYS.ADMIN_POSTS)
      const timestamp = localStorage.getItem(CACHE_KEYS.TIMESTAMP)
      
      console.log('🔍 Cache data found:', { 
        hasPosts: !!cachedPosts, 
        hasTimestamp: !!timestamp 
      })
      
      if (cachedPosts && timestamp) {
        const cacheAge = Date.now() - parseInt(timestamp)
        const cacheValidMinutes = 3
        const isCacheValid = cacheAge < cacheValidMinutes * 60 * 1000
        
        console.log('🔍 Cache age:', Math.round(cacheAge / 1000), 'seconds. Valid:', isCacheValid)
        
        if (isCacheValid) {
          try {
            const posts = JSON.parse(cachedPosts)
            
            // Update store with cached data
            allPosts.value = posts
            cacheLoading.value = false
            
            console.log('✅ Successfully loaded', posts.length, 'admin posts from cache - NO API CALL!')
            
            // Schedule background update check after 20 seconds (faster for admin)
            setTimeout(async () => {
              try {
                console.log('🔄 Checking for admin post updates in background...')
                const backgroundResult = await checkForAdminUpdatesOnly()
                if (backgroundResult.success && backgroundResult.hasUpdates) {
                  console.log('🆕 New admin posts found in background!')
                  hasNewPosts.value = true
                }
              } catch (error) {
                console.warn('Background admin update check failed:', error)
              }
            }, 20000)
            
            return { 
              success: true, 
              data: { posts: [...posts] },
              fromCache: true,
              hasUpdates: false
            }
          } catch (parseError) {
            console.warn('Failed to parse cached admin data:', parseError)
            clearPostsCache()
          }
        } else {
          console.log('🔧 Admin cache expired, clearing old cache')
          clearPostsCache()
        }
      }
      cacheLoading.value = false
    }

    // No cache available or not using cache, fetch fresh data
    console.log('🌐 Fetching fresh admin posts from API...')
    return await fetchFreshAdminPosts(true)
  }

  const checkForAdminUpdatesOnly = async () => {
    try {
      const url = `${API_BASE_URL}/admin/posts`
      console.log('🔍 Checking for admin post updates from:', url)

      const response = await fetch(url, {
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

      if (data.success) {
        const freshPosts = data.posts || []
        
        // Check if we have new posts compared to current cached posts
        const hasUpdates = checkForNewPosts(freshPosts, allPosts.value)
        
        console.log('🔍 Admin post update check complete. Has updates:', hasUpdates)
        return { 
          success: true, 
          hasUpdates: hasUpdates,
          freshData: data.posts
        }
      } else {
        throw new Error('Invalid response format')
      }
    } catch (err) {
      console.error('Error checking for admin updates:', err)
      return { success: false, error: err.message, hasUpdates: false }
    }
  }

  const fetchFreshAdminPosts = async (saveToCache = false) => {
    loadingPosts.value = true
    error.value = null
    hasNewPosts.value = false

    console.log('🌐 fetchFreshAdminPosts called with:', { saveToCache })

    try {
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
        const freshPosts = data.posts || []

        // Check if we have new posts compared to current posts
        const hasUpdates = allPosts.value.length > 0 && checkForNewPosts(freshPosts, allPosts.value)
        
        // Update store with fresh data
        allPosts.value = freshPosts
        
        // Save to cache
        if (saveToCache) {
          savePostsToCache(freshPosts)
          console.log('🔧 Admin posts saved to cache for future use')
        }
        
        console.log('Successfully loaded fresh admin posts:', allPosts.value.length)
        return { 
          success: true, 
          data: data.posts,
          fromCache: false,
          hasUpdates: hasUpdates
        }
      } else {
        throw new Error(data.message || 'Failed to fetch posts')
      }
    } catch (err) {
      console.error('Error fetching posts:', err)
      error.value = err.message
      allPosts.value = []
      return { success: false, error: err.message, fromCache: false }
    } finally {
      loadingPosts.value = false
    }
  }

  const refreshWithFreshAdminPosts = async () => {
    hasNewPosts.value = false
    const result = await fetchFreshAdminPosts(true)
    return result
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
