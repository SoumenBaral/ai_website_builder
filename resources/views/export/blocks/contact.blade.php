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
                            <label for="{{ $id }}" class="block text-sm font-semibold mb-1">{{ ucfirst($field) }}</label>
                            @if($type === 'textarea')
                                <textarea id="{{ $id }}" name="{{ $field }}" rows="4" class="w-full px-4 py-2 border outline-none focus:ring-2" style="border-radius: var(--radius); border-color: var(--text); background-color: var(--bg); color: var(--text); --tw-ring-color: var(--primary);"></textarea>
                            @else
                                <input id="{{ $id }}" type="{{ $type }}" name="{{ $field }}" @if($type === 'email') autocomplete="email" @elseif($type === 'tel') autocomplete="tel" @elseif(str_contains(mb_strtolower($field), 'name')) autocomplete="name" @endif class="w-full px-4 py-2 border outline-none focus:ring-2" style="border-radius: var(--radius); border-color: var(--text); background-color: var(--bg); color: var(--text); --tw-ring-color: var(--primary);">
                            @endif
                        </div>
                    @endforeach
                @endif

                <button type="submit" class="px-6 py-3 text-white font-semibold transition-opacity hover:opacity-90" style="background-color: var(--primary); border-radius: var(--radius);">
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
</section>
