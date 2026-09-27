/**
 *
 *    This file will be loaded from the backend_body.js of the Module if needed
 */


/**
 *
 *    Ensure the sortable function is loaded
 *    if isn't yet, load the jquery UI ('sortable' is part of the UI)
 */

if(!jQuery().sortable){
    jQuery.insert(WB_URL+"/include/jquery/jquery-ui-min.js");

}

if(jQuery().sortable){

    /**
        Drag&Drop
        =========
        sortable | http://jqueryui.com/demos/sortable/
    */

    jQuery(function() {
        jQuery('.dragdrop_item').addClass('dragdrop_handle');
        jQuery(".dragdrop_form .move_position a").remove();

        jQuery(".dragdrop_form tbody").sortable({
            appendTo:     'body',
            handle:      '.dragdrop_handle',
            opacity:     0.8,
            cursor:     'move',
            delay:         100,
            items:         'tr',
            dropOnEmpty: false,
            update: function() {
                var sortable = jQuery(this);
                var form = sortable.closest('form');
                sortable.attr('aria-busy', 'true');
                jQuery.ajax({
                    type:        'POST',
                    url:         MODULE_URL +'/ajax/ajax_dragdrop.php',
                    data:        sortable.sortable("serialize", {
                                     expression: /(.+)[:=](.+)/
                                 }) + '&action=updatePosition&' + form.find('input[type="hidden"]').serialize(),
                    dataType:     'json',
                    success:    function(json_respond){
                        if (json_respond.ftan) {
                            var holder = document.createElement('div');
                            holder.innerHTML = json_respond.ftan;
                            var fresh = holder.querySelector('input[type="hidden"][name]');
                            if (fresh) {
                                var current = form.find('input[type="hidden"][name="' + fresh.name.replace(/"/g, '\\"') + '"]').first();
                                if (current.length) current.replaceWith(fresh); else form.prepend(fresh);
                            }
                        }
                        if( json_respond.success != true ) {
                            if (window.WBCEAsyncForms) window.WBCEAsyncForms.toast(json_respond.message, true);
                        }
                        else if (window.WBCEAsyncForms) window.WBCEAsyncForms.toast(json_respond.message, false);
                    },
                    error: function(xhr){
                        var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'HTTP ' + xhr.status;
                        if (window.WBCEAsyncForms) window.WBCEAsyncForms.toast(message, true);
                    },
                    complete: function(){ sortable.removeAttr('aria-busy'); }
                });
            }
        })
    });
}//endif
