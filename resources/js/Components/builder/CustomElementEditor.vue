<script setup>
import { inject } from 'vue';
import ImagePicker from '@/Components/builder/ImagePicker.vue';

const props = defineProps({
    element: Object,
    index: Number,
});
const emit = defineEmits(['update', 'remove']);
const projectId = inject('projectId');

function set(key, value) {
    emit('update', props.index, { ...props.element, [key]: value });
}

const elementIcons = { heading: '𝐇', text: '𝐓', image: '🖼', icon: '⭐', button: '🔘', divider: '―', spacer: '↕' };
const elementLabels = { heading: 'Heading', text: 'Text', image: 'Image', icon: 'Icon', button: 'Button', divider: 'Divider', spacer: 'Spacer' };
</script>

<template>
    <div class="border border-gray-100 rounded-xl overflow-hidden bg-white">
        <!-- Header -->
        <details class="group">
            <summary class="flex items-center gap-2 px-3 py-2.5 cursor-pointer hover:bg-gray-50 transition-colors select-none">
                <span class="text-sm w-5 text-center shrink-0">{{ elementIcons[element.type] || '?' }}</span>
                <span class="text-xs font-semibold text-gray-700 flex-1 truncate">
                    {{ element.text || element.emoji || element.alt || elementLabels[element.type] }}
                </span>
                <button @click.prevent="emit('remove', index)" class="p-1 text-gray-300 hover:text-red-500 transition-colors" title="Remove">
                    <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                <svg class="size-3.5 text-gray-400 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </summary>

            <div class="px-3 pb-3 space-y-2.5 border-t border-gray-50 pt-2.5">
                <!-- HEADING -->
                <template v-if="element.type === 'heading'">
                    <div>
                        <label class="text-[10px] font-medium text-gray-400 mb-0.5 block">Text</label>
                        <input type="text" :value="element.text" @input="set('text', $event.target.value)" class="w-full px-2.5 py-1.5 text-xs border border-gray-200 rounded-lg" />
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-[10px] font-medium text-gray-400 mb-0.5 block">Size</label>
                            <select :value="element.size || '2xl'" @change="set('size', $event.target.value)" class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg">
                                <option value="xl">XL</option>
                                <option value="2xl">2XL</option>
                                <option value="3xl">3XL</option>
                                <option value="4xl">4XL</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-medium text-gray-400 mb-0.5 block">Align</label>
                            <select :value="element.align || 'left'" @change="set('align', $event.target.value)" class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg">
                                <option value="left">Left</option>
                                <option value="center">Center</option>
                                <option value="right">Right</option>
                            </select>
                        </div>
                    </div>
                </template>

                <!-- TEXT -->
                <template v-else-if="element.type === 'text'">
                    <div>
                        <label class="text-[10px] font-medium text-gray-400 mb-0.5 block">Text</label>
                        <textarea :value="element.text" @input="set('text', $event.target.value)" rows="3" class="w-full px-2.5 py-1.5 text-xs border border-gray-200 rounded-lg resize-none"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-[10px] font-medium text-gray-400 mb-0.5 block">Size</label>
                            <select :value="element.size || 'base'" @change="set('size', $event.target.value)" class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg">
                                <option value="sm">Small</option>
                                <option value="base">Normal</option>
                                <option value="lg">Large</option>
                                <option value="xl">XL</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-medium text-gray-400 mb-0.5 block">Align</label>
                            <select :value="element.align || 'left'" @change="set('align', $event.target.value)" class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg">
                                <option value="left">Left</option>
                                <option value="center">Center</option>
                                <option value="right">Right</option>
                            </select>
                        </div>
                    </div>
                </template>

                <!-- IMAGE -->
                <template v-else-if="element.type === 'image'">
                    <ImagePicker :model-value="element.src || ''" :project-id="projectId" @update:model-value="set('src', $event)" />
                    <div>
                        <label class="text-[10px] font-medium text-gray-400 mb-0.5 block">Alt Text</label>
                        <input type="text" :value="element.alt" @input="set('alt', $event.target.value)" class="w-full px-2.5 py-1.5 text-xs border border-gray-200 rounded-lg" placeholder="Description" />
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="text-[10px] font-medium text-gray-400 mb-0.5 block">Radius</label>
                            <select :value="element.radius || ''" @change="set('radius', $event.target.value)" class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg">
                                <option value="">Default</option>
                                <option value="none">None</option>
                                <option value="sm">Sm</option>
                                <option value="md">Md</option>
                                <option value="lg">Lg</option>
                                <option value="xl">XL</option>
                                <option value="full">Full</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-medium text-gray-400 mb-0.5 block">Shadow</label>
                            <select :value="element.shadow || ''" @change="set('shadow', $event.target.value)" class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg">
                                <option value="">None</option>
                                <option value="sm">Sm</option>
                                <option value="md">Md</option>
                                <option value="lg">Lg</option>
                                <option value="xl">XL</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-medium text-gray-400 mb-0.5 block">Width</label>
                            <select :value="element.width || 'full'" @change="set('width', $event.target.value)" class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg">
                                <option value="full">Full</option>
                                <option value="half">Half</option>
                                <option value="third">Third</option>
                            </select>
                        </div>
                    </div>
                </template>

                <!-- ICON -->
                <template v-else-if="element.type === 'icon'">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-[10px] font-medium text-gray-400 mb-0.5 block">Emoji</label>
                            <input type="text" :value="element.emoji" @input="set('emoji', $event.target.value)" class="w-full px-2.5 py-1.5 text-xs border border-gray-200 rounded-lg text-center text-lg" />
                        </div>
                        <div>
                            <label class="text-[10px] font-medium text-gray-400 mb-0.5 block">Size</label>
                            <select :value="element.size || '4xl'" @change="set('size', $event.target.value)" class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg">
                                <option value="2xl">2XL</option>
                                <option value="3xl">3XL</option>
                                <option value="4xl">4XL</option>
                                <option value="5xl">5XL</option>
                            </select>
                        </div>
                    </div>
                </template>

                <!-- BUTTON -->
                <template v-else-if="element.type === 'button'">
                    <div>
                        <label class="text-[10px] font-medium text-gray-400 mb-0.5 block">Label</label>
                        <input type="text" :value="element.text" @input="set('text', $event.target.value)" class="w-full px-2.5 py-1.5 text-xs border border-gray-200 rounded-lg" />
                    </div>
                    <div>
                        <label class="text-[10px] font-medium text-gray-400 mb-0.5 block">Link URL</label>
                        <input type="text" :value="element.href" @input="set('href', $event.target.value)" class="w-full px-2.5 py-1.5 text-xs border border-gray-200 rounded-lg" placeholder="#section or https://..." />
                    </div>
                    <div>
                        <label class="text-[10px] font-medium text-gray-400 mb-0.5 block">Style</label>
                        <select :value="element.variant || 'primary'" @change="set('variant', $event.target.value)" class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg">
                            <option value="primary">Primary (Filled)</option>
                            <option value="secondary">Secondary</option>
                            <option value="outline">Outline</option>
                        </select>
                    </div>
                </template>

                <!-- DIVIDER -->
                <template v-else-if="element.type === 'divider'">
                    <div>
                        <label class="text-[10px] font-medium text-gray-400 mb-0.5 block">Style</label>
                        <select :value="element.style || 'solid'" @change="set('style', $event.target.value)" class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg">
                            <option value="solid">Solid</option>
                            <option value="dashed">Dashed</option>
                            <option value="dotted">Dotted</option>
                        </select>
                    </div>
                </template>

                <!-- SPACER -->
                <template v-else-if="element.type === 'spacer'">
                    <div>
                        <label class="text-[10px] font-medium text-gray-400 mb-0.5 block">Height</label>
                        <select :value="element.height || 'md'" @change="set('height', $event.target.value)" class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg">
                            <option value="sm">Small</option>
                            <option value="md">Medium</option>
                            <option value="lg">Large</option>
                            <option value="xl">Extra Large</option>
                        </select>
                    </div>
                </template>
            </div>
        </details>
    </div>
</template>
