<script setup>
import { RouterLink, RouterView, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'

const authStore = useAuthStore()
const router = useRouter()

const handleLogout = async () => {
  await authStore.logout()
  // Redirect to homepage after logout
  await router.push('/')
}
</script>

<template>
  <header class="bg-white shadow-md sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-16">
        <div class="flex items-center">
          <h1 class="text-2xl font-bold text-gray-900">
            <RouterLink to="/" class="hover:text-indigo-600 transition-colors duration-200">
              Forum
            </RouterLink>
          </h1>
        </div>

        <nav class="flex items-center space-x-4">
          <RouterLink 
            to="/" 
            class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200"
          >
            Home
          </RouterLink>
          <RouterLink 
            to="/about" 
            class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200"
          >
            About
          </RouterLink>
          
          <template v-if="authStore.isLoggedIn">
            <RouterLink 
              to="/create" 
              class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200"
            >
              Create Post
            </RouterLink>
            <RouterLink 
              to="/profile" 
              class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200"
            >
              Profile
            </RouterLink>
            <RouterLink 
              v-if="authStore.isAdmin"
              to="/admin" 
              class="text-purple-700 hover:text-purple-900 px-3 py-2 rounded-md text-sm font-semibold transition-colors duration-200"
            >
              Admin Panel
            </RouterLink>
            <button 
              @click="handleLogout" 
              class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
            >
              Logout
            </button>
            <span class="text-sm text-gray-600 ml-2">
              Hello, {{ authStore.user?.Username }}!
            </span>
          </template>
          
          <template v-else>
            <RouterLink 
              to="/login" 
              class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200"
            >
              Login
            </RouterLink>
            <RouterLink 
              to="/register" 
              class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
            >
              Register
            </RouterLink>
          </template>
        </nav>
      </div>
    </div>
  </header>

  <main class="min-h-screen bg-gray-50">
    <RouterView />
  </main>
</template>

<style scoped>
/* All styling is now handled by TailwindCSS classes */
</style>
