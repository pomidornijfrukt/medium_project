<template>
  <div class="advanced-search-container min-h-screen bg-gray-50">
    <!-- Skip Navigation -->
    <a 
      href="#main-content" 
      class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-indigo-600 text-white px-4 py-2 rounded-md z-50 focus:z-50"
      @click="skipToMain"
    >
      Skip to main content
    </a>

    <!-- Search Header -->
    <header class="search-header bg-white shadow-sm border-b border-gray-200 py-6" role="banner">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
          <h1 
            id="page-title"
            class="text-3xl font-bold text-gray-800 mb-4"
            tabindex="-1"
          >
            <span class="sr-only">Advanced Post Search - </span>
            <svg class="inline-block w-8 h-8 mr-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            Advanced Post Search
          </h1>
          <p class="text-gray-600 mb-6 max-w-2xl mx-auto">
            Find posts using powerful filters and sorting options. Use the search form below to narrow down results by content, tags, author roles, and more.
          </p>
        </div>
      </div>
    </header>

    <main id="main-content" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" tabindex="-1">
      <!-- Live region for search announcements -->
      <div 
        id="search-announcements"
        aria-live="polite" 
        aria-atomic="true" 
        class="sr-only"
      >
        {{ announcementText }}
      </div>

      <!-- Search Form -->
      <section 
        class="search-form bg-white rounded-lg shadow-md p-6 mb-6" 
        aria-labelledby="search-form-title"
        role="search"
      >
        <h2 id="search-form-title" class="text-xl font-semibold text-gray-800 mb-4">
          Search Filters
        </h2>
        
        <form @submit.prevent="performSearch" role="form" aria-label="Advanced search form">
          <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
            <!-- Search Term -->
            <div>
              <AccessibleInput
                id="search-term"
                v-model="searchFilters.search"
                label="Search Term"
                placeholder="Search in titles and content..."
                :icon="'search'"
                help-text="Search for keywords in post titles and content"
                @keyup.enter="performSearch"
                :aria-describedby="'search-term-help'"
              />
              <p id="search-term-help" class="text-xs text-gray-500 mt-1">
                Press Enter to search
              </p>
            </div>

            <!-- Tags Filter -->
            <div>
              <AccessibleInput
                id="include-tags"
                v-model="searchFilters.tags"
                label="Include Tags"
                placeholder="e.g., javascript,laravel,vue"
                :icon="'tags'"
                help-text="Comma-separated tag names to include in search results"
                :aria-describedby="'include-tags-help'"
              />
              <p id="include-tags-help" class="text-xs text-gray-500 mt-1">
                Comma-separated tag names to include
              </p>
            </div>

            <!-- Exclude Tags Filter -->
            <div>
              <AccessibleInput
                id="exclude-tags"
                v-model="searchFilters.exclude_tags"
                label="Exclude Tags"
                placeholder="e.g., spam,off-topic,deprecated"
                :icon="'ban'"
                help-text="Comma-separated tag names to exclude from search results"
                :aria-describedby="'exclude-tags-help'"
              />
              <p id="exclude-tags-help" class="text-xs text-gray-500 mt-1">
                Comma-separated tag names to exclude
              </p>
            </div>

            <!-- Author Role Filter -->
            <div>
              <label for="author-role" class="block text-sm font-medium text-gray-700 mb-2">
                <svg class="inline-block w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Author Role
              </label>
              <select
                id="author-role"
                v-model="searchFilters.author_role"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                aria-describedby="author-role-help"
              >
                <option value="">All Roles</option>
                <option value="admin">Admin</option>
                <option value="moderator">Moderator</option>
                <option value="member">Member</option>
              </select>
              <p id="author-role-help" class="text-xs text-gray-500 mt-1">
                Filter by the role of the post author
              </p>
            </div>

            <!-- Minimum Replies -->
            <div>
              <label for="min-replies" class="block text-sm font-medium text-gray-700 mb-2">
                <svg class="inline-block w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                Minimum Replies
              </label>
              <input
                id="min-replies"
                v-model.number="searchFilters.min_replies"
                type="number"
                min="0"
                placeholder="0"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                aria-describedby="min-replies-help"
              />
              <p id="min-replies-help" class="text-xs text-gray-500 mt-1">
                Show only posts with at least this many replies
              </p>
            </div>

            <!-- Sort By -->
            <div>
              <label for="sort-by" class="block text-sm font-medium text-gray-700 mb-2">
                <svg class="inline-block w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4" />
                </svg>
                Sort By
              </label>
              <select
                id="sort-by"
                v-model="searchFilters.sort_by"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                aria-describedby="sort-by-help"
              >
                <option value="recent">Most Recent</option>
                <option value="popular">Most Popular</option>
                <option value="replies">Most Replies</option>
                <option value="engagement">Highest Engagement</option>
              </select>
              <p id="sort-by-help" class="text-xs text-gray-500 mt-1">
                Choose how to order the search results
              </p>
            </div>

            <!-- Results Per Page -->
            <div>
              <label for="results-per-page" class="block text-sm font-medium text-gray-700 mb-2">
                <svg class="inline-block w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                </svg>
                Results Per Page
              </label>
              <select
                id="results-per-page"
                v-model.number="searchFilters.per_page"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                aria-describedby="per-page-help"
              >
                <option :value="10">10</option>
                <option :value="20">20</option>
                <option :value="50">50</option>
              </select>
              <p id="per-page-help" class="text-xs text-gray-500 mt-1">
                Number of search results to display per page
              </p>
            </div>
          </div>

          <!-- Search Actions -->
          <div class="flex flex-wrap gap-3" role="group" aria-label="Search actions">
            <button
              type="submit"
              :disabled="loading"
              class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 flex items-center transition-colors"
              :aria-describedby="loading ? 'search-loading' : 'search-action'"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              {{ loading ? 'Searching...' : 'Search Posts' }}
            </button>
            <span v-if="loading" id="search-loading" class="sr-only">Search in progress, please wait</span>
            <span v-else id="search-action" class="sr-only">Submit the search form to find matching posts</span>
            
            <button
              type="button"
              @click="clearFilters"
              class="px-6 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 flex items-center transition-colors"
              aria-label="Clear all search filters and reset form"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
              Clear Filters
            </button>

            <button
              type="button"
              @click="getTrendingPosts"
              :disabled="loadingTrending"
              class="px-6 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 flex items-center transition-colors"
              :aria-describedby="loadingTrending ? 'trending-loading' : 'trending-action'"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
              </svg>
              {{ loadingTrending ? 'Loading...' : 'Show Trending' }}
            </button>
            <span v-if="loadingTrending" id="trending-loading" class="sr-only">Loading trending posts, please wait</span>
            <span v-else id="trending-action" class="sr-only">Load and display currently trending posts</span>
          </div>
        </form>
      </section>

      <!-- Search Results Summary -->
      <section
        v-if="searchResults && searchResults.aggregations"
        class="search-summary bg-indigo-50 rounded-lg p-6 mb-6"
        aria-labelledby="results-summary-title"
        role="region"
      >
        <h2 id="results-summary-title" class="text-lg font-semibold text-gray-800 mb-4">Search Results Summary</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
          <div class="summary-stat">
            <div class="text-3xl font-bold text-indigo-600" aria-label="Total results">
              {{ searchResults.aggregations.total_results }}
            </div>
            <div class="text-sm text-gray-600">Total Results</div>
          </div>
          <div class="summary-stat">
            <div class="text-3xl font-bold text-emerald-600" aria-label="Average replies">
              {{ (searchResults.aggregations.avg_replies || 0).toFixed(1) }}
            </div>
            <div class="text-sm text-gray-600">Avg. Replies</div>
          </div>
          <div class="summary-stat">
            <div class="text-3xl font-bold text-purple-600" aria-label="Average content length">
              {{ Math.round(searchResults.aggregations.avg_content_length || 0) }}
            </div>
            <div class="text-sm text-gray-600">Avg. Length</div>
          </div>
          <div class="summary-stat">
            <div class="text-3xl font-bold text-orange-600" aria-label="Role distribution">
              {{ Object.keys(searchResults.aggregations.role_distribution || {}).length }}
            </div>
            <div class="text-sm text-gray-600">Role Types</div>
          </div>
        </div>
      </section>

      <!-- Active Filters Display -->
      <section
        v-if="hasActiveFilters"
        class="active-filters mb-6"
        aria-labelledby="active-filters-title"
        role="region"
      >
        <h2 id="active-filters-title" class="sr-only">Active search filters</h2>
        <div class="flex flex-wrap items-center gap-2">
          <span class="text-sm text-gray-600 mr-2">Active filters:</span>
          <div
            v-if="searchFilters.search"
            class="filter-tag px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full text-sm flex items-center gap-2"
          >
            <span>Search: "{{ searchFilters.search }}"</span>
            <button
              @click="searchFilters.search = ''"
              class="text-indigo-600 hover:text-indigo-800 focus:outline-none focus:ring-1 focus:ring-indigo-500 rounded"
              aria-label="Remove search term filter"
            >
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div
            v-if="searchFilters.tags"
            class="filter-tag px-3 py-1 bg-emerald-100 text-emerald-800 rounded-full text-sm flex items-center gap-2"
          >
            <span>Tags: {{ searchFilters.tags }}</span>
            <button
              @click="searchFilters.tags = ''"
              class="text-emerald-600 hover:text-emerald-800 focus:outline-none focus:ring-1 focus:ring-emerald-500 rounded"
              aria-label="Remove include tags filter"
            >
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div
            v-if="searchFilters.exclude_tags"
            class="filter-tag px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm flex items-center gap-2"
          >
            <span>Exclude: {{ searchFilters.exclude_tags }}</span>
            <button
              @click="searchFilters.exclude_tags = ''"
              class="text-red-600 hover:text-red-800 focus:outline-none focus:ring-1 focus:ring-red-500 rounded"
              aria-label="Remove exclude tags filter"
            >
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div
            v-if="searchFilters.author_role"
            class="filter-tag px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm flex items-center gap-2"
          >
            <span>Role: {{ searchFilters.author_role }}</span>
            <button
              @click="searchFilters.author_role = ''"
              class="text-purple-600 hover:text-purple-800 focus:outline-none focus:ring-1 focus:ring-purple-500 rounded"
              aria-label="Remove author role filter"
            >
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div
            v-if="searchFilters.min_replies > 0"
            class="filter-tag px-3 py-1 bg-orange-100 text-orange-800 rounded-full text-sm flex items-center gap-2"
          >
            <span>Min Replies: {{ searchFilters.min_replies }}</span>
            <button
              @click="searchFilters.min_replies = 0"
              class="text-orange-600 hover:text-orange-800 focus:outline-none focus:ring-1 focus:ring-orange-500 rounded"
              aria-label="Remove minimum replies filter"
            >
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </section>

      <!-- Error Message -->
      <div v-if="error" class="error-message mb-6" role="alert" aria-live="assertive">
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
          <div class="flex items-start">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-red-800">Search Error</h3>
              <p class="text-sm text-red-700 mt-1">{{ error }}</p>
              <button
                @click="retrySearch"
                class="mt-2 bg-red-600 text-white px-4 py-2 rounded text-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors"
              >
                Try Again
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <AccessibleLoading
        v-if="loading"
        message="Searching posts with your criteria"
        :show-progress="true"
        size="large"
        class="my-12"
      />

      <!-- No Results -->
      <div
        v-else-if="searchResults && searchResults.posts && searchResults.posts.length === 0"
        class="no-results text-center py-12"
        role="status"
      >
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No posts found</h3>
        <p class="mt-1 text-sm text-gray-500">Try adjusting your search criteria or clearing filters.</p>
        <button
          @click="clearFilters"
          class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors"
        >
          Clear All Filters
        </button>
      </div>

      <!-- Search Results -->
      <section 
        v-else-if="searchResults && searchResults.posts" 
        class="search-results"
        aria-labelledby="search-results-title"
        role="region"
      >
        <!-- Results Header -->
        <div class="results-header bg-white rounded-lg shadow-sm p-4 mb-6 border border-gray-200">
          <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <div>
              <h3 
                id="search-results-title" 
                class="text-lg font-semibold text-gray-800"
                tabindex="-1"
                ref="resultsTitle"
              >
                <span class="sr-only">Search completed. </span>
                Search Results
                <span class="text-sm font-normal text-gray-600 ml-2">
                  ({{ searchResults.pagination.total }} {{ searchResults.pagination.total === 1 ? 'post' : 'posts' }} found)
                </span>
              </h3>
              <div class="text-sm text-gray-600 mt-1">
                <span class="sr-only">Currently viewing </span>
                Page {{ searchResults.pagination.current_page }} of {{ searchResults.pagination.last_page }}
                <span class="mx-2 text-gray-400">•</span>
                Showing {{ searchResults.posts.length }} {{ searchResults.posts.length === 1 ? 'result' : 'results' }}
              </div>
            </div>
            
            <!-- Sort Controls -->
            <div class="flex items-center gap-3">
              <label for="results-sort" class="text-sm font-medium text-gray-700 sr-only">
                Sort results by
              </label>
              <select
                id="results-sort"
                v-model="searchFilters.sort_by"
                @change="handleSortChange"
                class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                aria-label="Sort results by"
              >
                <option value="recent">Most Recent</option>
                <option value="popular">Most Popular</option>
                <option value="replies">Most Replies</option>
                <option value="engagement">Highest Engagement</option>
              </select>
              
              <!-- Results Navigation -->
              <div class="flex items-center gap-2" role="group" aria-label="Results navigation shortcuts">
                <button
                  @click="focusFirstResult"
                  class="px-3 py-1 text-xs bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                  title="Jump to first result"
                  aria-label="Jump to first result"
                >
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                  </svg>
                </button>
                <span class="text-xs text-gray-500">{{ currentResultIndex + 1 }}/{{ searchResults.posts.length }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Posts List -->
        <div 
          class="posts-list space-y-4" 
          role="feed" 
          aria-labelledby="search-results-title"
          aria-describedby="results-navigation-help"
        >
          <!-- Navigation Help -->
          <div id="results-navigation-help" class="sr-only">
            Use Tab to navigate between post cards. Press Enter or Space to view a post. Use arrow keys to navigate between tag actions.
          </div>

          <article
            v-for="(post, index) in searchResults.posts"
            :key="post.post_id"
            :ref="el => { if (el) postRefs[index] = el }"
            class="post-card bg-white rounded-lg shadow-md hover:shadow-lg transition-all duration-200 p-6 border-l-4 focus-within:ring-2 focus-within:ring-indigo-500 focus-within:ring-offset-2"
            :class="getPostBorderColor(post.engagement_score)"
            :aria-labelledby="`post-title-${post.post_id}`"
            :aria-describedby="`post-summary-${post.post_id}`"
            role="article"
            tabindex="0"
            @focus="handlePostFocus(index)"
            @keydown="handlePostKeydown($event, post, index)"
          >
            <!-- Post Header -->
            <header class="post-header flex flex-col sm:flex-row sm:justify-between sm:items-start gap-3 mb-4">
              <div class="flex-1">
                <h4 
                  :id="`post-title-${post.post_id}`"
                  class="text-xl font-semibold text-gray-800 hover:text-indigo-600 cursor-pointer transition-colors"
                  @click="viewPost(post.post_id)"
                  tabindex="-1"
                >
                  {{ post.title }}
                </h4>
                
                <!-- Author and Metadata -->
                <div class="flex flex-wrap items-center text-sm text-gray-600 mt-2 gap-2">
                  <div class="flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span class="font-medium">{{ post.author }}</span>
                  </div>
                  
                  <span 
                    class="px-2 py-1 rounded-full text-xs font-medium" 
                    :class="getRoleBadgeClass(post.author_role)"
                    :aria-label="`Author role: ${post.author_role}`"
                  >
                    {{ post.author_role }}
                  </span>
                  
                  <time 
                    :datetime="post.created_at"
                    class="text-gray-500"
                    :title="formatFullDate(post.created_at)"
                  >
                    {{ formatDate(post.created_at) }}
                  </time>
                </div>
              </div>
              
              <!-- Engagement Score -->
              <div class="engagement-score text-center sm:text-right">
                <div 
                  class="text-lg font-bold" 
                  :class="getEngagementScoreColor(post.engagement_score)"
                  :aria-label="`Engagement score: ${post.engagement_score} out of 100`"
                >
                  {{ post.engagement_score }}
                </div>
                <div class="text-xs text-gray-500">Engagement</div>
              </div>
            </header>

            <!-- Post Content Preview -->
            <div class="post-content mb-4">
              <p 
                :id="`post-summary-${post.post_id}`"
                class="text-gray-700 line-clamp-3"
              >
                {{ post.content_preview }}
              </p>
            </div>

            <!-- Post Metrics -->
            <div 
              class="post-metrics grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4 text-sm"
              role="list"
              aria-label="Post statistics"
            >
              <div class="metric flex items-center" role="listitem">
                <svg class="w-4 h-4 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <span>
                  <span class="font-medium">{{ post.reply_count }}</span>
                  {{ post.reply_count === 1 ? 'reply' : 'replies' }}
                </span>
              </div>
              
              <div class="metric flex items-center" role="listitem">
                <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span>
                  <span class="font-medium">{{ post.unique_repliers }}</span>
                  {{ post.unique_repliers === 1 ? 'participant' : 'participants' }}
                </span>
              </div>
              
              <div class="metric flex items-center" role="listitem">
                <svg class="w-4 h-4 text-purple-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
                <span>
                  <span class="font-medium">{{ post.tag_count }}</span>
                  {{ post.tag_count === 1 ? 'tag' : 'tags' }}
                </span>
              </div>
              
              <div class="metric flex items-center" role="listitem">
                <svg class="w-4 h-4 text-orange-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>
                  <span class="font-medium">{{ post.days_since_created }}</span>
                  {{ post.days_since_created === 1 ? 'day' : 'days' }} old
                </span>
              </div>
            </div>

            <!-- Post Tags -->
            <div 
              v-if="post.tag_list && post.tag_list.length > 0"
              class="post-tags mb-4"
              role="group"
              :aria-label="`Tags for ${post.title}`"
            >
              <div class="flex flex-wrap gap-2">
                <div
                  v-for="(tag, tagIndex) in post.tag_list"
                  :key="tag"
                  class="tag-container flex items-center"
                  role="group"
                  :aria-label="`Tag: ${tag} with filter and exclude options`"
                >
                  <button
                    class="px-2 py-1 bg-gray-100 text-gray-700 rounded-l-md text-xs hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                    @click="filterByTag(tag)"
                    :aria-label="`Filter posts by tag: ${tag}`"
                    @keydown="handleTagKeydown($event, tag, tagIndex, post.tag_list.length)"
                  >
                    #{{ tag }}
                  </button>
                  <button
                    class="px-2 py-1 bg-red-100 text-red-600 rounded-r-md text-xs hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors"
                    @click="excludeTag(tag)"
                    :aria-label="`Exclude posts with tag: ${tag}`"
                    @keydown="handleTagKeydown($event, tag, tagIndex, post.tag_list.length)"
                  >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>

            <!-- Post Actions -->
            <footer class="post-actions flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 pt-4 border-t border-gray-100">
              <div class="flex gap-2">
                <button
                  @click="viewPost(post.post_id)"
                  class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors text-sm font-medium"
                  :aria-label="`View full post: ${post.title}`"
                >
                  <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  View Post
                </button>
                
                <button
                  @click="addToBookmarks(post.post_id)"
                  class="px-3 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors text-sm"
                  :aria-label="`Bookmark post: ${post.title}`"
                  title="Bookmark this post"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                  </svg>
                </button>
              </div>
              
              <div class="text-xs text-gray-500">
                <span class="sr-only">Last activity: </span>
                <time 
                  :datetime="post.last_activity"
                  :title="formatFullDate(post.last_activity)"
                >
                  {{ formatDate(post.last_activity) }}
                </time>
              </div>
            </footer>
          </article>
        </div>

        <!-- Accessible Pagination -->
        <AccessiblePagination
          v-if="searchResults.pagination.last_page > 1"
          :current-page="searchResults.pagination.current_page"
          :total-pages="searchResults.pagination.last_page"
          :total-items="searchResults.pagination.total"
          :items-per-page="searchFilters.per_page"
          @page-change="handlePageChange"
          class="mt-8"
          aria-label="Search results pagination"
        />

        <!-- Keyboard Shortcuts Help -->
        <div 
          class="keyboard-shortcuts mt-6 p-4 bg-gray-50 rounded-lg border"
          role="region"
          aria-labelledby="shortcuts-title"
        >
          <h4 id="shortcuts-title" class="text-sm font-medium text-gray-800 mb-2">
            <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Keyboard Shortcuts
          </h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs text-gray-600">
            <div><kbd class="px-1 py-0.5 bg-white border rounded">Tab</kbd> Navigate between posts</div>
            <div><kbd class="px-1 py-0.5 bg-white border rounded">Enter</kbd> View selected post</div>
            <div><kbd class="px-1 py-0.5 bg-white border rounded">↑/↓</kbd> Navigate posts with keyboard</div>
            <div><kbd class="px-1 py-0.5 bg-white border rounded">Home/End</kbd> Jump to first/last post</div>
          </div>
        </div>
      </section>

    <!-- No Results -->
    <div v-else-if="searchPerformed && !loading" class="no-results text-center py-12">
      <i class="fas fa-search text-gray-400 text-4xl mb-4"></i>
      <h3 class="text-xl font-semibold text-gray-600 mb-2">No posts found</h3>
      <p class="text-gray-500">Try adjusting your search filters or criteria</p>
    </div>

    <!-- Initial State -->
    <div v-else class="initial-state text-center py-12">
      <i class="fas fa-search text-gray-400 text-4xl mb-4"></i>
      <h3 class="text-xl font-semibold text-gray-600 mb-2">Ready to search</h3>
      <p class="text-gray-500">Use the filters above to find the perfect posts</p>
    </div>
    </main>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useAccessibility } from '@/composables/useAccessibility'
