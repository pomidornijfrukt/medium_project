<template>
  <div class="auth-layout">
    <nav>
      <router-link to="/auth/register">Register</router-link>
      <router-link to="/auth/login">Login</router-link>
    </nav>
    <router-view />
  </div>
</template>

<style>
nav {
  margin-bottom: 20px;
}
nav a {
  margin-right: 15px;
  text-decoration: none;
}
</style>
