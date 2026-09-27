(function () {
    'use strict';
    var root = document.querySelector('.maintenance-settings');
    var form = document.getElementById('maintenance-form');
    var input = document.getElementById('maintMode');
    if (!root || !form || !input) return;
    var state = root.querySelector('.maintenance-state');
    var text = root.querySelector('.maintenance-switch-text');
    var confirmed = input.checked;


    function updateMaintenanceIndicator(enabled) {
        document.querySelectorAll('.wbcemm-link, .wbcemm').forEach(function (node) {
            var link = node.closest ? node.closest('.wbcemm-link') : null;
            (link || node).remove();
        });
        if (!enabled) return;
        var host = document.querySelector('#topNavigation .navbar-text, #topNavigation [data-maintenance-indicator]');
        if (!host) return;
        var link = document.createElement('a');
        link.className = 'wbcemm-link';
        link.href = (window.WBCE_ADMIN_URL || '/admin') + '/admintools/tool.php?tool=maintainance_mode';
        link.title = 'Wartungsmodus-Einstellungen';
        link.setAttribute('aria-label', link.title);
        link.innerHTML = '<span class="fa fa-wrench wbcemm" aria-hidden="true"></span>';
        host.appendChild(document.createTextNode(' '));
        host.appendChild(link);
    }

    function render() {
        var label = input.checked ? root.dataset.enabled : root.dataset.disabled;
        input.setAttribute('aria-checked', input.checked ? 'true' : 'false');
        state.textContent = label;
        text.textContent = label;
    }
    function replaceFtan(markup) {
        if (!markup) return;
        var holder = document.createElement('div');
        holder.innerHTML = markup;
        var fresh = holder.querySelector('input[type="hidden"]');
        var token = form.querySelector('input[type="hidden"]');
        if (fresh && token) { token.name = fresh.name; token.value = fresh.value; }
    }
    function toast(message, error) {
        var item = document.createElement('div');
        item.className = 'maintenance-toast wbce-admin-toast' + (error ? ' is-error' : '');
        item.setAttribute('role', error ? 'alert' : 'status');
        item.textContent = message;
        document.body.appendChild(item);
        window.setTimeout(function () { item.classList.add('is-visible'); }, 10);
        window.setTimeout(function () { item.classList.remove('is-visible'); window.setTimeout(function () { item.remove(); }, 220); }, 4000);
    }

    render();
    input.addEventListener('change', function () {
        render();
        var data = new FormData(form);
        data.set('maintMode', input.checked ? '1' : '0');
        input.disabled = true;
        root.classList.add('is-saving');
        form.setAttribute('aria-busy', 'true');
        fetch(form.action, {method:'POST',body:data,credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}})
            .then(function (response) { return response.text().then(function (raw) {
                var result;
                try { result = JSON.parse(raw); } catch (ignored) { throw new Error(root.dataset.invalidResponse + ' (HTTP ' + response.status + ')'); }
                if (!response.ok || !result.success) throw Object.assign(new Error(result.message || root.dataset.saveFailed), {responseData:result});
                return result;
            }); })
            .then(function (result) { replaceFtan(result.ftan); confirmed = !!result.enabled; input.checked = confirmed; render(); updateMaintenanceIndicator(confirmed); toast(result.message, false); })
            .catch(function (error) { replaceFtan(error.responseData && error.responseData.ftan); input.checked = confirmed; render(); toast(error.message || root.dataset.saveFailed, true); })
            .then(function () { input.disabled = false; root.classList.remove('is-saving'); form.removeAttribute('aria-busy'); });
    });
}());
