(function () {
    'use strict';
    // Output filters can clear hidden input values. Restore the FTAN from the
    // data attribute before the shared async form handler creates FormData.
    document.querySelectorAll('input[data-wbce-ftan]').forEach(function (input) {
        var value = input.getAttribute('data-wbce-ftan');
        if (value) input.value = value;
    });
}());


if (!window.WBCEAsyncForms) {
    (function(){var id='wbce-module-async-script',old=document.getElementById(id),ready=function(){window.WBCEAsyncForms.bind(document);};if(old){old.addEventListener('load',ready);return;}var script=document.createElement('script');script.id=id;script.src=WB_URL+'/include/wbce-module-async.js';script.addEventListener('load',ready);document.head.appendChild(script);}());
} else {
    window.WBCEAsyncForms.bind(document);
}

$( document ).ready(function() {
    var uploadForm = document.getElementById('droplets-chunk-form');
    if (uploadForm) {
        uploadForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            var fileInput = uploadForm.querySelector('input[type="file"]');
            var file = fileInput && fileInput.files ? fileInput.files[0] : null;
            if (!file) return;
            var button = uploadForm.querySelector('button[type="submit"]');
            var progressBox = uploadForm.querySelector('.droplets-upload-progress');
            var progress = progressBox.querySelector('progress');
            var progressText = progressBox.querySelector('span');
            var resultBox = uploadForm.querySelector('.droplets-upload-result');
            var chunkSize = 1024 * 1024;
            var chunks = Math.ceil(file.size / chunkSize);

            function showToast(message, failed) {
                var toast = document.createElement('div');
                toast.className = 'wbce-admin-toast droplets-toast' + (failed ? ' is-error' : ' is-success');
                toast.setAttribute('role', 'status');
                toast.textContent = message;
                document.body.appendChild(toast);
                window.setTimeout(function() { toast.remove(); }, 7000);
            }
            async function request(fields, blob) {
                var body = new FormData();
                body.append('token', uploadForm.dataset.token);
                Object.keys(fields).forEach(function(key) { body.append(key, fields[key]); });
                if (blob) body.append('chunk', blob, 'chunk.bin');
                var response = await fetch(uploadForm.dataset.endpoint, {
                    method: 'POST', body: body, credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                var payload;
                try { payload = await response.json(); }
                catch (error) { throw new Error('HTTP ' + response.status); }
                if (!response.ok || !payload.ok) throw new Error(payload.message || ('HTTP ' + response.status));
                return payload;
            }

            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            progressBox.hidden = false;
            resultBox.textContent = '';
            try {
                var initialized = await request({ action: 'init', name: file.name, size: file.size, chunks: chunks });
                for (var index = 0; index < chunks; index++) {
                    await request({ action: 'chunk', upload_id: initialized.upload_id, index: index }, file.slice(index * chunkSize, Math.min(file.size, (index + 1) * chunkSize)));
                    var percent = Math.round(((index + 1) / chunks) * 100);
                    progress.value = percent;
                    progressText.textContent = percent + '%';
                }
                var completed = await request({ action: 'complete', upload_id: initialized.upload_id });
                showToast(completed.message, false);
                var pageResponse = await fetch(uploadForm.dataset.returnUrl, { credentials: 'same-origin' });
                var pageHtml = await pageResponse.text();
                var parsed = new DOMParser().parseFromString(pageHtml, 'text/html');
                var replacement = parsed.querySelector('.pane');
                var current = document.querySelector('.pane');
                if (replacement && current) current.replaceWith(replacement);
            } catch (error) {
                resultBox.textContent = error.message;
                showToast(error.message, true);
                button.disabled = false;
                button.removeAttribute('aria-busy');
            }
        });
    }
    
    $.insert(WB_URL+"/modules/droplets/js/jquery.tablesorter.js");
    
    $(".show-droplet-code").on("click", function(event){
        event.preventDefault();
        $(this).next('.droplet-info').toggle();
    }); 
    
    
    $("#checkAll").click(function () {
       $('input:checkbox.checker').not(this).prop('checked', this.checked);
    });
    
    $('#markeddroplet').change(function() {
        $('#operateChecked').toggle();
    });
    
    var countChecked = function () {
        var n = $("input:checked.checker").length;
        if (n >= 1) {
            $("#operateChecked").css({
                "display": "block"
            });
        } else {
            $("#operateChecked").css({
                "display": "none"
            });
        }
    };
    countChecked();

    $("input[type=checkbox]").on("click", countChecked);
});


/**
 *  AJAX
 *  Function to toggle enabled|disabled status of a filter
 */
