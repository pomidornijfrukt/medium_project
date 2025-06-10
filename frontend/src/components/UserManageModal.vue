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
                    </div>                    <div>
                      <span class="font-medium text-gray-500">Status:</span>
                      <Tag
                        :label="user?.Status"
                        size="medium"
                        :variant="user?.Status === 'active' ? 'green' : user?.Status === 'banned' ? 'red' : 'yellow'"
                      />
                    </div>
                    <div>
                      <span class="font-medium text-gray-500">Current Role:</span>
                      <Tag
                        :label="user?.Role || 'member'"
                        size="medium"
                        :variant="user?.Role === 'admin' ? 'red' : user?.Role === 'moderator' ? 'yellow' : 'green'"
                      />
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

                <!-- Status Management -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Change Status
                  </label>
                  <select 
                    v-model="selectedStatus" 
                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                  >
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="banned">Banned</option>
                  </select>
                  <div class="mt-1 text-xs text-gray-500">
                    <p v-if="selectedStatus === 'active'" class="text-green-600">
                      🟢 User can access all features and create content
                    </p>
                    <p v-else-if="selectedStatus === 'inactive'" class="text-yellow-600">
                      🟡 User will be automatically logged out and cannot access protected routes
                    </p>
                    <p v-else-if="selectedStatus === 'banned'" class="text-red-600">
                      🔴 User is permanently banned and cannot access the platform
                    </p>
                  </div>
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
            @click="updateUser"
            :disabled="loading || !hasChanges"
            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:bg-gray-400 disabled:cursor-not-allowed"
          >
            <LoadingSpinner v-if="loading" size="medium" color="white" :aria-hidden="true" class="-ml-1 mr-3" />
            {{ loading ? 'Updating...' : hasChanges ? 'Save Changes' : 'No Changes' }}
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
import Tag from '@/components/Tag.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'

const props = defineProps({
  user: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'role-updated', 'status-updated'])

const adminStore = useAdminStore()
const selectedRole = ref(props.user?.Role || 'member')
const selectedStatus = ref(props.user?.Status || 'active')
const loading = ref(false)
const error = ref(null)
const success = ref(null)

// Watch for user changes
watch(() => props.user, (newUser) => {
  if (newUser) {
    selectedRole.value = newUser.Role || 'member'
    selectedStatus.value = newUser.Status || 'active'
    error.value = null
    success.value = null
  }
}, { immediate: true })

// Check if any changes have been made
const hasChanges = computed(() => {
  return selectedRole.value !== props.user?.Role || selectedStatus.value !== props.user?.Status
})

const updateUser = async () => {
  if (!hasChanges.value) return
  
  loading.value = true
  error.value = null
  success.value = null

  try {
    let results = []
    let successMessages = []

    // Update role if changed
    if (selectedRole.value !== props.user?.Role) {
      const roleResult = await adminStore.updateUserRole(props.user.UID, selectedRole.value)
      results.push(roleResult)
      if (roleResult.success) {
        successMessages.push(`Role updated to ${selectedRole.value}`)
        emit('role-updated')
      }
    }

    // Update status if changed
    if (selectedStatus.value !== props.user?.Status) {
      const statusResult = await adminStore.updateUserStatus(props.user.UID, selectedStatus.value)
      results.push(statusResult)
      if (statusResult.success) {
        successMessages.push(`Status updated to ${selectedStatus.value}`)
        emit('status-updated')
      }
    }

    // Check if all updates were successful
    const allSuccessful = results.every(result => result.success)
    const anyFailed = results.some(result => !result.success)

    if (allSuccessful && results.length > 0) {
      success.value = successMessages.join(' and ') + ' successfully!'
      
      // Auto-close after 2 seconds
      setTimeout(() => {
        emit('close')
      }, 2000)
    } else if (anyFailed) {
      const failedResults = results.filter(result => !result.success)
      error.value = failedResults.map(result => result.error).join(', ') || 'Some updates failed'
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
