(function () {
    'use strict';

    function bind(root) {
        if (root.dataset.recaptchaBound === '1') return;
        root.dataset.recaptchaBound = '1';
        var field = root.querySelector('input[name="g-recaptcha-response"]');
        var status = root.querySelector('[data-recaptcha-status]');
        var form = root.closest('form');
        if (!field || !form) return;

        form.addEventListener('submit', function (event) {
            if (form.dataset.wbceRecaptchaReady === '1') return;
            event.preventDefault();
            if (form.dataset.wbceRecaptchaBusy === '1') return;
            form.dataset.wbceRecaptchaBusy = '1';
            var submitter = event.submitter || null;
            var fail = function () {
                delete form.dataset.wbceRecaptchaBusy;
                if (status) status.textContent = root.dataset.error || '';
            };
            if (!window.grecaptcha || typeof window.grecaptcha.ready !== 'function') {
                fail();
                return;
            }
            window.grecaptcha.ready(function () {
                window.grecaptcha.execute(root.dataset.sitekey, {action: root.dataset.action})
                    .then(function (token) {
                        if (typeof token !== 'string' || token === '') { fail(); return; }
                        field.value = token;
                        form.dataset.wbceRecaptchaReady = '1';
                        delete form.dataset.wbceRecaptchaBusy;
                        if (typeof form.requestSubmit === 'function') {
                            if (submitter) form.requestSubmit(submitter);
                            else form.requestSubmit();
                            window.setTimeout(function () {
                                delete form.dataset.wbceRecaptchaReady;
                                field.value = '';
                            }, 0);
                        } else HTMLFormElement.prototype.submit.call(form);
                    })
                    .catch(fail);
            });
        });
    }

    function start() {
        document.querySelectorAll('.wbce-recaptcha-v3').forEach(bind);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
    else start();
}());