$(document).ready(function() {
    function updateFtan(html) {
        if (!html) return;
        var holder = document.createElement('div');
        holder.innerHTML = html;
        var fresh = holder.querySelector('input[type="hidden"][name]');
        if (!fresh) return;
        $('input[type="hidden"][name="' + fresh.name.replace(/"/g, '\\"') + '"]').val(fresh.value);
    }
    function asyncAction(fields) {
        var table = document.querySelector('table.droplets');
        var form = table ? table.closest('form') : document.querySelector('form[action*="droplets"]');
        var data = new FormData();
        Object.keys(fields).forEach(function(key){ data.append(key, fields[key]); });
        if (form) {
            var token = form.querySelector('input[type="hidden"][name]');
            if (token) data.append(token.name, token.value);
        }
        return fetch(WB_URL + '/modules/droplets/ajax_toggle_state.php', {
            method: 'POST', body: data, credentials: 'same-origin',
            headers: {'X-Requested-With':'XMLHttpRequest', 'Accept':'application/json'}
        }).then(function(response){
            return response.json().catch(function(){ throw new Error('HTTP ' + response.status); }).then(function(payload){
                updateFtan(payload.ftan);
                if (!response.ok || !payload.success) throw new Error(payload.message || ('HTTP ' + response.status));
                return payload;
            });
        });
    }
    
    $('.status [type=checkbox]').click(function(event) {
        // get the ID from the checkbox
        var ID = event.target.id;
        // get rid of prefix in the ID
        var ID = ID.replace("switch_", "");
        // prepand with # for to get an easy selector 
        var sRowID = '#' + ID;
        
        // get IDKEY from data-idkey attribute
        // PLEASE NOTE: It's not advisable to send IDKEY via the id attribute
        //              because the many special characters in it are not
        //              supportet for the id attribute and it causes problems        
        var IDKEY  = $(sRowID).data('idkey').replace("id_", "");
        
        // prepare tr class for DOM
        var state = ($(this).is(':checked')) ? '1' : '0';
        var sOldClass = $(sRowID).attr('class');                   
        var sNewClass = ((state == 0) ? 'in' : '') + 'active';
        console.log(sNewClass);
        // prepare the DATASTRING to send via ajax
        event.target.disabled = true;
        event.target.setAttribute('aria-busy', 'true');
        asyncAction({purpose:'toggle_status', action:state, idkey:IDKEY}).then(function(payload){
            $(sRowID).removeClass(sOldClass).addClass(sNewClass);
            if (window.WBCEAsyncForms) window.WBCEAsyncForms.toast(payload.message, false);
        }).catch(function(error){
            event.target.checked = state !== '1';
            if (window.WBCEAsyncForms) window.WBCEAsyncForms.toast(error.message, true);
        }).finally(function(){ event.target.disabled = false; event.target.removeAttribute('aria-busy'); });
    });
    
    // toggle show_date
    $('#show_date').on( "click", function () {  
        var checkbox = this;
        checkbox.disabled = true;
        asyncAction({purpose:'show_date', action:checkbox.checked ? '1' : '0'}).then(function(payload){
            DROPLETS_SHOW_DATE = checkbox.checked ? 1 : 0;
            $('.droplets-date-column').prop('hidden', !checkbox.checked);
            if (window.WBCEAsyncForms) window.WBCEAsyncForms.toast(payload.message, false);
        }).catch(function(error){
            checkbox.checked = !checkbox.checked;
            if (window.WBCEAsyncForms) window.WBCEAsyncForms.toast(error.message, true);
        }).finally(function(){ checkbox.disabled = false; });
    });

    $('tr').on("click", ".delete-item", function(event) {
        event.preventDefault();
        var ID     = $(this).parent().parent().attr('id');
        var sRowID = '#' + ID;
        
        // get IDKEY from data-idkey attribute
        // PLEASE NOTE: It's not advisable to send IDKEY via the id attribute
        //              because the many special characters in it are not
        //              supportet for the id attribute and it causes problems        
        var IDKEY  = $(sRowID).data('idkey').replace("id_", "");  
        
        var FILTER_NAME = $(sRowID).closest('tr').find('td:eq(1)').text()
        var MSG    = $(this).data('question').replace("%s", FILTER_NAME);
        var sCANCEL = $(this).data('cancel');
        var sDELETE = $(this).data('delete');
        
        // Create replacement HTML for table row
        // let's preserve the height of the row
        var height = $(sRowID).height() + 'px !important';
        var colspan = 6;
        var new_row = `
            <tr class="row-on-delete" id="`+ ID +`">
                <td colspan="`+ colspan +`" style="height: `+ height +`;">
                <table class="delete-table" width="100%">
                    <tr>
                        <td width="34"></td>
                        <td style="text-align:left;font-size: 14px;font-weight:800" class="droplet-name"><i class="fa fa-fw fa-edit"></i> ` + FILTER_NAME + `</td>
                        <td style="text-align:right;font-size: 15px;">` + MSG + `</td>
                        <td  style="height: `+ height +`;">
                            <a href="javascript:void(0);" id="reset" data-row="`+ sRowID +`" class="btn-inline red">` + sCANCEL + `</a>
                            <a href="javascript:void(0);" id="del" data-id="`+ ID +`" data-idkey="`+ IDKEY +`" data-uri="` + $(this).data('del-uri') + `" class="btn-inline green">` + sDELETE + `</a>

                        </td>
                    </tr>
                </td>
            </tr>`;
        
        // apply replacement
        // the object oReplacement will be used in the next function with#reset
        oReplacement = $(sRowID).replaceWith(new_row);
    });    
    

    $(document).on("click", "#reset", function() {
       // reset row to original
       $(this).parentsUntil( $( "tr.row-on-delete" )).parent().replaceWith(oReplacement);  
       return false;
    });

    $(document).on("click", "#del", function() {
       // Delete the row
        var ID    = $(this).data('id');
        var IDKEY = $(this).data('idkey');
        var button = this;
        button.setAttribute('aria-busy', 'true');
        asyncAction({purpose:'delete', action:'1', idkey:IDKEY}).then(function(payload){
            $("#" + ID).fadeOut(250, function(){ $(this).remove(); });
            if (window.WBCEAsyncForms) window.WBCEAsyncForms.toast(payload.message, false);
        }).catch(function(error){
            button.removeAttribute('aria-busy');
            if (window.WBCEAsyncForms) window.WBCEAsyncForms.toast(error.message, true);
        });
    });
});
