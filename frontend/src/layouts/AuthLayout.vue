<template>
  <div class="auth-layout">
    <header>
      <h1>My App</h1>
      <button class="burger-menu" @click="toggleMenu">☰</button>
    </header>

    <div v-if="menuOpen" class="dropdown-menu">
      <router-link to="/auth/login" @click="closeMenu">Login</router-link>
      <router-link to="/auth/register" @click="closeMenu">Register</router-link>
      <router-link to="/forum" @click="closeMenu">Forum</router-link>
    </div>

    <ThemeButton />

    <main>
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import ThemeButton from '@/components/ThemeButton.vue'

const menuOpen = ref(false)

const toggleMenu = () => {
  menuOpen.value = !menuOpen.value
}

const closeMenu = () => {
  menuOpen.value = false
}

const theme = ref(localStorage.getItem('dark-theme') || 'white-theme')

return { menuOpen, toggleMenu, closeMenu }
</script>

<style scoped>
.auth-layout {
  display: flex;
  flex-direction: column;
  height: 100vh;
  position: absolute;
  width: 100%;
  left: 0;
  top: 0;
  background: linear-gradient(135deg, #6a11cb, #2575fc);
}

header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
}

.burger-menu {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
}

.dropdown-menu {
  position: absolute;
  top: 60px;
  right: 20px;
  border-radius: 8px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  padding: 10px 20px;
}

main {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>
