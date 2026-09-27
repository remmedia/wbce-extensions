(function(){
    'use strict';
    var root=document.querySelector('.wak[data-webauthn-endpoint]');
    if(!root||root.dataset.webauthnBound==='1')return;
    root.dataset.webauthnBound='1';
    var endpoint=root.dataset.webauthnEndpoint,failed=root.dataset.actionFailed||'Action failed.',checkFailed=root.dataset.checkFailed||failed;
    var button=root.querySelector('#wak-register'),tokenForm=root.querySelector('#wak-token'),toastNode=root.querySelector('.wak-toast'),label=root.querySelector('#wak-label'),buttonText=button?button.textContent:'';
    function encode(buffer){return btoa(String.fromCharCode.apply(null,new Uint8Array(buffer))).replace(/\+/g,'-').replace(/\//g,'_').replace(/=+$/,'');}
    function decode(value){var source=value.replace(/-/g,'+').replace(/_/g,'/');while(source.length%4)source+='=';return Uint8Array.from(atob(source),function(character){return character.charCodeAt(0);});}
    function toast(message,error){toastNode.textContent=message;toastNode.hidden=false;toastNode.classList.toggle('error',!!error);window.setTimeout(function(){toastNode.hidden=true},6000);}
    function token(){return new FormData(tokenForm);}
    function optionsUrl(){return endpoint+(endpoint.indexOf('?')===-1?'?':'&')+'options=1';}
    function send(payload){return fetch(endpoint,{method:'POST',body:payload,credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'}}).then(function(response){return response.json().then(function(result){if(result.ftan)tokenForm.innerHTML=result.ftan;if(!response.ok||!result.success)throw new Error(result.message||failed);return result;});});}
    function refreshList(){return fetch(endpoint,{credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'}}).then(function(response){if(!response.ok)throw new Error(failed);return response.text();}).then(function(html){var page=new DOMParser().parseFromString(html,'text/html'),fresh=page.querySelector('#wak-list'),freshToken=page.querySelector('#wak-token');if(!fresh)throw new Error(failed);root.querySelector('#wak-list').replaceWith(fresh);if(freshToken)tokenForm.innerHTML=freshToken.innerHTML;bindRemove();});}
    function ask(message){return new Promise(function(resolve){var dialog=document.createElement('dialog');dialog.className='wbce-admin-card tf-confirm-dialog';dialog.innerHTML='<form method="dialog"><p></p><div class="tf-dialog-actions"><button class="button wbce-admin-button" value="cancel"></button><button class="button wbce-admin-button" value="confirm"></button></div></form>';dialog.querySelector('p').textContent=message;var buttons=dialog.querySelectorAll('button');buttons[0].textContent=root.dataset.cancel||'Cancel';buttons[1].textContent=root.dataset.confirm||'Confirm';dialog.addEventListener('close',function(){var accepted=dialog.returnValue==='confirm';dialog.remove();resolve(accepted);},{once:true});document.body.appendChild(dialog);dialog.showModal();});}
    function bindRemove(){root.querySelectorAll('[data-remove]:not([data-bound])').forEach(function(remove){remove.dataset.bound='1';remove.addEventListener('click',function(){ask(root.dataset.removeConfirm||'').then(function(accepted){if(!accepted)return;var form=token();form.append('action','remove');form.append('id',remove.dataset.remove);remove.disabled=true;remove.setAttribute('aria-busy','true');return send(form).then(function(result){return refreshList().then(function(){toast(result.message,false);});}).catch(function(error){remove.disabled=false;remove.removeAttribute('aria-busy');toast(error.message,true);});});});});}
    if(button)button.addEventListener('click',function(){
        button.disabled=true;button.setAttribute('aria-busy','true');
        fetch(optionsUrl(),{credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'}})
            .then(function(response){if(!response.ok)throw new Error(checkFailed);return response.json();})
            .then(function(options){options.challenge=decode(options.challenge);options.user.id=decode(options.user.id);options.excludeCredentials=(options.excludeCredentials||[]).map(function(item){item.id=decode(item.id);return item;});return navigator.credentials.create({publicKey:options});})
            .then(function(credential){var payload={id:credential.id,type:credential.type,response:{clientDataJSON:encode(credential.response.clientDataJSON),attestationObject:encode(credential.response.attestationObject)}};var form=token();form.append('action','register');form.append('label',label.value);form.append('credential',JSON.stringify(payload));return send(form);})
            .then(function(result){return refreshList().then(function(){toast(result.message,false);});})
            .catch(function(error){toast(error.message,true);})
            .then(function(){button.disabled=false;button.removeAttribute('aria-busy');button.textContent=buttonText;});
    });
    bindRemove();
}());
