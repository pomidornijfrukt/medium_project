<template>
  <div class="auth-page">
    <h1>Login</h1>
    <form @submit.prevent="handleLogin">
      <FormInput
        label="Email:"
        id="login-email"
        v-model="email"
        :inputAttrs="{ type: 'email', placeholder: 'Enter your email' }"
      />
      <FormInput
        label="Password:"
        id="login-password"
        v-model="password"
        :inputAttrs="{ type: 'password', placeholder: 'Enter your password' }"
      />
      <AuthButton type="submit">Login</AuthButton>
    </form>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref } from 'vue';
import FormInput from '@/components/FormInput.vue';
import AuthButton from '@/components/AuthButton.vue';

export default defineComponent({
  name: 'Login',
  components: { FormInput, AuthButton },
  setup() {
    const email = ref<string>('');
    const password = ref<string>('');

    const handleLogin = () => {
      console.log('Logging in with:', { email: email.value, password: password.value });
    };

    return { email, password, handleLogin };
  },
});
</script>

<style scoped>
.auth-page {
  margin: 50px auto;
  padding: 20px;
  background: white;
  border-radius: 10px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

h1 {
  text-align: center;
  margin-bottom: 20px;
}
</style>
