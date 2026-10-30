(function () {
    'use strict';
    const card=document.getElementById('chatbotCard');
    const launcher=document.getElementById('chatbotToggleBtn');
    const messages=document.getElementById('chatbotMessages');
    const input=document.getElementById('chatbotInput');
    const send=document.getElementById('chatbotSendBtn');
    const restore=document.getElementById('chatbotRestoreBtn');
    if (!card || !launcher || !messages || !input || !send) return;
    let initialized=false,loading=false;
    function scroll() { messages.scrollTop=messages.scrollHeight; }
    function message(text,user=false) {
        messages.querySelector('.chatbot-empty-state')?.remove();
        const row=document.createElement('div');row.className='chatbot-message '+(user?'user':'bot');
        const avatar=document.createElement('div');avatar.className='chatbot-avatar';avatar.textContent=user?'You':'Bot';
        const bubble=document.createElement('div');bubble.className='chatbot-bubble';bubble.textContent=String(text || '');
        row.append(...(user?[bubble,avatar]:[avatar,bubble]));messages.appendChild(row);scroll();return bubble;
    }
    function action(label,callback,parent=messages) {
        const button=document.createElement('button');button.type='button';button.className='assistant-chip';button.textContent=label;
        button.addEventListener('click',()=>{if (!loading) callback();});parent.appendChild(button);return button;
    }
    function safeLink(url,label,parent) {
        try {
            const target=new URL(url,location.origin);
            if (target.origin!==location.origin || !['http:','https:'].includes(target.protocol)) return;
            const link=document.createElement('a');link.className='assistant-record-link';link.href=target.href;link.textContent=label;
            const arrow=document.createElement('i');arrow.className='bi bi-arrow-up-right';arrow.setAttribute('aria-hidden','true');link.appendChild(arrow);parent.appendChild(link);
        } catch (error) {}
    }
    function records(items) {
        if (!Array.isArray(items)) return;
        const group=document.createElement('div');group.className='assistant-records';
        items.forEach(item=>{
            const box=document.createElement('article');box.className='assistant-record';
            const title=document.createElement('h6');title.textContent=item.name || 'Record';box.appendChild(title);
            (item.fields || []).forEach(field=>{
                const row=document.createElement('div');row.className='assistant-record-field';
                const label=document.createElement('span');label.textContent=field.label;
                const value=document.createElement('strong');value.textContent=String(field.value ?? '—');row.append(label,value);box.appendChild(row);
            });
            if (item.url) safeLink(item.url,item.type==='partners'?'Open partner dashboard':'Open record',box);
            if (item.type==='partners' && item.id) action('Show report',()=>request({message:`Partner ${item.id} report`},'Partner report'),box);
            group.appendChild(box);
        });
        messages.appendChild(group);
    }
    function topics(modules) {
        const group=document.createElement('div');group.className='assistant-shortcuts';
        action('Solar Overview',()=>request({message:'solar overview'},'Solar Overview'),group);
        modules.forEach(module=>action(module.label,()=>{
            const options=document.createElement('div');options.className='assistant-shortcuts';
            ['All','Today','This Week','This Month','Last Month'].forEach(label=>action(label,()=>request({module:module.key,period:label.toLowerCase().replaceAll(' ','_'),message:''},`${module.label} · ${label}`),options));
            if (module.key==='estimates') ['Pending estimates','Approved estimates','Equipment pending','Equipment partial','Equipment complete'].forEach(label=>action(label,()=>request({message:label},label),options));
            if (module.key==='invoices') ['Unpaid invoices','Paid invoices','Overdue invoices'].forEach(label=>action(label,()=>request({message:label},label),options));
            if (module.key==='partners') action('Partner report help',()=>message('Search partners by name, then ask “Partner 1 report” using the partner ID shown on its link.'),options);
            message(module.label);messages.appendChild(options);scroll();
        },group));
        messages.appendChild(group);
    }
    async function request(payload,label) {
        if (loading) return;
        if (label) message(label,true);
        loading=true;send.disabled=true;input.setAttribute('aria-busy','true');
        const typing=message('Checking live CRM records…');typing.classList.add('assistant-loading');
        const abort=new AbortController();const timeout=setTimeout(()=>abort.abort(),20000);
        try {
            const response=await fetch('/crm-assistant',{method:'POST',headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content || ''},body:JSON.stringify(payload),signal:abort.signal});
            if (!response.ok) throw new Error(response.status===401 || response.status===419 ? 'Your session expired. Refresh the page and sign in again.' : 'Unable to load records. Please try again.');
            const data=await response.json();typing.parentElement.remove();
            message(data.reply);records(data.data);
            if (data.modules) topics(data.modules);
            (data.actions || []).forEach(item=>safeLink(item.url,item.label,messages));
            if (data.next) { const group=document.createElement('div');group.className='assistant-shortcuts';action('Load next 10',()=>request(data.next,'Next page'),group);messages.appendChild(group); }
            if (data.as_of) { const stamp=document.createElement('div');stamp.className='assistant-asof';stamp.textContent='Updated '+data.as_of;messages.appendChild(stamp); }
        } catch (error) {
            typing.textContent=error.name==='AbortError'?'The request took too long. Please try again.':error.message;
        } finally {clearTimeout(timeout);loading=false;send.disabled=false;input.removeAttribute('aria-busy');scroll();}
    }
    function open() {
        card.classList.add('open');card.setAttribute('aria-hidden','false');restore?.setAttribute('aria-expanded','true');
        document.getElementById('chatbotInputWrapper')?.classList.remove('d-none');input.focus();
        if (!initialized) { initialized=true;request({action:'catalog'}); }
    }
    function close() {card.classList.remove('open');card.setAttribute('aria-hidden','true');restore?.setAttribute('aria-expanded','false');}
    launcher.tabIndex=0;
    launcher.addEventListener('click',()=>card.classList.contains('open')?close():open());
    launcher.addEventListener('keydown',event=>{if (event.target===launcher && ['Enter',' '].includes(event.key)){event.preventDefault();card.classList.contains('open')?close():open();}});
    document.getElementById('chatbotCloseBtn')?.addEventListener('click',close);
    document.getElementById('chatbotDismissBtn')?.addEventListener('click',event=>{
        event.stopPropagation();close();document.documentElement.classList.add('chatbot-is-dismissed');
        try {localStorage.setItem('crm.chatbot.dismissed','1');}catch(error){}restore?.focus();
    });
    restore?.addEventListener('click',()=>{
        document.documentElement.classList.remove('chatbot-is-dismissed');document.body.classList.remove('chatbot-is-dismissed');
        try {localStorage.removeItem('crm.chatbot.dismissed');}catch(error){}open();
    });
    function submit() {const text=input.value.trim();if (!text || loading) return;input.value='';request({message:text},text);}
    send.addEventListener('click',submit);
    input.maxLength=1000;input.placeholder='Try “unpaid invoices” or “partner 1 report”';
    input.addEventListener('keydown',event=>{if(event.key==='Enter' && !event.isComposing){event.preventDefault();submit();}});
    card.addEventListener('keydown',event=>{if(event.key==='Escape'){close();launcher.focus();}});
    messages.setAttribute('aria-live','polite');messages.setAttribute('aria-relevant','additions');
})();
