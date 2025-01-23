<template>
  <div class="form-group">
    <label :for="id">{{ label }}</label>
    <input :id="id" v-bind="inputAttrs" v-model="modelValue" />
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue';

export default defineComponent({
  name: 'FormInput',
  props: {
    label: String,
    id: String,
    modelValue: String,
    inputAttrs: {
      type: Object,
      default: () => ({}),
    },
  },
  emits: ['update:modelValue'],
});
</script>

<style scoped>
.form-group {
  margin-bottom: 1rem;
}

label {
  font-size: 14px;
  color: #333;
  margin-bottom: 4px;
}

input {
  width: 100%;
  padding: 8px;
  border: 1px solid #ccc;
  border-radius: 5px;
}
</style>
