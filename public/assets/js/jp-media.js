(function () {
    'use strict';

    var meta = document.querySelector('meta[name="jp-media-base"]');
    var base = meta ? meta.content.replace(/\/+$/, '') : '/uploads/products';
    var absoluteBase = new URL(base, location.href).href;
    var placeholder = '/assets/img/no-image.svg';

    // Builds the URL of a product file (also used by admin.js and product-details-modal.js).
    window.jpMediaUrl = function (file) {
        return base + '/' + encodeURIComponent(String(file || '').split('/').pop());
    };

    function isProductMedia(src) {
        return !!src && src.indexOf(absoluteBase + '/') === 0;
    }

    // A file that is still being processed (or failed) must never show a broken icon.
    function fallback(el) {
        if (el.tagName === 'IMG') {
            if (el.dataset.jpFallback || !isProductMedia(el.currentSrc || el.src)) { return; }
            el.dataset.jpFallback = '1';
            el.src = placeholder;
            el.alt = 'Image is being prepared';
            return;
        }

        if (el.tagName === 'SOURCE' && el.parentElement && el.parentElement.tagName === 'VIDEO') {
            var video = el.parentElement;
            if (!isProductMedia(el.src)) { return; }
            var img = document.createElement('img');
            img.src = placeholder;
            img.alt = 'Video is being prepared';
            img.className = video.className;
            img.dataset.jpFallback = '1';
            video.replaceWith(img);
        }
    }

    // error events do not bubble, so listen during the capture phase
    document.addEventListener('error', function (e) {
        if (e.target && e.target.tagName) { fallback(e.target); }
    }, true);

    // images that failed before this script ran
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('img').forEach(function (img) {
            if (img.complete && img.naturalWidth === 0) { fallback(img); }
        });
    });
})();
