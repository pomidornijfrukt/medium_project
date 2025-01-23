import { createRouter, createWebHistory } from 'vue-router'
import AuthLayout from '@/layouts/AuthLayout.vue';
import Register from '@/views/Register.vue';
import Login from '@/views/Login.vue';
import ForumPage from '@/views/ForumPage.vue';

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [ 
    { path: '/auth', component: AuthLayout, children: [
        {path: 'register', name: 'register', component: Register},
        {path: 'login', name: 'login', component: Login},
      ]
    },
    { path: '/', name: 'ForumPage', component: ForumPage, },
  ],
})

export default router
