<template>
  <button class="auth-button" @click="onClick">
    <slot></slot>
  </button>
</template>

<script setup lang="ts">
import { defineProps, defineEmits } from 'vue';

// Define props
defineProps({
  type: {
    type: String,
    default: 'button',
  },
});

// Define emits
const emit = defineEmits(['click'],);

const onClick = (event: Event) => {
  emit('click', event);
  console.log('Button clicked');
};
</script>



<style scoped>
.auth-button {
  width: 100%;
  padding: 10px;
  background: linear-gradient(135deg, #6a11cb, #2575fc);
  color: white;
  border: none;
  border-radius: 5px;
  font-size: 16px;
  cursor: pointer;
  transition: background 0.3s ease;
}
.auth-button:hover {
  background: linear-gradient(135deg, #2575fc, #6a11cb);
}
</style>