<template>
  <button :type="type" class="auth-button" @click="onClick">
    <slot />
  </button>
</template>

<script lang="ts">
import { defineComponent } from 'vue';

export default defineComponent({
  name: 'AuthButton',
  props: {
    type: {
      type: String,
      default: 'button',
    },
  },
  emits: ['click'],
  methods: {
    onClick(event: Event) {
      this.$emit('click', event);
    },
  },
});
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
