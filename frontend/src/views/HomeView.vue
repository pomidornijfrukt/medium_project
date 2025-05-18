<script setup>
import TheWelcome from '../components/TheWelcome.vue'
</script>

<template>
  <main>
    <TheWelcome />
  </main>
  <div class="home-container">
    <div class="header">
      <h1>Welcome to the Forum</h1>
      <div class="search-bar">
        <input 
          type="text" 
          v-model="searchQuery" 
          placeholder="Search posts..." 
          @input="handleSearch"
        />
      </div>
      <div class="actions">
        <router-link v-if="authStore.isLoggedIn" to="/create" class="create-post-btn">
          Create New Post
        </router-link>
      </div>
    </div>

    <div class="tags-container">
      <h2>Tags</h2>
      <div class="tags-list" v-if="tagStore.allTags.length">
        <router-link 
          v-for="tag in tagStore.allTags" 
          :key="tag.TagName" 
          :to="`/tags/${tag.TagName}`"
          class="tag-link"
        >
          {{ tag.TagName }} ({{ tag.posts_count }})
        </router-link>
      </div>
      <p v-else>No tags available</p>
    </div>

    <div class="posts-container">
      <h2>Latest Posts</h2>
      
      <div v-if="postStore.loading" class="loading">
        Loading posts...
      </div>
      
      <div v-else-if="postStore.error" class="error-message">
        {{ postStore.error }}
      </div>
      
      <div v-else-if="postStore.allPosts.length === 0" class="no-posts">
        <p>No posts found.</p>
        <router-link v-if="authStore.isLoggedIn" to="/create" class="create-post-btn">
          Create the first post
        </router-link>
      </div>
      
      <div v-else class="posts-list">
        <div v-for="post in postStore.allPosts" :key="post.PostID" class="post-card">
          <h3>
            <router-link :to="`/posts/${post.PostID}`">
              {{ post.Topic }}
            </router-link>
          </h3>
          
          <div class="post-meta">
            <span>By: {{ post.author ? post.author.Username : 'Unknown' }}</span>
            <span>{{ formatDate(post.created_at) }}</span>
          </div>
          
          <div class="post-tags" v-if="post.tags && post.tags.length">
            <router-link 
              v-for="tag in post.tags" 
              :key="tag.TagName" 
              :to="`/tags/${tag.TagName}`"
              class="tag-badge"
            >
              {{ tag.TagName }}
            </router-link>
          </div>
          
          <p class="post-excerpt">
            {{ truncateContent(post.Content) }}
          </p>
          
          <router-link :to="`/posts/${post.PostID}`" class="read-more">
            Read more
          </router-link>
        </div>
      </div>
      
      <!-- Pagination -->
      <div class="pagination" v-if="postStore.pagination.lastPage > 1">
        <button 
          @click="changePage(postStore.pagination.currentPage - 1)"
          :disabled="postStore.pagination.currentPage === 1"
          class="pagination-btn"
        >
          Previous
        </button>
        
        <span class="page-info">
          Page {{ postStore.pagination.currentPage }} of {{ postStore.pagination.lastPage }}
        </span>
        
        <button 
          @click="changePage(postStore.pagination.currentPage + 1)"
          :disabled="postStore.pagination.currentPage === postStore.pagination.lastPage"
          class="pagination-btn"
        >
          Next
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.home-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}

.header {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 2rem;
}

h1 {
  margin-bottom: 1rem;
  color: #333;
}

.search-bar {
  width: 100%;
  max-width: 500px;
  margin-bottom: 1rem;
}

.search-bar input {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
}

.actions {
  display: flex;
  justify-content: center;
}

.create-post-btn {
  display: inline-block;
  padding: 0.75rem 1.5rem;
  background-color: #4f46e5;
  color: white;
  border-radius: 4px;
  text-decoration: none;
  font-weight: 500;
  transition: background-color 0.2s;
}

.create-post-btn:hover {
  background-color: #4338ca;
}

.tags-container {
  margin-bottom: 2rem;
  padding: 1.5rem;
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.tags-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.tag-link {
  display: inline-block;
  padding: 0.4rem 0.8rem;
  background-color: #e5e7eb;
  color: #4b5563;
  border-radius: 9999px;
  text-decoration: none;
  font-size: 0.875rem;
  transition: background-color 0.2s, color 0.2s;
}

.tag-link:hover {
  background-color: #d1d5db;
}

.posts-container {
  padding: 1.5rem;
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.loading, .no-posts {
  text-align: center;
  margin: 2rem 0;
  color: #6b7280;
}

.error-message {
  background-color: #fee2e2;
  border: 1px solid #ef4444;
  color: #b91c1c;
  padding: 0.75rem;
  border-radius: 4px;
  margin: 1.5rem 0;
  text-align: center;
}

.posts-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

.post-card {
  padding: 1.5rem;
  background-color: #f9fafb;
  border-radius: 8px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  transition: transform 0.2s, box-shadow 0.2s;
}

.post-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.post-card h3 {
  margin-bottom: 0.5rem;
}

.post-card h3 a {
  color: #111827;
  text-decoration: none;
}

.post-card h3 a:hover {
  text-decoration: underline;
}

.post-meta {
  display: flex;
  justify-content: space-between;
  font-size: 0.875rem;
  color: #6b7280;
  margin-bottom: 0.75rem;
}

.post-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.tag-badge {
  display: inline-block;
  padding: 0.2rem 0.5rem;
  background-color: #e5e7eb;
  color: #4b5563;
  border-radius: 9999px;
  text-decoration: none;
  font-size: 0.75rem;
}

.post-excerpt {
  margin-bottom: 1rem;
  font-size: 0.95rem;
  color: #4b5563;
  line-height: 1.5;
}

.read-more {
  display: inline-block;
  color: #4f46e5;
  text-decoration: none;
  font-weight: 500;
  font-size: 0.875rem;
}

.read-more:hover {
  text-decoration: underline;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-top: 2rem;
  gap: 1rem;
}

.pagination-btn {
  padding: 0.5rem 1rem;
  background-color: #f3f4f6;
  border: 1px solid #d1d5db;
  color: #374151;
  border-radius: 4px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.pagination-btn:hover:not(:disabled) {
  background-color: #e5e7eb;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-info {
  font-size: 0.875rem;
  color: #6b7280;
}

@media (max-width: 768px) {
  .posts-list {
    grid-template-columns: 1fr;
  }
  
  .header {
    text-align: center;
  }
}
</style>
