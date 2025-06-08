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
  const hasNewPosts = ref(false)

  const posts = computed(() => allPosts.value)  // Improved cache management with longer timeouts and smarter invalidation
  const CACHE_KEYS = {
    POSTS: 'homepage_posts_cache',
    PAGINATION: 'homepage_pagination_cache',
    TIMESTAMP: 'homepage_cache_timestamp',
    VERSION: 'homepage_cache_version',
    // Per-page cache keys
    PAGE_PREFIX: 'homepage_page_',
    PAGE_PAGINATION_PREFIX: 'homepage_page_pagination_',
    PAGE_TIMESTAMP_PREFIX: 'homepage_page_timestamp_'
  }

  const savePostsToCache = (posts, paginationData) => {
    try {
      const cacheData = {
        posts,
        pagination: paginationData,
        timestamp: Date.now(),
        version: '1.0',
        userAgent: navigator.userAgent.slice(0, 50) // Detect if different device
      }
      
      localStorage.setItem(CACHE_KEYS.POSTS, JSON.stringify(cacheData.posts))
      localStorage.setItem(CACHE_KEYS.PAGINATION, JSON.stringify(cacheData.pagination))
      localStorage.setItem(CACHE_KEYS.TIMESTAMP, cacheData.timestamp.toString())
      localStorage.setItem(CACHE_KEYS.VERSION, cacheData.version)
      
      console.log('📋 Posts saved to cache:', posts.length, 'posts')
    } catch (error) {
      console.warn('Failed to save posts to cache:', error)
      // Clear corrupt cache
      clearPostsCache()
    }
  }
  const loadPostsFromCache = () => {
    try {
      const cachedPosts = localStorage.getItem(CACHE_KEYS.POSTS)
      const cachedPagination = localStorage.getItem(CACHE_KEYS.PAGINATION)
      const timestamp = localStorage.getItem(CACHE_KEYS.TIMESTAMP)
      const version = localStorage.getItem(CACHE_KEYS.VERSION)

      if (cachedPosts && cachedPagination && timestamp && version) {
        const posts = JSON.parse(cachedPosts)
        const paginationData = JSON.parse(cachedPagination)
        const cacheAge = Date.now() - parseInt(timestamp)
        
        // Use same cache validity as in fetchFreshPosts: 15 minutes
        const cacheValidMinutes = 15
        const isCacheValid = cacheAge < cacheValidMinutes * 60 * 1000
        
        // Validate cache version and structure
        const isVersionValid = version === '1.0'
        const isDataValid = Array.isArray(posts) && posts.length > 0 && paginationData.total >= 0
        
        if (isCacheValid && isVersionValid && isDataValid) {
          allPosts.value = posts
          pagination.value = paginationData
          console.log('📋 Loaded', posts.length, 'posts from cache (age:', Math.round(cacheAge / 1000), 'seconds)')
          return true
        } else {
          console.log('📋 Cache invalid:', { isCacheValid, isVersionValid, isDataValid })
          clearPostsCache()
        }
      }
      return false
    } catch (error) {
      console.warn('Failed to load posts from cache:', error)
      clearPostsCache()
      return false
    }
  }
  const clearPostsCache = () => {
    try {
      localStorage.removeItem(CACHE_KEYS.POSTS)
      localStorage.removeItem(CACHE_KEYS.PAGINATION)
      localStorage.removeItem(CACHE_KEYS.TIMESTAMP)
      localStorage.removeItem(CACHE_KEYS.VERSION)
      console.log('📋 Posts cache cleared')
    } catch (error) {
      console.warn('Failed to clear posts cache:', error)
    }
  }

  // Per-page cache functions for smart pagination
  const savePageToCache = (page, posts, paginationData) => {
    try {
      const cacheData = {
        posts,
        pagination: paginationData,
        timestamp: Date.now(),
        version: '1.0'
      }
      
      localStorage.setItem(`${CACHE_KEYS.PAGE_PREFIX}${page}`, JSON.stringify(cacheData.posts))
      localStorage.setItem(`${CACHE_KEYS.PAGE_PAGINATION_PREFIX}${page}`, JSON.stringify(cacheData.pagination))
      localStorage.setItem(`${CACHE_KEYS.PAGE_TIMESTAMP_PREFIX}${page}`, cacheData.timestamp.toString())
      
      console.log(`📋 Page ${page} saved to cache:`, posts.length, 'posts')
    } catch (error) {
      console.warn(`Failed to save page ${page} to cache:`, error)
    }
  }

  const loadPageFromCache = (page) => {
    try {
      const cachedPosts = localStorage.getItem(`${CACHE_KEYS.PAGE_PREFIX}${page}`)
      const cachedPagination = localStorage.getItem(`${CACHE_KEYS.PAGE_PAGINATION_PREFIX}${page}`)
      const timestamp = localStorage.getItem(`${CACHE_KEYS.PAGE_TIMESTAMP_PREFIX}${page}`)

      if (cachedPosts && cachedPagination && timestamp) {
        const posts = JSON.parse(cachedPosts)
        const paginationData = JSON.parse(cachedPagination)
        const cacheAge = Date.now() - parseInt(timestamp)
        
        // Use same cache validity as main cache: 15 minutes
        const cacheValidMinutes = 15
        const isCacheValid = cacheAge < cacheValidMinutes * 60 * 1000
        
        // Validate cache structure
        const isDataValid = Array.isArray(posts) && posts.length > 0 && paginationData.total >= 0
        
        if (isCacheValid && isDataValid) {
          allPosts.value = posts
          pagination.value = paginationData
          console.log(`📋 Loaded page ${page} from cache (age: ${Math.round(cacheAge / 1000)} seconds)`)
          return true
        } else {
          console.log(`📋 Page ${page} cache invalid:`, { isCacheValid, isDataValid })
          clearPageCache(page)
        }
      }
      return false
    } catch (error) {
      console.warn(`Failed to load page ${page} from cache:`, error)
      clearPageCache(page)
      return false
    }
  }

  const clearPageCache = (page) => {
    try {
      localStorage.removeItem(`${CACHE_KEYS.PAGE_PREFIX}${page}`)
      localStorage.removeItem(`${CACHE_KEYS.PAGE_PAGINATION_PREFIX}${page}`)
      localStorage.removeItem(`${CACHE_KEYS.PAGE_TIMESTAMP_PREFIX}${page}`)
      console.log(`📋 Page ${page} cache cleared`)
    } catch (error) {
      console.warn(`Failed to clear page ${page} cache:`, error)
    }
  }

  const clearAllPageCaches = () => {
    try {
      // Clear up to 20 pages (should be more than enough)
      for (let i = 1; i <= 20; i++) {
        clearPageCache(i)
      }
      console.log('📋 All page caches cleared')
    } catch (error) {
      console.warn('Failed to clear all page caches:', error)
    }
  }

  const checkForNewPosts = (freshPosts, cachedPosts) => {
    if (freshPosts.length !== cachedPosts.length) return true    // Check if the first few posts are the same (most recent posts)
    for (let i = 0; i < Math.min(3, freshPosts.length); i++) {
      if (freshPosts[i].PostID !== cachedPosts[i].PostID) return true
      if (new Date(freshPosts[i].updated_at) > new Date(cachedPosts[i].updated_at)) return true
    }
    return false
  }

  const fetchPosts = async (page = 1, search = '', useCache = true) => {
    console.log('🔍 fetchPosts called with:', { page, search, useCache })
    error.value = null

    // Smart caching: use cache for any page if no search and cache is enabled
    const shouldUseCache = useCache && !search.trim() && !hasNewPosts.value
    
    if (shouldUseCache) {
      console.log(`🔍 Attempting cache-first load for page ${page}...`)
      
      let cacheLoaded = false
      
      // For page 1, use the main cache
      if (page === 1) {
        cacheLoaded = loadPostsFromCache()
        if (cacheLoaded) {
          console.log('✅ Page 1 loaded from main cache - NO LOADING SCREEN!')
          // Start background monitoring for updates (silent)
          startBackgroundMonitoring()
        }
      } else {
        // For other pages, use per-page cache
        cacheLoaded = loadPageFromCache(page)
        if (cacheLoaded) {
          console.log(`✅ Page ${page} loaded from per-page cache - NO LOADING SCREEN!`)
        }
      }
      
      if (cacheLoaded) {
        return { 
          success: true, 
          data: { data: [...allPosts.value] },
          fromCache: true
        }
      }
    } else {
      // Cache bypassed - either no useCache flag, has search, or new posts detected
      if (hasNewPosts.value) {
        console.log('🚀 Bypassing cache due to new posts detected in background!')
      } else if (search.trim()) {
        console.log('🚀 Bypassing cache due to search query')
      } else {
        console.log('🚀 Bypassing cache - useCache = false')
      }
    }

    // No cache available or not using cache, fetch fresh data with loading state
    console.log(`🌐 No cached data for page ${page}, showing loading and fetching from API...`)
    return await fetchFreshPosts(page, search, !search.trim())
  }
  // Smart update checking with visibility-aware logic
  const checkForUpdatesOnly = async (page = 1, search = '') => {
    try {
      // Don't check if page is not visible (tab is inactive)
      if (document.hidden) {
        console.log('📋 Skipping update check - page not visible')
        return { success: true, hasUpdates: false, skipped: true }
      }

      const searchParams = new URLSearchParams({
        page: page.toString(),
        per_page: pagination.value.perPage.toString()
      })

      if (search.trim()) {
        searchParams.append('search', search.trim())
      }

      // Add timestamp to prevent caching at network level
      searchParams.append('t', Date.now().toString())

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
        }        // Check if we have new posts compared to current posts
        const hasUpdates = allPosts.value.length > 0 && checkForNewPosts(freshPosts, allPosts.value)
          // Update store with fresh data
        const oldPosts = [...allPosts.value]
        allPosts.value = freshPosts
        pagination.value = freshPagination
        
        // Save to cache based on page and search criteria
        if (!search.trim()) {
          if (page === 1) {
            // Save page 1 to main cache
            savePostsToCache(freshPosts, freshPagination)
            console.log('📋 Page 1 posts saved to main cache for future use')
          } else {
            // Save other pages to per-page cache
            savePageToCache(page, freshPosts, freshPagination)
            console.log(`📋 Page ${page} posts saved to per-page cache for future use`)
          }
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

  // Smart cached post retrieval that avoids unnecessary API calls
  const getPostFromCache = (postId) => {
    try {
      // Convert postId to number for comparison
      const numericPostId = parseInt(postId)
      
      // First check if the post is in our current allPosts cache
      const cachedPost = allPosts.value.find(post => post.PostID === numericPostId)
      if (cachedPost) {
        console.log('📋 Found post in current cache:', cachedPost.PostID)
        return {
          success: true,
          data: cachedPost,
          fromCache: true
        }
      }
      
      // If not in current cache, check localStorage for a more comprehensive cache
      const cachedPosts = localStorage.getItem(CACHE_KEYS.POSTS)
      if (cachedPosts) {
        try {
          const posts = JSON.parse(cachedPosts)
          const cachedPost = posts.find(post => post.PostID === numericPostId)
          if (cachedPost) {
            console.log('📋 Found post in localStorage cache:', cachedPost.PostID)
            return {
              success: true,
              data: cachedPost,
              fromCache: true
            }
          }
        } catch (parseError) {
          console.warn('Failed to parse cached posts for individual post lookup:', parseError)
        }
      }
      
      return { success: false, fromCache: false }
    } catch (error) {
      console.warn('Error checking cached post:', error)
      return { success: false, fromCache: false }
    }
  }

  // Build reply chain from cached data instead of making API calls
  const buildReplyChainFromCache = (postId) => {
    try {
      const numericPostId = parseInt(postId)
      const allCachedPosts = [...allPosts.value]
      
      // Also check localStorage for more posts
      const localCachedPosts = localStorage.getItem(CACHE_KEYS.POSTS)
      if (localCachedPosts) {
        try {
          const localPosts = JSON.parse(localCachedPosts)
          allCachedPosts.push(...localPosts.filter(lp => 
            !allCachedPosts.some(cp => cp.PostID === lp.PostID)
          ))
        } catch (parseError) {
          console.warn('Failed to parse localStorage for reply chain:', parseError)
        }
      }
      
      // Find all replies to this post recursively
      const findRepliesRecursively = (parentId, depth = 0, maxDepth = 10) => {
        if (depth >= maxDepth) return []
        
        const directReplies = allCachedPosts.filter(post => 
          post.ParentPostID === parentId && post.PostID !== parentId
        )
        
        const repliesWithNested = directReplies.map(reply => ({
          ...reply,
          nested_replies: findRepliesRecursively(reply.PostID, depth + 1, maxDepth)
        }))
        
        return repliesWithNested.sort((a, b) => 
          new Date(a.created_at) - new Date(b.created_at)
        )
      }
      
      const replies = findRepliesRecursively(numericPostId)
      console.log(`📋 Built ${replies.length} replies from cache for post ${postId}`)
      
      return {
        success: true,
        data: replies,
        fromCache: true
      }
    } catch (error) {
      console.warn('Error building reply chain from cache:', error)
      return { success: false, fromCache: false, data: [] }
    }
  }

  // Build post hierarchy from cached data for breadcrumb navigation
  const buildPostHierarchyFromCache = (postId) => {
    try {
      const numericPostId = parseInt(postId)
      const allCachedPosts = [...allPosts.value]
      
      // Also check localStorage
      const localCachedPosts = localStorage.getItem(CACHE_KEYS.POSTS)
      if (localCachedPosts) {
        try {
          const localPosts = JSON.parse(localCachedPosts)
          allCachedPosts.push(...localPosts.filter(lp => 
            !allCachedPosts.some(cp => cp.PostID === lp.PostID)
          ))
        } catch (parseError) {
          console.warn('Failed to parse localStorage for hierarchy:', parseError)
        }
      }
      
      const hierarchy = []
      let currentPost = allCachedPosts.find(post => post.PostID === numericPostId)
      
      // Build hierarchy by following parent chain
      while (currentPost && currentPost.ParentPostID) {
        const parentPost = allCachedPosts.find(post => post.PostID === currentPost.ParentPostID)
        if (parentPost && !hierarchy.some(h => h.PostID === parentPost.PostID)) {
          hierarchy.unshift({
            PostID: parentPost.PostID,
            Topic: parentPost.Topic,
            author: parentPost.author
          })
          currentPost = parentPost
        } else {
          break // Prevent infinite loops
        }
      }
      
      console.log(`📋 Built hierarchy with ${hierarchy.length} levels from cache`)
      return {
        success: true,
        data: hierarchy,
        fromCache: true
      }
    } catch (error) {      console.warn('Error building post hierarchy from cache:', error)
      return { success: false, fromCache: false, data: [] }
    }
  }

  // Background fetch without loading states - for silent cache updates
  const fetchPostInBackground = async (postId) => {
    try {
      console.log('🔄 Background update for post:', postId)
      
      const response = await fetch(`${API_BASE_URL}/posts/${postId}`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
      })

      const data = await response.json()

      if (response.ok && data.success && data.data) {
        // Silently update cache
        const existingIndex = allPosts.value.findIndex(p => p.PostID === data.data.PostID)
        if (existingIndex >= 0) {
          allPosts.value[existingIndex] = data.data
        } else {
          allPosts.value.push(data.data)
        }

        // If the post includes linked posts (replies), cache them for future use
        if (data.data.linkedPosts || data.data.linked_posts) {
          const linkedPosts = data.data.linkedPosts || data.data.linked_posts
          console.log('📋 Background caching', linkedPosts.length, 'linked posts')
          
          linkedPosts.forEach(linkedPost => {
            const existingLinkIndex = allPosts.value.findIndex(p => p.PostID === linkedPost.PostID)
            if (existingLinkIndex === -1) {
              allPosts.value.push(linkedPost)
            } else {
              allPosts.value[existingLinkIndex] = linkedPost
            }
          })
        }

        console.log('✅ Background update completed for post:', postId)
      }
    } catch (err) {
      console.warn('Background update failed for post:', postId, err.message)
    }
  }

  const fetchPost = async (postId, useCache = true) => {
    error.value = null

    // Cache-first approach: check cache without loading state
    if (useCache) {
      const cachedResult = getPostFromCache(postId)
      if (cachedResult.success) {
        currentPost.value = cachedResult.data
        console.log('📦 Fetched post from cache:', postId)
        
        // Background update without loading screen
        fetchPostInBackground(postId)
        
        return { success: true, data: cachedResult.data, fromCache: true }
      }
    }

    // Only show loading when no cache data exists
    loading.value = true

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
      }      if (data.success && data.data) {
        currentPost.value = data.data
        
        // If the post includes linked posts (replies), cache them for future use
        if (data.data.linkedPosts || data.data.linked_posts) {
          const linkedPosts = data.data.linkedPosts || data.data.linked_posts
          console.log('📋 Caching', linkedPosts.length, 'linked posts from API response')
          
          // Add linked posts to allPosts cache if they don't already exist
          linkedPosts.forEach(linkedPost => {
            const existingIndex = allPosts.value.findIndex(p => p.PostID === linkedPost.PostID)
            if (existingIndex === -1) {
              allPosts.value.push(linkedPost)
            } else {
              // Update existing post with newer data
              allPosts.value[existingIndex] = linkedPost
            }
          })
          
          // Also add the main post to cache if not already there
          const mainPostIndex = allPosts.value.findIndex(p => p.PostID === data.data.PostID)
          if (mainPostIndex === -1) {
            allPosts.value.push(data.data)
          } else {
            allPosts.value[mainPostIndex] = data.data
          }
        }
        
        console.log('📦 Fetched post from API:', postId)
        return { success: true, data: data.data, fromCache: false }
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
    }
  }

  const createReply = async (parentPostId, replyData) => {
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
          topic: replyData.title,
          content: replyData.content,
          tags: replyData.tags || [],
          parent_post_id: parentPostId
        })
      })

      const data = await response.json()

      if (!response.ok) {
        throw new Error(data.message || 'Failed to create reply')
      }

      if (data.success) {
        // Add the new reply to the cache
        if (data.data) {
          allPosts.value.push(data.data)
          console.log('📋 Added new reply to cache:', data.data.PostID)
        }
        
        // Mark that we have new posts to trigger cache refresh on next fetch
        hasNewPosts.value = true
        
        return { success: true, data: data.data }
      } else {
        throw new Error('Invalid response format')
      }
    } catch (err) {
      console.error('Error creating reply:', err)
      error.value = err.message
      return { success: false, error: err.message }
    } finally {
      loading.value = false
    }
  }

  const fetchLinkedPosts = async (postId) => {
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`${API_BASE_URL}/posts/${postId}/linked`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
      })

      const data = await response.json()

      if (!response.ok) {
        throw new Error(data.message || 'Failed to fetch replies')
      }

      if (data.success) {
        return { success: true, data: data.data }
      } else {
        throw new Error('Invalid response format')
      }
    } catch (err) {
      console.error('Error fetching replies:', err)
      error.value = err.message
      return { success: false, error: err.message }
    } finally {
      loading.value = false
    }
  }

  const deletePost = async (postId) => {
    loading.value = true
    error.value = null

    try {
      const authToken = localStorage.getItem('auth_token')
      if (!authToken) {
        throw new Error('Authentication required')
      }

      const response = await fetch(`${API_BASE_URL}/posts/${postId}`, {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${authToken}`
        }
      })

      const data = await response.json()

      if (!response.ok) {
        throw new Error(data.message || 'Failed to delete post')
      }

      if (data.success) {
        // Remove post from allPosts if it exists
        const postIndex = allPosts.value.findIndex(p => p.PostID === postId)
        if (postIndex !== -1) {
          allPosts.value.splice(postIndex, 1)
        }
        // Clear current post if it's the one being deleted
        if (currentPost.value && currentPost.value.PostID === postId) {
          currentPost.value = null
        }
        return true
      } else {
        throw new Error('Invalid response format')
      }
    } catch (err) {
      console.error('Error deleting post:', err)
      error.value = err.message
      return false
    } finally {
      loading.value = false
    }
  }

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
  // Smart background monitoring with adaptive timing and visibility awareness
  const startBackgroundMonitoring = () => {
    let monitoringInterval = null
    let currentInterval = 120000 // Start with 2 minutes instead of 30 seconds
    let consecutiveNoUpdates = 0
    let consecutiveErrors = 0
    let isMonitoring = false
    let isNetworkAvailable = navigator.onLine
    
    // Network status monitoring
    const handleOnline = () => {
      console.log('🌐 Network connection restored - resuming monitoring')
      isNetworkAvailable = true
      consecutiveErrors = 0
      currentInterval = 120000 // Reset interval when network comes back
      
      // Resume monitoring immediately when network is restored
      if (!document.hidden && !isMonitoring) {
        setTimeout(() => runMonitoringCycle(), 1000)
      }
    }
    
    const handleOffline = () => {
      console.log('🌐 Network connection lost - pausing monitoring')
      isNetworkAvailable = false
      if (monitoringInterval) {
        clearTimeout(monitoringInterval)
        monitoringInterval = null
      }
    }
    
    // Exponential backoff for errors
    const calculateErrorBackoff = (errorCount) => {
      return Math.min(30000 * Math.pow(2, errorCount), 300000) // 30s, 1m, 2m, 4m, max 5m
    }
    
    const runMonitoringCycle = async () => {
      // Don't run if page is hidden, already monitoring, or network is offline
      if (document.hidden || isMonitoring || !isNetworkAvailable) {
        console.log('📋 Skipping monitoring cycle - page hidden, already running, or offline')
        return
      }
      
      isMonitoring = true
      
      try {
        console.log(`🔄 Background monitoring cycle started (interval: ${currentInterval/1000}s, errors: ${consecutiveErrors})...`)
        const result = await checkForUpdatesOnly(1, '')
        
        if (result.skipped) {
          console.log('📋 Monitoring cycle skipped - page not visible')
          return
        }
        
        // Reset error count on successful check
        consecutiveErrors = 0
          if (result.success && result.hasUpdates) {
          console.log('🆕 New posts detected - updating cache!')
          hasNewPosts.value = true
          
          // Clear all caches when new posts are detected
          clearPostsCache()
          clearAllPageCaches()
          
          consecutiveNoUpdates = 0
          currentInterval = 120000 // Reset to 2 minutes when updates found
          
          const freshResult = await fetchFreshPosts(1, '', true)
          if (freshResult.success) {
            console.log('✅ Background cache update complete!')
          }
        } else if (result.success) {
          consecutiveNoUpdates++
          console.log(`📋 No updates found (${consecutiveNoUpdates} consecutive)`)
          
          // Adaptive timing: increase interval if no updates for a while
          if (consecutiveNoUpdates >= 3) {
            currentInterval = Math.min(currentInterval * 1.5, 600000) // Max 10 minutes
            console.log(`📋 Increased monitoring interval to ${currentInterval/1000}s`)
          }
        }
      } catch (error) {
        consecutiveErrors++
        console.warn(`Background monitoring cycle failed (${consecutiveErrors} consecutive):`, error)
        
        // Use exponential backoff for errors
        const errorBackoff = calculateErrorBackoff(consecutiveErrors)
        currentInterval = errorBackoff
        console.log(`📋 Error backoff: will retry in ${errorBackoff/1000}s`)
        
        // If too many consecutive errors, wait longer before next attempt
        if (consecutiveErrors >= 5) {
          console.warn('📋 Too many consecutive errors, reducing monitoring frequency')
          currentInterval = 600000 // 10 minutes for persistent errors
        }
      } finally {
        isMonitoring = false
        
        // Schedule next cycle directly without recursion
        if (monitoringInterval) {
          clearTimeout(monitoringInterval)
        }
        monitoringInterval = setTimeout(() => {
          runMonitoringCycle()
        }, currentInterval)
      }
    }    
    
    // Page Visibility API integration
    const handleVisibilityChange = () => {
      if (document.hidden) {
        console.log('📋 Page hidden - pausing background monitoring')
        if (monitoringInterval) {
          clearTimeout(monitoringInterval)
          monitoringInterval = null
        }
      } else {
        console.log('📋 Page visible - resuming background monitoring')
        consecutiveNoUpdates = 0 // Reset when user returns
        consecutiveErrors = 0 // Reset errors when user returns
        currentInterval = 120000 // Reset to shorter interval
        
        // Start monitoring with initial delay when page becomes visible
        if (monitoringInterval) {
          clearTimeout(monitoringInterval)
        }
        monitoringInterval = setTimeout(() => {
          runMonitoringCycle()
        }, 1000)
      }
    }
    
    // Set up event listeners
    document.addEventListener('visibilitychange', handleVisibilityChange)
    window.addEventListener('online', handleOnline)
    window.addEventListener('offline', handleOffline)
    
    // Start initial monitoring if page is visible and online
    if (!document.hidden && isNetworkAvailable) {
      monitoringInterval = setTimeout(() => {
        runMonitoringCycle()
      }, currentInterval)
    }
    
    // Return cleanup function
    return () => {
      if (monitoringInterval) {
        clearTimeout(monitoringInterval)
      }
      document.removeEventListener('visibilitychange', handleVisibilityChange)
      window.removeEventListener('online', handleOnline)
      window.removeEventListener('offline', handleOffline)
    }
  }
  
  // Performance metrics tracking
  const performanceMetrics = ref({
    cacheHitRate: 0,
    averageApiTime: 0,
    backgroundUpdateCount: 0,
    errorRate: 0,
    totalRequests: 0,
    cacheHits: 0,
    apiErrors: 0
  })
  
  const updatePerformanceMetrics = (isCache, apiTime = 0, isError = false) => {
    performanceMetrics.value.totalRequests++
    
    if (isCache) {
      performanceMetrics.value.cacheHits++
    }
    
    if (apiTime > 0) {
      const currentAvg = performanceMetrics.value.averageApiTime
      const totalApiRequests = performanceMetrics.value.totalRequests - performanceMetrics.value.cacheHits
      performanceMetrics.value.averageApiTime = totalApiRequests > 1 
        ? (currentAvg * (totalApiRequests - 1) + apiTime) / totalApiRequests
        : apiTime
    }
    
    if (isError) {
      performanceMetrics.value.apiErrors++
    }
    
    // Calculate rates
    performanceMetrics.value.cacheHitRate = 
      (performanceMetrics.value.cacheHits / performanceMetrics.value.totalRequests) * 100
    performanceMetrics.value.errorRate = 
      (performanceMetrics.value.apiErrors / performanceMetrics.value.totalRequests) * 100
  }
    const getPerformanceReport = () => {
    const metrics = performanceMetrics.value
    console.log('📊 Post Store Performance Metrics:')
    console.log(`- Cache Hit Rate: ${metrics.cacheHitRate.toFixed(1)}%`)
    console.log(`- Average API Time: ${metrics.averageApiTime.toFixed(0)}ms`)
    console.log(`- Background Updates: ${metrics.backgroundUpdateCount}`)
    console.log(`- Error Rate: ${metrics.errorRate.toFixed(1)}%`)
    console.log(`- Total Requests: ${metrics.totalRequests}`)
    
    return metrics
  }
  
  return {
    allPosts,
    currentPost,
    loading,
    error,
    pagination,
    posts,
    hasNewPosts,
    fetchPosts,
    createPost,
    fetchPost,
    updatePost,
    loadPostsFromCache,
    clearPostsCache,
    refreshWithFreshPosts,
    debugCache,
    startBackgroundMonitoring,
    createReply,
    fetchLinkedPosts,
    deletePost,
    getPerformanceReport,
    // Export cache helper functions
    getPostFromCache,
    buildReplyChainFromCache,
    buildPostHierarchyFromCache
  }
})
