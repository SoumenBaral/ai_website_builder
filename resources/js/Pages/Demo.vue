<script setup>
import { sampleSchema } from '@/schema/defaults.js';
import { themeToVars } from '@/composables/useTheme.js';
import BlockRenderer from '@/Components/builder/BlockRenderer.vue';

const schema = sampleSchema();
</script>

<template>
    <div :style="themeToVars(schema.theme)" style="font-family: var(--font-body);">
        <link :href="`https://fonts.googleapis.com/css2?family=${schema.theme.fontHeading}:wght@400;600;700&family=${schema.theme.fontBody}:wght@400;500;600&display=swap`" rel="stylesheet" />
        <BlockRenderer
            v-for="block in schema.sections"
            :key="block.id"
            :block="block"
            :editable="false"
        />
    </div>
</template>
