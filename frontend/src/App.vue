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
  <div class="flex flex-col min-h-screen bg-white">
    <header class="sticky top-0 z-50 flex-shrink-0 shadow-md bg-white/50 backdrop-blur">
      <div class="px-4 mx-auto sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-12">
          <div class="flex items-center">
            <h1 class="text-xl font-bold ">
              <RouterLink to="/" class="p-1 text-indigo-600 transition-colors rounded-md hover:text-indigo-700">
                Forum
              </RouterLink>
            </h1>
          </div>

          <nav class="flex items-center space-x-4">
            <RouterLink 
              to="/" 
              class="px-3 py-2 text-sm font-medium text-gray-700 transition-colors duration-200 rounded-md hover:text-indigo-600"
            >
              Home
            </RouterLink>
            <RouterLink 
              to="/about" 
              class="px-3 py-2 text-sm font-medium text-gray-700 transition-colors duration-200 rounded-md hover:text-indigo-600"
            >
              About
            </RouterLink>
            
            <template v-if="authStore.isLoggedIn">
              <RouterLink 
                to="/create" 
                class="px-3 py-2 text-sm font-medium text-gray-700 transition-colors duration-200 rounded-md hover:text-indigo-600"
              >
                Create Post
              </RouterLink>
              <RouterLink 
                to="/profile" 
                class="px-3 py-2 text-sm font-medium text-gray-700 transition-colors duration-200 rounded-md hover:text-indigo-600"
              >
                Profile
              </RouterLink>
              <RouterLink 
                v-if="authStore.isAdmin"
                to="/admin" 
                class="px-3 py-2 text-sm font-semibold text-purple-700 transition-colors duration-200 rounded-md hover:text-purple-900"
              >
                Admin Panel
              </RouterLink>
              <span class="ml-2 text-sm text-gray-600">
                Hello, {{ authStore.user?.Username }}!
              </span>
              <button 
                @click="handleLogout" 
                class="px-4 py-2 text-sm font-medium text-white transition-colors duration-200 rounded-md bg-red-600/80 hover:bg-red-700"
              >
                Logout
              </button>
            </template>
            
            <template v-else>
              <RouterLink 
                to="/login" 
                class="px-3 py-2 text-sm font-medium text-gray-700 transition-colors duration-200 rounded-md hover:text-indigo-600"
              >
                Login
              </RouterLink>
              <RouterLink 
                to="/register" 
                class="px-4 py-2 text-sm font-medium text-white transition-colors duration-200 bg-indigo-600 rounded-md hover:bg-indigo-700"
              >
                Register
              </RouterLink>
            </template>
          </nav>
        </div>
      </div>
    </header>

    <main class="flex-1 bg-gray-50">
      <RouterView />
    </main>
  </div>
</template>