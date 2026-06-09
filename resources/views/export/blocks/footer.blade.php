<footer class="py-10 px-6 text-center" style="background-color: #1a1a2e; color: #e0e0e0; font-family: var(--font-body);">
    <div class="max-w-4xl mx-auto">
        @if(!empty($props['text']))
            <p class="opacity-80">{!! $props['text'] !!}</p>
        @else
            <p class="opacity-80">&copy; {{ date('Y') }} {{ $props['brand'] ?? 'Company' }}. All rights reserved.</p>
        @endif
    </div>
</footer>
