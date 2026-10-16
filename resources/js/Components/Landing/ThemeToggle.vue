<script setup>
import Icon from './Icon.vue';
import { useColorMode } from './useColorMode';

const { isDark, toggle } = useColorMode();
</script>

<template>
    <button
        type="button"
        class="theme-toggle"
        :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
        :title="isDark ? 'Light mode' : 'Dark mode'"
        @click="toggle"
    >
        <span class="icon-stack" :class="{ 'is-dark': isDark }">
            <Icon name="sun" :stroke="1.8" class="icon icon-sun" />
            <Icon name="moon" :stroke="1.8" class="icon icon-moon" />
        </span>
    </button>
</template>

<style scoped>
.theme-toggle {
    display: inline-flex;
    width: 2.25rem;
    height: 2.25rem;
    align-items: center;
    justify-content: center;
    border-radius: 0.65rem;
    border: 1px solid rgb(var(--lp-ink) / 0.1);
    background: rgb(var(--lp-ink) / 0.03);
    color: rgb(var(--lp-muted));
    transition: color 0.2s, border-color 0.2s, background-color 0.2s;
}
.theme-toggle:hover { color: rgb(var(--lp-fg)); border-color: rgb(var(--lp-ink) / 0.2); background: rgb(var(--lp-ink) / 0.07); }
.icon-stack { position: relative; width: 1.1rem; height: 1.1rem; }
.icon {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    transition: transform 0.5s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.3s;
}
/* Dark theme shows the sun (action: go light); light theme shows the moon */
.icon-sun { opacity: 0; transform: rotate(-90deg) scale(0.5); }
.icon-moon { opacity: 1; transform: none; }
.is-dark .icon-sun { opacity: 1; transform: none; }
.is-dark .icon-moon { opacity: 0; transform: rotate(90deg) scale(0.5); }
</style>
