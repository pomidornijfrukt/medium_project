<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Create your account
        </h2>
      </div>
      
      <div v-if="authStore.error" class="bg-red-50 border border-red-400 text-red-700 px-4 py-3 rounded relative">
        {{ authStore.error }}
      </div>
      
      <form @submit.prevent="handleRegister" class="mt-8 space-y-6">
        <div class="space-y-4">
          <div>
            <label for="username" class="block text-sm font-medium text-gray-700">
              Username
            </label>
            <input 
              type="text" 
              id="username"
              v-model="registerForm.username"
              required
              :disabled="authStore.loading"
              placeholder="Choose a username"
              class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm disabled:bg-gray-100 disabled:cursor-not-allowed"
            />
          </div>
          
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700">
              Email
            </label>
            <input 
              type="email" 
              id="email"
              v-model="registerForm.email"
              required
              :disabled="authStore.loading"
              placeholder="Enter your email"
              class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm disabled:bg-gray-100 disabled:cursor-not-allowed"
            />
          </div>
          
          <div>
            <label for="password" class="block text-sm font-medium text-gray-700">
              Password
            </label>
            <input 
              type="password" 
              id="password"
              v-model="registerForm.password"
              required
              :disabled="authStore.loading"
              placeholder="Enter your password"
              minlength="8"
              class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm disabled:bg-gray-100 disabled:cursor-not-allowed"
            />
          </div>
          
          <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
              Confirm Password
            </label>
            <input 
              type="password" 
              id="password_confirmation"
              v-model="registerForm.password_confirmation"
              required
              :disabled="authStore.loading"
              placeholder="Confirm your password"
              minlength="8"
              class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm disabled:bg-gray-100 disabled:cursor-not-allowed"
            />
          </div>
        </div>
        
        <div v-if="!passwordsMatch && registerForm.password_confirmation" class="bg-red-50 border border-red-400 text-red-700 px-4 py-3 rounded text-sm">
          Passwords do not match
        </div>
        
        <div>
          <button 
            type="submit"            :disabled="authStore.loading || !passwordsMatch"
            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors duration-200"
          >
            <span v-if="authStore.loading" class="absolute left-0 inset-y-0 flex items-center pl-3">
              <LoadingSpinner size="medium" color="current" :aria-hidden="true" class="text-indigo-300" />
            </span>
            {{ authStore.loading ? 'Creating Account...' : 'Register' }}
          </button>
        </div>
        
        <div class="text-center">
          <p class="text-sm text-gray-600">
            Already have an account? 
            <router-link to="/login" class="font-medium text-indigo-600 hover:text-indigo-500 transition-colors duration-200">
              Login here
            </router-link>
          </p>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'
import LoadingSpinner from '@/components/LoadingSpinner.vue'

const router = useRouter()
const authStore = useAuthStore()

const registerForm = ref({
  username: '',
  email: '',
  password: '',
  password_confirmation: ''
})

const passwordsMatch = computed(() => {
  return registerForm.value.password === registerForm.value.password_confirmation
})

// Check if already logged in on component mount
onMounted(() => {
  if (authStore.isLoggedIn) {
    router.push('/')
  }
})

const handleRegister = async () => {
  if (!passwordsMatch.value) {
    return
  }
  
  const result = await authStore.register(registerForm.value)
  
  if (result.success) {
    router.push('/')
  }
}
</script>