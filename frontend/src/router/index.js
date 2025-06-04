import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'
import HomeView from '../views/HomeView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView,
    },
    {
      path: '/about',
      name: 'about',
      // route level code-splitting
      // this generates a separate chunk (About.[hash].js) for this route
      // which is lazy-loaded when the route is visited.
      component: () => import('../views/AboutView.vue'),
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/LoginView.vue'),
      meta: { requiresGuest: true }
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('../views/RegisterView.vue'),
      meta: { requiresGuest: true }
    },
    {
      path: '/posts/:id',
      name: 'post',
      component: () => import('../views/PostView.vue'),
      props: true
    },
    {
      path: '/create',
      name: 'create-post',
      component: () => import('../views/CreatePostView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/edit/:id',
      name: 'edit-post',
      component: () => import('../views/EditPostView.vue'),
      meta: { requiresAuth: true },
      props: true
    },
    {
      path: '/profile',
      name: 'profile',
      component: () => import('../views/ProfileView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/tags/:name',
      name: 'tag',
      // not realized yet
      // component: () => import('../views/TagView.vue'),
      props: true
    },
    {
      path: '/admin',
      name: 'admin',
      // not realized yet
      // component: () => import('../views/AdminView.vue'),
      meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      redirect: '/'
    }
  ],
})

// Navigation guards
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  
  // Wait for auth initialization if needed
  if (authStore.token && !authStore.user) {
    await authStore.initializeAuth()
  }
  
  // Check if route requires authentication
  if (to.meta.requiresAuth && !authStore.isLoggedIn) {
    next('/login')
    return
  }
  
  // Check if route requires guest (not logged in)
  if (to.meta.requiresGuest && authStore.isLoggedIn) {
    next('/')
    return
  }
  
  // Check if route requires admin
  if (to.meta.requiresAdmin && !authStore.isAdmin) {
    next('/')
    return
  }
  
  next()
})

export default router
