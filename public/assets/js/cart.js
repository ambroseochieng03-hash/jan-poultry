/* JAN Poultry: cart and checkout without page reloads. */
(function () {
    'use strict';

    function toast(message, isError) {
        if (!message) { return; }
        var old = document.querySelector('.jp-toast-lite');
        if (old) { old.remove(); }
        var el = document.createElement('div');
        el.className = 'jp-toast-lite' + (isError ? ' is-error' : '');
        el.setAttribute('role', 'status');
        el.textContent = message;
        document.body.appendChild(el);
        setTimeout(function () { el.classList.add('is-gone'); }, 2600);
        setTimeout(function () { el.remove(); }, 3100);
    }

    function isJson(res) {
        return (res.headers.get('content-type') || '').indexOf('json') !== -1;
    }

    /* ---------- cart page ---------- */
    var cartPage = document.querySelector('.jp-cart-page');
    if (cartPage) {
        var busy = false;
        cartPage.addEventListener('submit', function (e) {
            var form = e.target.closest('form[action="cart-update"]');
            if (!form) { return; }
            e.preventDefault();
            if (busy) { return; }
            busy = true;
            cartPage.classList.add('is-busy');

            var fd = new FormData(form);
            if (e.submitter && e.submitter.name) { fd.set(e.submitter.name, e.submitter.value); }

            fetch('/cart-update', {
                method: 'POST',
                body: new URLSearchParams(fd),
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'fetch' },
                credentials: 'same-origin'
            }).then(function (res) {
                if (!isJson(res)) { location.reload(); return null; }
                return res.json();
            }).then(function (data) {
                if (!data) { return null; }
                return fetch('/cart', { credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
                    .then(function (r) { return r.text(); })
                    .then(function (html) {
                        var fresh = new DOMParser().parseFromString(html, 'text/html').querySelector('.jp-cart-page');
                        if (fresh) { cartPage.innerHTML = fresh.innerHTML; } else { location.reload(); }
                        toast(data.message, !data.success);
                    });
            }).catch(function () {
                toast('Network problem. Please try again.', true);
            }).then(function () {
                busy = false;
                cartPage.classList.remove('is-busy');
            });
        });
    }

    /* ---------- checkout page ---------- */
    var form = document.querySelector('form[data-ajax-checkout]');
    if (form) {
        var submitting = false;
        var button = form.querySelector('button[type="submit"]');
        var status = document.createElement('p');
        status.className = 'jp-form-status';
        status.setAttribute('role', 'alert');
        status.hidden = true;
        form.insertBefore(status, button);

        function say(message) { status.textContent = message; status.hidden = !message; }

        function check() {
            var addr = form.elements.delivery_address.value.replace(/\s+/g, ' ').trim();
            if (addr.length < 5 || addr.length > 300) { return 'Enter a delivery address between 5 and 300 characters.'; }
            if (form.elements.email) {
                var email = form.elements.email.value.trim();
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { return 'Enter a valid email address.'; }
                var phone = form.elements.phone.value.replace(/[\s\-()]/g, '');
                if (!/^(?:\+?254|0)[17]\d{8}$/.test(phone)) { return 'Enter a valid Kenyan phone number, for example 0712345678.'; }
            }
            return '';
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (submitting) { return; }
            var problem = check();
            if (problem) { say(problem); return; }
            say('');
            submitting = true;
            button.disabled = true;
            var label = button.textContent;
            button.textContent = 'Placing order...';

            fetch('/checkout-place', {
                method: 'POST',
                body: new URLSearchParams(new FormData(form)),
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'fetch' },
                credentials: 'same-origin'
            }).then(function (res) {
                if (!isJson(res)) { location.reload(); return null; }
                return res.json();
            }).then(function (data) {
                if (!data) { return; }
                if (data.success) {
                    var go = data.redirect || '/orders';
                    if (typeof showAppResponseModal === 'function') {
                        showAppResponseModal({ icon: '\u2713', title: 'Order Received', message: data.message, buttonText: 'OK', autoClose: 2000, redirect: go });
                    } else {
                        location.assign(go);
                    }
                    return;
                }
                say(data.message || 'We could not place your order.');
                if (data.redirect) { setTimeout(function () { location.assign(data.redirect); }, 2200); }
            }).catch(function () {
                say('Network problem. Please try again.');
            }).then(function () {
                if (!button.dataset.done) {
                    submitting = false;
                    button.disabled = false;
                    button.textContent = label;
                }
            });
        });
    }
})();
