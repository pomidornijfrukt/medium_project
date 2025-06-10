<script setup>
import { RouterLink, RouterView, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'
import { ref } from 'vue'

const authStore = useAuthStore()
const router = useRouter()
const isMobileMenuOpen = ref(false)

const handleLogout = async () => {
  await authStore.logout()
  // Redirect to homepage after logout
  await router.push('/')
  // Close mobile menu after logout
  isMobileMenuOpen.value = false
}

const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value
}

const closeMobileMenu = () => {
  isMobileMenuOpen.value = false
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

          <!-- Desktop Navigation -->
          <nav class="hidden md:flex items-center space-x-4">
            <RouterLink to="/"
              class="px-3 py-2 text-sm font-medium text-gray-700 transition-colors duration-200 rounded-md hover:text-indigo-600">
              Home
            </RouterLink>
            <RouterLink to="/about"
              class="px-3 py-2 text-sm font-medium text-gray-700 transition-colors duration-200 rounded-md hover:text-indigo-600">
              About
            </RouterLink>

            <template v-if="authStore.isLoggedIn">
              <RouterLink to="/create"
                class="px-3 py-2 text-sm font-medium text-gray-700 transition-colors duration-200 rounded-md hover:text-indigo-600">
                Create Post
              </RouterLink>
              <RouterLink to="/search"
                class="px-3 py-2 text-sm font-medium text-gray-700 transition-colors duration-200 rounded-md hover:text-indigo-600">
                Deep Search
              </RouterLink>
              <RouterLink to="/profile"
                class="px-3 py-2 text-sm font-medium text-gray-700 transition-colors duration-200 rounded-md hover:text-indigo-600">
                Profile
              </RouterLink>
              <RouterLink v-if="authStore.isAdmin" to="/admin"
                class="px-3 py-2 text-sm font-semibold text-purple-700 transition-colors duration-200 rounded-md hover:text-purple-900">
                Admin Panel
              </RouterLink>
              <button @click="handleLogout"
                class="px-4 py-2 text-sm font-medium text-white transition-colors duration-200 rounded-md bg-red-600/80 hover:bg-red-700">
                Logout
              </button>
            </template>

            <template v-else>
              <RouterLink to="/login"
                class="px-3 py-2 text-sm font-medium text-gray-700 transition-colors duration-200 rounded-md hover:text-indigo-600">
                Login
              </RouterLink>
              <RouterLink to="/register"
                class="px-4 py-2 text-sm font-medium text-white transition-colors duration-200 bg-indigo-600 rounded-md hover:bg-indigo-700">
                Register
              </RouterLink>
            </template>
          </nav>

          <!-- Mobile menu section -->
          <div class="md:hidden flex items-center space-x-2">
            <!-- User greeting - visible on mobile outside burger menu -->
            <span v-if="authStore.isLoggedIn" class="text-sm text-gray-600">
              Hello, {{ authStore.user?.Username }}!
            </span>

            <!-- Burger menu button -->
            <button @click="toggleMobileMenu"
              class="p-2 rounded-md text-gray-700 hover:text-indigo-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
              aria-label="Toggle mobile menu">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path v-if="!isMobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M4 6h16M4 12h16M4 18h16" />
                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Mobile Navigation Menu -->
        <div v-if="isMobileMenuOpen" class="md:hidden border-t border-gray-200 bg-white">
          <nav class="px-2 pt-2 pb-3 space-y-1">
            <RouterLink @click="closeMobileMenu" to="/"
              class="block px-3 py-2 text-base font-medium text-gray-700 rounded-md hover:text-indigo-600 hover:bg-gray-50">
              Home
            </RouterLink>
            <RouterLink @click="closeMobileMenu" to="/about"
              class="block px-3 py-2 text-base font-medium text-gray-700 rounded-md hover:text-indigo-600 hover:bg-gray-50">
              About
            </RouterLink>

            <template v-if="authStore.isLoggedIn">
              <RouterLink @click="closeMobileMenu" to="/create"
                class="block px-3 py-2 text-base font-medium text-gray-700 rounded-md hover:text-indigo-600 hover:bg-gray-50">
                Create Post
              </RouterLink>
              <RouterLink @click="closeMobileMenu" to="/search"
                class="block px-3 py-2 text-base font-medium text-gray-700 rounded-md hover:text-indigo-600 hover:bg-gray-50">
                Deep Search
              </RouterLink>
              <RouterLink @click="closeMobileMenu" to="/profile"
                class="block px-3 py-2 text-base font-medium text-gray-700 rounded-md hover:text-indigo-600 hover:bg-gray-50">
                Profile
              </RouterLink>
              <RouterLink v-if="authStore.isAdmin" @click="closeMobileMenu" to="/admin"
                class="block px-3 py-2 text-base font-semibold text-purple-700 rounded-md hover:text-purple-900 hover:bg-purple-50">
                Admin Panel
              </RouterLink>
              <button @click="handleLogout"
                class="w-full text-left px-3 py-2 text-base font-medium text-red-700 rounded-md hover:text-red-900 hover:bg-red-50">
                Logout
              </button>
            </template>

            <template v-else>
              <RouterLink @click="closeMobileMenu" to="/login"
                class="block px-3 py-2 text-base font-medium text-gray-700 rounded-md hover:text-indigo-600 hover:bg-gray-50">
                Login
              </RouterLink>
              <RouterLink @click="closeMobileMenu" to="/register"
                class="block px-3 py-2 text-base font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
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