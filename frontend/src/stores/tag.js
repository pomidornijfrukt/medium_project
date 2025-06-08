import { ref, computed } from 'vue'
import { defineStore } from 'pinia'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:6969/api'

export const useTagStore = defineStore('tag', () => {
  // State
  const allTags = ref([])
  const currentTag = ref(null)
  const tagPosts = ref([])
  const loading = ref(false)
  const error = ref(null)

  // Cache management
  const CACHE_KEYS = {
    TAGS: 'homepage_tags_cache',
    TIMESTAMP: 'homepage_tags_timestamp'
  }

  const saveTagsToCache = (tags) => {
    try {
      localStorage.setItem(CACHE_KEYS.TAGS, JSON.stringify(tags))
      localStorage.setItem(CACHE_KEYS.TIMESTAMP, Date.now().toString())
      console.log('🏷️ Tags saved to cache:', tags.length)
    } catch (error) {
      console.warn('Failed to save tags to cache:', error)
    }
  }

  const loadTagsFromCache = () => {
    try {
      const cachedTags = localStorage.getItem(CACHE_KEYS.TAGS)
      const timestamp = localStorage.getItem(CACHE_KEYS.TIMESTAMP)

      if (cachedTags && timestamp) {
        const tags = JSON.parse(cachedTags)
        const cacheAge = Date.now() - parseInt(timestamp)
        
        // Use cache if it's less than 10 minutes old (tags change less frequently)
        if (cacheAge < 10 * 60 * 1000) {
          allTags.value = tags
          console.log('🏷️ Loaded tags from cache:', tags.length)
          return true
        } else {
          console.log('🏷️ Cache expired, will fetch fresh data')
          clearTagsCache()
        }
      }
      return false
    } catch (error) {
      console.warn('Failed to load tags from cache:', error)
      return false
    }
  }

  const clearTagsCache = () => {
    try {
      localStorage.removeItem(CACHE_KEYS.TAGS)
      localStorage.removeItem(CACHE_KEYS.TIMESTAMP)
      console.log('🏷️ Tags cache cleared')
    } catch (error) {
      console.warn('Failed to clear tags cache:', error)
    }
  }

  // Actions - Simplified fetchTags without monitoring
  const fetchTags = async (useCache = true) => {
    error.value = null

    // Cache-first approach: check cache without loading state
    if (useCache) {
      const cacheLoaded = loadTagsFromCache()
      if (cacheLoaded) {
        console.log('✅ Tags loaded from cache immediately - NO LOADING SCREEN!')
        return { 
          success: true, 
          data: [...allTags.value],
          fromCache: true
        }
      }
    }

    // No cache available - show loading and fetch fresh data
    console.log('🌐 No cached tags, showing loading and fetching from API...')
    loading.value = true
    
    try {
      const response = await fetch(`${API_BASE_URL}/tags`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
      })

      const data = await response.json()

      if (!response.ok) {
        throw new Error(data.message || 'Failed to fetch tags')
      }

      if (data.success && data.data) {
        const freshTags = data.data || []
        allTags.value = freshTags
        saveTagsToCache(freshTags)
        
        console.log('✅ Fresh tags loaded and cached:', freshTags.length)
        return { 
          success: true, 
          data: data.data,
          fromCache: false
        }
      } else {
        throw new Error('Invalid response format')
      }
    } catch (err) {
      console.error('Error fetching tags:', err)
      error.value = err.message
      allTags.value = []
      return { success: false, error: err.message, fromCache: false }
    } finally {
      loading.value = false
    }
  }

  const fetchTag = async (tagName) => {
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`${API_BASE_URL}/tags/${tagName}`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
      })

      const data = await response.json()

      if (!response.ok) {
        throw new Error(data.message || 'Failed to fetch tag')
      }

      if (data.success && data.data) {
        currentTag.value = data.data
        return { success: true, data: data.data }
      } else {
        throw new Error('Invalid response format')
      }
    } catch (err) {
      error.value = err.message
      currentTag.value = null
      return { success: false, error: err.message }
    } finally {
      loading.value = false
    }
  }

  const fetchPostsByTag = async (tagName, page = 1) => {
    loading.value = true
    error.value = null

    try {
      const searchParams = new URLSearchParams({
        page: page.toString()
      })

      const response = await fetch(`${API_BASE_URL}/tags/${tagName}/posts?${searchParams}`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
      })

      const data = await response.json()

      if (!response.ok) {
        throw new Error(data.message || 'Failed to fetch posts by tag')
      }

      if (data.success && data.data) {
        tagPosts.value = data.data
        return { success: true, data: data.data }
      } else {
        throw new Error('Invalid response format')
      }
    } catch (err) {
      error.value = err.message
      tagPosts.value = []
      return { success: false, error: err.message }
    } finally {
      loading.value = false
    }
  }

  const clearError = () => {
    error.value = null
  }

  const clearCurrentTag = () => {
    currentTag.value = null
    tagPosts.value = []
  }

  // Debug function to help test caching
  const debugTagsCache = () => {
    const cachedTags = localStorage.getItem(CACHE_KEYS.TAGS)
    const timestamp = localStorage.getItem(CACHE_KEYS.TIMESTAMP)
    
    console.log('🔍 Tags Cache Debug Info:')
    console.log('- Tags:', cachedTags ? `${JSON.parse(cachedTags).length} tags` : 'None')
    console.log('- Timestamp:', timestamp ? new Date(parseInt(timestamp)).toLocaleString() : 'None')
    console.log('- Age:', timestamp ? `${Math.round((Date.now() - parseInt(timestamp)) / 1000)}s` : 'N/A')
  }

  return {
    // State
    allTags,
    currentTag,
    tagPosts,
    loading,
    error,
    
    // Actions
    fetchTags,
    fetchTag,
    fetchPostsByTag,
    clearError,
    clearCurrentTag,
    loadTagsFromCache,
    clearTagsCache,
    debugTagsCache
  }
})
