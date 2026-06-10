<script setup>
import { computed, ref, inject } from 'vue';
import { useEditor } from '@/stores/editor.js';
import { BLOCK_TYPES } from '@/schema/defaults.js';
import ImagePicker from '@/Components/builder/ImagePicker.vue';
import CustomElementEditor from '@/Components/builder/CustomElementEditor.vue';
import axios from 'axios';

const projectId = inject('projectId');

const editor = useEditor();
const aiInstruction = ref('');
const aiLoading = ref(false);
const aiMode = ref('section'); // 'section' | 'full'

const block = computed(() => editor.selectedBlock);
const blockDef = computed(() => block.value ? BLOCK_TYPES[block.value.type] : null);

function update(key, value) {
    if (block.value) {
        editor.updateProps(block.value.id, { [key]: value });
    }
}

function updateNested(key, index, field, value) {
    if (!block.value) return;
    const arr = [...(block.value.props[key] || [])];
    arr[index] = { ...arr[index], [field]: value };
    editor.updateProps(block.value.id, { [key]: arr });
}

function addArrayItem(key, template) {
    if (!block.value) return;
    const arr = [...(block.value.props[key] || []), template];
    editor.updateProps(block.value.id, { [key]: arr });
}

function removeArrayItem(key, index) {
    if (!block.value) return;
    const arr = [...(block.value.props[key] || [])];
    arr.splice(index, 1);
    editor.updateProps(block.value.id, { [key]: arr });
}

// Custom block element management
const showAddElement = ref(false);
const elementTemplates = {
    heading: { type: 'heading', text: 'New Heading', size: '2xl', align: 'left' },
    text: { type: 'text', text: 'Add your text here...', size: 'base', align: 'left' },
    image: { type: 'image', src: '', alt: '', radius: '', shadow: '', border: '', width: 'full' },
    icon: { type: 'icon', emoji: '⭐', size: '4xl' },
    button: { type: 'button', text: 'Click Me', href: '#', variant: 'primary' },
    divider: { type: 'divider', style: 'solid' },
    spacer: { type: 'spacer', height: 'md' },
};
const elementTypeList = [
    { type: 'heading', icon: '𝐇', label: 'Heading' },
    { type: 'text', icon: '𝐓', label: 'Text' },
    { type: 'image', icon: '🖼', label: 'Image' },
    { type: 'icon', icon: '⭐', label: 'Icon' },
    { type: 'button', icon: '🔘', label: 'Button' },
    { type: 'divider', icon: '―', label: 'Divider' },
    { type: 'spacer', icon: '↕', label: 'Spacer' },
];

function addElement(type) {
    if (!block.value) return;
    const els = [...(block.value.props.elements || []), { ...elementTemplates[type] }];
    editor.updateProps(block.value.id, { elements: els });
    showAddElement.value = false;
}

function updateElement(index, newEl) {
    if (!block.value) return;
    const els = [...(block.value.props.elements || [])];
    els[index] = newEl;
    editor.updateProps(block.value.id, { elements: els });
}

function removeElement(index) {
    if (!block.value) return;
    const els = [...(block.value.props.elements || [])];
    els.splice(index, 1);
    editor.updateProps(block.value.id, { elements: els });
}

function isImageProp(key) {
    const k = key.toLowerCase();
    return k === 'image' || k === 'src' || k === 'avatar' || k === 'photo' || k === 'logo' || k === 'backgroundimage';
}

async function askAi(instruction) {
    const text = instruction || aiInstruction.value.trim();
    if (!text || aiLoading.value) return;
    aiLoading.value = true;
    try {
        if (aiMode.value === 'section' && block.value) {
            // Edit only the selected section
            const { data } = await axios.post(route('ai.editSection'), {
                section: block.value,
                theme: editor.schema.theme,
                instruction: text,
            });
            editor.updateSection(block.value.id, data.section);
        } else {
            // Edit the full schema
            const { data } = await axios.post(route('ai.edit'), {
                schema: editor.schema,
                instruction: text,
            });
            editor.setSchema(data.schema);
        }
        aiInstruction.value = '';
    } catch (e) {
        alert(e.response?.data?.error || 'AI edit failed');
    } finally {
        aiLoading.value = false;
    }
}

