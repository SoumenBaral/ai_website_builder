<section class="py-16 px-6" style="background-color: var(--bg); color: var(--text); font-family: var(--font-body);">
    <div class="max-w-6xl mx-auto">
        @if(!empty($props['heading']))
            <h2 class="text-3xl font-bold text-center mb-12" style="font-family: var(--font-heading);">
                {{ $props['heading'] }}
            </h2>
        @endif

        @php
            $cols = $props['columns'] ?? 4;
            $gridClass = [1 => 'grid-cols-1', 2 => 'grid-cols-1 sm:grid-cols-2', 3 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3', 4 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4'][$cols] ?? 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4';
        @endphp
        <div class="grid {{ $gridClass }} gap-8">
            @if(!empty($props['members']))
                @foreach($props['members'] as $member)
                    <div class="text-center">
                        @if(!empty($member['image']))
                            <img
                                src="{{ $member['image'] }}"
                                alt="{{ $member['name'] ?? '' }}"
                                class="w-32 h-32 rounded-full mx-auto mb-4 object-cover"
                            />
                        @endif

                        <h3 class="text-lg font-semibold" style="font-family: var(--font-heading);">
                            {{ $member['name'] ?? '' }}
                        </h3>

                        @if(!empty($member['role']))
                            <p class="text-sm opacity-60" style="color: var(--primary);">
                                {{ $member['role'] }}
                            </p>
                        @endif
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</section>