import AccessiblePagination from '@/components/AccessiblePagination.vue'
import AccessibleInput from '@/components/AccessibleInput.vue'
import AccessibleLoading from '@/components/AccessibleLoading.vue'

const router = useRouter()
const { announceToScreenReader, focusElement } = useAccessibility()

// Reactive state
const loading = ref(false)
const loadingTrending = ref(false)
const searchPerformed = ref(false)
const error = ref(null)
const searchResults = ref(null)
const announcementText = ref('')

// Accessibility refs
const resultsTitle = ref(null)
const postRefs = ref({})
const currentResultIndex = ref(0)

const searchFilters = reactive({
  search: '',
  tags: '',
  exclude_tags: '',
  author_role: '',
  min_replies: 0,
  sort_by: 'recent',
  per_page: 20,
  page: 1
})

// Computed properties
const hasActiveFilters = computed(() => {
  return searchFilters.search || 
         searchFilters.tags || 
         searchFilters.exclude_tags ||
         searchFilters.author_role || 
         searchFilters.min_replies > 0
})

// Methods
const performSearch = async () => {
  loading.value = true
  error.value = null
  searchPerformed.value = true
  announcementText.value = 'Searching...'

  try {
    const queryParams = new URLSearchParams()
    
    // Add non-empty filters to query params
    Object.keys(searchFilters).forEach(key => {
      const value = searchFilters[key]
      if (value !== '' && value !== null && value !== undefined && !(key === 'min_replies' && value === 0)) {
        queryParams.append(key, value)
      }
    })

    const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:6969/api'
    const response = await fetch(`${API_BASE_URL}/posts/advanced-search?${queryParams.toString()}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    })

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }

    const data = await response.json()
    
    if (data.success) {
      searchResults.value = data.data
      const resultCount = data.data.pagination.total
      announcementText.value = `Search completed. Found ${resultCount} ${resultCount === 1 ? 'post' : 'posts'}.`
      
      // Focus on results title after search completes
      await nextTick()
      if (resultsTitle.value) {
        focusElement(resultsTitle.value)
      }
    } else {
      throw new Error(data.message || 'Search failed')
    }

  } catch (err) {
    console.error('Search error:', err)
    error.value = err.message
    searchResults.value = null
    announcementText.value = `Search failed: ${err.message}`
  } finally {
    loading.value = false
  }
}

const getTrendingPosts = async () => {
  loadingTrending.value = true
  error.value = null

  try {
    const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:6969/api'
    const response = await fetch(`${API_BASE_URL}/posts/trending?period=7d`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    })

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }

    const data = await response.json()
    
    if (data.success) {
      // Convert trending posts to search results format
      searchResults.value = {
        posts: data.data.trending_posts.map(post => ({
          ...post,
          content_preview: `Trending post with ${post.trending_score} trending score`,
          tag_list: post.tags,
          engagement_score: post.trending_score,
          content_length: 0,
          last_activity: post.created_at
        })),
        aggregations: {
          total_results: data.data.trending_posts.length,
          avg_replies: data.data.trending_posts.reduce((sum, p) => sum + (p.reply_count || 0), 0) / data.data.trending_posts.length || 0,
          avg_content_length: 0,
          role_distribution: data.data.trending_posts.reduce((acc, p) => {
            const role = p.author_role || 'member'
            acc[role] = (acc[role] || 0) + 1
            return acc
          }, {})
        },
        pagination: {
          current_page: 1,
          per_page: data.data.trending_posts.length,
          total: data.data.trending_posts.length,
          last_page: 1
        }
      }
      searchPerformed.value = true
    } else {
      throw new Error(data.message || 'Failed to fetch trending posts')
    }

  } catch (err) {
    console.error('Trending posts error:', err)
    error.value = err.message
  } finally {
    loadingTrending.value = false
  }
}

const clearFilters = () => {
  Object.assign(searchFilters, {
    search: '',
    tags: '',
    exclude_tags: '',
    author_role: '',
    min_replies: 0,
    sort_by: 'recent',
    per_page: 20,
    page: 1
  })
  searchResults.value = null
  searchPerformed.value = false
  error.value = null
}

const changePage = (newPage) => {
  if (newPage >= 1 && newPage <= searchResults.value.pagination.last_page) {
    searchFilters.page = newPage
    performSearch()
  }
}

const filterByTag = (tagName) => {
  searchFilters.tags = tagName
  searchFilters.page = 1
  performSearch()
}

const excludeTag = (tagName) => {
  // Add to exclude_tags, handling existing exclusions
  const currentExcludes = searchFilters.exclude_tags.split(',').filter(tag => tag.trim() !== '')
  if (!currentExcludes.includes(tagName)) {
    currentExcludes.push(tagName)
    searchFilters.exclude_tags = currentExcludes.join(',')
    searchFilters.page = 1
    performSearch()
    announceToScreenReader(`Excluded tag ${tagName} from search`)
  }
}

const viewPost = (postId) => {
  router.push(`/posts/${postId}`)
}

const retrySearch = () => {
  performSearch()
}

// Accessibility methods
const skipToMain = () => {
  focusElement('#main-content')
}

const handleSortChange = async () => {
  searchFilters.page = 1
  await performSearch()
  announceToScreenReader(`Results sorted by ${searchFilters.sort_by}`)
  await nextTick()
  focusElement(resultsTitle.value)
}

const handlePageChange = async (newPage) => {
  searchFilters.page = newPage
  await performSearch()
  announceToScreenReader(`Moved to page ${newPage}`)
  await nextTick()
  focusElement(resultsTitle.value)
}

const focusFirstResult = () => {
  const firstPost = postRefs.value[0]
  if (firstPost) {
    firstPost.focus()
    currentResultIndex.value = 0
    announceToScreenReader('Focused on first search result')
  }
}

const handlePostFocus = (index) => {
  currentResultIndex.value = index
}

const handlePostKeydown = (event, post, index) => {
  switch (event.key) {
    case 'Enter':
    case ' ':
      event.preventDefault()
      viewPost(post.post_id)
      break
    case 'ArrowUp':
      event.preventDefault()
      navigateToPost(index - 1)
      break
    case 'ArrowDown':
      event.preventDefault()
      navigateToPost(index + 1)
      break
    case 'Home':
      event.preventDefault()
      navigateToPost(0)
      break
    case 'End':
      event.preventDefault()
      navigateToPost(searchResults.value.posts.length - 1)
      break
  }
}

const handleTagKeydown = (event, tag, tagIndex, totalTags) => {
  switch (event.key) {
    case 'ArrowRight':
      event.preventDefault()
      if (tagIndex < totalTags - 1) {
        // Focus next tag button
        const nextButton = event.target.parentElement.nextElementSibling?.querySelector('button')
        if (nextButton) nextButton.focus()
      }
      break
    case 'ArrowLeft':
      event.preventDefault()
      if (tagIndex > 0) {
        // Focus previous tag button
        const prevButton = event.target.parentElement.previousElementSibling?.querySelector('button')
        if (prevButton) prevButton.focus()
      }
      break
  }
}

const navigateToPost = (index) => {
  if (index >= 0 && index < searchResults.value.posts.length) {
    const targetPost = postRefs.value[index]
    if (targetPost) {
      targetPost.focus()
      currentResultIndex.value = index
    }
  }
}

const addToBookmarks = (postId) => {
  // TODO: Implement bookmark functionality
  announceToScreenReader('Post bookmarked')
}

const formatFullDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Utility methods
const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const getPostBorderColor = (engagementScore) => {
  if (engagementScore >= 20) return 'border-red-500'
  if (engagementScore >= 15) return 'border-orange-500'
  if (engagementScore >= 10) return 'border-yellow-500'
  if (engagementScore >= 5) return 'border-green-500'
  return 'border-blue-500'
}

const getEngagementScoreColor = (score) => {
  if (score >= 20) return 'text-red-600'
  if (score >= 15) return 'text-orange-600'
  if (score >= 10) return 'text-yellow-600'
  if (score >= 5) return 'text-green-600'
  return 'text-blue-600'
}

const getRoleBadgeClass = (role) => {
  switch (role) {
    case 'admin': return 'bg-red-100 text-red-800'
    case 'moderator': return 'bg-purple-100 text-purple-800'
    case 'member': return 'bg-blue-100 text-blue-800'
    default: return 'bg-gray-100 text-gray-800'
  }
}
</script>

<style scoped>
.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.advanced-search-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.post-card {
  transition: all 0.3s ease;
}

.post-card:hover {
  transform: translateY(-2px);
}

.metric {
  white-space: nowrap;
}

@media (max-width: 768px) {
  .post-metrics {
    grid-template-columns: 1fr 1fr;
  }
}
</style>
