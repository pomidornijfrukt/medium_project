<template>
  <div class="login">
    <h1>Login</h1>
    <form @submit.prevent="loginUser">
      <div>
        <label for="email">Email:</label>
        <input type="email" id="email" v-model="email" />
      </div>
      <div>
        <label for="password">Password:</label>
        <input type="password" id="password" v-model="password" />
      </div>
      <button type="submit">Login</button>
    </form>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref } from 'vue';

export default defineComponent({
  name: 'Login',
  setup() {
    const email = ref<string>('');
    const password = ref<string>('');

    const loginUser = () => {
      console.log('Login data:', {
        email: email.value,
        password: password.value,
      });
      // Add API logic or other handling
    };

    return {
      email,
      password,
      loginUser,
    };
  },
});
</script>
