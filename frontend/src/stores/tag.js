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
  const cacheLoading = ref(false)
  const hasNewTags = ref(false)

  // Cache management
  const CACHE_KEYS = {
    TAGS: 'homepage_tags_cache',
    TIMESTAMP: 'homepage_tags_timestamp'  }

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
  const checkForNewTags = (freshTags, cachedTags) => {
    if (freshTags.length !== cachedTags.length) return true
    
    // Check if any tag names are different
    const freshNames = new Set(freshTags.map(tag => tag.TagName))
    const cachedNames = new Set(cachedTags.map(tag => tag.TagName))
    
    if (freshNames.size !== cachedNames.size) return true
    
    for (const name of freshNames) {
      if (!cachedNames.has(name)) return true
    }
    
    return false
  }

  // Actions
  const fetchTags = async (useCache = true) => {
    console.log('🔍 fetchTags called with:', { useCache })
    
    // Try to load from cache first
    if (useCache) {
      console.log('🔍 Attempting to load tags from cache...')
      cacheLoading.value = true
      
      // Check if cache exists and is valid
      const cachedTags = localStorage.getItem(CACHE_KEYS.TAGS)
      const timestamp = localStorage.getItem(CACHE_KEYS.TIMESTAMP)
      
      console.log('🔍 Cache data found:', { 
        hasTags: !!cachedTags, 
        hasTimestamp: !!timestamp 
      })
      
      if (cachedTags && timestamp) {
        const cacheAge = Date.now() - parseInt(timestamp)
        const cacheValidMinutes = 10
        const isCacheValid = cacheAge < cacheValidMinutes * 60 * 1000
        
        console.log('🔍 Cache age:', Math.round(cacheAge / 1000), 'seconds. Valid:', isCacheValid)
        
        if (isCacheValid) {
          try {
            const tags = JSON.parse(cachedTags)
            
            // Update store with cached data
            allTags.value = tags
            cacheLoading.value = false
            
            console.log('✅ Successfully loaded', tags.length, 'tags from cache - NO API CALL!')
            
            // Schedule background update check after 30 seconds
            setTimeout(async () => {
              try {
                console.log('🔄 Checking for tag updates in background...')
                const backgroundResult = await checkForUpdatesOnly()
                if (backgroundResult.success && backgroundResult.hasUpdates) {
                  console.log('🆕 New tags found in background!')
                  hasNewTags.value = true
                }
              } catch (error) {
                console.warn('Background tag update check failed:', error)
              }
            }, 30000)
            
            return { 
              success: true, 
              data: [...tags],
              fromCache: true,
              hasUpdates: false
            }
          } catch (parseError) {
            console.warn('Failed to parse cached tag data:', parseError)
            clearTagsCache()
          }
        } else {
          console.log('🏷️ Cache expired, clearing old cache')
          clearTagsCache()
        }
      }
      cacheLoading.value = false
    }

    // No cache available or not using cache, fetch fresh data
    console.log('🌐 Fetching fresh tags from API...')
    return await fetchFreshTags(true)
  }

  const checkForUpdatesOnly = async () => {
    try {
      const url = `${API_BASE_URL}/tags`
      console.log('🔍 Checking for tag updates from:', url)

      const response = await fetch(url, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
      })

      const data = await response.json()

      if (!response.ok) {
        throw new Error(data.message || 'Failed to check for tag updates')
      }

      if (data.success && data.data) {
        const freshTags = data.data || []
        
        // Check if we have new tags compared to current cached tags
        const hasUpdates = checkForNewTags(freshTags, allTags.value)
        
        console.log('🔍 Tag update check complete. Has updates:', hasUpdates)
        return { 
          success: true, 
          hasUpdates: hasUpdates,
          freshData: data.data
        }
      } else {
        throw new Error('Invalid response format')
      }
    } catch (err) {
      console.error('Error checking for tag updates:', err)
      return { success: false, error: err.message, hasUpdates: false }
    }
  }
  const fetchFreshTags = async (saveToCache = false) => {
    loading.value = true
    error.value = null
    hasNewTags.value = false

    console.log('🌐 fetchFreshTags called with:', { saveToCache })

    try {
      const url = `${API_BASE_URL}/tags`
      console.log('Fetching fresh tags from:', url)

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
        throw new Error(data.message || 'Failed to fetch tags')
      }

      if (data.success && data.data) {
        const freshTags = data.data || []

        // Check if we have new tags compared to current tags
        const hasUpdates = allTags.value.length > 0 && checkForNewTags(freshTags, allTags.value)
        
        // Update store with fresh data
        allTags.value = freshTags
        
        // Save to cache
        if (saveToCache) {
          saveTagsToCache(freshTags)
          console.log('🏷️ Tags saved to cache for future use')
        }
        
        console.log('Successfully loaded fresh tags:', allTags.value.length)
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
      console.error('Error fetching fresh tags:', err)
      error.value = err.message
      allTags.value = []
      return { success: false, error: err.message, fromCache: false }
    } finally {
      loading.value = false
    }
  }

  const refreshWithFreshTags = async () => {
    hasNewTags.value = false
    const result = await fetchFreshTags(true)
    return result
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
    cacheLoading,
    hasNewTags,
    
    // Actions
    fetchTags,
    fetchTag,
    fetchPostsByTag,
    clearError,
    clearCurrentTag,
    loadTagsFromCache,
    clearTagsCache,
    refreshWithFreshTags,
    debugTagsCache
  }
})
