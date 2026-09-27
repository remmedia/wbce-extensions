(function () {
    'use strict';

    function toast(message, ok) {
        var node = document.createElement('div');
        node.className = 'wbce-admin-toast ' + (ok ? 'is-success' : 'is-error');
        node.setAttribute('role', ok ? 'status' : 'alert');
        node.textContent = message;
        document.body.appendChild(node);
        window.setTimeout(function () { node.classList.add('is-visible'); }, 10);
        window.setTimeout(function () {
            node.classList.remove('is-visible');
            window.setTimeout(function () { node.remove(); }, 250);
        }, 4200);
    }

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form || form.id !== 'miniform-settings' || !window.fetch) return;
        event.preventDefault();
        var button = form.querySelector('[type="submit"]');
        var oldText = button ? button.value : '';
        if (button) { button.disabled = true; button.setAttribute('aria-busy', 'true'); }
        fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest'} })
            .then(function (response) { return response.json().then(function (data) { return {ok: response.ok, data: data}; }); })
            .then(function (result) {
                if (result.data.ftan) {
                    var holder = document.createElement('div');
                    holder.innerHTML = result.data.ftan;
                    var fresh = holder.querySelector('input[type="hidden"]');
                    if (fresh) {
                        document.querySelectorAll('input[name="' + fresh.name.replace(/"/g, '\\"') + '"]').forEach(function (current) { current.value = fresh.value; });
                    }
                }
                toast(result.data.message || form.getAttribute('data-error'), result.ok && result.data.success);
            })
            .catch(function () { toast(form.getAttribute('data-error'), false); })
            .finally(function () {
                if (button) { button.disabled = false; button.removeAttribute('aria-busy'); button.value = oldText; }
            });
    });
}());
