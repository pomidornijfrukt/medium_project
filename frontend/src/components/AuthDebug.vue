<template>
  <div class="max-w-2xl mx-auto p-6 bg-white shadow-lg rounded-lg">
    <h2 class="text-2xl font-bold mb-4 text-gray-800">Authentication Debug</h2>
    
    <div class="space-y-4">
      <div class="p-4 bg-gray-50 rounded">
        <h3 class="font-semibold mb-2">Current Auth State:</h3>
        <p><strong>Token exists:</strong> {{ !!authStore.token }}</p>
        <p><strong>User exists:</strong> {{ !!authStore.user }}</p>
        <p><strong>Initialized:</strong> {{ authStore.initialized }}</p>
        <p><strong>Is Logged In:</strong> {{ authStore.isLoggedIn }}</p>
        <p><strong>Is Admin:</strong> {{ authStore.isAdmin }}</p>
        <p><strong>User data:</strong> {{ authStore.user ? JSON.stringify(authStore.user, null, 2) : 'None' }}</p>
      </div>

      <div class="p-4 bg-blue-50 rounded">
        <h3 class="font-semibold mb-2">LocalStorage:</h3>
        <p><strong>auth_token:</strong> {{ getTokenFromStorage() }}</p>
      </div>

      <div class="flex space-x-2">
        <button 
          @click="testAuth"
          class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
        >
          Test Auth
        </button>
        <button 
          @click="clearStorage"
          class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
        >
          Clear Storage
        </button>
        <button 
          @click="reinitialize"
          class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
        >
          Reinitialize Auth
        </button>
      </div>

      <div v-if="debugOutput" class="p-4 bg-gray-100 rounded">
        <h3 class="font-semibold mb-2">Debug Output:</h3>
        <pre class="text-sm whitespace-pre-wrap">{{ debugOutput }}</pre>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const debugOutput = ref('')

const getTokenFromStorage = () => {
  const token = localStorage.getItem('auth_token')
  return token ? token.substring(0, 20) + '...' : 'None'
}

const testAuth = async () => {
  debugOutput.value = '🔍 Testing authentication...\n'
  
  const token = localStorage.getItem('auth_token')
  debugOutput.value += `🔐 Token in localStorage: ${token ? token.substring(0, 20) + '...' : 'None'}\n`
  
  if (!token) {
    debugOutput.value += '❌ No token found\n'
    return
  }

  try {
    const response = await fetch('http://localhost:6969/api/user', {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    })

    debugOutput.value += `📡 Response status: ${response.status}\n`
    debugOutput.value += `📡 Response ok: ${response.ok}\n`
    
    const data = await response.json()
    debugOutput.value += `📄 Response data: ${JSON.stringify(data, null, 2)}\n`
    
  } catch (error) {
    debugOutput.value += `❌ Error: ${error.message}\n`
  }
}

const clearStorage = () => {
  localStorage.removeItem('auth_token')
  debugOutput.value = '🗑️ Cleared auth_token from localStorage\n'
  // Force reactive update
  authStore.token = null
  authStore.user = null
}

const reinitialize = async () => {
  debugOutput.value = '🔄 Reinitializing authentication...\n'
  await authStore.initializeAuth()
  debugOutput.value += '✅ Reinitialization complete\n'
}
</script>
