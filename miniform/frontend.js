if (window.jQuery) {  
	$(function() {

		var topOffset = 80;  // set this in pixels for a possible navbar offset

		$.fn.isInViewport = function(){
			var win = $(window);
			var viewport = {
				top : win.scrollTop(),
				left : win.scrollLeft()
			};
			viewport.right = viewport.left + win.width();
			viewport.bottom = viewport.top + win.height();
			var bounds = this.offset();
			bounds.right = bounds.left + this.outerWidth();
			bounds.bottom = bounds.top + this.outerHeight();
			return (!(viewport.right < bounds.left || viewport.left > bounds.right || viewport.bottom < bounds.top || viewport.top > bounds.bottom));
		};
		
		$(":submit").click(function() { 
			$('#ucomp').val(''); 
			$('#uname').val(''); 
			$('#umail').val(''); 
		});
		
		function init_ajaxform() {
			var initiator = '';
			$(":submit").click(function() { initiator = this;  });
			function uploadFiles($form) {
				var token = $form.find('input[name="mf_chunk_token"]').val();
				var sectionId = $form.find('input[name="miniform"]').val();
				var inputs = $form.find('input[type="file"]').filter(function(){ return this.files && this.files.length; }).toArray();
				var blockSize = 1024 * 1024;
				var maxBytes = Math.max(1, parseInt($form.attr('data-mf-upload-limit'), 10) || 512) * 1024 * 1024;
				return inputs.reduce(function(chain, input){
					return chain.then(function(){
						var file = input.files[0];
						if (file.size > maxBytes) throw new Error(($form.attr('data-mf-upload-too-large') || 'File too large').replace('%d', Math.round(maxBytes / 1024 / 1024)));
						var uploadId = Array.from(crypto.getRandomValues(new Uint8Array(16))).map(function(value){ return value.toString(16).padStart(2, '0'); }).join('');
						var chunks = Math.ceil(file.size / blockSize);
						var sequence = Promise.resolve();
						for (var index = 0; index < chunks; index++) {
							(function(chunkIndex){
								sequence = sequence.then(function(){
									var payload = new FormData();
									payload.append('token', token || ''); payload.append('section_id', sectionId || '0');
									payload.append('upload_id', uploadId); payload.append('field', input.name); payload.append('name', file.name);
									payload.append('index', String(chunkIndex)); payload.append('chunks', String(chunks)); payload.append('total', String(file.size));
									payload.append('chunk', file.slice(chunkIndex * blockSize, Math.min(file.size, (chunkIndex + 1) * blockSize)), file.name + '.part');
									return fetch(WB_URL + '/modules/miniform/chunk_upload.php', {method:'POST', body:payload, credentials:'same-origin', headers:{'X-Requested-With':'XMLHttpRequest'}})
										.then(function(response){ return response.json().catch(function(){ throw new Error('HTTP ' + response.status); }).then(function(result){ if (!response.ok || !result.ok) throw new Error(result.message || ('HTTP ' + response.status)); return result; }); });
								});
							}(index));
						}
						return sequence.then(function(){
							$('<input>', {type:'hidden', name:'mf_chunk[' + input.name + ']', value:uploadId}).appendTo($form);
							input.disabled = true;
						});
					});
				}, Promise.resolve());
			}
			function sendForm($form, $container) {
				var data = new FormData($form[0]);
				if (initiator && initiator.name) data.append(initiator.name, initiator.value);
				return $.ajax({
					type: "POST", data: data, processData: false, contentType: false,
					cache: false, headers: { "cache-control": "no-cache" }
				}).done(function(data) {
					$container.html(data);
					var $message = $container.find('.error, .ok');
					if($message.length && $message.isInViewport() == false ) $('html,body').animate({scrollTop: $message.offset().top - topOffset}, 500);
				});
			}
			$('.miniform_ajax').each(function( index ) {
				var $container = $(this);
				var $form = $container.find('form');
				$form.off();
				$form.on('submit',$form,function(event){
					event.preventDefault(); 
					/* Empty file fields are disabled for Safari and to keep the final request small. */
					$form.find( ':input[type="file"]' ).each( function( index, el ) {
						if (el.value == '') {
							$(this).prop('disabled', true);
							console.log('/* safari bugfix */ disabled empty file upload field: '+el.name);
						}
					});	
					$('.minispinner').fadeIn(10);
					uploadFiles($form).then(function(){ return sendForm($form, $container); })
					.catch(function(error){
						var box = $('<div>', {'class':'error alert alert-danger', role:'alert'}).text(error.message || $form.attr('data-mf-upload-failed'));
						$container.prepend(box);
					})
					.then(function () {
						$('.minispinner').fadeOut(500);
						init_ajaxform();
					});
				});
			});
			$('form[data-mf-upload-limit]').not('.miniform_ajax form').each(function(){
				var $form = $(this);
				if ($form.data('mf-chunk-bound')) return;
				$form.data('mf-chunk-bound', true).on('submit.miniformChunk', function(event){
					if (!$form.find('input[type="file"]').filter(function(){ return this.files && this.files.length; }).length) return;
					event.preventDefault();
					var formNode = this;
					$form.find(':submit').prop('disabled', true).attr('aria-busy', 'true');
					uploadFiles($form).then(function(){ HTMLFormElement.prototype.submit.call(formNode); }).catch(function(error){
						$form.find(':submit').prop('disabled', false).removeAttr('aria-busy');
						$form.prepend($('<div>', {'class':'error alert alert-danger', role:'alert'}).text(error.message || $form.attr('data-mf-upload-failed')));
					});
				});
			});
		}
		init_ajaxform();
	});
}
