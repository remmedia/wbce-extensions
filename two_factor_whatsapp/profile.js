(function () {
    'use strict';
    if (window.wbceWhatsAppProfileBound) return;
    window.wbceWhatsAppProfileBound = true;

    function root() { return document.querySelector('.wap[data-whatsapp-endpoint]'); }
    function toast(message, error) {
        var node = document.querySelector('.wap-async-toast');
        if (!node) {
            node = document.createElement('div');
            node.className = 'wap-async-toast';
            node.setAttribute('role', error ? 'alert' : 'status');
            document.body.appendChild(node);
        }
        node.textContent = message;
        node.classList.toggle('error', !!error);
        node.hidden = false;
        window.setTimeout(function () { node.hidden = true; }, 6000);
    }
    function refresh(endpoint) {
        return fetch(endpoint, {credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest'}})
            .then(function (response) { if (!response.ok) throw new Error(String(response.status)); return response.text(); })
            .then(function (html) {
                var page = new DOMParser().parseFromString(html, 'text/html');
                var fresh = page.querySelector('.wap[data-whatsapp-endpoint]');
                var current = root();
                if (!fresh || !current) throw new Error(current ? current.dataset.actionFailed : 'Action failed');
                current.replaceWith(fresh);
            });
    }
    document.addEventListener('submit', function (event) {
        var current = root();
        var form = event.target;
        if (!current || !form.closest || form.closest('.wap') !== current) return;
        event.preventDefault();
        var button = form.querySelector('button[type="submit"]');
        if (button) { button.disabled = true; button.setAttribute('aria-busy', 'true'); }
        fetch(current.dataset.whatsappEndpoint, {method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest'}})
            .then(function (response) { return response.json().then(function (result) { if (!response.ok || !result.success) throw new Error(result.message || current.dataset.actionFailed); return result; }); })
            .then(function (result) { return refresh(current.dataset.whatsappEndpoint).then(function () { toast(result.message, false); }); })
            .catch(function (error) { if (button) { button.disabled = false; button.removeAttribute('aria-busy'); } toast(error.message || current.dataset.actionFailed, true); });
    });
}());
