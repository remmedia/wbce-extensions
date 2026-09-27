(function(){
  if(window.AccessibilityToolsAdminLoaded)return;
  var form=document.getElementById('at-form'),root=document.querySelector('.at-wrap');if(!form||!root)return;
  window.AccessibilityToolsAdminLoaded=true;
  var toast=document.querySelector('.at-toast'),timer,busy=false,pending=false;
  function notice(text,error){clearTimeout(timer);toast.textContent=text;toast.classList.toggle('error',!!error);toast.classList.add('show');timer=setTimeout(function(){toast.classList.remove('show');},3500);}
  function save(){
    if(busy){pending=true;return;}busy=true;form.classList.add('at-saving');
    var data=new FormData(form);
    fetch(form.action,{method:'POST',body:data,credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'}})
      .then(function(response){return response.json().then(function(data){if(!response.ok||!data.ok)throw new Error(data.message||root.dataset.error);return data;});})
      .then(function(data){if(data.ftan){var box=document.createElement('div');box.innerHTML=data.ftan;var next=box.querySelector('input[type="hidden"]'),slot=form.querySelector('.at-ftan');if(next&&slot){slot.innerHTML='';slot.appendChild(next);}}notice(data.message,false);})
      .catch(function(error){notice(error.message||root.dataset.error,true);})
      .finally(function(){busy=false;form.classList.remove('at-saving');if(pending){pending=false;save();}});
  }
  form.addEventListener('submit',function(event){event.preventDefault();save();});
  document.querySelectorAll('[form="at-form"],#at-form input,#at-form select').forEach(function(control){control.addEventListener('change',save);});
}());
