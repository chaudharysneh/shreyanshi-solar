<?php
namespace App\Http\Controllers;
use App\Models\{Invoice,Payment};
use App\Services\InvoiceFinanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
class PaymentController extends Controller {
    public function index(){return redirect()->route('invoices.receivables');}
    public function indexByInvoice(Invoice $invoice){$this->authorize('view',$invoice);return redirect()->route('invoices.show',$invoice);}
    public function store(Request $request,InvoiceFinanceService $finance){
        abort_unless($request->user()->hasMatrixPermission('edit_invoices'),403);
        $data=$request->validate(['invoice_id'=>['required','integer','exists:invoices,id'],'amount'=>['required','numeric','min:0.01','max:9999999999.99','decimal:0,2'],'source'=>['nullable','in:customer,bank'],'payment_method'=>['required','in:cash,upi,bank_transfer,cheque,card,other'],'payment_date'=>['required','date','before_or_equal:'.now('Asia/Kolkata')->toDateString()],'transaction_id'=>['nullable','string','max:255'],'notes'=>['nullable','string','max:4000'],'request_key'=>['nullable','uuid']]);
        $invoice=Invoice::findOrFail($data['invoice_id']);$this->authorize('update',$invoice);$plan=$invoice->financePlan;$source=$data['source']??'customer';
        $current=(int)round((float)$invoice->payments()->sum('amount')*100);$new=(int)round((float)$data['amount']*100);
        $finance->save($invoice,['request_key'=>$data['request_key']??(string)Str::uuid(),'action'=>'payment','settlement'=>$current+$new===(int)round((float)$invoice->amount*100)?'full':'partial','funding_type'=>$plan?->funding_type??'customer','bank_amount'=>$plan?->bank_amount??0,'down_payment'=>$plan?->down_payment??0,'bank_name'=>$plan?->bank_name,'branch_name'=>$plan?->branch_name,'loan_reference'=>$plan?->loan_reference,'expected_bank_date'=>$plan?->expected_bank_date?->format('Y-m-d'),'customer_received_now'=>$source==='customer'?$data['amount']:0,'bank_received_now'=>$source==='bank'?$data['amount']:0,'payment_method'=>$data['payment_method'],'payment_date'=>$data['payment_date'],'customer_reference'=>$data['transaction_id']??null,'bank_reference'=>$data['transaction_id']??null,'notes'=>$data['notes']??null],$request->user()->id);
        return back()->with('success','Payment recorded successfully.');
    }
    public function destroy(Request $request,Payment $payment,InvoiceFinanceService $finance){
        abort_unless($request->user()->hasMatrixPermission('edit_invoices'),403);$this->authorize('update',$payment->invoice);$data=$request->validate(['reason'=>['required','string','max:255']]);$finance->void($payment,$data['reason'],$request->user()->id);return back()->with('success','Payment voided; audit record retained.');
    }
}
