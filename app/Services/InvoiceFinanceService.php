<?php
namespace App\Services;
use App\Models\{Invoice,InvoiceFinanceEvent,InvoiceFinancePlan,Payment};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceFinanceService {
    private function cents($amount): int {return (int)round((float)$amount*100);}
    private function money(int $value): string {return number_format($value/100,2,'.','');}
    public function summary(Invoice $invoice): array {
        $invoice->loadMissing(['financePlan','payments','currency','customer']);$plan=$invoice->financePlan;
        $total=$this->cents($invoice->amount);$customer=$invoice->payments->where('source','customer')->sum(fn($p)=>$this->cents($p->amount));$bank=$invoice->payments->where('source','bank')->sum(fn($p)=>$this->cents($p->amount));
        $ledger=$customer+$bank;$legacy=$invoice->status==='paid' && !$plan && $ledger<$total;
        $bankExpected=$this->cents($plan?->bank_amount ?? 0);$customerExpected=$total-$bankExpected;
        return ['invoice_id'=>$invoice->id,'invoice_name'=>$invoice->invoice_name,'invoice_no'=>$invoice->invoice_no,'customer'=>$invoice->customer?->name,'currency'=>$invoice->currency?->code ?: \App\Support\DocumentCurrency::defaultCode(),
            'total'=>$total/100,'received'=>($legacy?$total:$ledger)/100,'outstanding'=>max(0,$total-($legacy?$total:$ledger))/100,
            'customer_expected'=>$customerExpected/100,'bank_expected'=>$bankExpected/100,'customer_received'=>$customer/100,'bank_received'=>$bank/100,
            'customer_pending'=>$legacy?null:max(0,$customerExpected-$customer)/100,'bank_pending'=>$legacy?null:max(0,$bankExpected-$bank)/100,
            'legacy_unallocated'=>$legacy?($total-$ledger)/100:0,'status'=>$invoice->status,'plan'=>$plan];
    }
    public function save(Invoice $invoice,array $data,int $userId): array {
        return DB::transaction(function()use($invoice,$data,$userId){
            $invoice=Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            if(in_array($invoice->status,['cancelled','void','draft'],true))throw ValidationException::withMessages(['invoice'=>'Payments cannot be recorded on this invoice status.']);
            $hash=hash('sha256',json_encode($data));$existing=InvoiceFinanceEvent::where('request_key',$data['request_key'])->first();
            if($existing){if($existing->invoice_id!==$invoice->id || $existing->payload_hash!==$hash)throw ValidationException::withMessages(['request_key'=>'This submission key was already used. Reopen the payment form.']);return $this->summary($invoice);}
            $total=$this->cents($invoice->amount);if($total<=0)throw ValidationException::withMessages(['invoice'=>'The invoice total must be positive before recording finance.']);$bankExpected=$data['funding_type']==='bank'?$this->cents($data['bank_amount']??0):0;$down=$this->cents($data['down_payment']??0);
            $customerPaid=$this->cents($invoice->payments()->where('source','customer')->sum('amount'));$bankPaid=$this->cents($invoice->payments()->where('source','bank')->sum('amount'));
            $newCustomer=$this->cents($data['customer_received_now']??0);$newBank=$this->cents($data['bank_received_now']??0);
            if($data['funding_type']==='bank' && $bankExpected<=0)throw ValidationException::withMessages(['bank_amount'=>'Enter the bank-funded share of this invoice.']);
            if($bankExpected>$total || $down>$total-$bankExpected)throw ValidationException::withMessages(['bank_amount'=>'Bank funding and planned down payment must fit within the invoice total.']);
            if($customerPaid+$newCustomer>$total-$bankExpected)throw ValidationException::withMessages(['customer_received_now'=>'Customer receipts exceed the customer share of this invoice.']);
            if($bankPaid+$newBank>$bankExpected)throw ValidationException::withMessages(['bank_received_now'=>'Bank receipts exceed the bank funding allocated to this invoice.']);
            $received=$customerPaid+$bankPaid+$newCustomer+$newBank;
            if($data['action']==='payment'){
                if($newCustomer+$newBank<=0)throw ValidationException::withMessages(['customer_received_now'=>'Enter an amount actually received from the customer or bank.']);
                if($data['settlement']==='full' && $received!==$total)throw ValidationException::withMessages(['settlement'=>'For full payment, the total received must equal the invoice total.']);
                if($data['settlement']==='partial' && $received>=$total)throw ValidationException::withMessages(['settlement'=>'The invoice is fully covered. Choose Full payment.']);
            } elseif($newCustomer+$newBank>0)throw ValidationException::withMessages(['action'=>'Choose Record payment to save received amounts.']);
            if($invoice->status==='paid' && !$invoice->financePlan && $customerPaid+$bankPaid<$total && $received!==$total)throw ValidationException::withMessages(['settlement'=>'This invoice was previously marked Paid without a complete receipt breakdown. Record the full historical split before saving finance tracking.']);
            $before=$invoice->financePlan?->toArray();
            $plan=InvoiceFinancePlan::updateOrCreate(['invoice_id'=>$invoice->id],[
                'funding_type'=>$data['funding_type'],'bank_amount'=>$this->money($bankExpected),'down_payment'=>$this->money($down),
                'bank_name'=>$data['funding_type']==='bank'?$data['bank_name']:null,'branch_name'=>$data['funding_type']==='bank'?$data['branch_name']:null,
                'loan_reference'=>$data['funding_type']==='bank'?($data['loan_reference']??null):null,'expected_bank_date'=>$data['funding_type']==='bank'?($data['expected_bank_date']??null):null,'notes'=>$data['notes']??null]);
            $ids=[];
            foreach(['customer'=>$newCustomer,'bank'=>$newBank]as$source=>$amount){if(!$amount)continue;$payment=Payment::create(['invoice_id'=>$invoice->id,'source'=>$source,'amount'=>$this->money($amount),'payment_method'=>$source==='bank'?'bank_transfer':$data['payment_method'],'payment_date'=>$data['payment_date'],'transaction_id'=>$source==='bank'?($data['bank_reference']??null):($data['customer_reference']??null),'notes'=>$data['notes']??null,'created_by'=>$userId]);$ids[]=$payment->id;}
            $invoice->update(['status'=>$received===$total?'paid':($received>0?'partial':'unpaid')]);
            InvoiceFinanceEvent::create(['invoice_id'=>$invoice->id,'request_key'=>$data['request_key'],'payload_hash'=>$hash,'action'=>$data['action'],'created_by'=>$userId,'details'=>['previous_plan'=>$before,'plan'=>$plan->toArray(),'payment_ids'=>$ids,'customer_received'=>$newCustomer/100,'bank_received'=>$newBank/100,'status'=>$invoice->status]]);
            return $this->summary($invoice->fresh());
        });
    }
    public function void(Payment $payment,string $reason,int $userId): void {
        DB::transaction(function()use($payment,$reason,$userId){
            $invoice=Invoice::whereKey($payment->invoice_id)->lockForUpdate()->firstOrFail();$payment=Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();if($payment->voided_at)return;
            $payment->update(['voided_at'=>now(),'voided_by'=>$userId,'void_reason'=>$reason]);$received=$this->cents($invoice->payments()->sum('amount'));$invoice->update(['status'=>$received>=$this->cents($invoice->amount)?'paid':($received>0?'partial':'unpaid')]);
            InvoiceFinanceEvent::create(['invoice_id'=>$invoice->id,'request_key'=>(string)\Illuminate\Support\Str::uuid(),'payload_hash'=>hash('sha256',$payment->id.$reason),'action'=>'void_payment','created_by'=>$userId,'details'=>['payment_id'=>$payment->id,'amount'=>$payment->amount,'source'=>$payment->source,'reason'=>$reason]]);
        });
    }
}