// Quick action chips per block type
const quickActions = computed(() => {
    if (!block.value) return [];
    const type = block.value.type;
    const common = [
        { label: 'Make more professional', icon: '✨' },
        { label: 'Improve the copy', icon: '✍️' },
    ];
    const typeActions = {
        navbar: [
            { label: 'Add more nav links', icon: '🔗' },
            { label: 'Make brand name bolder', icon: '💪' },
        ],
        hero: [
            { label: 'Make heading more impactful', icon: '🎯' },
            { label: 'Add a subheading', icon: '📝' },
            { label: 'Change CTA text to something catchy', icon: '🚀' },
        ],
        features: [
            { label: 'Add 3 more features', icon: '➕' },
            { label: 'Rewrite with better descriptions', icon: '📖' },
            { label: 'Change icons to more relevant ones', icon: '🎨' },
        ],
        gallery: [
            { label: 'Add more images', icon: '🖼️' },
            { label: 'Write better captions', icon: '📝' },
        ],
        about: [
            { label: 'Make story more compelling', icon: '📖' },
            { label: 'Add mission and vision', icon: '🎯' },
        ],
        testimonials: [
            { label: 'Add 2 more testimonials', icon: '➕' },
            { label: 'Make reviews more detailed', icon: '⭐' },
        ],
        pricing: [
            { label: 'Adjust pricing tiers', icon: '💰' },
            { label: 'Add more features to each plan', icon: '📋' },
            { label: 'Highlight the best plan', icon: '🏆' },
        ],
        services: [
            { label: 'Add more services', icon: '➕' },
            { label: 'Write detailed descriptions', icon: '📖' },
        ],
        stats: [
            { label: 'Add more stats', icon: '📊' },
            { label: 'Update numbers to be more impressive', icon: '🔢' },
        ],
        faq: [
            { label: 'Add 3 more questions', icon: '❓' },
            { label: 'Make answers more detailed', icon: '📝' },
        ],
        team: [
            { label: 'Add more team members', icon: '👥' },
            { label: 'Add bios for each member', icon: '📄' },
        ],
        cta: [
            { label: 'Make more urgency-driven', icon: '⚡' },
            { label: 'Change CTA to something compelling', icon: '🎯' },
        ],
        contact: [
            { label: 'Add address and phone', icon: '📍' },
            { label: 'Add social media links', icon: '🌐' },
        ],
        footer: [
            { label: 'Add more footer links', icon: '🔗' },
            { label: 'Add social media icons', icon: '📱' },
        ],
    };
    return [...(typeActions[type] || []), ...common];
});
</script>

