/* JAN Poultry: asks to send order updates to this phone, then subscribes it. */
(function () {
    'use strict';

    function meta(name) {
        var el = document.querySelector('meta[name="' + name + '"]');
        return el ? el.content : '';
    }
    var vapid = meta('jp-vapid');
    var csrf = meta('jp-csrf');
    if (!vapid || !csrf || !('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) { return; }

    var KEY = 'jp_push_dismissed';
    var SYNC = 'jp_push_synced';
    var root = null;

    function standalone() {
        return (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) || window.navigator.standalone === true;
    }
    function isIos() {
        return /iphone|ipad|ipod/i.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    }
    function recentlyDismissed() {
        try {
            var t = parseInt(localStorage.getItem(KEY) || '0', 10);
            return !!t && (Date.now() - t) < 7 * 86400000;
        } catch (e) { return false; }
    }
    function stamp(key) { try { localStorage.setItem(key, String(Date.now())); } catch (e) {} }
    function syncedToday() {
        try { return (Date.now() - parseInt(localStorage.getItem(SYNC) || '0', 10)) < 86400000; } catch (e) { return false; }
    }
    function keyBytes(b64) {
        var pad = '='.repeat((4 - b64.length % 4) % 4);
        var raw = atob((b64 + pad).replace(/-/g, '+').replace(/_/g, '/'));
        var out = new Uint8Array(raw.length);
        for (var i = 0; i < raw.length; i++) { out[i] = raw.charCodeAt(i); }
        return out;
    }

    function save(sub, welcome) {
        var j = sub.toJSON();
        return fetch('/push-subscribe', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'fetch', 'X-CSRF-Token': csrf },
            body: JSON.stringify({ action: 'subscribe', endpoint: j.endpoint, keys: j.keys, welcome: !!welcome })
        }).then(function (r) { if (r.ok) { stamp(SYNC); } return r.ok; });
    }

    function subscribe(welcome) {
        return navigator.serviceWorker.ready.then(function (reg) {
            return reg.pushManager.getSubscription().then(function (sub) {
                return sub || reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: keyBytes(vapid) });
            });
        }).then(function (sub) { return save(sub, welcome); });
    }

    function close() {
        document.removeEventListener('keydown', onKey);
        if (root) { root.remove(); root = null; }
    }
    function onKey(e) { if (e.key === 'Escape') { stamp(KEY); close(); } }

    function ask() {
        if (root || Notification.permission !== 'default') { return; }
        var css = document.createElement('link');
        css.rel = 'stylesheet';
        css.href = '/assets/css/pwa.css';
        document.head.appendChild(css);

        root = document.createElement('div');
        root.className = 'jp-pwa';
        root.setAttribute('role', 'dialog');
        root.setAttribute('aria-modal', 'true');
        root.setAttribute('aria-labelledby', 'jpPushTitle');
        root.innerHTML =
            '<div class="jp-pwa-card">' +
            '<img class="jp-pwa-icon" src="/assets/icons/icon-192.png" alt="" width="72" height="72">' +
            '<h2 id="jpPushTitle">Get order updates</h2>' +
            '<p>Allow notifications and we will tell you when your order is accepted, on the way and delivered.</p>' +
            '<button type="button" class="jp-pwa-go" data-push-yes>Turn on notifications</button>' +
            '<button type="button" class="jp-pwa-later" data-push-no>Not now</button>' +
            '</div>';
        document.body.appendChild(root);
        root.querySelector('[data-push-yes]').focus();

        root.addEventListener('click', function (e) {
            if (e.target === root || e.target.closest('[data-push-no]')) { stamp(KEY); close(); return; }
            if (!e.target.closest('[data-push-yes]')) { return; }
            Notification.requestPermission().then(function (p) {
                close();
                if (p === 'granted') { subscribe(true).catch(function () {}); } else { stamp(KEY); }
            });
        });
        document.addEventListener('keydown', onKey);
    }

    function init() {
        if (Notification.permission === 'granted') {
            if (!syncedToday()) { subscribe(false).catch(function () {}); }
            return;
        }
        if (Notification.permission !== 'default' || recentlyDismissed()) { return; }
        // iPhones only allow push for apps added to the home screen.
        if (isIos() && !standalone()) { return; }
        setTimeout(ask, 12000);
    }

    // Right after the app is installed, ask without waiting.
    window.addEventListener('jp:installed', function () {
        if (Notification.permission === 'default' && !(isIos() && !standalone())) { setTimeout(ask, 3000); }
    });

    /* Any button or link with data-push-enable asks on demand. */
    document.addEventListener('click', function (e) {
        if (e.target.closest('[data-push-enable]')) { e.preventDefault(); ask(); }
    });

    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
})();
