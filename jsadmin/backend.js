(function () {
    'use strict';
    var root = document.querySelector('.jsadmin-settings');
    var form = document.getElementById('jsadmin-form');
    if (!root || !form) return;
    var saving = false;
    var queued = false;
    var confirmed = {};

    function state(input) {
        var text = input.closest('label').querySelector('.jsadmin-state');
        text.textContent = input.checked ? text.dataset.on : text.dataset.off;
    }
    function busy(active) {
        saving = active;
        root.classList.toggle('is-saving', active);
        form.setAttribute('aria-busy', active ? 'true' : 'false');
        form.querySelectorAll('input[type=checkbox]').forEach(function (box) { box.disabled = active; });
    }
    function toast(message, error) {
        var item = document.createElement('div');
        item.className = 'jsadmin-toast wbce-admin-toast' + (error ? ' is-error' : '');
        item.setAttribute('role', error ? 'alert' : 'status');
        item.textContent = message;
        document.body.appendChild(item);
        window.setTimeout(function () { item.classList.add('is-visible'); }, 10);
        window.setTimeout(function () { item.classList.remove('is-visible'); window.setTimeout(function () { item.remove(); }, 220); }, 4000);
    }
    function replaceFtan(markup) {
        if (!markup) return;
        var holder = document.createElement('div');
        holder.innerHTML = markup;
        var fresh = holder.querySelector('input[type="hidden"]');
        var token = form.querySelector('[data-jsadmin-ftan]');
        if (fresh && token) { token.name = fresh.name; token.value = fresh.value; token.setAttribute('data-jsadmin-ftan', fresh.value); }
    }
    function applyServerState(values) {
        if (!values) return;
        form.querySelectorAll('input[type=checkbox]').forEach(function (box) {
            if (Object.prototype.hasOwnProperty.call(values, box.name)) { box.checked = !!values[box.name]; confirmed[box.name] = box.checked; }
            state(box);
        });
    }
    function save() {
        if (saving) { queued = true; return; }
        var data = new FormData(form);
        var token = form.querySelector('[data-jsadmin-ftan]');
        if (token && token.name && token.getAttribute('data-jsadmin-ftan')) data.set(token.name, token.getAttribute('data-jsadmin-ftan'));
        form.querySelectorAll('input[type=checkbox]').forEach(function (box) { data.set(box.name, box.checked ? '1' : '0'); });
        busy(true);
        fetch(form.action, {method:'POST',body:data,credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}})
            .then(function (response) { return response.text().then(function (raw) {
                var result;
                try { result = JSON.parse(raw); } catch (ignored) { throw new Error(root.dataset.invalidResponse + ' (HTTP ' + response.status + ')'); }
                if (!response.ok || !result.success) throw Object.assign(new Error(result.message || root.dataset.saveFailed), {responseData:result});
                return result;
            }); })
            .then(function (result) { replaceFtan(result.ftan); applyServerState(result.state); toast(result.message, false); })
            .catch(function (error) { replaceFtan(error.responseData && error.responseData.ftan); applyServerState((error.responseData && error.responseData.state) || confirmed); toast(error.message || root.dataset.saveFailed, true); })
            .then(function () { busy(false); if (queued) { queued = false; save(); } });
    }
    form.querySelectorAll('input[type=checkbox]').forEach(function (input) { confirmed[input.name] = input.checked; state(input); input.addEventListener('change', save); });
}());
