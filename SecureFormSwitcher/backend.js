(function () {
    'use strict';
    if (window.__wbceSecureFormSwitcherLoaded) return;
    window.__wbceSecureFormSwitcherLoaded = true;
    var root = document.querySelector('.sfs');
    var form = document.getElementById('sfs_form');
    var dialog = document.getElementById('sfs-default-dialog');
    if (!root || !form || !dialog) return;
    var timer;
    var saving = false;
    var queuedAction = '';

    function toast(message, error) {
        var box = document.createElement('div');
        box.className = 'sfs-toast wbce-admin-toast' + (error ? ' is-error' : '');
        box.setAttribute('role', error ? 'alert' : 'status');
        box.textContent = message;
        document.body.appendChild(box);
        window.setTimeout(function () { box.classList.add('is-visible'); }, 10);
        window.setTimeout(function () { box.classList.remove('is-visible'); window.setTimeout(function () { box.remove(); }, 220); }, 4200);
    }

    function replaceFtan(markup) {
        if (!markup) return;
        var holder = document.createElement('div');
        holder.innerHTML = markup;
        var fresh = holder.querySelector('input[type="hidden"]');
        var current = form.querySelector('input[type="hidden"]');
        if (!fresh) return;
        if (current) current.replaceWith(fresh); else form.insertBefore(fresh, form.firstChild);
    }

    function applySettings(settings) {
        if (!settings) return;
        Object.keys(settings).forEach(function (key) {
            var field = form.elements[key];
            if (!field) return;
            if (field.type === 'checkbox') field.checked = !!settings[key]; else field.value = settings[key];
        });
    }

    function executeSave(action) {
        if (saving) { queuedAction = action || 'save'; return; }
        if (action !== 'defaults' && !form.reportValidity()) return;
        var data = new FormData(form);
        data.set('action', action || 'save');
        if (!form.elements.useFP.checked) data.set('useFP', '0');
        saving = true;
        root.classList.add('is-saving');
        form.setAttribute('aria-busy', 'true');
        fetch(form.action, {method:'POST',body:data,credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}})
            .then(function (response) { return response.text().then(function (text) {
                var data;
                try { data = JSON.parse(text); } catch (ignored) { throw new Error(root.dataset.invalidResponse + ' (HTTP ' + response.status + ')'); }
                if (!response.ok || !data.success) throw Object.assign(new Error(data.message || root.dataset.saveFailed), {responseData:data});
                return data;
            }); })
            .then(function (data) { replaceFtan(data.ftan); applySettings(data.settings); toast(data.message, false); })
            .catch(function (error) { replaceFtan(error.responseData && error.responseData.ftan); toast(error.message || root.dataset.saveFailed, true); })
            .then(function () {
                saving = false;
                root.classList.remove('is-saving');
                form.removeAttribute('aria-busy');
                if (queuedAction) { var next = queuedAction; queuedAction = ''; executeSave(next); }
            });
    }

    function save(action) { window.clearTimeout(timer); timer = window.setTimeout(function () { executeSave(action || 'save'); }, 120); }
    form.addEventListener('change', function (event) { if (event.target.matches('input,select')) save('save'); });
    form.addEventListener('submit', function (event) { event.preventDefault(); save('save'); });

    var reveal = root.querySelector('.sfs-reveal');
    if (reveal) reveal.addEventListener('click', function () {
        var input = document.getElementById('secret');
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        reveal.setAttribute('aria-pressed', show ? 'true' : 'false');
        reveal.textContent = show ? root.dataset.hideSecret : root.dataset.showSecret;
    });
    var defaults = root.querySelector('.sfs-defaults');
    if (defaults) defaults.addEventListener('click', function () { if (dialog.showModal) dialog.showModal(); else dialog.setAttribute('open', 'open'); });
    var cancel = dialog.querySelector('.sfs-cancel');
    if (cancel) cancel.addEventListener('click', function () { if (dialog.close) dialog.close(); else dialog.removeAttribute('open'); });
    var confirm = dialog.querySelector('.sfs-confirm');
    if (confirm) confirm.addEventListener('click', function () { if (dialog.close) dialog.close(); else dialog.removeAttribute('open'); executeSave('defaults'); });
}());
