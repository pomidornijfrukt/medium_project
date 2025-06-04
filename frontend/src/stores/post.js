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

  const posts = computed(() => allPosts.value)

  const fetchPosts = async (page = 1, search = '') => {
    loading.value = true
    error.value = null

    try {
      const searchParams = new URLSearchParams({
        page: page.toString(),
        per_page: pagination.value.perPage.toString()
      })

      if (search.trim()) {
        searchParams.append('search', search.trim())
      }

      const url = `${API_BASE_URL}/posts?${searchParams}`
      console.log('Fetching posts from:', url)

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
        allPosts.value = data.data.data || []
        
        pagination.value = {
          currentPage: data.data.current_page || 1,
          lastPage: data.data.last_page || 1,
          perPage: data.data.per_page || 10,
          total: data.data.total || 0
        }
        
        console.log('Successfully loaded posts:', allPosts.value.length)
        return { success: true, data: data.data }
      } else {
        throw new Error('Invalid response format')
      }
    } catch (err) {
      console.error('Error fetching posts:', err)
      error.value = err.message
      allPosts.value = []
      return { success: false, error: err.message }
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
    }
  }
  return {
    allPosts,
    currentPost,
    loading,
    error,
    pagination,
    posts,
    fetchPosts,
    createPost,
    fetchPost,
    updatePost
  }
})
