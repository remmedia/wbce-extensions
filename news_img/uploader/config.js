(function () {
  'use strict';
  var chunkSize = 1024 * 1024;
  var zone = document.getElementById('drag-and-drop-zone');
  var input = document.getElementById('nwi-chunk-files');
  if (!zone || !input || typeof NWI_UPLOAD_URL !== 'string') return;

  function request(fields, fileChunk) {
    var body = new FormData();
    Object.keys(fields).forEach(function (key) { body.append(key, fields[key]); });
    body.append('token', NWI_UPLOAD_TOKEN);
    if (fileChunk) body.append('chunk', fileChunk, 'chunk.bin');
    return fetch(NWI_UPLOAD_URL, { method: 'POST', body: body, credentials: 'same-origin' })
      .then(function (response) {
        return response.json().catch(function () { return {}; }).then(function (payload) {
          if (!response.ok || !payload.ok) throw new Error(payload.message || ('HTTP ' + response.status));
          return payload;
        });
      });
  }

  function upload(file, id) {
    var count = Math.ceil(file.size / chunkSize);
    ui_multi_update_file_status(id, 'uploading', NWI_UPLOADING_MESSAGE);
    ui_multi_update_file_progress(id, 0, '', true);
    return request({ action: 'init', name: file.name, size: file.size, chunks: count })
      .then(function (result) {
        var uploadId = result.upload_id;
        var chain = Promise.resolve();
        for (var index = 0; index < count; index += 1) {
          (function (chunkIndex) {
            chain = chain.then(function () {
              var blob = file.slice(chunkIndex * chunkSize, Math.min(file.size, (chunkIndex + 1) * chunkSize));
              return request({ action: 'chunk', upload_id: uploadId, index: chunkIndex }, blob).then(function () {
                ui_multi_update_file_progress(id, Math.round(((chunkIndex + 1) / count) * 100));
              });
            });
          }(index));
        }
        return chain.then(function () { return request({ action: 'complete', upload_id: uploadId }); });
      })
      .then(function (result) {
        ui_multi_update_file_status(id, 'success', result.message || NWI_COMPLETE_MESSAGE);
        ui_multi_update_file_progress(id, 100, 'success', false);
      })
      .catch(function (error) {
        ui_multi_update_file_status(id, 'danger', error.message);
        ui_multi_update_file_progress(id, 0, 'danger', false);
      });
  }

  function addFiles(files) {
    Array.prototype.forEach.call(files, function (file) {
      var id = Date.now().toString(36) + Math.random().toString(36).slice(2);
      ui_multi_add_file(id, file);
      upload(file, id);
    });
  }

  input.addEventListener('change', function () { addFiles(input.files); input.value = ''; });
  ['dragenter', 'dragover'].forEach(function (name) {
    zone.addEventListener(name, function (event) { event.preventDefault(); zone.classList.add('active'); });
  });
  ['dragleave', 'drop'].forEach(function (name) {
    zone.addEventListener(name, function (event) { event.preventDefault(); zone.classList.remove('active'); });
  });
  zone.addEventListener('drop', function (event) { addFiles(event.dataTransfer.files); });
}());
