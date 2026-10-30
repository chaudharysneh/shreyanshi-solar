(function () {
    const element=document.getElementById('invoiceFinanceModal');if(!element)return;
    const form=document.getElementById('invoiceFinanceForm');const save=document.getElementById('invoiceFinanceSave');
    const error=document.getElementById('invoiceFinanceError');let invoiceId=null,record=null,loading=0;
    const e=value=>String(value??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
    const money=value=>value===null?'Needs allocation':new Intl.NumberFormat('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2}).format(Number(value||0));
    const headers=()=>({Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content});
    async function request(url,options={}) { const response=await fetch(url,{credentials:'same-origin',headers:headers(),...options});const data=await response.json();if(!response.ok)throw new Error(Object.values(data.errors||{}).flat()[0]||data.message||'Unable to save finance records.');return data; }
    let masters={banks:[],branches:[]}, masterType='bank';
    const bankSelect=form.elements.bank_name,branchSelect=form.elements.branch_name;
    function refreshSelect(select){if(window.jQuery?.fn.select2){const $select=jQuery(select);if(!$select.hasClass('select2-hidden-accessible'))$select.select2({width:'100%',theme:'bootstrap-5',dropdownParent:jQuery(element),dropdownCssClass:'finance-master-dropdown'});$select.trigger('change.select2');}}
    function fillBranches(selected=''){
        const bank=masters.banks.find(item=>item.name===bankSelect.value);
        branchSelect.innerHTML='<option value="">Select branch</option>'+masters.branches.filter(item=>Number(item.bank_id)===Number(bank?.id)).map(item=>`<option value="${e(item.name)}">${e(item.name)}</option>`).join('');
        if(selected&&!Array.from(branchSelect.options).some(option=>option.value===selected))branchSelect.add(new Option(selected,selected));
        branchSelect.value=selected;refreshSelect(branchSelect);
    }
    function fillBanks(selected=''){
        bankSelect.innerHTML='<option value="">Select bank</option>'+masters.banks.map(item=>`<option value="${e(item.name)}">${e(item.name)}</option>`).join('');
        if(selected&&!Array.from(bankSelect.options).some(option=>option.value===selected))bankSelect.add(new Option(selected,selected));
        bankSelect.value=selected;refreshSelect(bankSelect);
    }
    bankSelect.addEventListener('change',()=>fillBranches());
    element.addEventListener('shown.bs.modal',()=>{refreshSelect(bankSelect);refreshSelect(branchSelect);if(window.jQuery?.fn.select2)jQuery(bankSelect).off('change.financeBank').on('change.financeBank',()=>fillBranches());});
    element.querySelectorAll('[data-add-finance-master]').forEach(button=>button.addEventListener('click',()=>{
        masterType=button.dataset.addFinanceMaster;
        if(masterType==='branch'&&!masters.banks.some(bank=>bank.name===bankSelect.value)){error.textContent='Select a bank before adding a branch.';return;}
        document.getElementById('financeMasterTitle').textContent=masterType==='bank'?'Add bank':'Add branch to '+bankSelect.value;
        document.getElementById('financeMasterEditor').hidden=false;document.getElementById('financeMasterName').value='';document.getElementById('financeMasterError').textContent='';document.getElementById('financeMasterName').focus();
    }));
    document.getElementById('financeMasterCancel').addEventListener('click',()=>document.getElementById('financeMasterEditor').hidden=true);
    document.getElementById('financeMasterSave').addEventListener('click',async function(){
        const name=document.getElementById('financeMasterName').value.trim();if(!name){document.getElementById('financeMasterError').textContent='Enter a name.';return;}
        this.disabled=true;
        try{const payload={name};if(masterType==='branch')payload.bank_id=masters.banks.find(bank=>bank.name===bankSelect.value)?.id;
            const result=await request('/finance-banks',{method:'POST',body:JSON.stringify(payload)});masters=await request('/finance-banks');
            if(masterType==='bank'){fillBanks(result.record.name);fillBranches();}else fillBranches(result.record.name);
            document.getElementById('financeMasterEditor').hidden=true;
        }catch(failure){document.getElementById('financeMasterError').textContent=failure.message;}finally{this.disabled=false;}
    });
    const cents=value=>Math.round(Number(value||0)*100);
    function balances(){
        const total=cents(record?.summary.total),bank=form.elements.funding_type.value==='bank'?cents(form.elements.bank_amount.value):0;
        return {total,bank,customer:total-bank,customerPaid:cents(record?.summary.customer_received),bankPaid:cents(record?.summary.bank_received)};
    }
    function preview(){
        if(!record)return;
        const b=balances(),payment=form.elements.action.value==='payment';
        const customerNow=payment?cents(form.elements.customer_received_now.value):0,bankNow=payment?cents(form.elements.bank_received_now.value):0;
        const customerDue=b.customer-b.customerPaid-customerNow,bankDue=b.bank-b.bankPaid-bankNow;
        const invalid=b.bank<0||b.customer<0||customerDue<0||bankDue<0;
        const remaining=customerDue+bankDue;
        const state=remaining===0?'Paid':b.customerPaid+b.bankPaid+customerNow+bankNow>0?'Partially paid':'Unpaid';
        document.getElementById('financePaymentPreview').innerHTML=`<h6>${payment?'Balance after this payment':'Funding breakdown'} <span class="small text-muted">(${e(record.summary.currency)})</span></h6><div class="invoice-finance-kpis"><div><span>Total customer contribution</span><strong>${money(b.customer/100)}</strong></div><div><span>Total bank loan</span><strong>${money(b.bank/100)}</strong></div><div><span>Still due from customer</span><strong>${money(Math.max(0,customerDue)/100)}</strong></div><div><span>Still due from bank</span><strong>${money(Math.max(0,bankDue)/100)}</strong></div></div><p class="small mt-3 mb-0 ${invalid?'text-danger':'text-muted'}">${invalid?'Amounts exceed the invoice or the allocated customer/bank share. Correct the funding or payment amounts.':record.summary.legacy_unallocated?'Earlier Paid record: complete the historical receipt split before saving.':'Invoice status after saving: '+state+'. Bank installments can be recorded again whenever received.'}</p>`;
        document.getElementById('financeBankReleaseHint').textContent=b.bankPaid>0?'Bank has already paid '+money(b.bankPaid/100)+'. Record the next installment or remaining balance.':'Choose the percentage actually received, or enter a custom amount below.';
        element.querySelectorAll('[data-bank-release]').forEach(button=>button.disabled=!record.can_edit||(button.dataset.bankRelease!=='remaining'&&b.bankPaid>0));
    }
    function syncCoverage(){
        const b=balances(),received=b.customerPaid+b.bankPaid+cents(form.elements.customer_received_now.value)+cents(form.elements.bank_received_now.value);
        form.elements.settlement.value=received===b.total?'full':'partial';preview();
    }
    element.querySelectorAll('[data-bank-release]').forEach(button=>button.addEventListener('click',()=>{
        const b=balances();form.elements.bank_received_now.value=((button.dataset.bankRelease==='remaining'?Math.max(0,b.bank-b.bankPaid):Math.round(b.bank*Number(button.dataset.bankRelease)/100))/100).toFixed(2);syncCoverage();
    }));
    document.getElementById('financeFillCustomer').addEventListener('click',()=>{const b=balances();form.elements.customer_received_now.value=(Math.max(0,b.customer-b.customerPaid)/100).toFixed(2);syncCoverage();});
    function calculateBankLoan(){
        if(!record || form.elements.funding_type.value!=='bank')return;
        form.elements.bank_amount.value=(Math.max(0,cents(record.summary.total)-cents(form.elements.down_payment.value))/100).toFixed(2);
    }
    form.addEventListener('input',event=>{
        if(event.target.name==='down_payment')calculateBankLoan();
        if(record)syncCoverage();
    });
    form.elements.funding_type.addEventListener('change',()=>{calculateBankLoan();syncCoverage();});
    function visibility(){
        const bank=form.elements.funding_type.value==='bank';const payment=form.elements.action.value==='payment';
        form.querySelectorAll('.finance-bank,.finance-payment').forEach(node=>{const visible=(!node.classList.contains('finance-bank')||bank)&&(!node.classList.contains('finance-payment')||payment);node.hidden=!visible;});
        form.querySelectorAll('.finance-payment input').forEach(input=>{if(!payment&&['customer_received_now','bank_received_now'].includes(input.name))input.value='0';});
        if(!bank){form.elements.bank_received_now.value='0';form.elements.bank_amount.value='0';}
        preview();
    }
    form.addEventListener('change',visibility);
    form.elements.settlement.addEventListener('change',()=>{
        if(form.elements.settlement.value!=='full'||!record)return;
        const total=Number(record.summary.total);const bank=Number(form.elements.bank_amount.value||0);
        form.elements.customer_received_now.value=Math.max(0,total-bank-Number(record.summary.customer_received)).toFixed(2);
        form.elements.bank_received_now.value=Math.max(0,bank-Number(record.summary.bank_received)).toFixed(2);
    });
    window.openInvoiceFinance=async function(id,action='plan'){
        invoiceId=id;const version=++loading;error.textContent='';document.getElementById('invoiceFinanceContent').hidden=true;document.getElementById('invoiceFinanceLoading').hidden=false;save.disabled=true;
        bootstrap.Modal.getOrCreateInstance(element).show();
        try{
            const [data,bankData]=await Promise.all([request('/invoices/'+encodeURIComponent(id)+'/finance'),request('/finance-banks')]);masters=bankData;if(version!==loading)return;record=data;form.reset();document.getElementById('financeMasterEditor').hidden=true;
            const s=data.summary,p=s.plan||{};document.getElementById('invoiceFinanceSubtitle').textContent=[s.customer,s.invoice_name,s.invoice_no,s.currency].filter(Boolean).join(' | ');
            document.getElementById('invoiceFinanceKpis').innerHTML=[['Invoice total',s.total],['Customer received',s.customer_received],['Bank received',s.bank_received],['Total outstanding',s.outstanding],['Customer pending',s.customer_pending],['Bank pending',s.bank_pending],['Bank funded share',s.bank_expected],['Earlier paid / unallocated',s.legacy_unallocated]].map(([label,value])=>`<div><span>${e(label)}</span><strong>${e(money(value))}</strong></div>`).join('');
            form.elements.request_key.value=data.request_key;
            ['funding_type','loan_reference','bank_amount','down_payment','notes'].forEach(key=>{if(p[key]!==undefined&&p[key]!==null)form.elements[key].value=p[key];});
            if(p.funding_type==='bank' && !Number(p.bank_amount))calculateBankLoan();
            fillBanks(p.bank_name||'');fillBranches(p.branch_name||'');
            form.elements.expected_bank_date.value=(p.expected_bank_date||'').slice(0,10);
            form.elements.action.value=action;
            form.elements.payment_date.value=new Intl.DateTimeFormat('en-CA',{timeZone:'Asia/Kolkata'}).format(new Date());
            document.getElementById('invoiceFinanceLegacy').hidden=!s.legacy_unallocated;
            const body=document.getElementById('invoiceFinanceReceipts');
            body.innerHTML=data.receipts.map(payment=>`<tr><td>${e((payment.payment_date||'').slice(0,10))}</td><td>${e(payment.source)}</td><td>${e(money(payment.amount))}</td><td>${e(payment.payment_method)}<div class="small text-muted">${e(payment.transaction_id)}</div><div>${e(payment.notes)}</div></td><td>${payment.voided_at?`<span class="badge bg-danger-subtle text-danger">Voided</span><div class="small">${e(payment.void_reason)}</div>`:`<span class="badge bg-success-subtle text-success">Recorded</span>${data.can_edit?`<details class="mt-1"><summary>Correct entry</summary><input class="form-control form-control-sm mt-1" aria-label="Reason for correction" placeholder="Reason to void" maxlength="255"><button type="button" class="btn btn-outline-danger btn-sm mt-1" data-void-payment="${payment.id}">Void receipt</button></details>`:''}`}</td></tr>`).join('')||'<tr><td colspan="5" class="text-muted text-center py-3">No receipts recorded yet.</td></tr>';
            document.getElementById('invoiceFinanceEvents').innerHTML=data.events.map(event=>`<div class="invoice-finance-audit"><strong>${e(event.action==='void_payment'?'Receipt voided':event.action==='payment'?'Payment recorded':'Funding plan updated')}</strong><div>${e(event.creator?.name||'System')} | <time>${e(new Date(event.created_at).toLocaleString('en-IN',{timeZone:'Asia/Kolkata'}))} IST</time></div>${event.action==='void_payment'?`<div>${e(event.details.reason)} | ${e(money(event.details.amount))}</div>`:`<div>Customer received: ${e(money(event.details.customer_received))} | Bank received: ${e(money(event.details.bank_received))}</div><div>${e(event.details.plan?.bank_name||'Customer funded')} ${e(event.details.plan?.branch_name||'')} | Loan share: ${e(money(event.details.plan?.bank_amount))}</div><div>${e(event.details.plan?.notes||'')}</div>`}</div>`).join('')||'<div class="small text-muted">No finance changes recorded yet.</div>';
            Array.from(form.elements).forEach(field=>field.disabled=!data.can_edit);element.querySelectorAll('[data-add-finance-master]').forEach(button=>button.hidden=!data.can_edit);save.hidden=!data.can_edit;save.disabled=false;refreshSelect(bankSelect);refreshSelect(branchSelect);visibility();
            document.getElementById('invoiceFinanceContent').hidden=false;document.getElementById('invoiceFinanceLoading').hidden=true;
        }catch(failure){error.textContent=failure.message;document.getElementById('invoiceFinanceLoading').hidden=true;}
    };
    document.addEventListener('click',event=>{const button=event.target.closest('[data-invoice-finance]');if(!button)return;event.preventDefault();window.openInvoiceFinance(button.dataset.invoiceFinance);});
    save.addEventListener('click',async()=>{
        if(!invoiceId||!record?.can_edit)return;save.disabled=true;error.textContent='';
        try{await request('/invoices/'+encodeURIComponent(invoiceId)+'/finance',{method:'POST',body:JSON.stringify(Object.fromEntries(new FormData(form)))});window.location.reload();}
        catch(failure){error.textContent=failure.message;save.disabled=false;}
    });
    element.addEventListener('click',async event=>{const button=event.target.closest('[data-void-payment]');if(!button)return;const reason=button.parentElement.querySelector('input').value.trim();if(!reason){error.textContent='Enter a reason before voiding a receipt.';return;}button.disabled=true;
        try{await request('/invoice-payments/'+button.dataset.voidPayment+'/void',{method:'POST',body:JSON.stringify({reason})});window.location.reload();}catch(failure){error.textContent=failure.message;button.disabled=false;}
    });
})();
