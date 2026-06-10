<script setup>
import { computed } from 'vue';
import { useImageStyle } from '@/composables/useImageStyle.js';

const props = defineProps({
    heading: String,
    plans: Array,
    image: String,
    columns: { type: Number, default: 3 },
    imageRadius: { type: String, default: '' },
    imageShadow: { type: String, default: '' },
    imageBorder: { type: String, default: '' },
    editable: { type: Boolean, default: false },
    id: String,
});

const gridClass = computed(() => ({
    1: 'grid-cols-1',
    2: 'grid-cols-1 sm:grid-cols-2',
    3: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
}[props.columns] || 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3'));
const { imageClasses } = useImageStyle(props);
const emit = defineEmits(['update:props']);

function onEdit(e, key) {
    emit('update:props', { [key]: e.target.innerText });
}

function onEditPlan(e, index, key) {
    const newPlans = [...props.plans];
    newPlans[index] = { ...newPlans[index], [key]: e.target.innerText };
    emit('update:props', { plans: newPlans });
}

function onEditFeature(e, planIndex, featureIndex) {
    const newPlans = [...props.plans];
    const newFeatures = [...newPlans[planIndex].features];
    newFeatures[featureIndex] = e.target.innerText;
    newPlans[planIndex] = { ...newPlans[planIndex], features: newFeatures };
    emit('update:props', { plans: newPlans });
}
</script>

<template>
    <section class="py-16 px-6" :style="{ background: 'var(--bg)', color: 'var(--text)' }">
        <div class="max-w-6xl mx-auto">
            <img v-if="image" :src="image" alt="" class="w-full h-48 object-cover mb-10" :class="imageClasses" />

            <h2
                class="text-3xl md:text-4xl font-bold text-center mb-12"
                :style="{ fontFamily: 'var(--font-heading)' }"
                :contenteditable="editable"
                @blur="onEdit($event, 'heading')"
            >{{ heading }}</h2>

            <div class="grid gap-8 items-start" :class="gridClass">
                <div
                    v-for="(plan, i) in plans"
                    :key="i"
                    class="p-8 border-2 hover:shadow-xl transition-all duration-300 flex flex-col"
                    :style="{
                        borderRadius: 'var(--radius)',
                        borderColor: plan.highlighted ? 'var(--primary)' : 'transparent',
                        background: 'var(--bg)',
                        boxShadow: plan.highlighted ? '0 8px 30px rgba(0,0,0,0.12)' : '0 2px 8px rgba(0,0,0,0.06)',
                    }"
                >
                    <div
                        v-if="plan.highlighted"
                        class="text-xs font-bold uppercase tracking-wider text-center mb-4 -mt-4 -mx-8 py-2"
                        :style="{ background: 'var(--primary)', color: '#fff' }"
                    >Most Popular</div>
                    <h3
                        class="text-xl font-semibold text-center mb-2"
                        :style="{ fontFamily: 'var(--font-heading)' }"
                        :contenteditable="editable"
                        @blur="onEditPlan($event, i, 'name')"
                    >{{ plan.name }}</h3>
                    <div class="text-center mb-6">
                        <span
                            class="text-4xl font-bold"
                            :style="{ fontFamily: 'var(--font-heading)', color: 'var(--primary)' }"
                            :contenteditable="editable"
                            @blur="onEditPlan($event, i, 'price')"
                        >{{ plan.price }}</span>
                        <span
                            class="text-sm opacity-60 ml-1"
                            :style="{ fontFamily: 'var(--font-body)' }"
                            :contenteditable="editable"
                            @blur="onEditPlan($event, i, 'period')"
                        >{{ plan.period }}</span>
                    </div>
                    <ul class="space-y-3 mb-8 flex-1">
                        <li
                            v-for="(feature, fi) in plan.features"
                            :key="fi"
                            class="flex items-start gap-2 text-sm"
                            :style="{ fontFamily: 'var(--font-body)' }"
                        >
                            <span class="mt-0.5" :style="{ color: 'var(--primary)' }">&#10003;</span>
                            <span
                                :contenteditable="editable"
                                @blur="onEditFeature($event, i, fi)"
                            >{{ feature }}</span>
                        </li>
                    </ul>
                    <button
                        class="w-full py-3 px-6 font-semibold text-center transition-opacity hover:opacity-90"
                        :style="{
                            background: plan.highlighted ? 'var(--primary)' : 'transparent',
                            color: plan.highlighted ? '#fff' : 'var(--primary)',
                            border: plan.highlighted ? 'none' : '2px solid var(--primary)',
                            borderRadius: 'var(--radius)',
                            fontFamily: 'var(--font-heading)',
                        }"
                    >
                        <span
                            :contenteditable="editable"
                            @blur="onEditPlan($event, i, 'ctaText')"
                        >{{ plan.ctaText }}</span>
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>
