(function () {
    'use strict';

    var root = document.querySelector('.addon-monitor');
    var texts = {};
    try { texts = JSON.parse(root ? (root.getAttribute('data-texts') || '{}') : '{}'); } catch (ignored) {}
    var table = document.getElementById('htmlgrid');

    function notice(message, error) {
        var item = document.querySelector('.addon-monitor-message[data-live]');
        if (!item) {
            item = document.createElement('p');
            item.dataset.live = '1';
            item.className = 'addon-monitor-message wbce-admin-toast';
            document.body.appendChild(item);
        }
        item.textContent = message;
        item.classList.toggle('error', !!error);
        item.classList.toggle('success', !error);
        item.hidden = false;
        window.clearTimeout(item._timer);
        item._timer = window.setTimeout(function () { item.hidden = true; }, 4500);
    }

    function applyFilters() {
        if (!table) return;
        var title = (document.getElementById('filter_titles') || {}).value || '';
        var author = (document.getElementById('filter_authors') || {}).value || '';
        title = title.toLocaleLowerCase();
        author = author.toLocaleLowerCase();
        table.querySelectorAll('tbody tr').forEach(function (row) {
            var type = row.getAttribute('rel') || '';
            var typeControl = document.getElementById('include_' + type + 's');
            var typeVisible = !typeControl || typeControl.checked;
            var nameCell = row.querySelector('.addon_name');
            var authorCell = row.querySelector('.addon_author');
            var nameVisible = !title || (nameCell && nameCell.textContent.toLocaleLowerCase().indexOf(title) !== -1);
            var authorVisible = !author || (authorCell && authorCell.textContent.toLocaleLowerCase().indexOf(author) !== -1);
            row.hidden = !(typeVisible && nameVisible && authorVisible);
        });
    }

    function installListExpansion() {
        document.querySelectorAll('ul.using_sections').forEach(function (list) {
            var entries = Array.prototype.slice.call(list.children);
            if (entries.length <= 4) return;
            entries.slice(4).forEach(function (entry) { entry.hidden = true; });
            var holder = document.createElement('li');
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'addon-monitor-expand';
            button.textContent = texts.expand || '[+]';
            button.setAttribute('aria-expanded', 'false');
            button.addEventListener('click', function () {
                var expanded = button.getAttribute('aria-expanded') === 'true';
                entries.slice(4).forEach(function (entry) { entry.hidden = expanded; });
                button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                button.textContent = expanded ? (texts.expand || '[+]') : (texts.collapse || '[-]');
            });
            holder.appendChild(button);
            list.appendChild(holder);
        });
    }

    function installSorting() {
        if (!table || !table.tHead || !table.tBodies.length) return;
        table.querySelectorAll('thead th.sort').forEach(function (heading) {
            heading.tabIndex = 0;
            heading.setAttribute('role', 'button');
            heading.setAttribute('aria-sort', 'none');
            function sort() {
                var index = Array.prototype.indexOf.call(heading.parentNode.children, heading);
                var ascending = heading.getAttribute('aria-sort') !== 'ascending';
                var rows = Array.prototype.slice.call(table.tBodies[0].rows);
                rows.sort(function (left, right) {
                    var a = (left.cells[index] ? left.cells[index].textContent : '').trim();
                    var b = (right.cells[index] ? right.cells[index].textContent : '').trim();
                    return a.localeCompare(b, undefined, {numeric:true, sensitivity:'base'}) * (ascending ? 1 : -1);
                });
                table.querySelectorAll('thead th[aria-sort]').forEach(function (item) { item.setAttribute('aria-sort', 'none'); });
                heading.setAttribute('aria-sort', ascending ? 'ascending' : 'descending');
                rows.forEach(function (row) { table.tBodies[0].appendChild(row); });
            }
            heading.addEventListener('click', sort);
            heading.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); sort(); }
            });
        });
    }

    document.querySelectorAll('#filter_titles,#filter_authors').forEach(function (input) {
        input.addEventListener('input', applyFilters);
    });
    document.querySelectorAll('.addon-type-row input[type="checkbox"]').forEach(function (input) {
        input.addEventListener('change', applyFilters);
    });
    document.querySelectorAll('.clearbtn').forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            var input = button.parentNode.querySelector('.filterinput');
            if (input) { input.value = ''; input.focus(); }
            applyFilters();
        });
    });

    document.addEventListener('change', function (event) {
        var checkbox = event.target.closest('.addon-monitor-state-form input[type="checkbox"]');
        if (!checkbox || checkbox.disabled) return;
        var form = checkbox.form;
        var hidden = form && form.querySelector('input[name="enabled"]');
        if (hidden) hidden.value = checkbox.checked ? '1' : '0';
        if (form) form.dispatchEvent(new Event('submit', {cancelable:true, bubbles:true}));
    });

    document.addEventListener('submit', function (event) {
        var form = event.target.closest('.addon-monitor-state-form');
        if (!form) return;
        event.preventDefault();
        var checkbox = form.querySelector('input[type="checkbox"]');
        var hidden = form.querySelector('input[name="enabled"]');
        var label = form.querySelector('small');
        var requestData = new FormData(form);
        if (checkbox) checkbox.disabled = true;
        form.setAttribute('aria-busy', 'true');
        fetch(form.action, {
            method:'POST', body:requestData, credentials:'same-origin',
            headers:{'X-Requested-With':'XMLHttpRequest', 'Accept':'application/json'}
        }).then(function (response) {
            return response.text().then(function (raw) {
                var data;
                try { data = JSON.parse(raw); }
                catch (ignored) { throw new Error((texts.save_failed || 'Request failed') + ' (HTTP ' + response.status + ')'); }
                if (!response.ok || !data.success) throw new Error(data.message || texts.save_failed);
                return data;
            });
        }).then(function (data) {
            form.removeAttribute('aria-busy');
            if (checkbox) { checkbox.checked = !!data.enabled; checkbox.disabled = false; }
            if (hidden) hidden.value = data.enabled ? '1' : '0';
            if (label) label.textContent = data.enabled ? texts.enabled : texts.disabled;
            if (data.ftan) {
                var holder = document.createElement('div');
                holder.innerHTML = data.ftan;
                var fresh = holder.querySelector('input');
                if (fresh) document.querySelectorAll('.addon-monitor-ftan').forEach(function (slot) { slot.innerHTML = ''; slot.appendChild(fresh.cloneNode(true)); });
            }
            notice(data.message || texts.saved, false);
        }).catch(function (error) {
            form.removeAttribute('aria-busy');
            if (checkbox) { checkbox.checked = !checkbox.checked; checkbox.disabled = false; }
            notice(error.message || texts.save_failed, true);
        });
    });

    installListExpansion();
    installSorting();
    applyFilters();
}());
