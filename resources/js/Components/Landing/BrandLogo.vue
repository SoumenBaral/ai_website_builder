<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from './Icon.vue';

defineProps({
    small: { type: Boolean, default: false },
    href: { type: String, default: '/' },
});
</script>

<template>
    <Link :href="href" class="group inline-flex items-center gap-2.5" aria-label="AiWebBuilder home">
        <span :class="['icon-solid transition-transform duration-300 group-hover:-rotate-6 group-hover:scale-105', small ? 'size-8 rounded-lg' : 'size-9 rounded-xl']">
            <Icon name="sparkles" :stroke="2" :class="small ? 'size-4' : 'size-[18px]'" />
        </span>
        <span :class="['font-bold tracking-tight text-lp-fg', small ? 'text-base' : 'text-[17px]']">Ai<span class="text-gradient">Web</span>Builder</span>
    </Link>
</template>
