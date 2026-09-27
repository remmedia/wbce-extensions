(function () {
  'use strict';
  var marker=document.querySelector('[data-wbce-accessibility-tools]'),config={};
  try{config=marker?JSON.parse(marker.getAttribute('data-config')||'{}'):{};}catch(ignore){}
  var language=((document.documentElement.getAttribute('lang')||'en').toLowerCase().split(/[-_]/)[0]);
  var dictionary=config.translations||{},translating=false;
  function translate(){
    if(translating)return;translating=true;
    var modal=document.getElementById('accessibility-modal');
    if(modal){
      modal.setAttribute('lang',language);
      modal.querySelectorAll('p,#reset-all').forEach(function(node){var source=(node.textContent||'').trim();if(dictionary[source])node.textContent=dictionary[source];});
      var close=document.getElementById('closeBtn');if(close){close.setAttribute('aria-label',config.open_close||'Open or close accessibility tools');close.setAttribute('title',close.getAttribute('aria-label'));}
      var reset=document.getElementById('reset-all');if(reset)reset.setAttribute('aria-label',config.reset_all||dictionary['Reset All']||'Reset all');
      modal.querySelectorAll('.acc-item').forEach(function(item){var text=item.querySelector('p');if(text)item.setAttribute('aria-label',(text.textContent||'').trim());});
    }
    translating=false;
  }
  translate();
  if(typeof MutationObserver!=='undefined'&&document.body)new MutationObserver(translate).observe(document.body,{childList:true,subtree:true,characterData:true});
}());
