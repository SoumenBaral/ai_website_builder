<section class="py-16 px-6" style="background-color: var(--bg); color: var(--text); font-family: var(--font-body);">
    @php $layout = $props['layout'] ?? 'split'; @endphp
    <div class="{{ $layout === 'stacked' ? 'max-w-2xl mx-auto space-y-8' : 'max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-12' }}">
        <div>
            @if(!empty($props['heading']))
                <h2 class="text-3xl font-bold mb-6" style="font-family: var(--font-heading);">
                    {{ $props['heading'] }}
                </h2>
            @endif

            @if(!empty($props['email']))
                <p class="mb-3">
                    <span class="font-semibold">Email:</span>
                    <a href="mailto:{{ $props['email'] }}" style="color: var(--primary);">{{ $props['email'] }}</a>
                </p>
            @endif

            @if(!empty($props['phone']))
                <p class="mb-3">
                    <span class="font-semibold">Phone:</span>
                    <a href="tel:{{ $props['phone'] }}" style="color: var(--primary);">{{ $props['phone'] }}</a>
                </p>
            @endif

            @if(!empty($props['address']))
                <p class="opacity-80">{{ $props['address'] }}</p>
            @endif
        </div>

        <div>
            <form class="space-y-4">
                @if(!empty($props['fields']))
                    @foreach($props['fields'] as $field)
                        <div>
                            <label class="block text-sm font-semibold mb-1">{{ ucfirst($field) }}</label>
                            @if($field === 'message')
                                <textarea name="{{ $field }}" rows="4" class="w-full px-4 py-2 border outline-none focus:ring-2" style="border-radius: var(--radius); border-color: var(--text); background-color: var(--bg); color: var(--text); --tw-ring-color: var(--primary);"></textarea>
                            @else
                                <input type="{{ $field === 'email' ? 'email' : 'text' }}" name="{{ $field }}" class="w-full px-4 py-2 border outline-none focus:ring-2" style="border-radius: var(--radius); border-color: var(--text); background-color: var(--bg); color: var(--text); --tw-ring-color: var(--primary);">
                            @endif
                        </div>
                    @endforeach
                @endif

                <button type="submit" class="px-6 py-3 text-white font-semibold transition-opacity hover:opacity-90" style="background-color: var(--primary); border-radius: var(--radius);">
                    {{ $props['buttonLabel'] ?? 'Send Message' }}
                </button>
            </form>
        </div>
    </div>
</section>
