/*
 * Shopping cart for published sites (inlined by resources/views/export/shell.blade.php when the
 * site sells something). Plain ES5, no dependencies.
 *
 * window.__SHOP__ = {
 *   key, mode: 'live' | 'preview' | 'static', orderUrl, siteName,
 *   products: { id: { title, price, amount, image, url } }, currency: { symbol, prefix, space },
 *   whatsapp, email
 * }
 *
 * The cart in localStorage holds only {id, quantity}. Names and prices always come from the
 * page's product list, so a renamed / re-priced / deleted product can never be ordered stale.
 */
(function (w, d) {
    var S = w.__SHOP__;
    if (!S || !S.products || w.SiteCart) return;

    var KEY = 'cart:' + S.key;
    var MAX_QTY = 99;
    var view = 'cart'; // cart · checkout · done
    var lastOrder = null;
    var lastFocus = null;
    var busy = false;

    /* ---------------- storage ---------------- */
    function read() {
        var raw = [];
        try {
            raw = JSON.parse(w.localStorage.getItem(KEY) || '[]');
        } catch (e) {}
        var out = [];
        for (var i = 0; i < raw.length; i++) {
            var line = raw[i];
            // Drop products that no longer exist on the site.
            if (line && S.products[line.id]) out.push({ id: line.id, quantity: clamp(line.quantity) });
        }
        return out;
    }
    function write(lines) {
        try {
            w.localStorage.setItem(KEY, JSON.stringify(lines));
        } catch (e) {}
        render();
    }
    function clamp(q) {
        q = parseInt(q, 10);
        return isNaN(q) ? 1 : Math.max(1, Math.min(MAX_QTY, q));
    }
    function count() {
        var n = 0;
        var lines = read();
        for (var i = 0; i < lines.length; i++) n += lines[i].quantity;
        return n;
    }

    /* ---------------- cart operations ---------------- */
    function add(id, qty) {
        if (!S.products[id]) return;
        var lines = read();
        var found = false;
        for (var i = 0; i < lines.length; i++) {
            if (lines[i].id === id) {
                lines[i].quantity = clamp(lines[i].quantity + qty);
                found = true;
            }
        }
        if (!found) lines.push({ id: id, quantity: clamp(qty) });
        write(lines);
    }
    function setQty(id, qty) {
        var lines = read();
        for (var i = 0; i < lines.length; i++) if (lines[i].id === id) lines[i].quantity = clamp(qty);
        write(lines);
    }
    function remove(id) {
        write(read().filter(function (l) { return l.id !== id; }));
    }
    function clear() {
        write([]);
    }

    /* ---------------- money ---------------- */
    function money(amount) {
        var c = S.currency || {};
        var fixed = amount % 1 === 0 ? 0 : 2;
        var n = amount.toLocaleString('en-US', { minimumFractionDigits: fixed, maximumFractionDigits: fixed });
        if (!c.symbol) return n;
        var gap = c.space ? ' ' : '';
        return c.prefix ? c.symbol + gap + n : n + gap + c.symbol;
    }
    function totals(lines) {
        var sum = 0;
        var priced = 0;
        for (var i = 0; i < lines.length; i++) {
            var p = S.products[lines[i].id];
            if (p.amount != null) {
                sum += p.amount * lines[i].quantity;
                priced++;
            }
        }
        return { sum: Math.round(sum * 100) / 100, priced: priced, unpriced: lines.length - priced };
    }

    /* ---------------- helpers ---------------- */
    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (ch) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
        });
    }
    function el(tag, cls, html) {
        var e = d.createElement(tag);
        if (cls) e.className = cls;
        if (html != null) e.innerHTML = html;
        return e;
    }
    var ICON_CART = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.5L21 8H6.2"/><circle cx="10" cy="20" r="1.2"/><circle cx="17" cy="20" r="1.2"/></svg>';
    var ICON_X = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>';
    var ICON_CHECK = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>';
    var ICON_WA = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.3-.4.7-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.3.8 3.2.6.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.2-1.2-.1-.1-.3-.2-.5-.3z"/></svg>';

    /* ---------------- UI shell ---------------- */
    var fab = el('button', 'sc-fab', ICON_CART + '<span class="sc-fab-count" aria-hidden="true">0</span>');
    fab.type = 'button';
    var overlay = el('div', 'sc-overlay');
    var drawer = el('aside', 'sc-drawer');
    drawer.setAttribute('role', 'dialog');
    drawer.setAttribute('aria-modal', 'true');
    drawer.setAttribute('aria-label', 'Your cart');
    drawer.setAttribute('tabindex', '-1');
    var live = el('div', 'sc-sr');
    live.setAttribute('aria-live', 'polite');
    var toast = el('div', 'sc-toast');
    toast.setAttribute('role', 'status');

    function open(which) {
        view = which || (view === 'done' ? 'cart' : view);
        if (!isOpen()) lastFocus = d.activeElement;
        d.documentElement.classList.add('sc-open');
        render();
        setTimeout(function () {
            // Checkout: straight to the name field. Cart: the panel itself (no stray focus ring).
            var first = (view === 'checkout' && drawer.querySelector('[name=name]')) || drawer;
            first.focus({ preventScroll: true });
        }, 60);
    }
    function close() {
        d.documentElement.classList.remove('sc-open');
        if (view === 'done') view = 'cart';
        if (lastFocus && lastFocus.focus) lastFocus.focus({ preventScroll: true });
    }
    function isOpen() {
        return d.documentElement.classList.contains('sc-open');
    }

    function showToast(html) {
        toast.innerHTML = html;
        toast.classList.add('sc-toast--on');
        clearTimeout(toast._t);
        toast._t = setTimeout(function () {
            toast.classList.remove('sc-toast--on');
        }, 3200);
    }

    /* ---------------- rendering ---------------- */
    function render() {
        var n = count();
        fab.querySelector('.sc-fab-count').textContent = n > 99 ? '99+' : n;
        fab.setAttribute('aria-label', 'Open cart, ' + n + ' item' + (n === 1 ? '' : 's'));
        fab.classList.toggle('sc-fab--on', n > 0 || !!d.querySelector('[data-add-to-cart]'));
        fab.classList.toggle('sc-fab--empty', n === 0);
        d.querySelectorAll('[data-cart-count]').forEach(function (c) { c.textContent = n; });
        if (isOpen() || view === 'done') drawer.innerHTML = view === 'checkout' ? checkoutHtml() : view === 'done' ? doneHtml() : cartHtml();
    }

    function header(title, back) {
        return '<header class="sc-head">' +
            (back ? '<button type="button" class="sc-icon" data-sc="back" aria-label="Back to cart"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg></button>' : '') +
            '<h2>' + title + '</h2><button type="button" class="sc-icon" data-sc="close" aria-label="Close cart">' + ICON_X + '</button></header>';
    }

    function summaryHtml(lines) {
        var t = totals(lines);
        var total = t.priced ? money(t.sum) : 'On request';
        return '<div class="sc-total"><span>Subtotal</span><strong>' + esc(total) + '</strong></div>' +
            (t.unpriced && t.priced ? '<p class="sc-fine">+ ' + t.unpriced + ' item' + (t.unpriced > 1 ? 's' : '') + ' priced on request</p>' : '') +
            '<p class="sc-fine">' + (S.mode === 'static' ? 'You\'ll send this order to the shop, who will confirm price and delivery.' : 'No payment now — the shop confirms your order, delivery and payment with you.') + '</p>';
    }

    function cartHtml() {
        var lines = read();
        if (!lines.length) {
            return header('Your cart') + '<div class="sc-empty">' + ICON_CART + '<p class="sc-empty-title">Your cart is empty</p><p>Add something you like and it will show up here.</p><button type="button" class="sc-btn sc-primary" data-sc="close">Continue shopping</button></div>';
        }
        var rows = '';
        for (var i = 0; i < lines.length; i++) {
            var p = S.products[lines[i].id];
            var q = lines[i].quantity;
            rows += '<li class="sc-line">' +
                '<a class="sc-thumb" href="' + esc(p.url || '#') + '">' + (p.image ? '<img src="' + esc(p.image) + '" alt="" loading="lazy">' : '<span>' + esc((p.title || '?').charAt(0)) + '</span>') + '</a>' +
                '<div class="sc-line-main"><a class="sc-line-title" href="' + esc(p.url || '#') + '">' + esc(p.title) + '</a>' +
                '<span class="sc-line-price">' + esc(p.amount != null ? money(p.amount) : 'Price on request') + '</span>' +
                '<div class="sc-line-row"><div class="sc-qty" role="group" aria-label="Quantity for ' + esc(p.title) + '">' +
                '<button type="button" data-sc="dec" data-id="' + esc(p.id) + '" aria-label="Decrease"' + (q <= 1 ? ' disabled' : '') + '>−</button>' +
                '<input type="number" min="1" max="99" inputmode="numeric" value="' + q + '" data-sc="qty" data-id="' + esc(p.id) + '" aria-label="Quantity">' +
                '<button type="button" data-sc="inc" data-id="' + esc(p.id) + '" aria-label="Increase"' + (q >= MAX_QTY ? ' disabled' : '') + '>+</button></div>' +
                '<button type="button" class="sc-remove" data-sc="remove" data-id="' + esc(p.id) + '">Remove</button></div></div>' +
                '<span class="sc-line-total">' + esc(p.amount != null ? money(p.amount * q) : '') + '</span></li>';
        }
        return header('Your cart <span class="sc-badge">' + count() + '</span>') +
            '<ul class="sc-lines">' + rows + '</ul>' +
            '<footer class="sc-foot">' + summaryHtml(lines) +
            '<button type="button" class="sc-btn sc-primary" data-sc="checkout">Checkout</button>' +
            '<button type="button" class="sc-link" data-sc="close">Continue shopping</button></footer>';
    }

    function altHtml() {
        var alts = '';
        if (S.whatsapp) alts += '<a class="sc-btn sc-wa" data-sc="whatsapp" href="#">' + ICON_WA + 'Order on WhatsApp</a>';
        if (S.email && S.mode === 'static') alts += '<a class="sc-btn sc-ghost" data-sc="email" href="#">Send order by email</a>';
        return alts ? '<div class="sc-or"><span>' + (S.mode === 'static' ? 'Send your order' : 'or') + '</span></div><div class="sc-alts">' + alts + '</div>' : '';
    }

    function checkoutHtml() {
        var lines = read();
        if (!lines.length) {
            view = 'cart';
            return cartHtml();
        }
        var saved = {};
        try {
            saved = JSON.parse(w.localStorage.getItem(KEY + ':customer') || '{}');
        } catch (e) {}
        var canOrder = (S.mode === 'live' && S.orderUrl) || S.mode === 'demo';
        var form =
            '<form class="sc-form" novalidate data-sc-form>' +
            '<div class="sc-err" role="alert" hidden></div>' +
            field('name', 'Full name', 'text', 'name', true, saved.name) +
            '<div class="sc-grid">' + field('phone', 'Phone', 'tel', 'tel', false, saved.phone) + field('email', 'Email', 'email', 'email', false, saved.email) + '</div>' +
            '<p class="sc-fine sc-hint">Add a phone or an email so the shop can confirm your order.</p>' +
            field('address', 'Delivery address', 'textarea', 'street-address', false, saved.address) +
            field('note', 'Note for the shop', 'textarea', 'off', false, '', 'Size, colour, delivery time…') +
            '<input type="text" name="website" tabindex="-1" autocomplete="off" class="sc-hp" aria-hidden="true">' +
            (canOrder ? '<button type="submit" class="sc-btn sc-primary">Place order</button>' : '') +
            (S.mode === 'preview' ? '<p class="sc-notice">Ordering is turned off in preview. Publish your site to start taking orders.</p>' : '') +
            (S.mode === 'demo' ? '<p class="sc-notice">This is a demo shop — nothing is charged or sent. Try it out!</p>' : '') +
            '</form>';
        return header('Checkout', true) +
            '<div class="sc-scroll"><div class="sc-mini">' + lines.map(function (l) {
                var p = S.products[l.id];
                return '<div class="sc-mini-row"><span>' + l.quantity + ' × ' + esc(p.title) + '</span><span>' + esc(p.amount != null ? money(p.amount * l.quantity) : 'On request') + '</span></div>';
            }).join('') + summaryHtml(lines) + '</div>' + form + altHtml() + '</div>';
    }

    function field(name, label, type, auto, required, value, placeholder) {
        var id = 'sc-' + name;
        var input = type === 'textarea'
            ? '<textarea id="' + id + '" name="' + name + '" rows="2" autocomplete="' + auto + '"' + (placeholder ? ' placeholder="' + esc(placeholder) + '"' : '') + '>' + esc(value || '') + '</textarea>'
            : '<input id="' + id + '" name="' + name + '" type="' + type + '" autocomplete="' + auto + '" value="' + esc(value || '') + '"' + (required ? ' required' : '') + '>';
        return '<label class="sc-field" for="' + id + '"><span>' + label + (required ? '' : ' <em>optional</em>') + '</span>' + input + '</label>';
    }

    function doneHtml() {
        var o = lastOrder || {};
        return header('Order sent') +
            '<div class="sc-empty sc-done"><span class="sc-done-icon">' + ICON_CHECK + '</span>' +
            '<p class="sc-empty-title">Thank you' + (o.name ? ', ' + esc(o.name.split(' ')[0]) : '') + '!</p>' +
            (o.demo ? '<p>Demo order <strong>#' + esc(o.number) + '</strong> complete — on a real site it lands in the owner\'s Orders page and inbox.</p>'
                : o.number ? '<p>Your order <strong>#' + esc(o.number) + '</strong> has been sent to ' + esc(S.siteName) + '.</p>' : '<p>Your order is ready to send.</p>') +
            (o.total ? '<p class="sc-fine">Total: <strong>' + esc(o.total) + '</strong>' + (o.hasUnpriced ? ' + items priced on request' : '') + '</p>' : '') +
            '<p class="sc-fine">They\'ll contact you soon to confirm delivery and payment.</p>' +
            '<button type="button" class="sc-btn sc-primary" data-sc="close">Continue shopping</button></div>';
    }

    /* ---------------- order text (WhatsApp / email) ---------------- */
    function orderText(customer) {
        var lines = read();
        var t = totals(lines);
        var text = 'Hello ' + S.siteName + ', I\'d like to order:\n';
        lines.forEach(function (l) {
            var p = S.products[l.id];
            text += '• ' + l.quantity + ' × ' + p.title + (p.amount != null ? ' (' + money(p.amount) + ')' : '') + '\n';
        });
        if (t.priced) text += '\nSubtotal: ' + money(t.sum) + (t.unpriced ? ' + items priced on request' : '') + '\n';
        if (customer) {
            if (customer.name) text += '\nName: ' + customer.name;
            if (customer.phone) text += '\nPhone: ' + customer.phone;
            if (customer.email) text += '\nEmail: ' + customer.email;
            if (customer.address) text += '\nAddress: ' + customer.address;
            if (customer.note) text += '\nNote: ' + customer.note;
        }
        return text;
    }

    function formData() {
        var f = drawer.querySelector('[data-sc-form]');
        var get = function (n) { var i = f && f.elements[n]; return i ? i.value.trim() : ''; };
        return { name: get('name'), phone: get('phone'), email: get('email'), address: get('address'), note: get('note'), website: get('website') };
    }

    function showError(msg, field) {
        var box = drawer.querySelector('.sc-err');
        if (box) {
            box.textContent = msg;
            box.hidden = false;
            box.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
        drawer.querySelectorAll('.sc-field--bad').forEach(function (x) { x.classList.remove('sc-field--bad'); });
        if (field) {
            var input = drawer.querySelector('[name="' + field + '"]');
            if (input) {
                input.closest('.sc-field').classList.add('sc-field--bad');
                input.focus();
            }
        }
    }

    function validate(c) {
        if (!c.name) return ['Please add your name.', 'name'];
        if (!c.phone && !c.email) return ['Add a phone number or an email so the shop can reach you.', 'phone'];
        if (c.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(c.email)) return ['That email doesn\'t look right.', 'email'];
        if (c.phone && !/^[\d\s()+\-.]{6,}$/.test(c.phone)) return ['That phone number doesn\'t look right.', 'phone'];
        return null;
    }

    function remember(c) {
        try {
            w.localStorage.setItem(KEY + ':customer', JSON.stringify({ name: c.name, phone: c.phone, email: c.email, address: c.address }));
        } catch (e) {}
    }

    function submit() {
        if (busy) return;
        var c = formData();
        var bad = validate(c);
        if (bad) return showError(bad[0], bad[1]);
        remember(c);
        busy = true;
        var btn = drawer.querySelector('.sc-form .sc-primary');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Sending…';
        }
        if (S.mode === 'demo') {
            // Demo shop: the full checkout experience, without sending anything.
            return setTimeout(function () {
                busy = false;
                lastOrder = { number: 'DEMO-' + (1000 + Math.floor(Math.random() * 9000)), total: totals(read()).priced ? money(totals(read()).sum) : null, name: c.name, demo: true };
                clear();
                view = 'done';
                render();
                live.textContent = 'Demo order complete.';
            }, 900);
        }
        var payload = { name: c.name, phone: c.phone || null, email: c.email || null, address: c.address || null, note: c.note || null, website: c.website, items: read() };
        fetch(S.orderUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify(payload),
        })
            .then(function (res) {
                return res.json().catch(function () { return {}; }).then(function (body) { return { status: res.status, body: body }; });
            })
            .then(function (r) {
                busy = false;
                if (r.status === 201) {
                    lastOrder = { number: r.body.number, total: r.body.total, hasUnpriced: r.body.hasUnpriced, name: c.name };
                    clear();
                    view = 'done';
                    render();
                    live.textContent = 'Order sent. Thank you!';
                    return;
                }
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Place order';
                }
                if (r.status === 429) return showError('Too many orders in a short time. Please wait a minute and try again.');
                var errors = (r.body && r.body.errors) || {};
                var first = Object.keys(errors)[0];
                if (first === 'items') {
                    write(read()); // drop anything that vanished from the shop
                    return showError(errors.items[0]);
                }
                showError(first ? errors[first][0] : 'We couldn\'t send your order. Please try again' + (S.whatsapp ? ' or order on WhatsApp.' : '.'), first && first.split('.')[0]);
            })
            .catch(function () {
                busy = false;
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Place order';
                }
                showError('No connection. Check your internet and try again' + (S.whatsapp ? ', or order on WhatsApp.' : '.'));
            });
    }

    /* ---------------- events ---------------- */
    d.addEventListener('click', function (e) {
        var t = e.target;
        // Product page controls
        var addBtn = t.closest('[data-add-to-cart], [data-buy-now]');
        if (addBtn) {
            e.preventDefault();
            var id = addBtn.getAttribute('data-id');
            var qtyInput = d.querySelector('[data-qty-for="' + id + '"]');
            var qty = qtyInput ? clamp(qtyInput.value) : 1;
            add(id, qty);
            var p = S.products[id];
            if (addBtn.hasAttribute('data-buy-now')) return open('checkout');
            fab.classList.remove('sc-bump');
            void fab.offsetWidth;
            fab.classList.add('sc-bump');
            d.querySelectorAll('[data-add-to-cart][data-id="' + id + '"]').forEach(function (b) {
                b.classList.add('is-added');
                clearTimeout(b._t);
                b._t = setTimeout(function () { b.classList.remove('is-added'); }, 1800);
            });
            showToast(ICON_CHECK + '<span><strong>' + esc(p.title) + '</strong>' + (qty > 1 ? ' × ' + qty : '') + ' added to your cart</span><button type="button" data-sc="open">View cart</button>');
            live.textContent = p.title + ' added to cart. ' + count() + ' items in cart.';
            return;
        }
        var step = t.closest('[data-qty-step]');
        if (step) {
            var input = d.querySelector('[data-qty-for="' + step.getAttribute('data-qty-step') + '"]');
            if (input) {
                input.value = clamp(clamp(input.value) + parseInt(step.getAttribute('data-delta'), 10));
                syncSteppers(input);
            }
            return;
        }

        if (t.closest('.sc-fab') || t.closest('[data-open-cart]')) return open('cart');
        if (t === overlay) return close();

        var action = t.closest('[data-sc]');
        if (!action) return;
        var a = action.getAttribute('data-sc');
        var aid = action.getAttribute('data-id');
        var line = aid && read().filter(function (l) { return l.id === aid; })[0];
        if (a === 'close') close();
        else if (a === 'open') open('cart');
        else if (a === 'back') { view = 'cart'; render(); }
        else if (a === 'checkout') { view = 'checkout'; render(); var n = drawer.querySelector('[name=name]'); if (n) n.focus(); }
        else if (a === 'inc' && line) setQty(aid, line.quantity + 1);
        else if (a === 'dec' && line) setQty(aid, line.quantity - 1);
        else if (a === 'remove') {
            remove(aid);
            live.textContent = 'Removed from cart.';
        } else if (a === 'whatsapp') {
            e.preventDefault();
            var c = view === 'checkout' ? formData() : null;
            if (c) remember(c);
            w.open('https://wa.me/' + S.whatsapp + '?text=' + encodeURIComponent(orderText(c)), '_blank', 'noopener');
        } else if (a === 'email') {
            e.preventDefault();
            var ce = formData();
            var badE = validate(ce);
            if (badE) return showError(badE[0], badE[1]);
            remember(ce);
            w.location.href = 'mailto:' + S.email + '?subject=' + encodeURIComponent('Order from ' + ce.name) + '&body=' + encodeURIComponent(orderText(ce));
        }
    });

    d.addEventListener('submit', function (e) {
        if (e.target.matches('[data-sc-form]')) {
            e.preventDefault();
            submit();
        }
    });

    d.addEventListener('change', function (e) {
        var t = e.target;
        if (t.matches('[data-sc="qty"]')) setQty(t.getAttribute('data-id'), t.value);
        if (t.matches('[data-qty-for]')) {
            t.value = clamp(t.value);
            syncSteppers(t);
        }
    });

    // Keep the page's two quantity pickers (main + phone bar) in step.
    function syncSteppers(source) {
        d.querySelectorAll('[data-qty-for="' + source.getAttribute('data-qty-for') + '"]').forEach(function (i) {
            if (i !== source) i.value = source.value;
        });
    }

    d.addEventListener('keydown', function (e) {
        if (!isOpen()) return;
        if (e.key === 'Escape') return close();
        if (e.key === 'Tab') {
            // Keep keyboard focus inside the open cart.
            var f = drawer.querySelectorAll('a[href], button:not([disabled]), input:not(.sc-hp), textarea');
            if (!f.length) return;
            var first = f[0];
            var last = f[f.length - 1];
            if (e.shiftKey && d.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && d.activeElement === last) { e.preventDefault(); first.focus(); }
        }
    });

    // Another tab changed the cart.
    w.addEventListener('storage', function (e) {
        if (e.key === KEY) render();
    });

    function mount() {
        d.body.appendChild(fab);
        d.body.appendChild(overlay);
        d.body.appendChild(drawer);
        d.body.appendChild(toast);
        d.body.appendChild(live);
        d.documentElement.classList.add('shop-on'); // reveals the add-to-cart controls
        if (d.querySelector('.ip-bar')) d.documentElement.classList.add('sc-has-bar');
        write(read()); // also cleans out products that were removed from the site
        if (/[?&]cart=open/.test(w.location.search)) open('cart');
    }
    if (d.readyState === 'loading') d.addEventListener('DOMContentLoaded', mount);
    else mount();

    w.SiteCart = { add: add, remove: remove, clear: clear, count: count, open: open, close: close, items: read };
})(window, document);
