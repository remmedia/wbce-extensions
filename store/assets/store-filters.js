(function(){
'use strict';
if(window.wbceStoreReliableFiltersBound)return;window.wbceStoreReliableFiltersBound=true;
function norm(value){return String(value||'').trim().toLocaleLowerCase();}
function apply(){
 var app=document.querySelector('#store-app'),search=app&&app.querySelector('[data-store-search]');if(!search)return;
 var typeField=app.querySelector('[data-store-type]'),categoryField=app.querySelector('[data-store-category]'),statusField=app.querySelector('[data-store-status]'),sortField=app.querySelector('[data-store-sort]');
 var query=norm(search.value),type=norm(typeField&&typeField.value),category=norm(categoryField&&categoryField.value),status=norm(statusField&&statusField.value),sort=sortField?sortField.value:'name',filtering=!!(query||type||category||status),visible=0;
 app.querySelectorAll('.store-package').forEach(function(card){var installed=card.getAttribute('data-store-installed')==='1',update=card.getAttribute('data-store-update')==='1',statusOk=!status||(status==='update'&&update)||(status==='installed'&&installed)||(status==='available'&&!installed),show=(!query||norm(card.textContent).indexOf(query)!==-1)&&(!type||norm(card.getAttribute('data-store-type'))===type)&&(!category||norm(card.getAttribute('data-store-category'))===category)&&statusOk;card.hidden=!show;card.classList.toggle('store-filter-hidden',!show);if(show)visible++;});
 app.querySelectorAll('.store-grid').forEach(function(grid){var cards=Array.prototype.slice.call(grid.querySelectorAll('.store-package'));cards.sort(function(a,b){var byName=(a.getAttribute('data-store-name')||'').localeCompare(b.getAttribute('data-store-name')||'','de',{sensitivity:'base'});if(sort==='name')return byName;var field=sort==='new-package'?'data-store-package-created':'data-store-update-created',byDate=parseInt(b.getAttribute(field)||'0',10)-parseInt(a.getAttribute(field)||'0',10);if(byDate)return byDate;return(b.getAttribute('data-store-version')||'').localeCompare(a.getAttribute('data-store-version')||'','de',{numeric:true,sensitivity:'base'})||byName;});cards.forEach(function(card){grid.appendChild(card);});});
 app.querySelectorAll('.store-category-group').forEach(function(group){var shown=!!group.querySelector('.store-package:not(.store-filter-hidden)');group.hidden=!shown;group.classList.toggle('store-filter-hidden',!shown);if(shown&&filtering)group.open=true;});
 app.querySelectorAll('.store-type').forEach(function(heading){var node=heading.nextElementSibling,shown=false;while(node&&!(node.classList&&node.classList.contains('store-type'))){if(node.classList&&node.classList.contains('store-category-group')&&!node.classList.contains('store-filter-hidden')){shown=true;break;}node=node.nextElementSibling;}heading.hidden=!shown;heading.classList.toggle('store-filter-hidden',!shown);});
 var empty=app.querySelector('.store-filter-empty');if(empty){empty.hidden=visible!==0;empty.classList.toggle('store-filter-hidden',visible!==0);}
}
function relevant(target){return target&&target.matches&&target.matches('[data-store-search],[data-store-type],[data-store-category],[data-store-status],[data-store-sort]');}
document.addEventListener('input',function(event){if(relevant(event.target))apply();},true);document.addEventListener('change',function(event){if(relevant(event.target))apply();},true);
window.wbceStoreApplyFilters=apply;window.wbceStoreBindFilters=apply;apply();
}());
