<template>
  <div class="register">
    <h1>Register</h1>
    <form @submit.prevent="registerUser">
      <div>
        <label for="email">Email:</label>
        <input type="email" id="email" v-model="email" />
      </div>
      <div>
        <label for="password">Password:</label>
        <input type="password" id="password" v-model="password" />
      </div>
      <button type="submit">Register</button>
    </form>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref } from 'vue';

export default defineComponent({
  name: 'Register',
  setup() {
    // Define reactive variables with types
    const email = ref<string>('');
    const password = ref<string>('');

    const registerUser = () => {
      console.log('Registration data:', {
        email: email.value,
        password: password.value,
      });
      // Add API logic or other handling
    };

    return {
      email,
      password,
      registerUser,
    };
  },
});
</script>
