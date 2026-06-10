<script setup>
import { computed } from 'vue';

const props = defineProps({
    heading: String,
    members: Array,
    columns: { type: Number, default: 4 },
    editable: { type: Boolean, default: false },
    id: String,
});

const gridClass = computed(() => ({
    1: 'grid-cols-1',
    2: 'grid-cols-1 sm:grid-cols-2',
    3: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
    4: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
}[props.columns] || 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4'));
const emit = defineEmits(['update:props']);

function onEdit(e, key) {
    emit('update:props', { [key]: e.target.innerText });
}

function onEditMember(e, index, key) {
    const newMembers = [...props.members];
    newMembers[index] = { ...newMembers[index], [key]: e.target.innerText };
    emit('update:props', { members: newMembers });
}
</script>

<template>
    <section class="py-16 px-6" :style="{ background: 'var(--bg)', color: 'var(--text)' }">
        <div class="max-w-6xl mx-auto">
            <h2
                class="text-3xl md:text-4xl font-bold text-center mb-12"
                :style="{ fontFamily: 'var(--font-heading)' }"
                :contenteditable="editable"
                @blur="onEdit($event, 'heading')"
            >{{ heading }}</h2>

            <div class="grid gap-8" :class="gridClass">
                <div
                    v-for="(member, i) in members"
                    :key="i"
                    class="text-center group"
                >
                    <div
                        class="w-32 h-32 mx-auto mb-4 rounded-full overflow-hidden bg-gray-200"
                    >
                        <img
                            v-if="member.image"
                            :src="member.image"
                            :alt="member.name"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                        />
                        <div
                            v-else
                            class="w-full h-full flex items-center justify-center text-3xl font-bold text-white"
                            :style="{ background: 'var(--primary)' }"
                        >{{ member.name?.charAt(0) }}</div>
                    </div>
                    <h3
                        class="text-lg font-semibold mb-1"
                        :style="{ fontFamily: 'var(--font-heading)' }"
                        :contenteditable="editable"
                        @blur="onEditMember($event, i, 'name')"
                    >{{ member.name }}</h3>
                    <p
                        class="text-sm opacity-60 mb-2"
                        :style="{ color: 'var(--primary)', fontFamily: 'var(--font-body)' }"
                        :contenteditable="editable"
                        @blur="onEditMember($event, i, 'role')"
                    >{{ member.role }}</p>
                    <p
                        v-if="member.bio"
                        class="text-sm opacity-70"
                        :style="{ fontFamily: 'var(--font-body)' }"
                        :contenteditable="editable"
                        @blur="onEditMember($event, i, 'bio')"
                    >{{ member.bio }}</p>
                </div>
            </div>
        </div>
    </section>
</template>
