<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Loading State -->
      <div v-if="postStore.loading" class="flex justify-center items-center h-64">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
        <span class="ml-4 text-gray-600">Loading post...</span>
      </div>

      <!-- Error State -->
      <div v-else-if="postStore.error" class="bg-red-50 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
        <h2 class="text-lg font-semibold mb-2">Error Loading Post</h2>
        <p>{{ postStore.error }}</p>
        <button 
          @click="loadPost" 
          class="mt-4 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
        >
          Try Again
        </button>
      </div>

      <!-- Post Content -->
      <article v-else-if="post" class="bg-white rounded-lg shadow-lg overflow-hidden">
        <!-- Post Header -->
        <header class="px-6 py-4 border-b border-gray-200">
          <div class="flex justify-between items-start">
            <div class="flex-1">              <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ post.Topic }}</h1>
              <div class="flex items-center text-sm text-gray-500 space-x-4">
                <span>By {{ post.author?.Username || 'Anonymous' }}</span>
                <span>•</span>
                <span>{{ formatDate(post.created_at) }}</span>
                <span v-if="post.updated_at !== post.created_at">
                  • Updated {{ formatDate(post.updated_at) }}
                </span>
              </div>
            </div>
            
            <!-- Action Buttons (for post owner) -->
            <div v-if="canEditPost" class="flex space-x-2">
              <router-link 
                :to="`/edit/${post.PostID}`"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
              >
                Edit
              </router-link>
              <button 
                @click="deletePost"
                :disabled="postStore.loading"
                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200 disabled:bg-gray-400"
              >
                Delete
              </button>
            </div>
          </div>
        </header>

        <!-- Post Content -->
        <div class="px-6 py-6">
          <div class="prose prose-lg max-w-none">
            <p class="text-gray-700 leading-relaxed whitespace-pre-wrap">{{ post.Content }}</p>
          </div>
        </div>

        <!-- Tags -->        <div v-if="post.tags && post.tags.length > 0" class="px-6 py-4 border-t border-gray-200">
          <div class="flex flex-wrap gap-2">
            <span 
              v-for="tag in post.tags" 
              :key="tag.TagName"
              class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800"
            >
              {{ tag.TagName }}
            </span>
          </div>
        </div>
      </article>

      <!-- Post Not Found -->
      <div v-else class="text-center py-12">
        <h2 class="text-2xl font-bold text-gray-900 mb-4">Post Not Found</h2>
        <p class="text-gray-600 mb-6">The post you're looking for doesn't exist or has been removed.</p>
        <router-link 
          to="/" 
          class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-md text-sm font-medium transition-colors duration-200"
        >
          Back to Home
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePostStore } from '@/stores/post.js'
import { useAuthStore } from '@/stores/auth.js'

const route = useRoute()
const router = useRouter()
const postStore = usePostStore()
const authStore = useAuthStore()

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

const loadPost = async () => {
  const postId = route.params.id
  if (postId) {
    await postStore.fetchPost(postId)
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
</script>
