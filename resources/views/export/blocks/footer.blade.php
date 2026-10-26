{{-- Mirrors resources/js/Components/builder/blocks/BlockFooter.vue: keep the two in step.
     --secondary is the theme colour, or the section's own sectionBg (set on the wrapper). --}}
<footer class="py-8 px-6 text-center" style="background-color: var(--secondary, #0f172a); color: {{ e($props['sectionText'] ?? '#94a3b8') }}; font-family: var(--font-body);">
    <div class="max-w-4xl mx-auto">
        @if(!empty($props['text']))
            <p class="text-sm">{{ $props['text'] }}</p>
        @else
            <p class="text-sm">&copy; {{ date('Y') }} {{ $props['brand'] ?? 'Company' }}. All rights reserved.</p>
        @endif
    </div>
</footer>
