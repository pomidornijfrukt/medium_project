<template>
  <div class="min-h-screen py-8 bg-gray-50">
    <div class="max-w-4xl px-4 mx-auto sm:px-6 lg:px-8">      <!-- Loading State -->
      <div v-if="postStore.loading" class="flex items-center justify-center h-64">
        <LoadingSpinner size="xl" color="indigo" class="mr-4" aria-label="Loading post" />
        <span class="text-gray-600">Loading post...</span>
      </div>

      <!-- Error State -->
      <div v-else-if="postStore.error" class="px-4 py-3 text-red-700 border border-red-400 rounded-lg bg-red-50">
        <h2 class="mb-2 text-lg font-semibold">Error Loading Post</h2>
        <p>{{ postStore.error }}</p>
        <button 
          @click="loadPost" 
          class="px-4 py-2 mt-4 text-sm font-medium text-white transition-colors duration-200 bg-red-600 rounded-md hover:bg-red-700"
        >
          Try Again
        </button>
      </div>

      <!-- Post Content -->
      <article v-else-if="post" class="overflow-hidden bg-white rounded-lg shadow-lg">
        <!-- Post Header -->
        <header class="px-6 py-4 border-b border-gray-200">
          <div class="flex items-start justify-between">
            <div class="flex-1">              <!-- Post Hierarchy Navigation (for replies) -->
              <div v-if="post.ParentPostID && postHierarchy.length > 0" class="mb-4">
                <nav class="flex items-center space-x-2 text-sm text-gray-600">
                  <!-- Root post link -->
                  <router-link 
                    :to="originalReplyId ? `/posts/${postHierarchy[0].PostID}#reply-${originalReplyId}` : `/posts/${postHierarchy[0].PostID}`"
                    class="text-indigo-600 hover:text-indigo-800 hover:underline transition-colors duration-200"
                    :title="`Go to root post: ${postHierarchy[0].Topic}`"
                  >
                    🏠 {{ postHierarchy[0].Topic }}
                  </router-link>
                  
                  <!-- Hierarchy breadcrumbs -->
                  <template v-for="(hierarchyPost, index) in postHierarchy.slice(1)" :key="hierarchyPost.PostID">
                    <span class="text-gray-400">→</span>
                    <router-link 
                      :to="originalReplyId ? `/posts/${hierarchyPost.PostID}#reply-${originalReplyId}` : `/posts/${hierarchyPost.PostID}`"
                      class="text-indigo-600 hover:text-indigo-800 hover:underline transition-colors duration-200 max-w-xs truncate"
                      :title="`Go to: ${hierarchyPost.Topic}`"
                    >
                      {{ hierarchyPost.Topic }}
                    </router-link>
                  </template>
                  
                  <!-- Current post indicator -->
                  <span class="text-gray-400">→</span>
                  <span class="font-medium text-gray-900 max-w-xs truncate" :title="post.Topic">
                    {{ post.Topic }}
                  </span>
                </nav>
                
                <!-- Quick action link to parent -->
                <div class="mt-2">
                  <router-link 
                    :to="`/posts/${post.ParentPostID}#reply-${post.PostID}`"
                    class="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800 transition-colors duration-200"
                  >
                    ← Back to parent post
                  </router-link>
                </div>
              </div>
              
              <!-- Simple parent navigation fallback -->
              <div v-else-if="post.ParentPostID" class="mb-3">
                <router-link 
                  :to="`/posts/${post.ParentPostID}#reply-${post.PostID}`"
                  class="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800"
                >
                  ← Back to parent post
                </router-link>
              </div>
              
              <h1 class="mb-2 text-3xl font-bold text-gray-900">{{ post.Topic }}</h1>
              <div class="flex items-center space-x-4 text-sm text-gray-500">
                <span>By {{ post.author?.Username || 'Anonymous' }}</span>
                <span>•</span>
                <span>{{ formatDate(post.created_at) }}</span>
                <span v-if="post.updated_at !== post.created_at">
                  • Updated {{ formatDate(post.updated_at) }}
                </span>
              </div>            </div>
            
            <!-- Action Buttons -->
            <div class="flex flex-col items-end space-y-2 sm:flex-row sm:items-center sm:space-y-0 sm:space-x-2">
              <!-- Refresh Button -->
              <RefreshButton 
                :is-loading="postStore.loading"
                @refresh="refreshPost"
                variant="secondary"
                size="small"
                :show-mobile-text="false"
                class="order-2 sm:order-1"
              />
              
              <!-- Edit/Delete Buttons (for post owner) -->
              <div v-if="canEditPost" class="flex order-1 space-x-2 sm:order-2">
                <router-link 
                  :to="`/edit/${post.PostID}`"
                  class="px-4 py-2 text-sm font-medium text-white transition-colors duration-200 bg-indigo-600 rounded-md hover:bg-indigo-700"
                >
                  Edit
                </router-link>
                <button 
                  @click="deletePost"
                  :disabled="postStore.loading"
                  class="px-4 py-2 text-sm font-medium text-white transition-colors duration-200 bg-red-600 rounded-md hover:bg-red-700 disabled:bg-gray-400"
                >
                  Delete
                </button>
              </div>
            </div>
          </div>
        </header>        <!-- Post Content -->
        <div class="px-6 py-6">
          <div class="prose prose-lg max-w-none">
            <p class="leading-relaxed text-gray-700 whitespace-pre-wrap">{{ post.Content }}</p>
          </div>
        </div>

        <!-- Tags -->
        <div v-if="post.tags && post.tags.length > 0" class="px-6 py-4 border-t border-gray-200">
          <div class="flex flex-wrap gap-2">
            <span 
              v-for="tag in post.tags" 
              :key="tag.TagName"
              class="inline-flex items-center px-3 py-1 text-sm font-medium text-indigo-800 bg-indigo-100 rounded-full"
            >
              {{ tag.TagName }}
            </span>
          </div>
        </div>
      </article>

      <!-- Reply Section -->
      <div v-if="post" class="mt-8 overflow-hidden bg-white rounded-lg shadow-lg">
        <!-- Reply Button -->
        <div v-if="!showReplyForm" class="px-6 py-4 border-b border-gray-200">
          <button 
            v-if="authStore.isLoggedIn"
            @click="showReplyForm = true"
            class="px-4 py-2 text-sm font-medium text-white transition-colors duration-200 bg-indigo-600 rounded-md hover:bg-indigo-700"
          >
            Reply to this post
          </button>
          <p v-else class="text-sm text-gray-500">
            <RouterLink to="/login" class="text-indigo-600 hover:text-indigo-800">Login</RouterLink> to reply to this post
          </p>
        </div>

        <!-- Reply Form -->
        <div v-if="showReplyForm && authStore.isLoggedIn" class="px-6 py-6 border-b border-gray-200">
          <h3 class="mb-4 text-lg font-semibold text-gray-900">Write a Reply</h3>
          
          <!-- Error Display -->
          <div v-if="replyError" class="px-4 py-3 mb-4 text-red-700 border border-red-400 rounded bg-red-50">
            {{ replyError }}
          </div>

          <form @submit.prevent="handleReplySubmit" class="space-y-4">
            <!-- Reply Title -->
            <div>
              <label for="reply-title" class="block mb-2 text-sm font-medium text-gray-700">
                Reply Title *
              </label>
              <input 
                type="text" 
                id="reply-title"
                v-model="replyForm.title"
                required
                :disabled="postStore.loading"
                placeholder="Enter reply title"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
              />
            </div>

            <!-- Reply Content -->
            <div>
              <label for="reply-content" class="block mb-2 text-sm font-medium text-gray-700">
                Reply Content *
              </label>
              <textarea 
                id="reply-content"
                v-model="replyForm.content"
                required
                :disabled="postStore.loading"
                placeholder="Write your reply here..."
                rows="6"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm resize-y focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
              ></textarea>
            </div>

            <!-- Reply Tags -->
            <div>
              <label for="reply-tags" class="block mb-2 text-sm font-medium text-gray-700">
                Tags (optional)
              </label>
              <div class="flex gap-2 mb-2">
                <input 
                  type="text" 
                  id="reply-tags"
                  v-model="replyTagInput"
                  @keydown="handleReplyTagKeydown"
                  :disabled="postStore.loading"
                  placeholder="Add tags and press Shift+Enter"
                  class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
                />
                <button 
                  type="button"
                  @click="addReplyTag"
                  :disabled="postStore.loading || !replyTagInput.trim()"
                  class="px-4 py-2 text-sm font-medium text-white transition-colors duration-200 bg-gray-600 rounded-md hover:bg-gray-700 disabled:bg-gray-400 disabled:cursor-not-allowed"
                >
                  Add Tag
                </button>
              </div>
              
              <!-- Selected Tags -->
              <div v-if="replyForm.tags.length > 0" class="flex flex-wrap gap-2">
                <span 
                  v-for="(tag, index) in replyForm.tags" 
                  :key="index"
                  class="inline-flex items-center px-3 py-1 text-sm font-medium text-indigo-800 bg-indigo-100 rounded-full"
                >
                  {{ tag }}
                  <button 
                    type="button"
                    @click="removeReplyTag(index)"
                    :disabled="postStore.loading"
                    class="ml-2 text-indigo-600 hover:text-indigo-800 disabled:cursor-not-allowed"
                  >
                    ×
                  </button>
                </span>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between pt-4">
              <button 
                type="button"
                @click="cancelReply"
                :disabled="postStore.loading"
                class="px-4 py-2 text-sm font-medium text-gray-600 transition-colors duration-200 hover:text-gray-800 disabled:cursor-not-allowed"
              >
                Cancel
              </button>
              
              <button 
                type="submit"                :disabled="postStore.loading || !replyForm.title.trim() || !replyForm.content.trim()"
                class="flex items-center px-6 py-2 text-sm font-medium text-white transition-colors duration-200 bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:bg-gray-400 disabled:cursor-not-allowed"
              >
                <LoadingSpinner v-if="postStore.loading" size="small" color="white" :aria-hidden="true" class="mr-2" />
                {{ postStore.loading ? 'Posting...' : 'Post Reply' }}
              </button>
            </div>
          </form>
        </div>

        <!-- Existing Replies -->
        <div class="px-6 py-6">
          <h3 class="mb-4 text-lg font-semibold text-gray-900">
            Replies {{ replies.length > 0 ? `(${replies.length})` : '' }}
          </h3>          <!-- Loading Replies -->
          <div v-if="postStore.loading" class="flex items-center justify-center py-8">
            <LoadingSpinner size="large" color="indigo" class="mr-3" aria-label="Loading replies" />
            <span class="text-gray-600">Loading replies...</span>
          </div><!-- No Replies -->
          <div v-else-if="!postStore.loading && replies.length === 0" class="py-8 text-center text-gray-500">
            <p>No replies yet. Be the first to reply!</p>
          </div>          <!-- Replies List -->
          <div v-else-if="!postStore.loading" class="space-y-6">
            <ReplyItem
              v-for="reply in replies"
              :key="reply.PostID"
              :reply="reply"
              :depth="1"
              :root-post-id="post.PostID"
              :highlighted-reply-id="highlightedReplyId"
              :nested-reply-form="nestedReplyForm"
              :post-store="postStore"
              :auth-store="authStore"
              :format-date="formatDate"
              @start-nested-reply="startNestedReply"
              @cancel-nested-reply="cancelNestedReply"
              @handle-nested-reply-submit="handleNestedReplySubmit"
            />
          </div>
        </div>
      </div>

      <!-- Post Not Found -->
      <div v-else class="py-12 text-center">
        <h2 class="mb-4 text-2xl font-bold text-gray-900">Post Not Found</h2>
        <p class="mb-6 text-gray-600">The post you're looking for doesn't exist or has been removed.</p>
        <router-link 
          to="/" 
          class="px-6 py-3 text-sm font-medium text-white transition-colors duration-200 bg-indigo-600 rounded-md hover:bg-indigo-700"
        >
          Back to Home
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePostStore } from '@/stores/post.js'
import { useAuthStore } from '@/stores/auth.js'
import ReplyItem from '@/components/ReplyItem.vue'
import RefreshButton from '@/components/RefreshButton.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'

