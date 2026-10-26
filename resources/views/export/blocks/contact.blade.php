{{-- Mirrors resources/js/Components/builder/blocks/BlockContact.vue (look); the form here also sends. --}}
<section class="py-16 px-6" style="background-color: var(--bg); color: var(--text); font-family: var(--font-body);">
    @php
        $layout = $props['layout'] ?? 'split';
        $stacked = $layout === 'stacked';
        $fieldLabels = ['name' => 'Your Name', 'email' => 'Email Address', 'phone' => 'Phone Number', 'message' => 'Your Message', 'subject' => 'Subject'];
        $labelFor = fn ($field) => $fieldLabels[mb_strtolower(trim((string) $field))] ?? \Illuminate\Support\Str::title((string) $field);
    @endphp
    <div class="max-w-4xl mx-auto">
        @if(!empty($props['heading']))
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-12" style="font-family: var(--font-heading);">
                {{ $props['heading'] }}
            </h2>
        @endif

        @if(!empty($props['image']))
            <img {!! \App\Support\SiteHtml::img($props['image'], '(min-width: 896px) 896px, 100vw') !!} alt="" loading="lazy" decoding="async" class="w-full h-48 object-cover mb-8" style="{{ \App\Support\SiteHtml::imageStyle($props) }}">
        @endif

    <div class="{{ $stacked ? 'space-y-8' : 'grid grid-cols-1 md:grid-cols-2 gap-12' }}">
        <div class="{{ $stacked ? 'flex flex-wrap gap-6 justify-center' : '' }}">
            @if(!empty($props['email']))
                <a href="mailto:{{ $props['email'] }}" class="flex items-center gap-3 mb-4" style="color: inherit; text-decoration: none;">
                    <span class="text-2xl" aria-hidden="true">📧</span><span>{{ $props['email'] }}</span>
                </a>
            @endif

            @if(!empty($props['phone']))
                <a href="tel:{{ preg_replace('/[^\d+]/', '', $props['phone']) }}" class="flex items-center gap-3 mb-4" style="color: inherit; text-decoration: none;">
                    <span class="text-2xl" aria-hidden="true">📱</span><span>{{ $props['phone'] }}</span>
                </a>
            @endif

            @if(!empty($props['address']))
                <p class="flex items-center gap-3 mb-4">
                    <span class="text-2xl" aria-hidden="true">📍</span><span>{{ $props['address'] }}</span>
                </p>
            @endif
        </div>

        <div>
            @php
                $contactEmail = filter_var($props['email'] ?? null, FILTER_VALIDATE_EMAIL) ?: '';
                $contactPhone = preg_replace('/\D+/', '', (string) ($props['phone'] ?? ''));
                $fieldType = function ($label) {
                    $f = mb_strtolower(trim((string) $label));
                    return match (true) {
                        str_contains($f, 'mail') => 'email',
                        str_contains($f, 'phone') || str_contains($f, 'mobile') || str_contains($f, 'whatsapp') => 'tel',
                        $f === 'date' || str_contains($f, 'date') => 'date',
                        str_contains($f, 'message') || str_contains($f, 'note') || str_contains($f, 'detail') || str_contains($f, 'comment') || str_contains($f, 'question') || str_contains($f, 'request') => 'textarea',
                        default => 'text',
                    };
                };
            @endphp
            <form class="space-y-4" data-contact-form data-email="{{ $contactEmail }}" data-whatsapp="{{ strlen($contactPhone) >= 8 ? $contactPhone : '' }}" novalidate>
                @if(!empty($props['fields']))
                    @foreach($props['fields'] as $i => $field)
                        @php $type = $fieldType($field); $id = 'cf-' . $i; @endphp
                        <div>
                            <label for="{{ $id }}" class="block text-sm font-medium mb-1">{{ $labelFor($field) }}</label>
                            @if($type === 'textarea')
                                <textarea id="{{ $id }}" name="{{ $field }}" rows="4" placeholder="{{ $labelFor($field) }}" class="w-full px-4 py-2 border border-gray-300 outline-none focus:ring-2" style="border-radius: var(--radius); background-color: #fff; color: #111827; --tw-ring-color: var(--primary);"></textarea>
                            @else
                                <input id="{{ $id }}" type="{{ $type }}" name="{{ $field }}" @if($type === 'email') autocomplete="email" @elseif($type === 'tel') autocomplete="tel" @elseif(str_contains(mb_strtolower($field), 'name')) autocomplete="name" @endif placeholder="{{ $labelFor($field) }}" class="w-full px-4 py-2 border border-gray-300 outline-none focus:ring-2" style="border-radius: var(--radius); background-color: #fff; color: #111827; --tw-ring-color: var(--primary);">
                            @endif
                        </div>
                    @endforeach
                @endif

                <button type="submit" class="w-full px-6 py-3 font-medium transition-opacity hover:opacity-90" style="background-color: var(--primary); color: var(--on-primary); border-radius: var(--radius);">
                    {{ $props['buttonLabel'] ?? 'Send Message' }}
                </button>
                <p data-contact-status class="text-sm opacity-80" role="status" hidden></p>
            </form>
            <script>
            (function () {
                // Sends the message through the visitor's own email app (or WhatsApp) — works on any host.
                var form = document.currentScript.previousElementSibling;
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    var lines = [], name = '', missing = false;
                    Array.prototype.forEach.call(form.elements, function (el) {
                        if (!el.name) return;
                        var v = el.value.trim();
                        if (el.type === 'email' && v && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) missing = el;
                        if (/name/i.test(el.name) && !name) name = v;
                        if (v) lines.push(el.name + ': ' + v);
                    });
                    var status = form.querySelector('[data-contact-status]');
                    status.hidden = false;
                    if (missing) { status.textContent = 'Please check your email address.'; missing.focus(); return; }
                    if (!lines.length) { status.textContent = 'Please fill in the form first.'; return; }
                    var body = lines.join('\n');
                    var email = form.getAttribute('data-email'), wa = form.getAttribute('data-whatsapp');
                    if (email) {
                        status.textContent = 'Opening your email app… just press send.';
                        window.location.href = 'mailto:' + email + '?subject=' + encodeURIComponent('Message from ' + (name || 'your website')) + '&body=' + encodeURIComponent(body);
                    } else if (wa) {
                        status.textContent = 'Opening WhatsApp… just press send.';
                        window.open('https://wa.me/' + wa + '?text=' + encodeURIComponent(body), '_blank', 'noopener');
                    } else {
                        status.textContent = 'Sorry, this form isn\'t connected yet. Please use the contact details on this page.';
                    }
                });
            })();
            </script>
        </div>
    </div>
    </div>
</section>
