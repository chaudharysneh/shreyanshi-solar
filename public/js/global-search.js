(function(){
'use strict';
function init(){
 const input=document.getElementById('crmGlobalSearch');if(!input)return;
 const panel=document.createElement('section');panel.className='crm-search-panel';panel.hidden=true;panel.id='crm-search-panel';panel.setAttribute('aria-label','CRM search results');document.body.append(panel);
 input.setAttribute('aria-controls',panel.id);input.setAttribute('aria-expanded','false');
 const escape=value=>String(value??'').replace(/[&<>"']/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
 const pages={customers:'/masters/customers',leads:'/leads',projects:'/projects',partners:'/partners',estimates:'/estimates',invoices:'/invoices',tasks:'/tasks',deals:'/deals',followups:'/follow-ups',meetings:'/meetings',tickets:'/tickets',products:'/all_product',inventory:'/inventory',services:'/services',staff:'/users'};
 const styles={leads:['megaphone-fill','#0866ff','name, email, or phone'],customers:['people-fill','#12834e','name, email, or phone'],tasks:['list-check','#06b6d4','title, status, or date'],projects:['diagram-3-fill','#dc3545','name or description'],followups:['person-badge-fill','#6c757d','purpose, comment, or date'],meetings:['people-fill','#12834e','title, date, or customer']};
 let modules=[];try{modules=JSON.parse(input.dataset.modules||'[]');}catch(error){modules=[];}
 const order=['leads','customers','tasks','projects','followups','meetings'];
 modules.sort((a,b)=>(order.includes(a.key)?order.indexOf(a.key):99)-(order.includes(b.key)?order.indexOf(b.key):99));
 function showModules(){
  clearTimeout(timer);controller?.abort();version++;
  panel.innerHTML='<div class="crm-search-heading">Searchable modules</div>'+modules.filter(item=>pages[item.key]).map(item=>{const [icon,color,description]=styles[item.key]||['grid-fill','#6274aa','name or details'];return `<a class="crm-search-shortcut" href="${pages[item.key]}"><span class="crm-search-shortcut-icon" style="--search-color:${color}"><i class="bi bi-${icon}" aria-hidden="true"></i></span><span class="crm-search-shortcut-copy"><strong>${escape(item.label)}</strong><small>Search ${escape(item.label.toLowerCase())} by ${description}</small></span></a>`;}).join('');
  panel.hidden=false;input.setAttribute('aria-expanded','true');position();
 }
 let timer,controller,version=0,module='',page=1;
 function position(){const rect=input.getBoundingClientRect();panel.style.left=Math.max(12,Math.min(rect.left,window.innerWidth-Math.min(380,window.innerWidth-24)-12))+'px';panel.style.top=(rect.bottom+10)+'px';panel.style.maxHeight=Math.min(420,Math.max(0,window.innerHeight-rect.bottom-28))+'px';}
 function close(){clearTimeout(timer);controller?.abort();version++;panel.hidden=true;input.setAttribute('aria-expanded','false');}
 async function search(){
  const q=input.value.trim();if(q.length<2){showModules();return;}
  controller?.abort();controller=new AbortController();const current=++version;
  panel.hidden=false;input.setAttribute('aria-expanded','true');position();panel.innerHTML='<div class="crm-search-state" role="status"><span class="spinner-border spinner-border-sm me-2"></span>Searching CRM records...</div>';
  try{
   const response=await fetch('/global-search?'+new URLSearchParams({q,module,page}),{headers:{Accept:'application/json'},signal:controller.signal});if(!response.ok)throw Error('Search unavailable. Please try again.');const data=await response.json();if(current!==version)return;

   panel.innerHTML=data.groups.length?data.groups.map(group=>`<section class="crm-search-group"><div class="crm-search-module"><a href="${pages[group.key]||'/dashboard'}">${escape(group.label)}</a><a class="crm-search-view-all" href="${pages[group.key]||'/dashboard'}">View all <i class="bi bi-arrow-right"></i></a></div>${group.items.slice(0,3).map(item=>{const detail=item.fields.find(field=>field.label==='Customer')||item.fields.find(field=>field.label==='Phone')||item.fields.find(field=>field.label==='Invoice No.')||item.fields.find(field=>field.label==='Estimate No.')||item.fields.find(field=>field.label==='Status');return `<a class="crm-search-record" href="${escape(item.url||pages[group.key]||'#')}"><div><strong>${escape(item.name)}</strong>${detail?`<small>${escape(detail.value)}</small>`:''}</div><i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>`;}).join('')}</section>`).join(''):'<div class="crm-search-state" role="status">No matching records found.</div>';

  }catch(error){if(error.name!=='AbortError'&&current===version)panel.innerHTML='<div class="crm-search-state" role="alert">Search unavailable. <button type="button" data-search-retry>Retry</button></div>';}
 }
 input.addEventListener('input',()=>{clearTimeout(timer);controller?.abort();version++;module='';page=1;if(input.value.trim().length<2){showModules();return;}timer=setTimeout(search,280);});
 input.addEventListener('focus',()=>{if(input.value.trim().length>=2)search();else showModules();});
 input.addEventListener('keydown',event=>{if(event.key==='ArrowDown'){event.preventDefault();panel.querySelector('a')?.focus();}if(event.key==='Enter'){event.preventDefault();search();}});
 panel.addEventListener('keydown',event=>{if(event.key!=='ArrowDown'&&event.key!=='ArrowUp')return;event.preventDefault();const links=Array.from(panel.querySelectorAll('a'));const index=links.indexOf(document.activeElement);if(event.key==='ArrowUp'&&index<=0){input.focus();return;}links[(index+(event.key==='ArrowDown'?1:-1)+links.length)%links.length]?.focus();});
 panel.addEventListener('click',event=>{const button=event.target.closest('button');if(!button)return;if(button.hasAttribute('data-search-close')){close();return;}if(button.hasAttribute('data-search-module')){module=button.dataset.searchModule;page=1;}else if(button.hasAttribute('data-search-more')){module=button.dataset.searchMore;page++;}else if(button.hasAttribute('data-search-back'))page=Math.max(1,page-1);search();});
 document.addEventListener('keydown',event=>{if(event.key==='Escape')close();if((event.ctrlKey||event.metaKey)&&event.key==='k'){event.preventDefault();input.focus();}});
 document.addEventListener('pointerdown',event=>{if(!panel.contains(event.target)&&event.target!==input)close();});
 window.addEventListener('resize',position);document.addEventListener('scroll',()=>{if(!panel.hidden)position();},true);
}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