const route = useRoute()
const router = useRouter()
const postStore = usePostStore()
const authStore = useAuthStore()

// Reply functionality
const showReplyForm = ref(false)
const replies = ref([])
const replyError = ref(null)
const replyTagInput = ref('')
const highlightedReplyId = ref(null)

// Post hierarchy navigation
const postHierarchy = ref([])
const originalReplyId = ref(null)

const replyForm = ref({
  title: '',
  content: '',
  tags: []
})

// Nested reply functionality
const nestedReplyForm = ref({
  parentId: null,
  title: '',
  content: '',
  tags: [],
  error: null
})
const nestedReplyTagInput = ref('')

const post = computed(() => postStore.currentPost)

const canEditPost = computed(() => {
  return authStore.isLoggedIn && 
         authStore.user && 
         post.value && 
         (authStore.user.UID === post.value.Author || authStore.user.Role === 'admin')
})

const formatDate = (dateString) => {
  if (!dateString) return 'Unknown date'
  
  try {
    const date = new Date(dateString)
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch (error) {
    return 'Invalid date'
  }
}

// Reply form methods
const handleReplyTagKeydown = (event) => {
  // Check for Shift+Enter combination
  if (event.shiftKey && event.key === 'Enter') {
    event.preventDefault()
    addReplyTag()
  }
}

const addReplyTag = () => {
  const tag = replyTagInput.value.trim()
  if (tag && !replyForm.value.tags.includes(tag)) {
    replyForm.value.tags.push(tag)
    replyTagInput.value = ''
  }
}

const removeReplyTag = (index) => {
  replyForm.value.tags.splice(index, 1)
}

const cancelReply = () => {
  showReplyForm.value = false
  replyForm.value = { title: '', content: '', tags: [] }
  replyTagInput.value = ''
  replyError.value = null
}

const handleReplySubmit = async () => {
  replyError.value = null
  
  const replyData = {
    title: replyForm.value.title.trim(),
    content: replyForm.value.content.trim(),
    tags: replyForm.value.tags
  }

  const result = await postStore.createReply(post.value.PostID, replyData)
  
  if (result.success) {
    // Reset form and hide it
    cancelReply()
    // Refresh the entire post data to get updated replies
    await postStore.fetchPost(post.value.PostID)
    loadReplies()
  } else {
    replyError.value = result.error || 'Failed to create reply'
  }
}

// Nested reply methods
const startNestedReply = (parentReplyId) => {
  nestedReplyForm.value = {
    parentId: parentReplyId,
    title: '',
    content: '',
    tags: [],
    error: null
  }
  nestedReplyTagInput.value = ''
}

const cancelNestedReply = () => {
  nestedReplyForm.value = {
    parentId: null,
    title: '',
    content: '',
    tags: [],
    error: null
  }
  nestedReplyTagInput.value = ''
}

const handleNestedReplyTagKeydown = (event) => {
  // Check for Shift+Enter combination
  if (event.shiftKey && event.key === 'Enter') {
    event.preventDefault()
    addNestedReplyTag()
  }
}

const addNestedReplyTag = () => {
  const tag = nestedReplyTagInput.value.trim()
  if (tag && !nestedReplyForm.value.tags.includes(tag)) {
    nestedReplyForm.value.tags.push(tag)
    nestedReplyTagInput.value = ''
  }
}

const removeNestedReplyTag = (index) => {
  nestedReplyForm.value.tags.splice(index, 1)
}

const handleNestedReplySubmit = async () => {
  nestedReplyForm.value.error = null
  
  const replyData = {
    title: nestedReplyForm.value.title.trim(),
    content: nestedReplyForm.value.content.trim(),
    tags: nestedReplyForm.value.tags
  }

  const result = await postStore.createReply(nestedReplyForm.value.parentId, replyData)
  
  if (result.success) {
    // Reset form and hide it
    cancelNestedReply()
    // Refresh the entire post data to get updated replies including nested ones
    await postStore.fetchPost(post.value.PostID)
    loadReplies()
  } else {
    nestedReplyForm.value.error = result.error || 'Failed to create reply'
  }
}

const loadReplies = async () => {
  if (!post.value) return
  
  console.log('🔍 Loading replies for post', post.value.PostID)
  
  // Helper function to organize replies into tree structure
  const organizeRepliesRecursively = (parentId, allReplies) => {
    const directReplies = allReplies.filter(reply => reply.ParentPostID === parentId)
    
    return directReplies.map(reply => ({
      ...reply,
      nested_replies: organizeRepliesRecursively(reply.PostID, allReplies)
    }))
  }
  
  // First, try to get replies from cache using the smart cache function
  const cachedResult = postStore.buildReplyChainFromCache(post.value.PostID)
  
  if (cachedResult.success && cachedResult.data.length > 0) {
    console.log('✅ Loaded', cachedResult.data.length, 'replies from cache - NO API CALL!')
    replies.value = cachedResult.data
    return
  }
  
  // If no cached replies, check if the main post has linked posts
  const cachedLinkedPosts = post.value.linkedPosts || post.value.linked_posts || []
  
  if (cachedLinkedPosts.length > 0) {
    console.log('📋 Using linked posts from main post data')
    replies.value = organizeRepliesRecursively(post.value.PostID, cachedLinkedPosts)
    return
  }
  
  // No cache data available - wait for background fetch to complete
  console.log('⏳ No cached replies found, waiting for background fetch...')
  replies.value = [] // Show empty state initially
  
  // Set up a retry mechanism to check cache again after background fetch
  const checkCacheAfterDelay = async (delay = 1000, maxAttempts = 3) => {
    let attempts = 0
    
    const checkCache = async () => {
      attempts++
      const updatedCachedResult = postStore.buildReplyChainFromCache(post.value.PostID)
      
      if (updatedCachedResult.success && updatedCachedResult.data.length > 0) {
        console.log('✅ Background fetch complete! Loaded', updatedCachedResult.data.length, 'replies from cache')
        replies.value = updatedCachedResult.data
        return true
      }
      
      // Check if post now has linked posts after background fetch
      const updatedLinkedPosts = post.value.linkedPosts || post.value.linked_posts || []
      if (updatedLinkedPosts.length > 0) {
        console.log('📋 Background fetch complete! Using linked posts from main post data')
        replies.value = organizeRepliesRecursively(post.value.PostID, updatedLinkedPosts)
        return true
      }
      
      if (attempts < maxAttempts) {
        setTimeout(checkCache, delay)
        return false
      } else {
        console.log('📭 No replies found after background fetch - post may have no replies')
        return true // Stop trying
      }
    }
    
    setTimeout(checkCache, delay)
  }
  
  // Start checking cache after a delay to allow background fetch to complete
  checkCacheAfterDelay()
}

const buildPostHierarchy = async () => {
  if (!post.value) return
  
  console.log('🔍 Building post hierarchy for post', post.value.PostID)
  
  // Store the original reply ID from hash for final anchoring
  if (route.hash && route.hash.startsWith('#reply-')) {
    originalReplyId.value = route.hash.replace('#reply-', '')
  }
  
  // First, try to build hierarchy from cache
  const cachedResult = postStore.buildPostHierarchyFromCache(post.value.PostID)
  
  if (cachedResult.success && cachedResult.data.length > 0) {
    console.log('✅ Built hierarchy with', cachedResult.data.length, 'levels from cache - NO API CALLS!')
    postHierarchy.value = cachedResult.data
    return
  }
  
  // If cache doesn't have enough data, fall back to API calls
  console.log('🌐 Cache insufficient for hierarchy, fetching from API...')
  
  const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:6969/api'
  const hierarchy = []
  let currentPost = post.value
  
  // Build the hierarchy by following ParentPostID chain
  while (currentPost && currentPost.ParentPostID) {
    try {
      // Fetch the parent post
      const parentResponse = await fetch(`${API_BASE_URL}/posts/${currentPost.ParentPostID}`)
      if (parentResponse.ok) {
        const parentData = await parentResponse.json()
        if (parentData.success) {
          const parentPost = parentData.data
          hierarchy.unshift({
            PostID: parentPost.PostID,
            Topic: parentPost.Topic,
            author: parentPost.author
          })
          currentPost = parentPost
        } else {
          break
        }
      } else {
        break
      }
    } catch (error) {
      console.error('Error fetching parent post:', error)
      break
    }
  }  
  postHierarchy.value = hierarchy
}

const scrollToReply = async (replyId) => {
  if (!replyId) return
  
  // Wait for DOM to update
  await nextTick()
  
  const replyElement = document.getElementById(`reply-${replyId}`)
  
  if (replyElement) {
    // Scroll to the reply with some offset
    replyElement.scrollIntoView({ 
      behavior: 'smooth', 
      block: 'center' 
    })
    
    // Highlight the reply temporarily
    highlightedReplyId.value = parseInt(replyId)
    
    // Remove highlight after 3 seconds
    setTimeout(() => {
      highlightedReplyId.value = null
    }, 3000)
  }
}

const handleHashNavigation = async () => {
  const hash = route.hash
  if (hash && hash.startsWith('#reply-')) {
    const replyId = hash.replace('#reply-', '')
    
    // Multiple attempts with increasing delays to ensure DOM is ready
    const attempts = [200, 500, 1000, 2000]
    
    for (const delay of attempts) {
      setTimeout(() => {
        const element = document.getElementById(`reply-${replyId}`)
        if (element) {
          scrollToReply(replyId)
          return // Stop trying once we find the element
        }
      }, delay)
    }
  }
}

const refreshPost = async () => {
  if (post.value) {
    await postStore.fetchPost(post.value.PostID, false) // Force fresh fetch
    await loadReplies()
    await buildPostHierarchy()
  }
}

const loadPost = async () => {
  const postId = route.params.id
  if (postId) {
    console.log('🔍 Loading post', postId)
    
    // Use cache-first approach for post loading
    await postStore.fetchPost(postId, true) // useCache = true
    
    // Load replies from cached data after post is loaded
    await loadReplies()
    
    // Build post hierarchy for navigation using cache-first approach
    await buildPostHierarchy()
    
    // Handle hash navigation after everything is loaded
    await nextTick() // Wait for replies to render
    await handleHashNavigation()
  }
}

const deletePost = async () => {
  if (confirm('Are you sure you want to delete this post? This action cannot be undone.')) {
    const success = await postStore.deletePost(post.value.PostID)
    if (success) {
      router.push('/')
    }
  }
}

onMounted(() => {
  loadPost()
})

// Watch for route changes (including hash changes)
watch(() => route.hash, () => {
  handleHashNavigation()
})

// Watch for route param changes (when navigating between different posts)
watch(() => route.params.id, () => {
  loadPost()
})

// Watch for replies loading to trigger hash navigation
watch(replies, (newReplies) => {
  if (newReplies.length > 0 && route.hash) {
    setTimeout(() => {
      handleHashNavigation()
    }, 100)
  }
}, { deep: true })
</script>

<style scoped>
.reply-container {
  position: relative;
}

.reply-container .ml-8 {
  position: relative;
}

.reply-container .ml-8::before {
  content: '';
  position: absolute;
  left: -20px;
  top: 0;
  bottom: 0;
  width: 2px;
  background-color: #e5e7eb;
}

.reply-container .ml-8::after {
  content: '';
  position: absolute;
  left: -20px;
  top: 20px;
  width: 20px;
  height: 2px;
  background-color: #e5e7eb;
}

/* Breadcrumb navigation styling */
nav .max-w-xs {
  display: inline-block;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Responsive breadcrumb styling */
@media (max-width: 640px) {
  nav .max-w-xs {
    max-width: 8rem;
  }
}

@media (min-width: 641px) and (max-width: 1024px) {
  nav .max-w-xs {
    max-width: 12rem;
  }
}
</style>
