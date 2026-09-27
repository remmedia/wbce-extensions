(function () {
    'use strict';
    var root = document.querySelector('[data-store-dashboard-update]');
    if (!root || root.dataset.polling === '1') return;
    root.dataset.polling = '1';
    var state = root.querySelector('.store-dashboard-state');
    var content = root.querySelector('.store-dashboard-content');
    var timer = null;
    var bulkRunning = false;

    function schedule(delay) {
        window.clearTimeout(timer);
        timer = window.setTimeout(refresh, delay);
    }

    function notice(message, error) {
        if (typeof window.mediaCmsToast === 'function') {
            window.mediaCmsToast(message, error);
            return;
        }
        var box = document.createElement('div');
        box.textContent = message;
        box.setAttribute('role', error ? 'alert' : 'status');
        box.style.cssText = 'position:fixed;right:22px;bottom:22px;z-index:2147483647;padding:.85rem 1rem;background:#fff;border-left:4px solid '+(error?'#b32d2e':'#2e8540')+';box-shadow:0 5px 22px rgba(0,0,0,.22)';
        document.body.appendChild(box);
        window.setTimeout(function () { box.remove(); }, 5000);
    }

    function hidden(form, name, value) {
        var input = document.createElement('input');
        input.type = 'hidden'; input.name = name; input.value = value;
        form.appendChild(input);
    }

    function lockControls(locked) {
        root.querySelectorAll('button,input:not([type="hidden"]),select,textarea,a').forEach(function (control) {
            if ('disabled' in control) control.disabled = locked;
            else if (locked) control.setAttribute('aria-disabled', 'true');
            else control.removeAttribute('aria-disabled');
        });
    }

    function rowFeedback(form, withProgress) {
        var row=form.closest('.store-update-row'), button=form.querySelector('button'), feedback=form.querySelector('.store-update-install-state');
        form.classList.add('store-dashboard-installing');
        if (button) { button.hidden=true; button.style.setProperty('display','none','important'); }
        if (!row) return;
        if (!feedback) {
            feedback=document.createElement('span'); feedback.className='store-update-install-state';
            var label=document.createElement('span'); label.textContent='Modul wird installiert …'; feedback.appendChild(label); form.insertBefore(feedback, button);
        }
        var progress=feedback.querySelector('progress');
        if (withProgress && !progress) { progress=document.createElement('progress'); progress.className='store-install-progress'; progress.setAttribute('aria-label','Modul wird installiert'); feedback.appendChild(progress); }
        if (!withProgress && progress) progress.remove();
    }

    function install(item, button) {
        if (button.disabled && !bulkRunning) return Promise.resolve();
        var form = button.form, original = button.textContent;
        lockControls(true); button.disabled = true; rowFeedback(form,true);
        return fetch(form.action, {
            method: 'POST', body: new FormData(form), credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).then(function (response) {
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return response.text();
        }).then(function (html) {
            var page = new DOMParser().parseFromString(html, 'text/html');
            var error = page.querySelector('#store-app .store-toast.store-error');
            if (error) throw new Error(error.textContent.trim());
            notice(item.name + ' ' + item.version + ': ' + (root.dataset.doneLabel || 'wurde aktualisiert.'), false);
            if (bulkRunning) {
                var completedRow=form.closest('.store-update-row');
                if (completedRow) completedRow.remove();
                return Promise.resolve();
            }
            return refresh();
        }).catch(function (error) {
            var feedback=form.querySelector('.store-update-install-state');if(feedback)feedback.remove();
            form.classList.remove('store-dashboard-installing');
            if(!bulkRunning)lockControls(false);button.hidden=false;button.style.removeProperty('display');button.disabled = false; button.textContent = original;
            notice(error.message, true);
        });
    }

    function createForm(item) {
        var form = document.createElement('form');
        form.method = 'post'; form.action = root.dataset.action;
        form.className = 'store-dashboard-single-update-form';
        hidden(form, 'store_action', 'install');
        hidden(form, 'source_id', item.source_id);
        hidden(form, 'package_ref', item.package_ref);
        hidden(form, 'install_dependencies', '1');
        if (item.ftan_name) hidden(form, item.ftan_name, item.ftan_value);
        var button = document.createElement('button');
        button.type = 'submit'; button.className = 'store-button';
        button.textContent = root.dataset.singleLabel || 'Dieses Paket aktualisieren';
        button.disabled = bulkRunning;
        form.appendChild(button);
        form.addEventListener('submit', function (event) { event.preventDefault(); install(item, button); });
        return form;
    }

    function render(data) {
        if (!data || !data.ok || !Array.isArray(data.updates)) throw new Error(data && data.message ? data.message : 'Ungültige Serverantwort');
        content.innerHTML = '';
        state.textContent = data.summary || '';
        if (!data.updates.length) {
            root.hidden = true;
            schedule(60000);
            return;
        }
        root.hidden = false;
        var list = document.createElement('div'); list.className = 'store-update-list';
        data.updates.forEach(function (item) {
            var row=document.createElement('div'), text=document.createElement('div'), title=document.createElement('strong'), details=document.createElement('small');
            row.className='store-update-row'; title.textContent=item.name; details.textContent=item.details;
            var updateForm=createForm(item);text.appendChild(title); text.appendChild(details); row.appendChild(text); row.appendChild(updateForm); list.appendChild(row);if(bulkRunning)rowFeedback(updateForm,false);
        });
        var actions=document.createElement('div'), bulkInfo=document.createElement('span'), all=document.createElement('button');
        actions.className='store-update-actions'; all.type='button'; all.className='store-button';
        all.classList.add('store-dashboard-bulk-button');
        bulkInfo.className='store-dashboard-bulk-info'; bulkInfo.textContent=data.summary || '';
        all.textContent=(root.dataset.allLabel || 'Alle {count} kompatiblen Updates installieren').replace('{count}',data.updates.length);
        all.addEventListener('click', function () {
            bulkRunning=true;root.classList.add('store-dashboard-bulk-running');lockControls(true);all.disabled=true;
            root.querySelectorAll('button').forEach(function(button){if(button!==all){button.hidden=true;button.style.setProperty('display','none','important');}});
            root.querySelectorAll('.store-dashboard-single-update-form').forEach(function(form){rowFeedback(form,false);});
            var items=data.updates.slice().sort(function (a, b) {
                var aStore=String(a.package_ref||'').indexOf('module|store|')===0;
                var bStore=String(b.package_ref||'').indexOf('module|store|')===0;
                return aStore===bStore ? 0 : (aStore ? 1 : -1);
            }), index=0;
            function updateBulkProgress() {
                var progress=index+' von '+items.length+' installiert';
                bulkInfo.textContent=progress;
                all.textContent=progress;
            }
            updateBulkProgress();
            function next() {
                if (index>=items.length) { bulkRunning=false; refresh().then(function(){root.classList.remove('store-dashboard-bulk-running');lockControls(false);}); return; }
                var item=items[index++];
                var form=Array.prototype.find.call(root.querySelectorAll('.store-dashboard-single-update-form'),function(candidate){return candidate.querySelector('[name="package_ref"]').value===item.package_ref;});
                if (!form) { next(); return; }
                install(item,form.querySelector('button')).then(function(){updateBulkProgress();next();});
            }
            next();
        });
        actions.appendChild(bulkInfo); actions.appendChild(all); content.appendChild(actions); content.appendChild(list);
        schedule(60000);
    }

    function refresh() {
        window.clearTimeout(timer);
        return fetch(root.dataset.endpoint, {credentials:'same-origin',cache:'no-store',headers:{'X-Requested-With':'XMLHttpRequest'}})
            .then(function (response) { if(!response.ok) throw new Error('HTTP '+response.status); return response.json(); })
            .then(render)
            .catch(function (error) {
                root.hidden=true;
                state.textContent=(root.dataset.errorLabel || 'Die Store-Updates konnten nicht geladen werden.')+' '+error.message;
                schedule(30000);
            });
    }

    refresh();
}());
