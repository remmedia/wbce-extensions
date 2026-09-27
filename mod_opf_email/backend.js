(function () {
    'use strict';
    var root = document.querySelector('.opf-email-settings');
    var form = document.getElementById('opf-email-form');
    var timer = 0;
    var request = null;
    if (!root || !form) return;
    function render() {
        form.querySelectorAll('input[type="checkbox"]').forEach(function (input) {
            input.setAttribute('aria-checked', input.checked ? 'true' : 'false');
            var wrapper = input.closest('.wbce-admin-switch');
            var state = wrapper ? wrapper.querySelector('.opf-email-state') : null;
            if (state) state.textContent = input.checked ? state.dataset.on : state.dataset.off;
        });
        var mailto = form.elements.mailto_filter;
        var javascript = form.elements.js_mailto;
        if (mailto && javascript) {
            javascript.disabled = !mailto.checked || root.classList.contains('is-saving');
            if (!mailto.checked) javascript.checked = false;
        }
    }
    function toast(message, error) {
        var box = document.createElement('div');
        box.className = 'opf-email-toast' + (error ? ' is-error' : '');
        box.setAttribute('role', error ? 'alert' : 'status');
        box.textContent = message;
        document.body.appendChild(box);
        window.setTimeout(function () { box.classList.add('is-visible'); }, 10);
        window.setTimeout(function () { box.classList.remove('is-visible'); window.setTimeout(function () { box.remove(); }, 220); }, 4200);
    }
    function busy(active) {
        root.classList.toggle('is-saving', active);
        form.querySelectorAll('input, select, textarea, button').forEach(function (field) { field.disabled = active; });
        render();
    }
    function replaceFtan(markup) {
        if (!markup) return;
        var holder = document.createElement('div');
        holder.innerHTML = markup;
        var fresh = holder.querySelector('input[type="hidden"]');
        var current = form.querySelector('input[type="hidden"]');
        if (fresh && current) { current.name = fresh.name; current.value = fresh.value; }
    }
    function applySettings(settings) {
        if (!settings) return;
        Object.keys(settings).forEach(function (name) {
            var field = form.elements[name];
            if (!field) return;
            if (field.type === 'checkbox') field.checked = !!settings[name]; else field.value = settings[name];
        });
    }
    function save() {
        window.clearTimeout(timer);
        timer = window.setTimeout(function () {
            if (request) request.abort();
            var controller = new AbortController();
            request = controller;
            var data = new FormData(form);
            form.querySelectorAll('input[type="checkbox"]').forEach(function (input) { data.set(input.name, input.checked ? '1' : '0'); });
            busy(true);
            fetch(form.action, {method:'POST',body:data,credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'},signal:controller.signal})
                .then(function (response) { return response.json().then(function (payload) { return {ok:response.ok,payload:payload}; }); })
                .then(function (result) { replaceFtan(result.payload.ftan); applySettings(result.payload.settings); toast(result.payload.message || root.dataset.saveFailed, !result.ok || !result.payload.success); })
                .catch(function (error) { if (error.name !== 'AbortError') toast(root.dataset.saveFailed, true); })
                .finally(function () { if (request === controller) { request = null; busy(false); } });
        }, 180);
    }
    form.addEventListener('change', function () { render(); save(); });
    render();
}());
