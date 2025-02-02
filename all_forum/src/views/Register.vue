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
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
  text-align: left;
}

.auth-page h1 {
  text-align: center;
  color: #333;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  font-size: 14px;
  color: #666;
  margin-bottom: 5px;
}

.form-group input {
  width: 100%;
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 5px;
}

button {
  width: 100%;
  padding: 10px;
  background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
  color: white;
  border: none;
  border-radius: 5px;
  font-size: 16px;
  cursor: pointer;
}

button:hover {
  background: linear-gradient(135deg, #2575fc 0%, #6a11cb 100%);
}
</style>
