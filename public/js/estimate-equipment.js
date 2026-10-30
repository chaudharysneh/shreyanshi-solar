(function () {
    let busy=false;
    window.collectEstimateEquipment = async function (id, options = {}) {
        if (busy) return null;
        busy=true;
        let element;
        try {
            const response=await fetch(options.url || `/api/estimates/${id}/equipment`,{headers:{Accept:'application/json'}});
            if (!response.ok) throw new Error('Unable to load equipment details. Please try again.');
            const data=await response.json();
            element=document.createElement('div');element.className='modal fade';element.tabIndex=-1;
            element.setAttribute('aria-label','Equipment serial numbers');
            element.innerHTML=`<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><form class="modal-content border-0 rounded-4 shadow"><div class="modal-header px-4 py-3" style="background:#121a33"><h5 class="modal-title text-white fw-bold"><i class="bi bi-upc-scan me-2"></i>Equipment Serial Numbers</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cancel approval"></button></div><div class="modal-body p-4"><div class="p-3 rounded-3 border mb-4" style="background:var(--crm-bg-soft,#f8fafc)"><div class="fw-semibold mb-1"></div><p class="small text-muted mb-0">Panel and inverter quantities are managed separately. All serial numbers and remarks are optional.</p></div><div class="equipment-serial-fields"></div><label class="form-label fw-semibold mt-4" for="equipmentRemarks">Remarks / Notes</label><textarea id="equipmentRemarks" class="form-control" rows="3" maxlength="4000" placeholder="Add equipment or installation notes..."></textarea><div class="equipment-error text-danger small mt-2" role="alert"></div></div><div class="modal-footer"><button type="button" class="btn btn-outline-dark-blue equipment-skip">Skip &amp; Approve</button><button type="submit" class="btn btn-dark-blue">Save &amp; Approve</button></div></form></div>`;
            element.querySelector('.modal-body p').textContent='Choose panels per kW below. Add inverter entries separately as needed. All serial numbers and remarks are optional.';
            const fields=element.querySelector('.equipment-serial-fields');
            if (options.edit) {
                element.querySelector('.modal-title').textContent='Equipment Details';
                element.querySelector('button[type="submit"]').textContent='Save Details';
                element.querySelector('.equipment-skip').textContent='Cancel';
            }
            const configuration=document.createElement('div');configuration.className='mb-4';
            configuration.innerHTML='<label for="equipmentPanelsPerKw" class="form-label fw-semibold">Panels per kW</label><select id="equipmentPanelsPerKw" class="form-select"><option value="1">1 panel per kW</option><option value="2">2 panels per kW (0.5 kW per panel)</option></select>';
            fields.before(configuration);
            const ratioSelect=configuration.querySelector('select');ratioSelect.value=String(data.panels_per_kw || 2);
            const serialCache={panels:[...(data.equipment?.panels || [])],inverters:[...(data.equipment?.inverters || [])]};
            if (!serialCache.inverters.length) serialCache.inverters.push('');
            function rememberFields() {
                ['panels','inverters'].forEach(type=>fields.querySelectorAll(`[data-type="${type}"]`).forEach((input,index)=>{serialCache[type][index]=input.value;}));
            }
            function renderFields() {
                rememberFields();
                fields.replaceChildren();
                const count=Math.ceil(Number(data.quantity)*Number(ratioSelect.value));
                element.querySelector('.modal-body .fw-semibold').textContent=`${data.quantity} kW | ${count} panels | ${serialCache.inverters.length} inverter(s)`;
                element.querySelector('.equipment-error').textContent='';
                ['panels','inverters'].forEach(type=>{
                    const section=document.createElement('div');section.className='border rounded-3 p-3 mb-3';
                    const heading=document.createElement('h6');heading.className='fw-bold mb-3';heading.textContent=type==='panels'?'Panels':'Inverters';
                    const row=document.createElement('div');row.className='row g-3';
                    const total=type==='panels'?count:serialCache.inverters.length;
                    for (let i=0;i<total;i++) {
                        const col=document.createElement('div');col.className='col-12 col-sm-6 col-lg-4';
                        const input=document.createElement('input');input.className='form-control';input.maxLength=100;input.dataset.type=type;input.id=`equipment-${type}-${i}`;input.value=serialCache[type][i] || '';
                        input.placeholder=`Enter ${type==='panels'?'panel':'inverter'} serial number`;
                        const label=document.createElement('label');label.className='form-label small fw-semibold';label.htmlFor=input.id;label.textContent=`${type==='panels'?'Panel':'Inverter'} ${i+1} Serial No.`;
                        col.append(label,input);
                        if (type==='inverters' && total>1) {
                            const remove=document.createElement('button');remove.type='button';remove.className='btn btn-sm btn-outline-danger mt-2';remove.textContent='Remove';remove.setAttribute('aria-label',`Remove inverter ${i+1}`);
                            remove.addEventListener('click',()=>{rememberFields();serialCache.inverters.splice(i,1);fields.querySelectorAll('[data-type="inverters"]').forEach(input=>input.remove());renderFields();});
                            col.appendChild(remove);
                        }
                        row.appendChild(col);
                    }
                    section.append(heading,row);
                    if (type==='inverters') {
                        const add=document.createElement('button');add.type='button';add.className='btn btn-sm btn-outline-dark-blue mt-3';add.textContent='+ Add More Inverter';
                        add.addEventListener('click',()=>{rememberFields();serialCache.inverters.push('');renderFields();fields.querySelectorAll('[data-type="inverters"]')[serialCache.inverters.length-1].focus();});
                        section.appendChild(add);
                    }
                    fields.appendChild(section);
                });
            }
            renderFields();
            element.querySelector('textarea').value=data.equipment?.notes || '';
            document.body.appendChild(element);
            const enhancedRatio=window.jQuery?.fn.select2 ? window.jQuery(ratioSelect) : null;
            if (enhancedRatio) {
                enhancedRatio.select2({
                    theme:'bootstrap-5',width:'100%',dropdownParent:window.jQuery(element),
                    minimumResultsForSearch:Infinity,dropdownCssClass:'equipment-ratio-dropdown',selectionCssClass:'equipment-ratio-selection',
                    templateResult:function (option) {
                        if (!option.id) return option.text;
                        const row=document.createElement('span');row.className='equipment-ratio-option';
                        row.classList.toggle('equipment-ratio-current',String(option.id)===ratioSelect.value);
                        const icon=document.createElement('i');icon.className='bi bi-grid-3x3-gap equipment-ratio-icon';icon.setAttribute('aria-hidden','true');
                        const text=document.createElement('span');
                        const title=document.createElement('strong');title.textContent=`${option.id} panel${option.id==='1'?'':'s'} per kW`;
                        const subtitle=document.createElement('small');subtitle.textContent=option.id==='1'?'1 kW per panel':'0.5 kW per panel';
                        text.append(title,subtitle);row.append(icon,text);
                        return window.jQuery(row);
                    },
                }).on('change.equipment',renderFields);
            } else ratioSelect.addEventListener('change',renderFields);
            const modal=bootstrap.Modal.getOrCreateInstance(element);
            return await new Promise(resolve=>{
                let result=null;
                element.addEventListener('hidden.bs.modal',()=>{if (enhancedRatio) enhancedRatio.off('.equipment').select2('destroy');modal.dispose();element.remove();resolve(result);},{once:true});
                element.querySelector('.equipment-skip').addEventListener('click',()=>{result=options.edit?null:{};modal.hide();});
                element.querySelector('form').addEventListener('submit',event=>{
                    event.preventDefault();
                    const equipment={panels_per_kw:Number(ratioSelect.value),notes:element.querySelector('textarea').value.trim()};
                    for (const type of ['panels','inverters']) {
                        equipment[type]=Array.from(element.querySelectorAll(`[data-type="${type}"]`)).map(input=>input.value.trim());
                        const entered=equipment[type].filter(Boolean);
                        if (new Set(entered).size !== entered.length) {element.querySelector('.equipment-error').textContent=`Please use unique serial numbers for ${type}.`;return;}
                    }
                    result={equipment_serials:equipment};modal.hide();
                });
                modal.show();
            });
        } catch(error) {
            if (element?.isConnected) element.remove();
            window.showAlert?.('error',error.message);
            return null;
        } finally {busy=false;}
    };
    document.addEventListener('click',async function (event) {
        const button=event.target.closest('[data-equipment-url]');
        if (!button) return;
        event.preventDefault();button.disabled=true;
        try {
            const payload=await window.collectEstimateEquipment(button.dataset.estimateId,{edit:true,url:button.dataset.equipmentUrl});
            if (!payload) return;
            const response=await fetch(button.dataset.equipmentUrl,{method:'PATCH',headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content || ''},body:JSON.stringify(payload)});
            const data=await response.json();
            if (!response.ok) throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'Unable to save equipment details.');
            const tab=button.closest('.tab-pane');if (tab?.id) location.hash=tab.id;
            location.reload();
        } catch (error) {window.showAlert?.('error',error.message);}
        finally {button.disabled=false;}
    });
})();
