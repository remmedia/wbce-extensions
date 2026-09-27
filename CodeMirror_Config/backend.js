(function () {
    'use strict';
    var form = document.getElementById('cmc-settings');
    if (!form) return;
    var app = form.closest('.cmc-app');
    var editor = window.code;

    function toast(message, failed) {
        var item = document.createElement('div');
        item.className = 'cmc-toast' + (failed ? ' is-error' : '');
        item.setAttribute('role', failed ? 'alert' : 'status');
        item.textContent = message;
        document.body.appendChild(item);
        window.setTimeout(function () { item.classList.add('is-visible'); }, 10);
        window.setTimeout(function () { item.classList.remove('is-visible'); window.setTimeout(function () { item.remove(); }, 220); }, 4000);
    }

    function preview() {
        if (!editor) editor = window.code;
        if (!editor) return;
        editor.setOption('theme', form.elements.theme.value);
        var wrapper = editor.getWrapperElement();
        wrapper.style.fontFamily = form.elements.font.value === 'default' ? 'monospace' : form.elements.font.value;
        wrapper.style.fontSize = form.elements.font_size.value + 'px';
        editor.refresh();
    }

    function replaceFtan(markup) {
        if (!markup) return;
        var holder = document.createElement('div');
        holder.innerHTML = markup;
        var fresh = holder.querySelector('input[type="hidden"]');
        var token = form.querySelector('[data-cmc-ftan]');
        if (fresh && token) { token.name = fresh.name; token.value = fresh.value; token.setAttribute('data-cmc-ftan', fresh.value); }
    }

    function save() {
            var data = new FormData(form);
            var token = form.querySelector('[data-cmc-ftan]');
            if (token && token.name && token.getAttribute('data-cmc-ftan')) data.set(token.name, token.getAttribute('data-cmc-ftan'));
            app.classList.add('is-saving');
            form.setAttribute('aria-busy', 'true');
            form.querySelectorAll('select').forEach(function (field) { field.disabled = true; });
            fetch(form.action, {method:'POST',body:data,credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}})
                .then(function (response) { return response.text().then(function (raw) {
                    var data;
                    try { data = JSON.parse(raw); } catch (ignored) { throw new Error((app.dataset.saveFailed || 'Save failed') + ' (HTTP ' + response.status + ')'); }
                    if (!response.ok || !data.success) throw Object.assign(new Error(data.message || app.dataset.saveFailed), {responseData:data});
                    return data;
                }); })
                .then(function (data) {
                    replaceFtan(data.ftan);
                    if (data.config) {
                        form.elements.theme.value = data.config.theme;
                        form.elements.font.value = data.config.font;
                        form.elements.font_size.value = data.config.font_size;
                    }
                    preview();
                    toast(data.message, false);
                })
                .catch(function (error) {
                    replaceFtan(error.responseData && error.responseData.ftan);
                    toast(error.message || app.dataset.saveFailed, true);
                })
                .then(function () {
                    form.querySelectorAll('select').forEach(function (field) { field.disabled = false; });
                    form.removeAttribute('aria-busy');
                    app.classList.remove('is-saving');
                });
    }

    form.addEventListener('change', preview);
    form.addEventListener('submit', function (event) { event.preventDefault(); save(); });
    preview();
}());
