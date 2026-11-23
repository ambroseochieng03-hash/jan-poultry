/* JAN Poultry: registers the service worker and offers to install the app. */
(function () {
    'use strict';

    if ('serviceWorker' in navigator && (location.protocol === 'https:' || location.hostname === 'localhost')) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(function () {});
        });
    }

    var KEY = 'jp_install_dismissed';
    var COOLDOWN_DAYS = 7;
    var deferred = null;
    var shown = false;
    var timer = null;
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
            return !!t && (Date.now() - t) < COOLDOWN_DAYS * 86400000;
        } catch (e) { return false; }
    }
    function remember() {
        try { localStorage.setItem(KEY, String(Date.now())); } catch (e) {}
    }
    function canInstall() {
        return !standalone() && (!!deferred || isIos());
    }
    function syncButtons() {
        var show = canInstall();
        document.querySelectorAll('[data-install-app]').forEach(function (b) { b.hidden = !show; });
    }

    /* For checking on a phone: type __jpPwa() in the browser console. */
    window.__jpPwa = function () {
        return { standalone: standalone(), ios: isIos(), promptReady: !!deferred, dismissedRecently: recentlyDismissed(), worker: !!(navigator.serviceWorker && navigator.serviceWorker.controller) };
    };

    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferred = e;
        syncButtons();
        schedule();
    });
    window.addEventListener('appinstalled', function () {
        deferred = null;
        remember();
        close();
        syncButtons();
        window.dispatchEvent(new CustomEvent('jp:installed'));
    });

    function schedule() {
        if (shown || timer || standalone() || recentlyDismissed()) { return; }
        timer = setTimeout(function () { timer = null; open(false); }, 6000);
    }

    function runPrompt() {
        if (!deferred) { return; }
        var p = deferred;
        deferred = null;
        p.prompt();
        p.userChoice.then(function (choice) {
            if (choice && choice.outcome !== 'accepted') { remember(); }
            close();
            syncButtons();
        }).catch(function () { close(); });
    }

    function open(force) {
        if (shown || standalone()) { return; }
        var mode = deferred ? 'prompt' : (isIos() ? 'ios' : (force ? 'manual' : ''));
        if (!mode) { return; }
        shown = true;

        var css = document.createElement('link');
        css.rel = 'stylesheet';
        css.href = '/assets/css/pwa.css';
        document.head.appendChild(css);

        var steps = mode === 'ios'
            ? '<ol class="jp-pwa-steps"><li>Tap the <strong>Share</strong> button in Safari.</li><li>Choose <strong>Add to Home Screen</strong>.</li><li>Tap <strong>Add</strong>.</li></ol>'
            : '<ol class="jp-pwa-steps"><li>Open your browser menu.</li><li>Choose <strong>Install app</strong> or <strong>Add to Home screen</strong>.</li></ol>';

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
            (mode === 'prompt' ? '<button type="button" class="jp-pwa-go" data-pwa-install>Install app</button>' : steps) +
            '<button type="button" class="jp-pwa-later" data-pwa-later>' + (mode === 'prompt' ? 'Not now' : 'Got it') + '</button>' +
            '</div>';
        document.body.appendChild(root);

        var primary = root.querySelector('[data-pwa-install]') || root.querySelector('[data-pwa-later]');
        if (primary) { primary.focus(); }

        root.addEventListener('click', function (e) {
            if (e.target === root || e.target.closest('[data-pwa-later]')) { remember(); close(); return; }
            if (e.target.closest('[data-pwa-install]')) { runPrompt(); }
        });
        document.addEventListener('keydown', onKey);
    }
    function onKey(e) { if (e.key === 'Escape') { remember(); close(); } }
    function close() {
        document.removeEventListener('keydown', onKey);
        if (root) { root.remove(); root = null; }
        shown = false;
    }

    /* "Install app" links and buttons placed anywhere on a page. */
    document.addEventListener('click', function (e) {
        if (!e.target.closest('[data-install-app]')) { return; }
        e.preventDefault();
        if (deferred) { runPrompt(); } else { open(true); }
    });

    function ready() {
        syncButtons();
        if (isIos()) { schedule(); }
        if (/[?&]install=1\b/.test(location.search)) { setTimeout(function () { open(true); }, 1000); }
    }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', ready); } else { ready(); }
})();
