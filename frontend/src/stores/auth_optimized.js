import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

// API base URL configuration
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:6969/api'

// Create a singleton HTTP client with optimizations
class OptimizedHttpClient {
  constructor() {
    this.defaultHeaders = {
      'Accept': 'application/json',
      'Cache-Control': 'no-cache',
      'Connection': 'keep-alive'
    }
  }

  async request(url, options = {}) {
    const controller = new AbortController()
    const timeoutId = setTimeout(() => controller.abort(), 8000) // 8-second timeout

    try {
      const startTime = performance.now()
      
      const response = await fetch(url, {
        ...options,
        headers: {
          ...this.defaultHeaders,
          ...options.headers
        },
        signal: controller.signal
      })

      clearTimeout(timeoutId)
      const endTime = performance.now()
      console.log(`🚀 API Request to ${url} completed in ${Math.round(endTime - startTime)}ms`)

      return response
    } catch (error) {
      clearTimeout(timeoutId)
      if (error.name === 'AbortError') {
        throw new Error('Request timed out. Please check your connection.')
      }
      throw error
    }
  }
}

const httpClient = new OptimizedHttpClient()

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('auth_token'))
  const loading = ref(false)
  const error = ref(null)
  const initialized = ref(false)

  // Only consider user logged in if we have both token AND user data AND initialization is complete
  const isLoggedIn = computed(() => {
    return initialized.value && !!token.value && !!user.value
  })
  
  const isAdmin = computed(() => {
    return isLoggedIn.value && user.value?.Role === 'admin'
  })

  // Initialize user data if token exists
  const initializeAuth = async () => {
    console.log('🔐 Initializing auth state...')
    
    // If no token, mark as initialized and return
    if (!token.value) {
      console.log('🔐 No token found, setting initialized to true')
      initialized.value = true
      return
    }

    // If we already have user data, mark as initialized
    if (user.value) {
      console.log('🔐 User data already exists, setting initialized to true')
      initialized.value = true
      return
    }

    // Try to fetch user data with the token
    try {
      console.log('🔐 Fetching user data with token...', token.value.substring(0, 10) + '...')
      
      const response = await httpClient.request(`${API_BASE_URL}/user`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${token.value}`
        }
      })

      console.log('🔐 API Response status:', response.status)
      
      if (response.ok) {
        const data = await response.json()
        console.log('🔐 User data fetched successfully:', data)
        
        // Handle the response structure from the backend
        if (data.success && data.data && data.data.user) {
          user.value = data.data.user
          initialized.value = true
          console.log('🔐 User authenticated successfully:', user.value.Username)
        } else {
          console.log('🔐 Invalid response structure:', data)
          clearAuthData()
        }
      } else {
        const errorText = await response.text()
        console.log('🔐 Token validation failed, status:', response.status, 'Response:', errorText)
        // Token is invalid, clear everything
        clearAuthData()
      }
    } catch (err) {
      console.error('🔐 Failed to initialize auth:', err)
      console.error('🔐 Error details:', err.message)
      error.value = err.message
      // Network error or other issue, clear auth data
      clearAuthData()
    }
  }

  // Helper function to clear all auth data
  const clearAuthData = () => {
    token.value = null
    user.value = null
    initialized.value = true
    localStorage.removeItem('auth_token')
  }

  const register = async (userData) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await httpClient.request(`${API_BASE_URL}/register`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          username: userData.username,
          email: userData.email,
          password: userData.password,
          password_confirmation: userData.password_confirmation
        })
      })

      const data = await response.json()

      if (response.ok && data.success) {
        token.value = data.data.access_token
        user.value = data.data.user
        initialized.value = true
        localStorage.setItem('auth_token', data.data.access_token)
        return { success: true }
      } else {
        error.value = data.message || data.errors || 'Registration failed'
        return { success: false, error: error.value }
      }
    } catch (err) {
      error.value = err.message || 'Network error occurred'
      return { success: false, error: error.value }
    } finally {
      loading.value = false
    }
  }

  const login = async (credentials) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await httpClient.request(`${API_BASE_URL}/login`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(credentials)
      })

      const data = await response.json()

      if (response.ok && data.success) {
        token.value = data.data.access_token
        user.value = data.data.user
        initialized.value = true
        localStorage.setItem('auth_token', data.data.access_token)
        return { success: true }
      } else {
        error.value = data.message || data.errors || 'Login failed'
        return { success: false, error: error.value }
      }
    } catch (err) {
      error.value = err.message || 'Network error occurred'
      return { success: false, error: error.value }
    } finally {
      loading.value = false
    }
  }

  const logout = async () => {
    if (token.value) {
      try {
        await httpClient.request(`${API_BASE_URL}/logout`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${token.value}`
          }
        })
      } catch (err) {
        console.error('Logout API call failed:', err)
      }
    }
    clearAuthData()
  }
  
  const updateProfile = async (profileData) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await httpClient.request(`${API_BASE_URL}/user/profile`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token.value}`
        },
        body: JSON.stringify(profileData)
      })

      const data = await response.json()

      if (response.ok && data.success) {
        // Update local user data
        user.value = { ...user.value, ...data.data }
        return { success: true }
      } else {
        error.value = data.message || data.errors || 'Profile update failed'
        return { success: false, error: error.value }
      }
    } catch (err) {
      error.value = err.message || 'Network error occurred'
      return { success: false, error: error.value }
    } finally {
      loading.value = false
    }
  }

  const updatePassword = async (passwordData) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await httpClient.request(`${API_BASE_URL}/user/password`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token.value}`
        },
        body: JSON.stringify(passwordData)
      })

      const data = await response.json()

      if (response.ok && data.success) {
        return { success: true, message: data.message }
      } else {
        error.value = data.message || data.errors || 'Password update failed'
        return { success: false, error: error.value }
      }
    } catch (err) {
      error.value = err.message || 'Network error occurred'
      return { success: false, error: error.value }
    } finally {
      loading.value = false
    }
  }

  const clearError = () => {
    error.value = null
  }

  // Auto-initialize when store is created
  console.log('🔐 Auth store created, token exists:', !!token.value)
  if (token.value) {
    console.log('🔐 Token found:', token.value.substring(0, 20) + '...')
  }
  initializeAuth()

  return {
    user,
    token,
    loading,
    error,
    initialized,
    isLoggedIn,
    isAdmin,
    login,
    register,
    logout,
    clearError,
    initializeAuth,
    updateProfile,
    updatePassword
  }
})
