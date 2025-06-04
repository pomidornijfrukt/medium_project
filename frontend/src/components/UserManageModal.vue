<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>

      <!-- Modal panel -->
      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
          <div class="sm:flex sm:items-start">
            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
              <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
              <h3 class="text-lg leading-6 font-medium text-gray-900">
                Manage User: {{ user?.Username }}
              </h3>
              <div class="mt-4 space-y-4">
                <!-- User Info -->
                <div class="bg-gray-50 rounded-lg p-4">
                  <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                      <span class="font-medium text-gray-500">Email:</span>
                      <p class="text-gray-900">{{ user?.Email }}</p>
                    </div>
                    <div>
                      <span class="font-medium text-gray-500">Status:</span>
                      <span :class="[
                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                        user?.Status === 'active' ? 'bg-green-100 text-green-800' :
                        user?.Status === 'banned' ? 'bg-red-100 text-red-800' :
                        'bg-yellow-100 text-yellow-800'
                      ]">
                        {{ user?.Status }}
                      </span>
                    </div>
                    <div>
                      <span class="font-medium text-gray-500">Current Role:</span>
                      <span :class="[
                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                        user?.Role === 'admin' ? 'bg-red-100 text-red-800' :
                        user?.Role === 'moderator' ? 'bg-yellow-100 text-yellow-800' :
                        'bg-green-100 text-green-800'
                      ]">
                        {{ user?.Role || 'member' }}
                      </span>
                    </div>
                    <div>
                      <span class="font-medium text-gray-500">Joined:</span>
                      <p class="text-gray-900">{{ formatDate(user?.created_at) }}</p>
                    </div>
                  </div>
                </div>

                <!-- Role Management -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Change Role
                  </label>
                  <select 
                    v-model="selectedRole" 
                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                  >
                    <option value="member">Member</option>
                    <option value="moderator">Moderator</option>
                    <option value="admin">Administrator</option>
                  </select>
                  <p class="mt-1 text-xs text-gray-500">
                    Be careful when changing roles, especially admin permissions
                  </p>
                </div>

                <!-- Error Display -->
                <div v-if="error" class="bg-red-50 border border-red-200 rounded-md p-3">
                  <p class="text-sm text-red-800">{{ error }}</p>
                </div>

                <!-- Success Display -->
                <div v-if="success" class="bg-green-50 border border-green-200 rounded-md p-3">
                  <p class="text-sm text-green-800">{{ success }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Modal Actions -->
        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
          <button 
            @click="updateRole"
            :disabled="loading || selectedRole === user?.Role"
            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:bg-gray-400 disabled:cursor-not-allowed"
          >
            <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ loading ? 'Updating...' : 'Update Role' }}
          </button>
          <button 
            @click="$emit('close')"
            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
          >
            Cancel
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useAdminStore } from '@/stores/admin'

const props = defineProps({
  user: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'role-updated'])

const adminStore = useAdminStore()
const selectedRole = ref(props.user?.Role || 'member')
const loading = ref(false)
const error = ref(null)
const success = ref(null)

// Watch for user changes
watch(() => props.user, (newUser) => {
  if (newUser) {
    selectedRole.value = newUser.Role || 'member'
    error.value = null
    success.value = null
  }
}, { immediate: true })

const updateRole = async () => {
  if (selectedRole.value === props.user?.Role) return
  
  loading.value = true
  error.value = null
  success.value = null

  try {
    const result = await adminStore.updateUserRole(props.user.UID, selectedRole.value)
    
    if (result.success) {
      success.value = `Role updated to ${selectedRole.value} successfully!`
      emit('role-updated')
      
      // Auto-close after 2 seconds
      setTimeout(() => {
        emit('close')
      }, 2000)
    } else {
      error.value = result.error || 'Failed to update role'
    }
  } catch (err) {
    error.value = err.message || 'An error occurred'
  } finally {
    loading.value = false
  }
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString()
}
</script>
