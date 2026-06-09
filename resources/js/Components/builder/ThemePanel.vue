<script setup>
import { useEditor } from '@/stores/editor.js';
import { computed } from 'vue';

const editor = useEditor();
const theme = computed(() => editor.schema?.theme);

function update(key, value) {
    editor.updateTheme({ [key]: value });
}

const fonts = [
    'Poppins', 'Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat',
    'Playfair Display', 'Merriweather', 'Raleway', 'Nunito',
    'Source Sans 3', 'PT Sans', 'Ubuntu', 'Oswald', 'DM Sans',
];

const radiusOptions = [
    { value: 'none', label: 'None' },
    { value: 'sm', label: 'Small' },
    { value: 'md', label: 'Medium' },
    { value: 'lg', label: 'Large' },
    { value: 'xl', label: 'Extra Large' },
    { value: 'full', label: 'Full' },
];
</script>

<template>
    <div class="p-4" v-if="theme">
        <h3 class="font-semibold text-gray-800 mb-4 pb-3 border-b border-gray-100">Theme Settings</h3>

        <div class="space-y-5">
            <!-- Colors -->
            <div>
                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Colors</h4>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="text-sm text-gray-600">Primary</label>
                        <div class="flex items-center gap-2">
                            <input type="color" :value="theme.primaryColor" @input="update('primaryColor', $event.target.value)" class="w-8 h-8 rounded cursor-pointer border-0" />
                            <span class="text-xs text-gray-400 font-mono">{{ theme.primaryColor }}</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <label class="text-sm text-gray-600">Secondary</label>
                        <div class="flex items-center gap-2">
                            <input type="color" :value="theme.secondaryColor" @input="update('secondaryColor', $event.target.value)" class="w-8 h-8 rounded cursor-pointer border-0" />
                            <span class="text-xs text-gray-400 font-mono">{{ theme.secondaryColor }}</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <label class="text-sm text-gray-600">Background</label>
                        <div class="flex items-center gap-2">
                            <input type="color" :value="theme.backgroundColor" @input="update('backgroundColor', $event.target.value)" class="w-8 h-8 rounded cursor-pointer border-0" />
                            <span class="text-xs text-gray-400 font-mono">{{ theme.backgroundColor }}</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <label class="text-sm text-gray-600">Text</label>
                        <div class="flex items-center gap-2">
                            <input type="color" :value="theme.textColor" @input="update('textColor', $event.target.value)" class="w-8 h-8 rounded cursor-pointer border-0" />
                            <span class="text-xs text-gray-400 font-mono">{{ theme.textColor }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fonts -->
            <div>
                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Typography</h4>
                <div class="space-y-3">
                    <div>
                        <label class="text-sm text-gray-600 block mb-1">Heading Font</label>
                        <select
                            :value="theme.fontHeading"
                            @change="update('fontHeading', $event.target.value)"
                            class="w-full px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-1 focus:ring-blue-500"
                        >
                            <option v-for="font in fonts" :key="font" :value="font" :style="{ fontFamily: font }">{{ font }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 block mb-1">Body Font</label>
                        <select
                            :value="theme.fontBody"
                            @change="update('fontBody', $event.target.value)"
                            class="w-full px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-1 focus:ring-blue-500"
                        >
                            <option v-for="font in fonts" :key="font" :value="font" :style="{ fontFamily: font }">{{ font }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Border Radius -->
            <div>
                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Border Radius</h4>
                <div class="grid grid-cols-3 gap-2">
                    <button
                        v-for="opt in radiusOptions"
                        :key="opt.value"
                        @click="update('radius', opt.value)"
                        class="px-2 py-1.5 text-xs font-medium rounded-lg border transition-colors"
                        :class="theme.radius === opt.value ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-200 text-gray-600 hover:bg-gray-50'"
                    >
                        {{ opt.label }}
                    </button>
                </div>
            </div>

            <!-- Dark/Light Mode -->
            <div>
                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Mode</h4>
                <div class="flex gap-2">
                    <button
                        @click="update('mode', 'light')"
                        class="flex-1 px-3 py-2 text-sm font-medium rounded-lg border transition-colors"
                        :class="theme.mode === 'light' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-200 text-gray-600 hover:bg-gray-50'"
                    >
                        ☀️ Light
                    </button>
                    <button
                        @click="update('mode', 'dark')"
                        class="flex-1 px-3 py-2 text-sm font-medium rounded-lg border transition-colors"
                        :class="theme.mode === 'dark' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-200 text-gray-600 hover:bg-gray-50'"
                    >
                        🌙 Dark
                    </button>
                </div>
            </div>

            <!-- Site Meta -->
            <div>
                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Site Info</h4>
                <div class="space-y-3">
                    <div>
                        <label class="text-sm text-gray-600 block mb-1">Title</label>
                        <input
                            type="text"
                            :value="editor.schema?.meta?.title"
                            @input="editor.updateMeta({ title: $event.target.value })"
                            class="w-full px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-1 focus:ring-blue-500"
                        />
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 block mb-1">Description</label>
                        <textarea
                            :value="editor.schema?.meta?.description"
                            @input="editor.updateMeta({ description: $event.target.value })"
                            rows="2"
                            class="w-full px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-1 focus:ring-blue-500"
                        ></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
