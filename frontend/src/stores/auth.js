import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

// API base URL configuration
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('auth_token'))
  const loading = ref(false)
  const error = ref(null)

  const isLoggedIn = computed(() => !!token.value)

  // Initialize user data if token exists
  const initializeAuth = async () => {
    if (token.value && !user.value) {
      try {
        const response = await fetch(`${API_BASE_URL}/user`, {
          headers: {
            'Authorization': `Bearer ${token.value}`,
            'Accept': 'application/json'
          }
        })

        if (response.ok) {
          const data = await response.json()
          user.value = data.user || data.data
        } else {
          // Token is invalid, clear it
          logout()
        }
      } catch (err) {
        console.error('Failed to initialize auth:', err)
        logout()
      }
    }
  }

  const register = async (userData) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await fetch(`${API_BASE_URL}/register`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'        },
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
        localStorage.setItem('auth_token', data.data.access_token)
        return { success: true }
      } else {
        error.value = data.message || data.errors || 'Registration failed'
        return { success: false, error: error.value }
      }
    } catch (err) {
      error.value = 'Network error occurred'
      return { success: false, error: error.value }
    } finally {
      loading.value = false
    }
  }

  const login = async (credentials) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await fetch(`${API_BASE_URL}/login`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(credentials)      })

      const data = await response.json()

      if (response.ok && data.success) {
        token.value = data.data.access_token
        user.value = data.data.user
        localStorage.setItem('auth_token', data.data.access_token)
        return { success: true }
      } else {
        error.value = data.message || data.errors || 'Login failed'
        return { success: false, error: error.value }
      }
    } catch (err) {
      error.value = 'Network error occurred'
      return { success: false, error: error.value }
    } finally {
      loading.value = false
    }
  }

  const logout = async () => {
    if (token.value) {
      try {
        await fetch(`${API_BASE_URL}/logout`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${token.value}`,
            'Accept': 'application/json'
          }
        })
      } catch (err) {
        console.error('Logout API call failed:', err)
      }
    }
    
    token.value = null
    user.value = null
    localStorage.removeItem('auth_token')
  }

  const clearError = () => {
    error.value = null
  }

  // Auto-initialize when store is created
  initializeAuth()

  return {
    user,
    token,
    loading,
    error,
    isLoggedIn,
    login,
    register,
    logout,
    clearError,
    initializeAuth
  }
})