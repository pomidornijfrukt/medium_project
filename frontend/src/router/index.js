import { createRouter, createWebHistory } from 'vue-router'
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
      component: () => import('../views/TagView.vue'),
      props: true
    },
    {
      path: '/admin',
      name: 'admin',
      component: () => import('../views/AdminView.vue'),
      meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('../views/NotFoundView.vue')
    }
  ],
})

export default router
