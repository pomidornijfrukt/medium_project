<template>
  <div class="auth-page">
    <h1>Register</h1>
    <form @submit.prevent="handleRegister">
      <FormInput 
        label="Email:"
        id="register-email"
        v-model="email"
        :inputAttrs="{ type: 'email', placeholder: 'Ur email'}"
      />
      <FormInput
        label="Password:"
        id="register-password"
        v-model="password"
        :inputAttrs="{ type: 'passwrd', placeholder: 'Ur pass bro'}"
      />
      <AuthButton type="submit">Register</AuthButton>
    </form>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref } from 'vue';
import FormInput from '@/components/FormInput.vue';
import AuthButton from '@/components/AuthButton.vue';

export default defineComponent({
  name: 'Register',
  components: { FormInput, AuthButton },
  setup() {
    const email = ref<string>('');
    const password = ref<string>('');

    const handleRegister = () => {
      console.log({ email: email.value, password: password.value });
      // Add form logic here
    };

    return {
      email,
      password,
      handleRegister,
    };
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
