<template>
  <div class="min-h-screen bg-gray-50 py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="px-6 py-8">
          <h1 class="text-4xl font-bold text-gray-900 mb-6 text-center">About Our Forum</h1>
          
          <div class="prose prose-lg max-w-none">
            <p class="text-xl text-gray-600 mb-8 text-center">
              Welcome to our community-driven forum where ideas flourish and conversations thrive.
            </p>
            
            <div class="grid md:grid-cols-2 gap-8 mt-8">
              <div class="space-y-4">
                <h2 class="text-2xl font-semibold text-gray-900">Our Mission</h2>
                <p class="text-gray-700">
                  We believe in creating a space where people from all walks of life can share their thoughts, 
                  experiences, and knowledge. Our platform is designed to foster meaningful discussions and 
                  build lasting connections within our community.
                </p>
              </div>
              
              <div class="space-y-4">
                <h2 class="text-2xl font-semibold text-gray-900">Features</h2>
                <ul class="text-gray-700 space-y-2">
                  <li class="flex items-center">
                    <svg class="h-5 w-5 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Create and share posts
                  </li>
                  <li class="flex items-center">
                    <svg class="h-5 w-5 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Tag your content
                  </li>
                  <li class="flex items-center">
                    <svg class="h-5 w-5 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Search and discover
                  </li>
                  <li class="flex items-center">
                    <svg class="h-5 w-5 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    User profiles
                  </li>
                </ul>
              </div>
            </div>
            
            <div class="mt-12 text-center">
              <h2 class="text-2xl font-semibold text-gray-900 mb-4">Get Started</h2>
              <p class="text-gray-700 mb-6">
                Ready to join our community? Create an account and start sharing your thoughts today.
              </p>
              <div class="space-x-4">
                <router-link 
                  to="/register" 
                  class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-md text-sm font-medium transition-colors duration-200"
                >
                  Join Now
                </router-link>
                <router-link 
                  to="/login" 
                  class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-3 rounded-md text-sm font-medium transition-colors duration-200"
                >
                  Sign In
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
