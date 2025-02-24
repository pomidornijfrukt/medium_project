<template>
    <div> Вот както так но никак иначе
    </div>
    <ThemeButton />
</template>

<script lang="ts">
import { defineComponent, ref, onMounted } from 'vue';
import ThemeButton from '@/components/ThemeButton.vue';

export default defineComponent({
  name: 'ForumLayout',
  components: {
    ThemeButton,
  },
});

</script>

<style scoped>
nav {
    width: 100%;
    font-size: 12px;
    text-align: center;
    margin-top: 2rem;
  }
</style>