<template>
    <div class="p-4">
        <div v-if="!block" class="text-center text-gray-400 py-8">
            <div class="text-3xl mb-2">👆</div>
            <p class="text-sm">Select a block to edit its properties</p>
        </div>

        <div v-else>
            <div class="flex items-center gap-2 mb-4 pb-3 border-b border-gray-100">
                <span class="text-lg">{{ blockDef?.icon }}</span>
                <h3 class="font-semibold text-gray-800">{{ blockDef?.label || block.type }}</h3>
                <button
                    @click="editor.removeBlock(block.id)"
                    class="ml-auto text-xs text-red-500 hover:text-red-700"
                >Delete</button>
            </div>

            <!-- Section Colors -->
            <div class="mb-4 pb-4 border-b border-gray-100 space-y-3">
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Section Colors</label>

                <!-- Background -->
                <div class="space-y-1">
                    <label class="text-[10px] font-medium text-gray-400">Background</label>
                    <div class="flex items-center gap-2">
                        <input
                            type="color"
                            :value="block.props.sectionBg || '#ffffff'"
                            @input="update('sectionBg', $event.target.value)"
                            class="w-10 h-8 rounded cursor-pointer border border-gray-200"
                        />
                        <input
                            type="text"
                            :value="block.props.sectionBg || ''"
                            @input="update('sectionBg', $event.target.value)"
                            placeholder="Theme default"
                            class="flex-1 px-3 py-1.5 text-xs border border-gray-200 rounded-lg focus:ring-1 focus:ring-blue-500"
                        />
                        <button
                            v-if="block.props.sectionBg"
                            @click="update('sectionBg', '')"
                            class="text-xs text-gray-400 hover:text-red-500"
                            title="Reset"
                        >&#x2715;</button>
                    </div>
                </div>

                <!-- Text Color -->
                <div class="space-y-1">
                    <label class="text-[10px] font-medium text-gray-400">Text Color</label>
                    <div class="flex items-center gap-2">
                        <input
                            type="color"
                            :value="block.props.sectionText || '#1a1a1a'"
                            @input="update('sectionText', $event.target.value)"
                            class="w-10 h-8 rounded cursor-pointer border border-gray-200"
                        />
                        <input
                            type="text"
                            :value="block.props.sectionText || ''"
                            @input="update('sectionText', $event.target.value)"
                            placeholder="Theme default"
                            class="flex-1 px-3 py-1.5 text-xs border border-gray-200 rounded-lg focus:ring-1 focus:ring-blue-500"
                        />
                        <button
                            v-if="block.props.sectionText"
                            @click="update('sectionText', '')"
                            class="text-xs text-gray-400 hover:text-red-500"
                            title="Reset"
                        >&#x2715;</button>
                    </div>
                </div>
            </div>

            <!-- Image Styling (show when block has an image set) -->
            <div v-if="block.props.image || block.props.imageRadius || block.props.imageShadow || block.props.imageBorder" class="mb-4 pb-4 border-b border-gray-100 space-y-3">
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Image Style</label>
                <div class="grid grid-cols-3 gap-2">
                    <div class="space-y-1">
                        <label class="text-[10px] font-medium text-gray-400">Radius</label>
                        <select :value="block.props.imageRadius || ''" @change="update('imageRadius', $event.target.value)" class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg">
                            <option value="">Default</option>
                            <option value="none">None</option>
                            <option value="sm">Small</option>
                            <option value="md">Medium</option>
                            <option value="lg">Large</option>
                            <option value="xl">XL</option>
                            <option value="full">Full</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-medium text-gray-400">Shadow</label>
                        <select :value="block.props.imageShadow || ''" @change="update('imageShadow', $event.target.value)" class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg">
                            <option value="">None</option>
                            <option value="sm">Small</option>
                            <option value="md">Medium</option>
                            <option value="lg">Large</option>
                            <option value="xl">XL</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-medium text-gray-400">Border</label>
                        <select :value="block.props.imageBorder || ''" @change="update('imageBorder', $event.target.value)" class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg">
                            <option value="">None</option>
                            <option value="thin">Thin</option>
                            <option value="thick">Thick</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- ===== CUSTOM BLOCK ELEMENT EDITOR ===== -->
            <div v-if="block.type === 'custom'" class="space-y-3 mb-4 pb-4 border-b border-gray-100">
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Layout</label>
                <div class="grid grid-cols-3 gap-2">
                    <div class="space-y-1">
                        <label class="text-[10px] font-medium text-gray-400">Columns</label>
                        <select :value="block.props.columns || 1" @change="update('columns', Number($event.target.value))" class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg">
                            <option :value="1">1</option>
                            <option :value="2">2</option>
                            <option :value="3">3</option>
                            <option :value="4">4</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-medium text-gray-400">Gap</label>
                        <select :value="block.props.gap || 'md'" @change="update('gap', $event.target.value)" class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg">
                            <option value="sm">Small</option>
                            <option value="md">Medium</option>
                            <option value="lg">Large</option>
                            <option value="xl">XL</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-medium text-gray-400">Padding</label>
                        <select :value="block.props.padding || 'lg'" @change="update('padding', $event.target.value)" class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg">
                            <option value="sm">Small</option>
                            <option value="md">Medium</option>
                            <option value="lg">Large</option>
                            <option value="xl">XL</option>
                        </select>
                    </div>
                </div>

                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider mt-4 block">Elements</label>
                <div class="space-y-2">
                    <CustomElementEditor
                        v-for="(el, i) in (block.props.elements || [])"
                        :key="i"
                        :element="el"
                        :index="i"
                        @update="updateElement"
                        @remove="removeElement"
                    />
                </div>

                <!-- Add element -->
                <div class="relative">
                    <button
                        @click="showAddElement = !showAddElement"
                        class="w-full px-3 py-2 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors"
                    >+ Add Element</button>
                    <div v-if="showAddElement" class="absolute bottom-full left-0 right-0 mb-1 bg-white border border-gray-200 rounded-xl shadow-xl z-30 overflow-hidden">
                        <button
                            v-for="et in elementTypeList"
                            :key="et.type"
                            @click="addElement(et.type)"
                            class="w-full flex items-center gap-2.5 px-3 py-2 text-xs hover:bg-gray-50 transition-colors text-left"
                        >
                            <span class="w-5 text-center">{{ et.icon }}</span>
                            <span class="font-medium text-gray-700">{{ et.label }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="block.type !== 'custom'" class="space-y-4">
                <!-- String props -->
                <template v-for="(value, key) in block.props" :key="key">
                    <!-- Skip styled props (shown above), arrays and objects (handled separately) -->
                    <div v-if="['sectionBg','sectionText','imageRadius','imageShadow','imageBorder'].includes(key)" class="hidden"></div>
                    <div v-else-if="typeof value === 'string'" class="space-y-1">
                        <label class="text-xs font-medium text-gray-500 capitalize">{{ key }}</label>
                        <!-- Image props -->
                        <ImagePicker
                            v-if="isImageProp(key)"
                            :model-value="value"
                            :project-id="projectId"
                            @update:model-value="update(key, $event)"
                        />
                        <textarea
                            v-else-if="key === 'body' || key === 'description'"
                            :value="value"
                            @input="update(key, $event.target.value)"
                            rows="3"
                            class="w-full px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-1 focus:ring-blue-500"
                        ></textarea>
                        <input
                            v-else-if="key.toLowerCase().includes('color')"
                            type="color"
                            :value="value"
                            @input="update(key, $event.target.value)"
                            class="w-full h-8 rounded cursor-pointer"
                        />
                        <input
                            v-else
                            type="text"
                            :value="value"
                            @input="update(key, $event.target.value)"
                            class="w-full px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-1 focus:ring-blue-500"
                        />
                    </div>

                    <!-- Number props -->
                    <div v-else-if="typeof value === 'number'" class="space-y-1">
                        <label class="text-xs font-medium text-gray-500 capitalize">{{ key }}</label>
                        <input
                            type="number"
                            :value="value"
                            @input="update(key, Number($event.target.value))"
                            class="w-full px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-1 focus:ring-blue-500"
                            min="1"
                            max="6"
                        />
                    </div>

                    <!-- Array of objects (links, items, images) -->
                    <div v-else-if="Array.isArray(value) && value.length && typeof value[0] === 'object'" class="space-y-2">
                        <label class="text-xs font-medium text-gray-500 capitalize">{{ key }}</label>
                        <div
                            v-for="(item, i) in value"
                            :key="i"
                            class="p-2 bg-gray-50 rounded-lg space-y-1 relative group"
                        >
                            <button
                                @click="removeArrayItem(key, i)"
                                class="absolute top-1 right-1 text-xs text-red-400 hover:text-red-600 opacity-0 group-hover:opacity-100"
                            >✕</button>
                            <div v-for="(val, field) in item" :key="field">
                                <label class="text-[10px] text-gray-400 capitalize">{{ field }}</label>
                                <ImagePicker
                                    v-if="isImageProp(field)"
                                    :model-value="val"
                                    :project-id="projectId"
                                    @update:model-value="updateNested(key, i, field, $event)"
                                />
                                <input
                                    v-else
                                    type="text"
                                    :value="val"
                                    @input="updateNested(key, i, field, $event.target.value)"
                                    :placeholder="field"
                                    class="w-full px-2 py-1 text-xs border border-gray-200 rounded focus:ring-1 focus:ring-blue-500"
                                />
                            </div>
                        </div>
                        <button
                            @click="addArrayItem(key, Object.fromEntries(Object.keys(value[0] || {}).map(k => [k, ''])))"
                            class="text-xs text-blue-600 hover:text-blue-800"
                        >+ Add {{ key.slice(0, -1) }}</button>
                    </div>

                    <!-- Array of strings (fields) -->
                    <div v-else-if="Array.isArray(value) && (!value.length || typeof value[0] === 'string')" class="space-y-1">
                        <label class="text-xs font-medium text-gray-500 capitalize">{{ key }}</label>
                        <div class="text-xs text-gray-400">{{ value.join(', ') }}</div>
                    </div>
                </template>
            </div>

            <!-- AI Assist -->
            <div class="mt-6 pt-4 border-t border-gray-100">
                <div class="flex items-center justify-between mb-3">
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">AI Assist</label>
                    <div class="flex rounded-md border border-gray-200 overflow-hidden">
                        <button
                            @click="aiMode = 'section'"
                            class="px-2 py-0.5 text-[10px] font-medium transition-colors"
                            :class="aiMode === 'section' ? 'bg-purple-600 text-white' : 'bg-white text-gray-500 hover:bg-gray-50'"
                        >This Section</button>
                        <button
                            @click="aiMode = 'full'"
                            class="px-2 py-0.5 text-[10px] font-medium transition-colors"
                            :class="aiMode === 'full' ? 'bg-purple-600 text-white' : 'bg-white text-gray-500 hover:bg-gray-50'"
                        >Full Site</button>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="flex flex-wrap gap-1.5 mb-3">
                    <button
                        v-for="action in quickActions"
                        :key="action.label"
                        @click="askAi(action.label)"
                        :disabled="aiLoading"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-[11px] font-medium rounded-lg border border-gray-200 bg-white text-gray-600 hover:border-purple-300 hover:bg-purple-50 hover:text-purple-700 transition-all disabled:opacity-40"
                    >
                        <span>{{ action.icon }}</span>
                        {{ action.label }}
                    </button>
                </div>

                <!-- Custom instruction -->
                <div class="flex gap-2">
                    <input
                        v-model="aiInstruction"
                        type="text"
                        :placeholder="aiMode === 'section' ? `Edit this ${block.type} section...` : 'Edit the full site...'"
                        class="flex-1 px-3 py-2 text-xs border border-gray-200 rounded-lg focus:ring-1 focus:ring-purple-500 focus:border-purple-300"
                        @keydown.enter="askAi()"
                        :disabled="aiLoading"
                    />
                    <button
                        @click="askAi()"
                        :disabled="aiLoading || !aiInstruction.trim()"
                        class="px-3 py-2 text-xs font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 disabled:opacity-50 transition-colors flex items-center gap-1.5 shrink-0"
                    >
                        <svg v-if="aiLoading" class="animate-spin size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-3"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" /></svg>
                        {{ aiLoading ? 'Working...' : 'Ask AI' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
