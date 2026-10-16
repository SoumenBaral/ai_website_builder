<script setup>
import { ref, computed } from 'vue';
import Icon from '@/Components/Landing/Icon.vue';
import AppMenu from '@/Components/App/AppMenu.vue';
import ProjectThumbnail from './ProjectThumbnail.vue';
import { timeAgo, compactNumber } from './format';

const props = defineProps({
    project: { type: Object, required: true },
});
const emit = defineEmits(['delete']);

const menuOpen = ref(false);
const isLive = computed(() => props.project.status === 'published');
const editorUrl = computed(() => route('editor.show', props.project.id));
</script>

<template>
    <!-- z-index bump while the menu is open so it isn't covered by the next card -->
    <article :class="['card group flex flex-col !overflow-visible hover:-translate-y-0.5 hover:shadow-[0_24px_60px_-28px_rgb(15_15_35/0.35)]', menuOpen && 'z-20']">
        <!-- Thumbnail; the whole preview opens the editor -->
        <a :href="editorUrl" class="thumb-frame relative m-2 mb-0 block overflow-hidden rounded-[0.9rem]" :aria-label="`Open ${project.name} in the editor`">
            <ProjectThumbnail :preview="project.preview" :name="project.name" class="transition-transform duration-500 group-hover:scale-[1.03]" />

            <span class="open-cta absolute inset-x-0 bottom-0 flex justify-center pb-4">
                <span class="btn btn-primary h-9 px-4 text-[13px]"><Icon name="pencil" :stroke="1.9" class="size-3.5" />Open editor</span>
            </span>
        </a>

        <div class="flex flex-1 flex-col p-4 pt-3.5">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <h3 class="truncate text-[15px] font-semibold text-lp-fg">{{ project.name }}</h3>
                    <p class="mt-0.5 truncate text-[13px] text-lp-muted">{{ project.description || 'No description' }}</p>
                    <a v-if="project.shop || project.orders" :href="route('orders.index', project.id)" :class="['orders-chip mt-2', project.newOrders && 'orders-chip--new']">
                        <Icon name="tag" :stroke="1.9" class="size-3.5" />
                        <template v-if="project.newOrders">{{ project.newOrders }} new order{{ project.newOrders === 1 ? '' : 's' }}</template>
                        <template v-else>Orders{{ project.orders ? ` · ${project.orders}` : '' }}</template>
                    </a>
                </div>

                <AppMenu width="w-52" @open-change="menuOpen = $event">
                    <template #trigger="{ toggle, open }">
                        <button type="button" class="-mr-1.5 flex size-8 shrink-0 items-center justify-center rounded-lg text-lp-subtle transition hover:bg-lp-ink/5 hover:text-lp-fg" :aria-expanded="open" aria-haspopup="menu" :aria-label="`Actions for ${project.name}`" @click="toggle">
                            <Icon name="ellipsis" :stroke="2" class="size-5" />
                        </button>
                    </template>
                    <template #default="{ close }">
                        <a :href="editorUrl" class="menu-item" role="menuitem"><Icon name="pencil" :stroke="1.7" class="size-4" />Open editor</a>
                        <a v-if="isLive" :href="`/s/${project.slug}`" target="_blank" rel="noopener" class="menu-item" role="menuitem" @click="close"><Icon name="externalLink" :stroke="1.7" class="size-4" />View live site</a>
                        <a :href="route('analytics.dashboard', project.id)" class="menu-item" role="menuitem"><Icon name="chart" :stroke="1.7" class="size-4" />Analytics</a>
                        <a :href="route('orders.index', project.id)" class="menu-item" role="menuitem"><Icon name="tag" :stroke="1.7" class="size-4" />Orders</a>
                        <a :href="route('export.download', project.id)" class="menu-item" role="menuitem" @click="close"><Icon name="download" :stroke="1.7" class="size-4" />Download ZIP</a>
                        <div class="menu-sep"></div>
                        <button type="button" class="menu-item menu-item--danger" role="menuitem" @click="close(); emit('delete', project)"><Icon name="trash" :stroke="1.7" class="size-4" />Delete website</button>
                    </template>
                </AppMenu>
            </div>

            <div class="mt-auto flex flex-wrap items-center gap-x-3 gap-y-2 pt-4 text-xs text-lp-subtle">
                <span :class="['status', isLive ? 'status--live' : 'status--draft']">
                    <span class="relative flex size-1.5">
                        <span v-if="isLive" class="live-ping absolute inset-0 rounded-full bg-emerald-400"></span>
                        <span :class="['relative size-1.5 rounded-full', isLive ? 'bg-emerald-500' : 'bg-lp-subtle']"></span>
                    </span>
                    {{ isLive ? 'Live' : 'Draft' }}
                </span>
                <span class="inline-flex items-center gap-1" :title="new Date(project.updated_at).toLocaleString()"><Icon name="clock" :stroke="1.7" class="size-3.5" />{{ timeAgo(project.updated_at) }}</span>
                <span class="inline-flex items-center gap-1"><Icon name="document" :stroke="1.7" class="size-3.5" />{{ project.pages_count }} {{ project.pages_count === 1 ? 'page' : 'pages' }}</span>
                <span v-if="isLive" class="inline-flex items-center gap-1" title="Views in the last 30 days"><Icon name="eye" :stroke="1.7" class="size-3.5" />{{ compactNumber(project.views) }}</span>
            </div>
        </div>
    </article>
</template>

<style scoped>
.orders-chip { display: inline-flex; align-items: center; gap: 0.35rem; border-radius: 9999px; border: 1px solid rgb(var(--lp-ink) / 0.1); padding: 0.15rem 0.6rem; font-size: 12px; font-weight: 600; color: rgb(var(--lp-muted)); transition: border-color 0.15s, color 0.15s, background-color 0.15s; }
.orders-chip:hover { border-color: rgb(139 92 246 / 0.5); color: rgb(var(--lp-fg)); }
.orders-chip--new { border-color: rgb(139 92 246 / 0.4); background: rgb(139 92 246 / 0.12); color: rgb(124 58 237); }
:global(.dark) .orders-chip--new, :global([data-theme='dark']) .orders-chip--new { color: rgb(196 181 253); }
.thumb-frame { box-shadow: inset 0 0 0 1px rgb(var(--lp-ink) / 0.06); }
.thumb-frame::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    box-shadow: inset 0 0 0 1px rgb(var(--lp-ink) / 0.08);
    pointer-events: none;
}
.status {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    border-radius: 9999px;
    padding: 0.15rem 0.55rem;
    font-size: 11px;
    font-weight: 600;
}
.status--live { background: rgb(16 185 129 / 0.12); color: rgb(16 185 129); }
.status--draft { background: rgb(var(--lp-ink) / 0.06); color: rgb(var(--lp-muted)); }

/* Hover affordance — opacity/transform only */
.open-cta {
    background: linear-gradient(to top, rgb(0 0 0 / 0.35), transparent 60%);
    opacity: 0;
    transform: translateY(6px);
    transition: opacity 0.25s, transform 0.3s cubic-bezier(0.22, 1, 0.36, 1);
}
.group:hover .open-cta, .thumb-frame:focus-visible .open-cta { opacity: 1; transform: none; }
@media (hover: none) { .open-cta { display: none; } }
</style>
