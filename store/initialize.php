<?php

if (!defined('WB_PATH')) return;
require_once __DIR__ . '/AutoUpdate.php';
require_once __DIR__ . '/UpdateChecker.php';

$storeAutoUpdateDefinition = array(
    'label' => 'Store automatisch aktualisieren',
    'description' => 'Installiert verfügbare kompatible Updates für über den Store verwaltete Pakete.',
    'default_cron' => '*/5 * * * *',
    'default_name' => 'Store-Autoupdate',
    'default_configuration' => array(),
    'managed_task' => true,
    'callable' => array('WbceStoreAutoUpdate', 'run'),
);
if (function_exists('wbce_add_filter')) {
    wbce_add_filter('worker.definitions', static function (array $definitions) use ($storeAutoUpdateDefinition) {
        $definitions['store.autoupdate'] = $storeAutoUpdateDefinition;
        return $definitions;
    });
} elseif (class_exists('WbceWorkerRegistry')) {
    WbceWorkerRegistry::register('store.autoupdate', $storeAutoUpdateDefinition);
}

if (function_exists('wbce_add_filter')) {
    wbce_add_filter('admin.dashboard.widgets', static function (array $widgets) {
        global $database, $admin;
        try {
            $cachedDashboardReport = WbceStoreUpdateChecker::report($database, true);
        } catch (Throwable $ignored) {
            $cachedDashboardReport = array('compatible'=>array(),'incompatible'=>array());
        }
        $hasCachedDashboardUpdates = false;
        if (!$hasCachedDashboardUpdates) {
        // Never contact remote stores while WBCE renders the dashboard. The
        // browser loads the update preview separately; dashboard-data.php
        // releases the PHP session before doing any network I/O.
        $endpoint = WB_URL.'/modules/store/dashboard-data.php';
        $action = ADMIN_URL.'/admintools/tool.php?tool=store';
        $loading = wbce_store_text('dashboard_loading');
        $errorText = wbce_store_text('dashboard_load_failed');
        $titleText = wbce_store_text('dashboard_title');
        $singleText = wbce_store_text('dashboard_update_single');
        $allText = wbce_store_text('dashboard_update_all');
        $busyText = wbce_store_text('dashboard_updating');
        $doneText = wbce_store_text('dashboard_updated');
        $asyncHtml = '<section class="store-panel store-updates store-dashboard-update" data-store-dashboard-update hidden data-endpoint="'.htmlspecialchars($endpoint,ENT_QUOTES,'UTF-8').'" data-action="'.htmlspecialchars($action,ENT_QUOTES,'UTF-8').'" data-single-label="'.htmlspecialchars($singleText,ENT_QUOTES,'UTF-8').'" data-all-label="'.htmlspecialchars($allText,ENT_QUOTES,'UTF-8').'" data-busy-label="'.htmlspecialchars($busyText,ENT_QUOTES,'UTF-8').'" data-done-label="'.htmlspecialchars($doneText,ENT_QUOTES,'UTF-8').'" data-error-label="'.htmlspecialchars($errorText,ENT_QUOTES,'UTF-8').'">'
            .'<h3>'.htmlspecialchars($titleText,ENT_QUOTES,'UTF-8').'</h3><p class="store-dashboard-state" role="status">'.htmlspecialchars($loading,ENT_QUOTES,'UTF-8').'</p><div class="store-dashboard-content"></div></section>';
        $asyncHtml .= '<link rel="stylesheet" href="'.htmlspecialchars(WB_URL.'/modules/store/assets/install-feedback.css?v=4.1.135',ENT_QUOTES,'UTF-8').'">';
        $asyncHtml .= '<script>(function(){var widget=document.querySelector("[data-store-dashboard-update]"),shell=document.querySelector(".wbce-dashboard-shell"),grid=shell&&shell.querySelector(".wbce-dashboard-grid");if(widget){widget.dataset.storeDashboardModern="1";if(shell&&grid)shell.insertBefore(widget,grid);}})();</script>';
        $config = json_encode(array('single'=>$singleText,'all'=>$allText,'busy'=>$busyText,'failed'=>$errorText,'done'=>$doneText), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG);
        $asyncHtml .= '<style>.store-dashboard-update.store-panel{box-sizing:border-box!important;border:1px solid var(--wbce-primary-soft,#9abfd9)!important;border-left:5px solid var(--wbce-primary,#2271b1)!important;border-radius:var(--wbce-radius,8px)!important;box-shadow:0 1px 3px rgba(0,0,0,.08)!important}</style>';
        $asyncHtml .= '<style>.store-dashboard-update{background:var(--wbce-primary-background,#f4f9fd);border:1px solid var(--wbce-primary-soft,#9abfd9);border-left:5px solid var(--wbce-primary,#2271b1);border-radius:var(--wbce-radius,8px);padding:1rem;margin:1rem 0}.store-dashboard-update h3{color:var(--wbce-primary-dark,#135e96)}.store-update-list{display:grid;gap:.6rem;margin:.8rem 0}.store-update-row{display:flex;align-items:center;gap:1rem;padding:.7rem .8rem;border:1px solid var(--wbce-border,#dcdcde);border-radius:var(--wbce-radius,7px);background:var(--wbce-card-background,#fff)}.store-update-row>div{display:grid;gap:.3rem;flex:1}.store-update-row form{margin-left:auto}.store-update-actions{display:flex;justify-content:flex-end;margin-top:.8rem;padding-top:.8rem;border-top:1px solid var(--wbce-border,#dcdcde)}.store-button{background:var(--wbce-primary,#2271b1);color:var(--wbce-on-primary,#fff);border:0;border-radius:var(--wbce-radius-small,4px);padding:.45rem .75rem;cursor:pointer}.store-button:disabled{cursor:wait;opacity:.7}@media(max-width:620px){.store-update-row{align-items:stretch;flex-direction:column}.store-update-row form,.store-update-row button{width:100%}}</style>';
        $asyncHtml .= '<style>.store-dashboard-update .store-update-actions{display:flex!important;align-items:center!important;justify-content:flex-end!important;width:100%!important;box-sizing:border-box!important;margin:.25rem 0 1rem!important;padding:0 .75rem 1rem!important;border-top:0!important;border-bottom:1px solid var(--wbce-border,#dcdcde)!important}.store-dashboard-update .store-update-actions>.store-button,.store-dashboard-update .store-update-actions>form>.store-button{float:none!important;display:block!important;width:auto!important;margin:0 0 0 auto!important}.store-dashboard-update .store-update-actions>form{display:flex!important;justify-content:flex-end!important;width:100%!important;margin:0!important}.store-dashboard-update.store-dashboard-bulk-running .store-dashboard-single-update-form>button,.store-dashboard-single-update-form.store-dashboard-installing>button{display:none!important}</style>';
        $asyncHtml .= '<script>(function(){var root=document.querySelector("[data-store-dashboard-update]");if(!root||root.dataset.storeDashboardModern==="1"||root.dataset.bound)return;root.dataset.bound="1";var cfg='.$config.',state=root.querySelector(".store-dashboard-state"),content=root.querySelector(".store-dashboard-content");function toast(text,bad){if(window.mediaCmsToast){window.mediaCmsToast(text,bad);return;}var n=document.createElement("div");n.textContent=text;n.style.cssText="position:fixed;right:22px;bottom:22px;z-index:2147483647;padding:.85rem 1rem;background:#fff;border-left:4px solid "+(bad?"#b32d2e":"#2e8540")+";box-shadow:0 5px 22px rgba(0,0,0,.22)";document.body.appendChild(n);setTimeout(function(){n.remove();},5000);}function load(){fetch(root.dataset.endpoint,{credentials:"same-origin",cache:"no-store",headers:{"X-Requested-With":"XMLHttpRequest"}}).then(function(r){if(!r.ok)throw new Error("HTTP "+r.status);return r.json();}).then(render).catch(function(e){state.textContent=cfg.failed+" "+e.message;content.innerHTML="";});}function makeForm(item){var f=document.createElement("form");f.method="post";f.action=root.dataset.action;f.className="store-dashboard-single-update-form";[["store_action","install"],["source_id",item.source_id],["package_ref",item.package_ref],["install_dependencies","1"]].forEach(function(p){var i=document.createElement("input");i.type="hidden";i.name=p[0];i.value=p[1];f.appendChild(i);});var token=document.createElement("input");token.type="hidden";token.name=item.ftan_name;token.value=item.ftan_value;f.appendChild(token);var b=document.createElement("button");b.type="submit";b.className="store-button";b.textContent=cfg.single;f.appendChild(b);f.addEventListener("submit",function(e){e.preventDefault();run(f,b,item.name,item.version);});return f;}function run(f,b,name,version){if(b.disabled)return Promise.resolve();b.disabled=true;var old=b.textContent;b.textContent=cfg.busy;return fetch(f.action,{method:"POST",body:new FormData(f),credentials:"same-origin",headers:{"X-Requested-With":"XMLHttpRequest"}}).then(function(r){if(!r.ok)throw new Error("HTTP "+r.status);return r.text();}).then(function(html){var d=new DOMParser().parseFromString(html,"text/html"),err=d.querySelector("#store-app .store-toast.store-error");if(err)throw new Error(err.textContent.trim());toast(name+" "+version+": "+cfg.done,false);return load();}).catch(function(e){b.disabled=false;b.textContent=old;toast(e.message,true);});}function render(data){if(!data.ok)throw new Error(data.message||cfg.failed);content.innerHTML="";if(!data.updates.length){root.remove();return;}state.textContent=data.summary;var list=document.createElement("div");list.className="store-update-list";data.updates.forEach(function(item){var row=document.createElement("div");row.className="store-update-row";var text=document.createElement("div"),strong=document.createElement("strong"),small=document.createElement("small");strong.textContent=item.name;small.textContent=item.details;text.appendChild(strong);text.appendChild(small);row.appendChild(text);row.appendChild(makeForm(item));list.appendChild(row);});content.appendChild(list);var actions=document.createElement("div");actions.className="store-update-actions";var all=document.createElement("button");all.type="button";all.className="store-button";all.textContent=cfg.all.replace("{count}",data.updates.length);all.addEventListener("click",function(){var forms=Array.prototype.slice.call(root.querySelectorAll(".store-dashboard-single-update-form")),i=0;all.disabled=true;function next(){if(i>=forms.length){load();return;}var form=forms[i++],button=form.querySelector("button");run(form,button,form.previousElementSibling?form.previousElementSibling.querySelector("strong").textContent:"","").then(next);}next();});actions.appendChild(all);content.appendChild(actions);}load();})();</script>';
        $asyncHtml .= '<script src="'.htmlspecialchars(WB_URL.'/modules/store/assets/dashboard.js?v=4.1.135',ENT_QUOTES,'UTF-8').'" defer></script>';
        // The WBCE 1.7 dashboard hook consumes HTML entries. Returning a
        // structured array here makes the core dashboard silently ignore it.
        $widgets[] = $asyncHtml;
        return $widgets;
        }

        try {
            // Use the report already read from the local catalogue cache. The
            // dashboard request itself must never start Store network I/O.
            $report = $cachedDashboardReport;
            $count = count($report['compatible']);
            $incompatibleCount = count($report['incompatible']);
            if ($count < 1 && $incompatibleCount < 1) return $widgets;
            $url = ADMIN_URL . '/admintools/tool.php?tool=store';
            $label = $count === 0
                ? wbce_store_text('dashboard_no_compatible_updates')
                : wbce_store_text($count === 1 ? 'dashboard_update_count_one' : 'dashboard_update_count_many', array('count' => $count));
            $blocked = wbce_store_text($incompatibleCount === 1 ? 'dashboard_incompatible_one' : 'dashboard_incompatible_many', array('count' => $incompatibleCount));
            $items = '';
            $ftan = is_object($admin) && method_exists($admin,'getFTAN') ? $admin->getFTAN() : '';
            foreach ($report['compatible'] as $update) {
                $package = $update['package'];
                $name = isset($package['name']) ? $package['name'] : $package['slug'];
                $packageRef = $package['type'].'|'.$package['slug'].'|'.$package['version'];
                $sourceName = isset($update['source']['name']) ? $update['source']['name'] : 'Store';
                $items .= '<div class="store-update-row"><div><strong>'.htmlspecialchars($name,ENT_QUOTES,'UTF-8').'</strong><small>'.htmlspecialchars(wbce_store_text('dashboard_update_details', array('installed' => $update['installed'], 'version' => $package['version'], 'store' => $sourceName)),ENT_QUOTES,'UTF-8').'</small></div><form class="store-dashboard-single-update-form" method="post" action="'.htmlspecialchars($url,ENT_QUOTES,'UTF-8').'" data-dashboard-url="'.htmlspecialchars(ADMIN_URL . '/start/index.php',ENT_QUOTES,'UTF-8').'" data-package-name="'.htmlspecialchars($name,ENT_QUOTES,'UTF-8').'" data-package-version="'.htmlspecialchars($package['version'],ENT_QUOTES,'UTF-8').'">'.$ftan.'<input type="hidden" name="store_action" value="install"><input type="hidden" name="source_id" value="'.(int)$update['source']['id'].'"><input type="hidden" name="package_ref" value="'.htmlspecialchars($packageRef,ENT_QUOTES,'UTF-8').'"><input type="hidden" name="install_dependencies" value="1"><button type="submit" class="store-button">'.htmlspecialchars(wbce_store_text('dashboard_update_single'),ENT_QUOTES,'UTF-8').'</button></form></div>';
            }
            $dashboardUrl = ADMIN_URL . '/start/index.php';
            $form = ($items!=='' ? '<div class="store-update-list">'.$items.'</div><div class="store-update-actions" style="display:flex;align-items:flex-end;justify-content:flex-end;width:100%;box-sizing:border-box;padding:1rem .8rem .2rem 0"><form id="store-dashboard-bulk-form" class="store-dashboard-update-form store-update-all" style="display:flex;flex-direction:column;align-items:flex-end;width:100%;margin:0 0 0 auto" method="post" action="'.htmlspecialchars($url,ENT_QUOTES,'UTF-8').'" data-dashboard-url="'.htmlspecialchars($dashboardUrl,ENT_QUOTES,'UTF-8').'">'.$ftan.'<input type="hidden" name="store_action" value="update_all"><div class="store-dashboard-feedback" aria-live="polite"></div><button type="submit" class="store-button" style="align-self:flex-end;margin:0 0 0 auto" data-busy-label="Updates werden installiert …">Alle '.(int)$count.' kompatiblen Updates installieren</button></form></div>' : '<p>Für diese WBCE-Version sind keine Updates ausführbar.</p>').'<style>.store-update-row>div>strong{display:block!important;margin-bottom:.3rem!important}.store-update-row>div>small{display:block!important;font-weight:400!important}</style>';
            $script = <<<'JS'
<script>
(function () {
    if (window.wbceStoreDashboardUpdatesBound) return;
    window.wbceStoreDashboardUpdatesBound = true;
    document.addEventListener('submit', function (event) {
        var form = event.target.closest && event.target.closest('.store-dashboard-update-form');
        if (!form) return;
        event.preventDefault();
        if (form.getAttribute('aria-busy') === 'true') return;
        var button = form.querySelector('button[type="submit"]');
        var feedback = form.querySelector('.store-dashboard-feedback');
        var oldLabel = button ? button.textContent : '';
        form.setAttribute('aria-busy', 'true');
        if (button) {
            button.disabled = true;
            button.classList.add('is-busy');
            button.textContent = button.getAttribute('data-busy-label') || 'Bitte warten …';
        }
        if (feedback) {
            feedback.className = 'store-dashboard-feedback';
            feedback.textContent = '';
        }
        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).then(function (response) {
            if (!response.ok) throw new Error('Die Aktualisierung konnte nicht abgeschlossen werden.');
            return response.text();
        }).then(function (resultHtml) {
            var resultDocument = new DOMParser().parseFromString(resultHtml, 'text/html');
            var actionError = resultDocument.querySelector('#store-app .store-toast.store-error');
            return fetch(form.getAttribute('data-dashboard-url'), {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            }).then(function (response) {
                if (!response.ok) throw new Error('Die Update-Liste konnte nicht neu geladen werden.');
                return response.text();
            }).then(function (dashboardHtml) {
                var dashboardDocument = new DOMParser().parseFromString(dashboardHtml, 'text/html');
                if (dashboardDocument.querySelector('input[type="password"]')) {
                    throw new Error('Die Administratorsitzung ist abgelaufen.');
                }
                var current = form.closest('[data-store-dashboard-update]');
                var fresh = dashboardDocument.querySelector('[data-store-dashboard-update]');
                if (current && fresh) current.replaceWith(fresh);
                else if (current) current.remove();
                if (actionError) showStoreDashboardNotice(actionError.textContent.trim(), true);
                else showStoreDashboardNotice('Alle kompatiblen Updates wurden verarbeitet.', false);
            });
        }).catch(function (error) {
            form.removeAttribute('aria-busy');
            if (button) {
                button.disabled = false;
                button.classList.remove('is-busy');
                button.textContent = oldLabel;
            }
            if (feedback) {
                feedback.className = 'store-dashboard-feedback is-error';
                feedback.textContent = error && error.message ? error.message : 'Die Aktualisierung ist fehlgeschlagen.';
            }
        });
    });
    function showStoreDashboardNotice(message, isError) {
        var notice = document.createElement('div');
        notice.className = 'store-dashboard-notice' + (isError ? ' is-error' : '');
        notice.setAttribute('role', 'status');
        notice.style.cssText = 'position:fixed;right:22px;bottom:22px;z-index:2147483647;max-width:min(390px,calc(100vw - 44px));padding:.9rem 1rem;border-left:4px solid '+(isError?'#b32d2e':'#2e8540')+';border-radius:8px;background:'+(isError?'#fce8e8':'#e5f5e8')+';color:'+(isError?'#8a2424':'#176b27')+';box-shadow:0 8px 28px rgba(0,0,0,.24);opacity:0;transform:translateY(.75rem);transition:.2s ease';
        notice.textContent = message;
        document.body.appendChild(notice);
        window.setTimeout(function () { notice.classList.add('is-visible'); notice.style.opacity = '1'; notice.style.transform = 'none'; }, 10);
        window.setTimeout(function () {
            notice.classList.remove('is-visible'); notice.style.opacity = '0'; notice.style.transform = 'translateY(.75rem)';
            window.setTimeout(function () { if (notice.parentNode) notice.parentNode.removeChild(notice); }, 250);
        }, 4500);
    }
})();
</script>
JS;
            $script .= '<style>.store-dashboard-update.store-panel{box-sizing:border-box!important;border:1px solid var(--wbce-primary-soft,#9abfd9)!important;border-left:5px solid var(--wbce-primary,#2271b1)!important;border-radius:var(--wbce-radius,8px)!important;box-shadow:0 1px 3px rgba(0,0,0,.08)!important}</style>';
            $script .= '<style>.store-dashboard-update .store-update-actions{display:flex!important;align-items:flex-end!important;justify-content:flex-end!important;width:100%!important;box-sizing:border-box!important;margin-top:.8rem!important;padding:1rem .8rem .2rem 0!important}.store-dashboard-update form.store-dashboard-update-form.store-update-all{display:flex!important;flex-direction:column!important;align-items:flex-end!important;width:100%!important;margin:0 0 0 auto!important}.store-dashboard-update form.store-dashboard-update-form.store-update-all>button.store-button{align-self:flex-end!important;display:block!important;width:auto!important;margin:0 0 0 auto!important}</style>';
            $script .= '<style>.store-dashboard-update .store-update-row>form.store-dashboard-single-update-form{display:flex!important;justify-content:flex-end!important;flex:0 0 auto!important;width:auto!important;margin:0 0 0 auto!important}.store-dashboard-update .store-dashboard-single-update-form>.store-button{display:block!important;width:auto!important;margin-left:auto!important}@media(max-width:620px){.store-dashboard-update .store-update-row>form.store-dashboard-single-update-form{width:100%!important}}</style>';
            $script .= <<<'JS'
<script>
(function () {
    if (window.wbceStoreDashboardSequentialBound) return;
    window.wbceStoreDashboardSequentialBound = true;
    document.addEventListener('submit', function (event) {
        var singleForm = event.target.closest && event.target.closest('.store-dashboard-single-update-form');
        if (singleForm) {
            event.preventDefault();
            event.stopImmediatePropagation();
            if (singleForm.getAttribute('aria-busy') === 'true') return;
            var singleButton = singleForm.querySelector('button[type="submit"]');
            var singleLabel = singleButton ? singleButton.textContent : '';
            singleForm.setAttribute('aria-busy', 'true');
            if (singleButton) {
                singleButton.disabled = true;
                singleButton.classList.add('is-busy');
                singleButton.textContent = 'Wird aktualisiert …';
            }
            fetch(singleForm.action, {
                method: 'POST', body: new FormData(singleForm), credentials: 'same-origin',
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            }).then(function (response) {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.text();
            }).then(function (html) {
                var result = new DOMParser().parseFromString(html, 'text/html');
                var actionError = result.querySelector('#store-app .store-toast.store-error');
                if (actionError) throw new Error(actionError.textContent.trim());
                return fetch(singleForm.getAttribute('data-dashboard-url'), {
                    credentials: 'same-origin', cache: 'no-store',
                    headers: {'X-Requested-With': 'XMLHttpRequest'}
                });
            }).then(function (response) {
                if (!response.ok) throw new Error('Die Update-Liste konnte nicht neu geladen werden.');
                return response.text();
            }).then(function (html) {
                var page = new DOMParser().parseFromString(html, 'text/html');
                if (page.querySelector('input[type="password"]')) throw new Error('Die Administratorsitzung ist abgelaufen.');
                var current = document.querySelector('[data-store-dashboard-update]');
                var fresh = page.querySelector('[data-store-dashboard-update]');
                if (current && fresh) current.replaceWith(fresh);
                else if (current) current.remove();
                showNotice((singleForm.getAttribute('data-package-name') || 'Das Paket') + ' wurde auf Version ' + (singleForm.getAttribute('data-package-version') || '') + ' aktualisiert.', false);
            }).catch(function (error) {
                singleForm.removeAttribute('aria-busy');
                if (singleButton) {
                    singleButton.disabled = false;
                    singleButton.classList.remove('is-busy');
                    singleButton.textContent = singleLabel;
                }
                showNotice(error && error.message ? error.message : 'Das Update ist fehlgeschlagen.', true);
            });
            return;
        }
        var form = event.target.closest && event.target.closest('.store-dashboard-update-form');
        if (!form) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        if (form.getAttribute('aria-busy') === 'true') return;
        var widget = form.closest('[data-store-dashboard-update]');
        var updates = Array.prototype.map.call(widget ? widget.querySelectorAll('.store-dashboard-single-update-form') : [], function (updateForm) {
            var source = updateForm.querySelector('input[name="source_id"]');
            var packageRef = updateForm.querySelector('input[name="package_ref"]');
            return source && packageRef ? {sourceId: source.value, packageRef: packageRef.value, name: updateForm.getAttribute('data-package-name') || 'Paket', version: updateForm.getAttribute('data-package-version') || ''} : null;
        }).filter(function (update) {
            return update !== null;
        });
        if (!updates.length) return;
        var button = form.querySelector('button[type="submit"]');
        form.setAttribute('aria-busy', 'true');
        if (button) {
            button.disabled = true;
            button.classList.add('is-busy');
            button.textContent = 'Update 1 von ' + updates.length + ' …';
        }
        var index = 0, errors = [];
        function setSingleButtonsDisabled(disabled) {
            document.querySelectorAll('.store-dashboard-single-update-form button').forEach(function (singleButton) {
                singleButton.disabled = disabled;
                singleButton.classList.toggle('is-busy', disabled);
            });
        }
        setSingleButtonsDisabled(true);
        function next() {
            if (index >= updates.length) {
                setSingleButtonsDisabled(false);
                if (errors.length) showNotice(errors.join(' | '), true);
                return;
            }
            var update = updates[index];
            var currentForm = document.querySelector('.store-dashboard-update-form');
            if (!currentForm) { index++; next(); return; }
            var currentButton = currentForm.querySelector('button[type="submit"]');
            currentForm.setAttribute('aria-busy', 'true');
            if (currentButton) {
                currentButton.disabled = true;
                currentButton.classList.add('is-busy');
                currentButton.textContent = 'Update ' + (index + 1) + ' von ' + updates.length + ' …';
            }
            var data = new FormData(currentForm);
            data.set('store_action', 'install');
            data.set('source_id', update.sourceId);
            data.set('package_ref', update.packageRef);
            data.set('install_dependencies', '1');
            fetch(currentForm.action, {
                method: 'POST', body: data, credentials: 'same-origin',
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            }).then(function (response) {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.text();
            }).then(function (html) {
                var result = new DOMParser().parseFromString(html, 'text/html');
                var actionError = result.querySelector('#store-app .store-toast.store-error');
                if (actionError) throw new Error(actionError.textContent.trim());
                return fetch(currentForm.getAttribute('data-dashboard-url'), {
                    credentials: 'same-origin', cache: 'no-store',
                    headers: {'X-Requested-With': 'XMLHttpRequest'}
                });
            }).then(function (response) {
                if (!response.ok) throw new Error('Die Update-Liste konnte nicht neu geladen werden.');
                return response.text();
            }).then(function (html) {
                var page = new DOMParser().parseFromString(html, 'text/html');
                if (page.querySelector('input[type="password"]')) throw new Error('Die Administratorsitzung ist abgelaufen.');
                var current = document.querySelector('[data-store-dashboard-update]');
                var fresh = page.querySelector('[data-store-dashboard-update]');
                if (current && fresh) current.replaceWith(fresh);
                else if (current) current.remove();
                showNotice(update.name + ' wurde auf Version ' + update.version + ' aktualisiert.', false);
                index++;
                next();
            }).catch(function (error) {
                errors.push(error && error.message ? error.message : 'Ein Update ist fehlgeschlagen.');
                index++;
                next();
            });
        }
        next();
    }, true);
    function showNotice(message, isError) {
        if (typeof showStoreDashboardNotice === 'function') {
            showStoreDashboardNotice(message, isError);
            return;
        }
        var notice = document.createElement('div');
        notice.className = 'store-dashboard-notice is-visible' + (isError ? ' is-error' : '');
        notice.textContent = message;
        notice.setAttribute('role', 'status');
        notice.style.cssText = 'position:fixed;right:22px;bottom:22px;z-index:2147483647;max-width:min(390px,calc(100vw - 44px));padding:.9rem 1rem;border-left:4px solid '+(isError?'#b32d2e':'#2e8540')+';border-radius:8px;background:'+(isError?'#fce8e8':'#e5f5e8')+';color:'+(isError?'#8a2424':'#176b27')+';box-shadow:0 8px 28px rgba(0,0,0,.24)';
        document.body.appendChild(notice);
        window.setTimeout(function () { if (notice.parentNode) notice.parentNode.removeChild(notice); }, 4500);
    }
})();
</script>
JS;
            $widgetHtml = '<section class="store-panel store-updates store-dashboard-update" data-store-dashboard-update><h3>'.htmlspecialchars(wbce_store_text('dashboard_title'),ENT_QUOTES,'UTF-8').'</h3><p><strong>'.(int)$count.'</strong> '.htmlspecialchars(wbce_store_text($count === 1 ? 'dashboard_intro_one' : 'dashboard_intro_many'),ENT_QUOTES,'UTF-8').'</p>'.$form.($incompatibleCount > 0 ? '<div class="store-incompatible-updates">'.htmlspecialchars($blocked,ENT_QUOTES,'UTF-8').'</div>' : '').'</section><style>.store-panel{background:var(--wbce-surface,#f6f7f7);border:1px solid var(--wbce-border,#c3c4c7);border-radius:var(--wbce-radius,8px);padding:1rem;margin:1rem 0}.store-updates{border-color:var(--wbce-primary-soft,#9abfd9);border-left:5px solid var(--wbce-primary,#2271b1);background:var(--wbce-primary-background,#f4f9fd)}.store-updates>h3{color:var(--wbce-primary-dark,#135e96)}.store-update-list{display:grid;gap:.6rem;margin:.8rem 0}.store-update-row{display:flex;align-items:center;gap:1rem;padding:.7rem .8rem;border:1px solid var(--wbce-border,#dcdcde);border-radius:var(--wbce-radius,7px);background:var(--wbce-card-background,#fff)}.store-update-row>div{display:grid;gap:.15rem;flex:1}.store-update-row>form{flex:0 0 auto;margin-left:auto!important;text-align:right}.store-update-row small{color:var(--wbce-muted,#646970)}.store-update-actions{display:flex;justify-content:flex-end;margin-top:.8rem;padding-top:.8rem;border-top:1px solid var(--wbce-border,#dcdcde)}.store-update-all{display:block;margin:0 0 0 auto!important;text-align:right}.store-button,.store-dashboard-update button.store-button{background:var(--wbce-primary,#2271b1);color:var(--wbce-on-primary,#fff);border:0;border-radius:var(--wbce-radius-small,4px);padding:.45rem .75rem;cursor:pointer}.store-button:disabled,.store-button.is-busy{cursor:wait;opacity:.72}.store-incompatible-updates{margin-top:1rem;padding-top:.75rem;border-top:1px solid var(--wbce-border,#dcdcde);color:#8a5a00;font-weight:700}.store-dashboard-feedback{color:var(--wbce-muted,#50575e);font-weight:600}.store-dashboard-feedback.is-error{margin-bottom:.45rem;color:#b32d2e}.store-dashboard-notice{position:fixed;right:1.25rem;bottom:1.25rem;z-index:100000;max-width:min(28rem,calc(100vw - 2.5rem));padding:.85rem 1rem;border-left:4px solid #2e8540;border-radius:var(--wbce-radius,6px);background:var(--wbce-card-background,#fff);box-shadow:0 5px 22px rgba(0,0,0,.22);opacity:0;transform:translateY(.75rem);transition:.2s ease}.store-dashboard-notice.is-visible{opacity:1;transform:none}.store-dashboard-notice.is-error{border-left-color:#b32d2e}.store-dashboard-update button.is-busy:before{content:"";display:inline-block;width:.75rem;height:.75rem;margin-right:.45rem;border:2px solid rgba(255,255,255,.45);border-top-color:#fff;border-radius:50%;vertical-align:-.1rem;animation:store-dashboard-spin .75s linear infinite}@keyframes store-dashboard-spin{to{transform:rotate(360deg)}}@media(max-width:620px){.store-update-row{align-items:stretch;flex-direction:column}.store-update-row form,.store-update-row button{width:100%}}</style>'.$script;
            $widgetHtml .= '<script>(function(){var widget=document.querySelector("[data-store-dashboard-update]"),shell=document.querySelector(".wbce-dashboard-shell"),grid=shell&&shell.querySelector(".wbce-dashboard-grid");if(widget&&shell&&grid)shell.insertBefore(widget,grid);})();</script>';
            $widgetHtml .= '<style>.store-dashboard-update .store-update-actions{display:flex!important;align-items:center!important;justify-content:flex-end!important;width:100%!important;box-sizing:border-box!important;margin-top:1.25rem!important;padding:1.25rem .75rem .25rem!important}.store-dashboard-update .store-update-actions>.store-button,.store-dashboard-update .store-update-actions>form>.store-button{float:none!important;display:block!important;width:auto!important;margin:0 0 0 auto!important}.store-dashboard-update .store-update-actions>form{display:flex!important;justify-content:flex-end!important;width:100%!important;margin:0!important}</style>';
            $widgets[] = $widgetHtml;
        } catch (Throwable $ignored) { }
        return $widgets;
    });
}
