<script setup>
import { useImageStyle } from '@/composables/useImageStyle.js';

const props = defineProps({
    heading: String,
    email: String,
    phone: String,
    address: String,
    image: String,
    fields: Array,
    layout: { type: String, default: 'split' },
    imageRadius: { type: String, default: '' },
    imageShadow: { type: String, default: '' },
    imageBorder: { type: String, default: '' },
    editable: { type: Boolean, default: false },
    id: String,
});

const { imageClasses } = useImageStyle(props);
const emit = defineEmits(['update:props']);

function onEdit(e, key) {
    emit('update:props', { [key]: e.target.innerText });
}

const fieldLabels = {
    name: 'Your Name',
    email: 'Email Address',
    phone: 'Phone Number',
    message: 'Your Message',
    subject: 'Subject',
};
</script>

<template>
    <section class="py-16 px-6" :style="{ background: 'var(--bg)', color: 'var(--text)' }">
        <div class="max-w-4xl mx-auto">
            <h2
                class="text-3xl md:text-4xl font-bold text-center mb-12"
                :style="{ fontFamily: 'var(--font-heading)' }"
                :contenteditable="editable"
                @blur="onEdit($event, 'heading')"
            >{{ heading }}</h2>

            <img v-if="image" :src="image" alt="" class="w-full h-48 object-cover mb-8" :class="imageClasses" />

            <div :class="layout === 'stacked' ? 'space-y-8' : 'grid grid-cols-1 md:grid-cols-2 gap-12'">
                <div :class="layout === 'stacked' ? 'flex flex-wrap gap-6 justify-center' : ''">
                    <div v-if="email" class="flex items-center gap-3 mb-4">
                        <span class="text-2xl">📧</span>
                        <span
                            :style="{ fontFamily: 'var(--font-body)' }"
                            :contenteditable="editable"
                            @blur="onEdit($event, 'email')"
                        >{{ email }}</span>
                    </div>
                    <div v-if="phone" class="flex items-center gap-3 mb-4">
                        <span class="text-2xl">📱</span>
                        <span
                            :style="{ fontFamily: 'var(--font-body)' }"
                            :contenteditable="editable"
                            @blur="onEdit($event, 'phone')"
                        >{{ phone }}</span>
                    </div>
                    <div v-if="address" class="flex items-center gap-3 mb-4">
                        <span class="text-2xl">📍</span>
                        <span
                            :style="{ fontFamily: 'var(--font-body)' }"
                            :contenteditable="editable"
                            @blur="onEdit($event, 'address')"
                        >{{ address }}</span>
                    </div>
                </div>

                <form @submit.prevent class="space-y-4">
                    <div v-for="field in fields" :key="field">
                        <label class="block text-sm font-medium mb-1 capitalize" :style="{ fontFamily: 'var(--font-body)' }">
                            {{ fieldLabels[field] || field }}
                        </label>
                        <textarea
                            v-if="field === 'message'"
                            rows="4"
                            class="w-full px-4 py-2 border border-gray-300 focus:outline-none focus:ring-2"
                            :style="{ borderRadius: 'var(--radius)', focusRingColor: 'var(--primary)' }"
                            :placeholder="fieldLabels[field] || field"
                        ></textarea>
                        <input
                            v-else
                            type="text"
                            class="w-full px-4 py-2 border border-gray-300 focus:outline-none focus:ring-2"
                            :style="{ borderRadius: 'var(--radius)' }"
                            :placeholder="fieldLabels[field] || field"
                        />
                    </div>
                    <button
                        type="submit"
                        class="w-full px-6 py-3 text-white font-medium transition-opacity hover:opacity-90"
                        :style="{ background: 'var(--primary)', borderRadius: 'var(--radius)' }"
                    >Send Message</button>
                </form>
            </div>
        </div>
    </section>
</template>
