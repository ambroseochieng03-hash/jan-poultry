/* JAN Poultry: registers the service worker and offers to install the app. */
(function () {
    'use strict';

    if ('serviceWorker' in navigator && (location.protocol === 'https:' || location.hostname === 'localhost')) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(function () {});
        });
    }

    var KEY = 'jp_install_dismissed';
    var COOLDOWN_DAYS = 14;
    var deferred = null;

    function standalone() {
        return (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) || window.navigator.standalone === true;
    }
    function isIos() {
        return /iphone|ipad|ipod/i.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    }
    function phoneLike() {
        return window.matchMedia && (window.matchMedia('(pointer: coarse)').matches || window.matchMedia('(max-width: 900px)').matches);
    }
    function recentlyDismissed() {
        try {
            var t = parseInt(localStorage.getItem(KEY) || '0', 10);
            return t && (Date.now() - t) < COOLDOWN_DAYS * 86400000;
        } catch (e) { return false; }
    }
    function remember() {
        try { localStorage.setItem(KEY, String(Date.now())); } catch (e) {}
    }

    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferred = e;
        schedule();
    });
    window.addEventListener('appinstalled', function () {
        deferred = null;
        remember();
        close();
        window.dispatchEvent(new CustomEvent('jp:installed'));
    });

    var shown = false;
    var timer = null;
    function schedule() {
        if (shown || timer || standalone() || recentlyDismissed() || !phoneLike()) { return; }
        timer = setTimeout(open, 8000);
    }
    // iPhones never fire beforeinstallprompt, so they get the how-to version.
    if (isIos()) { schedule(); }

    var root = null;
    function open() {
        if (shown || standalone() || (!deferred && !isIos())) { return; }
        shown = true;

        var css = document.createElement('link');
        css.rel = 'stylesheet';
        css.href = '/assets/css/pwa.css';
        document.head.appendChild(css);

        var ios = !deferred;
        root = document.createElement('div');
        root.className = 'jp-pwa';
        root.setAttribute('role', 'dialog');
        root.setAttribute('aria-modal', 'true');
        root.setAttribute('aria-labelledby', 'jpPwaTitle');
        root.innerHTML =
            '<div class="jp-pwa-card">' +
            '<img class="jp-pwa-icon" src="/assets/icons/icon-192.png" alt="" width="72" height="72">' +
            '<h2 id="jpPwaTitle">Install JAN Poultry</h2>' +
            '<p>Open the shop in one tap from your home screen, order faster, and get told when your order is on the way.</p>' +
            (ios
                ? '<ol class="jp-pwa-steps"><li>Tap the <strong>Share</strong> button in Safari.</li><li>Choose <strong>Add to Home Screen</strong>.</li><li>Tap <strong>Add</strong>.</li></ol>'
                : '<button type="button" class="jp-pwa-go" data-pwa-install>Install app</button>') +
            '<button type="button" class="jp-pwa-later" data-pwa-later>' + (ios ? 'Got it' : 'Not now') + '</button>' +
            '</div>';
        document.body.appendChild(root);

        var primary = root.querySelector('[data-pwa-install]') || root.querySelector('[data-pwa-later]');
        if (primary) { primary.focus(); }

        root.addEventListener('click', function (e) {
            if (e.target === root || e.target.closest('[data-pwa-later]')) { remember(); close(); return; }
            if (e.target.closest('[data-pwa-install]') && deferred) {
                var p = deferred;
                deferred = null;
                p.prompt();
                p.userChoice.then(function (choice) {
                    if (choice && choice.outcome !== 'accepted') { remember(); }
                    close();
                }).catch(close);
            }
        });
        document.addEventListener('keydown', onKey);
    }
    function onKey(e) { if (e.key === 'Escape') { remember(); close(); } }
    function close() {
        document.removeEventListener('keydown', onKey);
        if (root) { root.remove(); root = null; }
    }
})();
