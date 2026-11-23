/* JAN Poultry home page: category filter, add to order, guest/account modal. */
(function () {
    'use strict';
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    var toasts = document.getElementById('jpToasts');
    var modal = document.getElementById('authModal');

    function toast(text, opts) {
        opts = opts || {};
        var el = document.createElement('div');
        el.className = 'jp-toast' + (opts.error ? ' is-error' : '');
        var span = document.createElement('span');
        span.textContent = text;
        el.appendChild(span);
        if (opts.action) { el.appendChild(opts.action); }
        toasts.appendChild(el);
        setTimeout(function () { el.remove(); }, opts.error ? 5000 : 4000);
    }

    function setCount(n) {
        document.querySelectorAll('[data-cart-count]').forEach(function (b) {
            b.textContent = n;
            b.hidden = !n;
        });
        document.querySelectorAll('[data-cart-count-text]').forEach(function (b) { b.textContent = n; });
    }

    /* Category filter */
    var chips = document.querySelectorAll('.jp-chip');
    var cards = document.querySelectorAll('.jp-card');
    var noMatch = document.getElementById('jpNoMatch');
    chips.forEach(function (chip) {
        chip.addEventListener('click', function () {
            var f = chip.dataset.filter;
            chips.forEach(function (c) { c.classList.toggle('is-active', c === chip); });
            var shown = 0;
            cards.forEach(function (card) {
                var show = f === '*' || card.dataset.category === f;
                card.hidden = !show;
                if (show) { shown++; }
            });
            if (noMatch) { noMatch.hidden = shown > 0; }
        });
    });

    /* Sign-in modal: tabs, forms, open/close */
    var tabs = modal ? modal.querySelectorAll('.jp-tabs [data-tab]') : [];
    var panels = modal ? modal.querySelectorAll('.jp-form') : [];
    var titles = { login: 'Sign in', register: 'Create your account', guest: 'Continue as a guest', forgot: 'Reset your password' };
    var notice = document.getElementById('authNotice');

    function showTab(name) {
        if (!modal) { return; }
        panels.forEach(function (f) { f.hidden = f.dataset.panel !== name; });
        tabs.forEach(function (t) { t.classList.toggle('is-active', t.dataset.tab === name); });
        document.getElementById('authTitle').textContent = titles[name] || 'Welcome to JAN Poultry';
        var f = modal.querySelector('.jp-form[data-panel="' + name + '"]');
        var first = f && f.querySelector('input:not([type=hidden])');
        if (first) { setTimeout(function () { first.focus(); }, 30); }
    }
    function openModal(tab) {
        if (!modal) { return; }
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        showTab(tab || 'login');
    }
    function closeModal() {
        if (!modal) { return; }
        modal.hidden = true;
        document.body.style.overflow = '';
        if (notice) { notice.hidden = true; }
    }
    document.querySelectorAll('[data-auth]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!modal) { return; }
            e.preventDefault();
            openModal(el.dataset.auth);
        });
    });
    document.querySelectorAll('[data-close-auth]').forEach(function (b) { b.addEventListener('click', closeModal); });
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) { closeModal(); return; }
            var t = e.target.closest('[data-tab]');
            if (t) { showTab(t.dataset.tab); }
        });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) { closeModal(); } });
        if (modal.dataset.open) { openModal(modal.dataset.open); }

        panels.forEach(function (form) {
            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                var msg = form.querySelector('.jp-form-msg');
                var btn = form.querySelector('button[type=submit]');
                msg.hidden = true;
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }
                btn.disabled = true;
                try {
                    var res = await fetch(form.dataset.endpoint, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json' },
                        body: new URLSearchParams(new FormData(form)),
                        credentials: 'same-origin'
                    });
                    var data = await res.json();
                    if (data.success && data.redirect) {
                        window.location.href = data.redirect;
                        return;
                    }
                    msg.textContent = data.message || (data.success ? 'Done.' : 'Something went wrong. Please try again.');
                    msg.className = 'jp-form-msg' + (data.success ? ' is-ok' : '');
                    msg.hidden = false;
                } catch (err) {
                    msg.textContent = 'Network problem. Please try again.';
                    msg.hidden = false;
                } finally {
                    btn.disabled = false;
                }
            });
        });
    }

    /* Add to order */
    document.querySelectorAll('[data-add]').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            if (btn.disabled) { return; }
            btn.disabled = true;
            try {
                var res = await fetch('/cart-add', {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': csrf, 'Accept': 'application/json' },
                    body: new URLSearchParams({ product_id: btn.dataset.add, csrf_token: csrf }),
                    credentials: 'same-origin'
                });
                var data = await res.json();
                if (!data.success) {
                    toast(data.message || 'Could not add this item.', { error: true });
                    return;
                }
                if (data.pending) {
                    setCount(data.pending_count);
                    var seen = false;
                    try { seen = sessionStorage.getItem('jpOrderModal') === '1'; } catch (e) {}
                    if (!seen) {
                        try { sessionStorage.setItem('jpOrderModal', '1'); } catch (e) {}
                        openModal('guest');
                    } else {
                        var go = document.createElement('button');
                        go.type = 'button';
                        go.textContent = 'Checkout';
                        go.addEventListener('click', function () { openModal('guest'); });
                        toast('Added to your order.', { action: go });
                    }
                } else {
                    setCount(data.cart_count);
                    var a = document.createElement('a');
                    a.href = '/cart';
                    a.textContent = 'View cart';
                    toast('Added to your cart.', { action: a });
                }
            } catch (e) {
                toast('Network problem. Please try again.', { error: true });
            } finally {
                btn.disabled = false;
            }
        });
    });
})();
