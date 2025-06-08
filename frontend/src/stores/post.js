import { ref, computed } from 'vue'
import { defineStore } from 'pinia'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:6969/api'

export const usePostStore = defineStore('post', () => {
  const allPosts = ref([])
  const currentPost = ref(null)
  const loading = ref(false)
  const error = ref(null)
  const pagination = ref({
    currentPage: 1,
    lastPage: 1,
    perPage: 10,
    total: 0
  })
  const cacheLoading = ref(false)
  const hasNewPosts = ref(false)

  const posts = computed(() => allPosts.value)

  // Cache management
  const CACHE_KEYS = {
    POSTS: 'homepage_posts_cache',
    PAGINATION: 'homepage_pagination_cache',
    TIMESTAMP: 'homepage_cache_timestamp'
  }

  const savePostsToCache = (posts, paginationData) => {
    try {
      localStorage.setItem(CACHE_KEYS.POSTS, JSON.stringify(posts))
      localStorage.setItem(CACHE_KEYS.PAGINATION, JSON.stringify(paginationData))
      localStorage.setItem(CACHE_KEYS.TIMESTAMP, Date.now().toString())
      console.log('📋 Posts saved to cache:', posts.length)
    } catch (error) {
      console.warn('Failed to save posts to cache:', error)
    }
  }

  const loadPostsFromCache = () => {
    try {
      const cachedPosts = localStorage.getItem(CACHE_KEYS.POSTS)
      const cachedPagination = localStorage.getItem(CACHE_KEYS.PAGINATION)
      const timestamp = localStorage.getItem(CACHE_KEYS.TIMESTAMP)

      if (cachedPosts && cachedPagination && timestamp) {
        const posts = JSON.parse(cachedPosts)
        const paginationData = JSON.parse(cachedPagination)
        const cacheAge = Date.now() - parseInt(timestamp)
        
        // Use cache if it's less than 5 minutes old
        if (cacheAge < 5 * 60 * 1000) {
          allPosts.value = posts
          pagination.value = paginationData
          console.log('📋 Loaded posts from cache:', posts.length)
          return true
        } else {
          console.log('📋 Cache expired, will fetch fresh data')
          clearPostsCache()
        }
      }
      return false
    } catch (error) {
      console.warn('Failed to load posts from cache:', error)
      return false
    }
  }
  const clearPostsCache = () => {
    try {
      localStorage.removeItem(CACHE_KEYS.POSTS)
      localStorage.removeItem(CACHE_KEYS.PAGINATION)
      localStorage.removeItem(CACHE_KEYS.TIMESTAMP)
      console.log('📋 Posts cache cleared')
    } catch (error) {
      console.warn('Failed to clear posts cache:', error)
    }
  }

  const checkForNewPosts = (freshPosts, cachedPosts) => {
    if (freshPosts.length !== cachedPosts.length) return true
    
    // Check if the first few posts are the same (most recent posts)
    for (let i = 0; i < Math.min(3, freshPosts.length); i++) {
      if (freshPosts[i].PostID !== cachedPosts[i].PostID) return true
      if (new Date(freshPosts[i].updated_at) > new Date(cachedPosts[i].updated_at)) return true
    }
    return false
  }

  const fetchPosts = async (page = 1, search = '', useCache = true) => {    console.log('🔍 fetchPosts called with:', { page, search, useCache })
    
    // For page 1 and no search, try to load from cache first
    // BUT bypass cache if new posts were detected in background
    const shouldUseCache = useCache && page === 1 && !search.trim() && !hasNewPosts.value
    console.log('🔍 shouldUseCache:', shouldUseCache, { hasNewPosts: hasNewPosts.value })

    if (shouldUseCache) {
      console.log('🔍 Attempting to load from cache...')
      cacheLoading.value = true
      
      // Check if cache exists and is valid
      const cachedPosts = localStorage.getItem(CACHE_KEYS.POSTS)
      const cachedPagination = localStorage.getItem(CACHE_KEYS.PAGINATION)
      const timestamp = localStorage.getItem(CACHE_KEYS.TIMESTAMP)
      
      console.log('🔍 Cache data found:', { 
        hasPosts: !!cachedPosts, 
        hasPagination: !!cachedPagination, 
        hasTimestamp: !!timestamp 
      })
      
      if (cachedPosts && cachedPagination && timestamp) {
        const cacheAge = Date.now() - parseInt(timestamp)
        const cacheValidMinutes = 5
        const isCacheValid = cacheAge < cacheValidMinutes * 60 * 1000
        
        console.log('🔍 Cache age:', Math.round(cacheAge / 1000), 'seconds. Valid:', isCacheValid)
        
        if (isCacheValid) {
          try {
            const posts = JSON.parse(cachedPosts)
            const paginationData = JSON.parse(cachedPagination)
            
            // Update store with cached data
            allPosts.value = posts
            pagination.value = paginationData
            cacheLoading.value = false
              console.log('✅ Successfully loaded', posts.length, 'posts from cache - NO API CALL!')
            
            // Start background monitoring using dedicated function
            startBackgroundMonitoring()
            
            return { 
              success: true, 
              data: { data: [...posts] },
              fromCache: true,
              hasUpdates: false
            }
          } catch (parseError) {
            console.warn('Failed to parse cached data:', parseError)
            clearPostsCache()
          }
        } else {
          console.log('📋 Cache expired, clearing old cache')
          clearPostsCache()
        }      }
      cacheLoading.value = false
    } else {
      // Cache bypassed - either no useCache flag, not page 1, has search, or new posts detected
      if (hasNewPosts.value) {
        console.log('🚀 Bypassing cache due to new posts detected in background!')
      } else {
        console.log('🚀 Bypassing cache - not cacheable request')
      }
    }

    // No cache available or not using cache, fetch fresh data
    console.log('🌐 Fetching fresh data from API...')
    return await fetchFreshPosts(page, search, shouldUseCache)
  }

  const checkForUpdatesOnly = async (page = 1, search = '') => {
    try {
      const searchParams = new URLSearchParams({
        page: page.toString(),
        per_page: pagination.value.perPage.toString()
      })

      if (search.trim()) {
        searchParams.append('search', search.trim())
      }

      const url = `${API_BASE_URL}/posts?${searchParams}`
      console.log('🔍 Checking for updates from:', url)

      const response = await fetch(url, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
      })

      const data = await response.json()

      if (!response.ok) {
        throw new Error(data.message || 'Failed to check for updates')
      }

      if (data.success && data.data) {
        const freshPosts = data.data.data || []
        
        // Check if we have new posts compared to current cached posts
        const hasUpdates = checkForNewPosts(freshPosts, allPosts.value)
        
        console.log('🔍 Update check complete. Has updates:', hasUpdates)
        return { 
          success: true, 
          hasUpdates: hasUpdates,
          freshData: data.data
        }
      } else {
        throw new Error('Invalid response format')
      }
    } catch (err) {
      console.error('Error checking for updates:', err)
      return { success: false, error: err.message, hasUpdates: false }
    }
  }
  const fetchFreshPosts = async (page = 1, search = '', saveToCache = false) => {
    loading.value = true
    error.value = null
    hasNewPosts.value = false

    console.log('🌐 fetchFreshPosts called with:', { page, search, saveToCache })

    try {
      const searchParams = new URLSearchParams({
        page: page.toString(),
        per_page: pagination.value.perPage.toString()
      })

      if (search.trim()) {
        searchParams.append('search', search.trim())
      }

      const url = `${API_BASE_URL}/posts?${searchParams}`
      console.log('Fetching fresh posts from:', url)

      const response = await fetch(url, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
      })

      console.log('Response status:', response.status)
      
      const data = await response.json()
      console.log('Response data:', data)

      if (!response.ok) {
        throw new Error(data.message || 'Failed to fetch posts')
      }

      if (data.success && data.data) {
        const freshPosts = data.data.data || []
        const freshPagination = {
          currentPage: data.data.current_page || 1,
          lastPage: data.data.last_page || 1,
          perPage: data.data.per_page || 10,
          total: data.data.total || 0
        }

        // Check if we have new posts compared to current posts
        const hasUpdates = allPosts.value.length > 0 && checkForNewPosts(freshPosts, allPosts.value)
        
        // Update store with fresh data
        const oldPosts = [...allPosts.value]
        allPosts.value = freshPosts
        pagination.value = freshPagination
          // Save to cache if this is the first page with no search
        if (page === 1 && !search.trim()) {
          savePostsToCache(freshPosts, freshPagination)
          console.log('📋 Posts saved to cache for future use')
        }
        
        console.log('Successfully loaded fresh posts:', allPosts.value.length)
        return { 
          success: true, 
          data: data.data,
          fromCache: false,
          hasUpdates: hasUpdates
        }
      } else {
        throw new Error('Invalid response format')
      }
    } catch (err) {
      console.error('Error fetching fresh posts:', err)
      error.value = err.message
      
      return { success: false, error: err.message, fromCache: false }
    } finally {
      loading.value = false
    }
  }
  
  const createPost = async (postData) => {
    loading.value = true
    error.value = null

    try {
      const authToken = localStorage.getItem('auth_token')
      if (!authToken) {
        throw new Error('Authentication required')
      }

      const response = await fetch(`${API_BASE_URL}/posts`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${authToken}`
        },
        body: JSON.stringify({
          topic: postData.title,
          content: postData.content,
          tags: postData.tags || []
        })
      })

      const data = await response.json()

      if (!response.ok) {
        throw new Error(data.message || 'Failed to create post')
      }

      if (data.success) {
        // Optionally refresh posts after creating
        await fetchPosts()
        return { success: true, data: data.data }
      } else {
        throw new Error('Invalid response format')
      }
    } catch (err) {
      console.error('Error creating post:', err)
      error.value = err.message
      return { success: false, error: err.message }
    } finally {
      loading.value = false
    }
  }

  const fetchPost = async (postId) => {
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`${API_BASE_URL}/posts/${postId}`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
      })

      const data = await response.json()

      if (!response.ok) {
        throw new Error(data.message || 'Failed to fetch post')
      }

      if (data.success && data.data) {
        currentPost.value = data.data
        return { success: true, data: data.data }
      } else {
        throw new Error('Invalid response format')
      }
    } catch (err) {
      console.error('Error fetching post:', err)
      error.value = err.message
      currentPost.value = null
      return { success: false, error: err.message }
    } finally {
      loading.value = false
    }
  }

  const updatePost = async (postId, postData) => {
    loading.value = true
    error.value = null

    try {
      const authToken = localStorage.getItem('auth_token')
      if (!authToken) {
        throw new Error('Authentication required')
      }

      const response = await fetch(`${API_BASE_URL}/posts/${postId}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${authToken}`
        },
        body: JSON.stringify({
          topic: postData.title,
          content: postData.content,
          tags: postData.tags || []
        })
      })

      const data = await response.json()

      if (!response.ok) {
        throw new Error(data.message || 'Failed to update post')
      }

      if (data.success) {
        currentPost.value = data.data
        // Update the post in allPosts if it exists there
        const postIndex = allPosts.value.findIndex(p => p.PostID === postId)
        if (postIndex !== -1) {
          allPosts.value[postIndex] = data.data
        }
        return { success: true, data: data.data }
      } else {
        throw new Error('Invalid response format')
      }
    } catch (err) {
            console.error('Error updating post:', err)
      error.value = err.message
      return { success: false, error: err.message }
    } finally {
      loading.value = false
    }  }
  const refreshWithFreshPosts = async (page = 1, search = '') => {
    hasNewPosts.value = false
    const result = await fetchFreshPosts(page, search, true)
    return result
  }
  // Debug function to help test caching
  const debugCache = () => {
    const cachedPosts = localStorage.getItem(CACHE_KEYS.POSTS)
    const cachedPagination = localStorage.getItem(CACHE_KEYS.PAGINATION)
    const timestamp = localStorage.getItem(CACHE_KEYS.TIMESTAMP)
    
    console.log('🔍 Cache Debug Info:')
    console.log('- Posts:', cachedPosts ? `${JSON.parse(cachedPosts).length} posts` : 'None')
    console.log('- Pagination:', cachedPagination ? JSON.parse(cachedPagination) : 'None')
    console.log('- Timestamp:', timestamp ? new Date(parseInt(timestamp)).toLocaleString() : 'None')
    console.log('- Age:', timestamp ? `${Math.round((Date.now() - parseInt(timestamp)) / 1000)}s` : 'N/A')
  }

  // Dedicated background monitoring function
  const startBackgroundMonitoring = () => {
    const runMonitoringCycle = async () => {
      try {
        console.log('🔄 Background monitoring cycle started...')
        const result = await checkForUpdatesOnly(1, '')
        
        if (result.success && result.hasUpdates) {
          console.log('🆕 New posts detected - auto-updating cache!')
          hasNewPosts.value = true
          
          const freshResult = await fetchFreshPosts(1, '', true)
          if (freshResult.success) {
            console.log('✅ Background cache update complete!')
            console.log('🔄 UI will automatically reflect new data!')
          }
        }
        
        // Schedule next monitoring cycle
        setTimeout(runMonitoringCycle, 30000)
      } catch (error) {
        console.warn('Background monitoring cycle failed:', error)
        // Still schedule next cycle even if this one failed
        setTimeout(runMonitoringCycle, 30000)
      }
    }
    
    // Start the first cycle after 30 seconds
    setTimeout(runMonitoringCycle, 30000)
  }

  return {
    allPosts,
    currentPost,
    loading,
    error,
    pagination,
    posts,
    cacheLoading,
    hasNewPosts,
    fetchPosts,
    createPost,
    fetchPost,
    updatePost,
    loadPostsFromCache,
    clearPostsCache,
    refreshWithFreshPosts,    debugCache,
    startBackgroundMonitoring
  }
})
