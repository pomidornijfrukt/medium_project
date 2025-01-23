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

    <main>
      <router-view />
    </main>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref } from 'vue';

export default defineComponent({
  name: 'AuthLayout',
  setup() {
    const menuOpen = ref(false);

    const toggleMenu = () => {
      menuOpen.value = !menuOpen.value;
    };

    const closeMenu = () => {
      menuOpen.value = false;
    };

    return { menuOpen, toggleMenu, closeMenu };
  },
});
</script>

<style scoped>
.auth-layout {
  display: flex;
  flex-direction: column;
  height: 100vh;
  position: absolute;
  width: 100%;
  left:0;
  top: 0;
  background: linear-gradient(135deg, #6a11cb, #2575fc);
}

header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  color: white;
}

.burger-menu {
  background: none;
  border: none;
  font-size: 24px;
  color: white;
  cursor: pointer;
}

.dropdown-menu {
  position: absolute;
  top: 60px;
  right: 20px;
  background: white;
  color: black;
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
