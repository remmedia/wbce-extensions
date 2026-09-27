(function () {
  async function sha256(value) {
    const bytes = new TextEncoder().encode(value);
    const digest = await crypto.subtle.digest('SHA-256', bytes);
    return Array.from(new Uint8Array(digest)).map(v => v.toString(16).padStart(2, '0')).join('');
  }
  async function solve(node) {
    if (node._wbceAltchaPromise) return node._wbceAltchaPromise;
    node._wbceAltchaPromise = (async function () {
      node.dataset.solving = '1';
      node.classList.add('is-solving');
      try {
        if (!window.crypto || !window.crypto.subtle) throw new Error('secure-context-required');
        const challenge = JSON.parse(atob(node.dataset.challenge));
        let number = 0;
        for (; number <= challenge.maxnumber; number++) {
          if (await sha256(challenge.salt + number) === challenge.challenge) break;
          if (number % 250 === 0) await new Promise(resolve => setTimeout(resolve, 0));
        }
        if (number > challenge.maxnumber) throw new Error('unsolved');
        challenge.number = number;
        node.querySelector('input[name="altcha"]').value = btoa(JSON.stringify(challenge));
        const syncInput = node.querySelector('input[data-altcha-sync]');
        if (syncInput) syncInput.value = syncInput.dataset.altchaSync || '';
        node.querySelector('.wbce-altcha-status').textContent = node.dataset.i18nCompleted || 'Security check completed';
        node.classList.add('is-verified');
        node.setAttribute('aria-checked', 'true');
        return true;
      } catch (error) {
        node.querySelector('.wbce-altcha-status').textContent = node.dataset.i18nFailed || 'Security check failed. Please reload the page.';
        return false;
      } finally {
        node.classList.remove('is-solving');
        delete node.dataset.solving;
      }
    }());
    try { return await node._wbceAltchaPromise; }
    finally { node._wbceAltchaPromise = null; }
  }
  function activate(node) {
    if (node.dataset.altchaActivated === '1') return;
    node.dataset.altchaActivated = '1';
    const delay = Math.max(0, Math.min(3000, Number(node.dataset.delay) || 0));
    const run = () => window.setTimeout(() => solve(node), delay);
    const button = node.querySelector('.wbce-altcha-start');
    if (button) button.addEventListener('click', run);
    node.addEventListener('click', event => {
      if (event.target === button || node.classList.contains('is-verified') || node.dataset.solving === '1') return;
      run();
    });
    if (node.dataset.auto === 'onload') run();
    if (node.closest('form')) {
      const form = node.closest('form');
      form.addEventListener('submit', async event => {
        if (node.classList.contains('is-verified') || form.dataset.altchaSubmitting) return;
        event.preventDefault();
        await new Promise(resolve => window.setTimeout(resolve, delay));
        if (await solve(node)) {
          form.dataset.altchaSubmitting='1';
          if (typeof form.requestSubmit === 'function') form.requestSubmit();
          else HTMLFormElement.prototype.submit.call(form);
        }
      });
    }
  }
  function start() { document.querySelectorAll('.wbce-altcha[data-challenge]').forEach(activate); }
  document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', start) : start();
}());
