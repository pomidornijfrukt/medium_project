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

  // Actions
  const fetchTags = async () => {
    loading.value = true
    error.value = null

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
        allTags.value = data.data
        return { success: true, data: data.data }
      } else {
        throw new Error('Invalid response format')
      }
    } catch (err) {
      error.value = err.message
      allTags.value = []
      return { success: false, error: err.message }
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
    clearCurrentTag
  }
})
