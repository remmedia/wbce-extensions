(function () {
    'use strict';
    if (window.wbceStoreAutomaticRefresh) return;
    window.wbceStoreAutomaticRefresh = true;
    var timer = null;

    function signature(root) {
        if (!root) return '';
        return Array.prototype.map.call(root.querySelectorAll('.store-package'), function (card) {
            return [card.getAttribute('data-store-type'), card.getAttribute('data-store-name'), card.getAttribute('data-store-version'), card.getAttribute('data-store-update'), card.getAttribute('data-store-installed')].join('|');
        }).join('\n');
    }

    function schedule(delay) {
        window.clearTimeout(timer);
        timer = window.setTimeout(refresh, delay);
    }

    function refresh() {
        var current = document.querySelector('#store-app');
        if (!current) return;
        if (current.querySelector('[aria-busy="true"]') || document.querySelector('dialog[open]')) {
            schedule(15000);
            return;
        }
        fetch(window.location.href, {
            credentials: 'same-origin', cache: 'no-store',
            headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html'}
        }).then(function (response) {
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return response.text();
        }).then(function (html) {
            var page = new DOMParser().parseFromString(html, 'text/html');
            var fresh = page.querySelector('#store-app');
            current = document.querySelector('#store-app');
            if (!fresh || !current || signature(fresh) === signature(current)) return;
            var saved = window.wbceStoreCaptureState ? window.wbceStoreCaptureState() : null;
            current.innerHTML = fresh.innerHTML;
            if (window.wbceStoreBind) window.wbceStoreBind();
            if (window.wbceStoreRestoreState) window.wbceStoreRestoreState(saved);
            else if (window.wbceStoreApplyFilters) window.wbceStoreApplyFilters();
        }).catch(function () {
            // The visible Store remains usable with the last valid catalogue.
        }).finally(function () {
            schedule(60000);
        });
    }

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) schedule(500);
    });
    schedule(60000);
}());
