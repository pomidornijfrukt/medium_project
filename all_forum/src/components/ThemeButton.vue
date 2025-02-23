<template>
    <v-button @click="changeTheme">Change your theme </v-button>
</template>

<script lang="ts">
import {defineComponent, ref} from 'vue';

export default defineComponent({
    name: 'ThemeButton',
    setup(){
        const theme= ref(localStorage.getItem('dark-theme') || 'white-theme');

        const changeTheme = () => {
            const oldTheme = theme.value;
            const newTheme = oldTheme === 'white-theme' ? 'dark-theme' : 'white-theme';
            theme.value = newTheme;
            document.body.classList.remove(oldTheme);
            document.body.classList.add(newTheme);
            localStorage.setItem('dark-theme', newTheme);
        };

        return {changeTheme};
    }
})
</script>