(function () {
  'use strict';
  var chunkSize = 1024 * 1024;

  function send(url, token, fields, blob) {
    var body = new FormData();
    Object.keys(fields).forEach(function (key) { body.append(key, fields[key]); });
    body.append('token', token);
    if (blob) body.append('chunk', blob, 'chunk.bin');
    return fetch(url, { method: 'POST', body: body, credentials: 'same-origin' }).then(function (response) {
      return response.json().catch(function () { return {}; }).then(function (payload) {
        if (!response.ok || !payload.ok) throw new Error(payload.message || ('HTTP ' + response.status));
        return payload;
      });
    });
  }

  function upload(form, input) {
    var file = input.files[0];
    var url = form.dataset.nwiChunkUrl;
    var token = form.dataset.nwiChunkToken;
    var count = Math.ceil(file.size / chunkSize);
    var submitButtons = form.querySelectorAll('button[type="submit"],input[type="submit"]');
    submitButtons.forEach(function (button) { button.disabled = true; button.setAttribute('aria-busy', 'true'); });
    return send(url, token, { action: 'init', name: file.name, size: file.size, chunks: count, section_id: form.elements.section_id.value, kind: input.dataset.nwiChunkKind })
      .then(function (result) {
        var chain = Promise.resolve();
        for (var index = 0; index < count; index += 1) {
          (function (chunkIndex) {
            chain = chain.then(function () {
              return send(url, token, { action: 'chunk', upload_id: result.upload_id, index: chunkIndex }, file.slice(chunkIndex * chunkSize, (chunkIndex + 1) * chunkSize));
            });
          }(index));
        }
        return chain.then(function () { return send(url, token, { action: 'complete', upload_id: result.upload_id }); });
      }).then(function (result) {
        var hiddenName = input.dataset.nwiStagedName;
        var hidden = form.querySelector('input[type="hidden"][name="' + hiddenName + '"]');
        if (!hidden) { hidden = document.createElement('input'); hidden.type = 'hidden'; hidden.name = hiddenName; form.appendChild(hidden); }
        hidden.value = result.upload_id;
        input.value = '';
      }).finally(function () {
        submitButtons.forEach(function (button) { button.disabled = false; button.removeAttribute('aria-busy'); });
      });
  }

  document.addEventListener('submit', function (event) {
    var form = event.target.closest('form[data-nwi-chunk-url]');
    if (!form || form.dataset.nwiChunkReady === '1') { if (form) delete form.dataset.nwiChunkReady; return; }
    var input = form.querySelector('input[type="file"][data-nwi-staged-name]');
    if (!input || !input.files || !input.files.length) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    var submitter = event.submitter;
    upload(form, input).then(function () {
      form.dataset.nwiChunkReady = '1';
      if (form.requestSubmit) form.requestSubmit(submitter || undefined); else form.submit();
    }).catch(function (error) {
      if (window.WBCEAsyncForms) window.WBCEAsyncForms.toast(error.message, true);
      else window.alert(error.message);
    });
  }, true);
}());
