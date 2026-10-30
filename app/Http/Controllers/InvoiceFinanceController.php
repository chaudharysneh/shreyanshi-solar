<?php
namespace App\Http\Controllers;
use App\Models\{Invoice,Payment,InvoiceFinancePlan,Currency};
use App\Services\InvoiceFinanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
class InvoiceFinanceController extends Controller {
    public function show(Invoice $invoice,InvoiceFinanceService $finance) {
        $this->authorize('view',$invoice);
        return response()->json(['summary'=>$finance->summary($invoice),'receipts'=>Payment::where('invoice_id',$invoice->id)->latest('id')->get(),'events'=>$invoice->financeEvents()->with('creator:id,name')->get(),'can_edit'=>auth()->user()->hasMatrixPermission('edit_invoices') && auth()->user()->can('update',$invoice),'request_key'=>(string)Str::uuid()]);
    }
    public function store(Request $request,Invoice $invoice,InvoiceFinanceService $finance) {
        $this->authorize('update',$invoice);
        $money=['nullable','numeric','min:0','max:9999999999.99','decimal:0,2'];
        $data=$request->validate([
            'request_key'=>['required','uuid'],'action'=>['required','in:payment,plan'],'settlement'=>['required','in:full,partial'],
            'funding_type'=>['required','in:customer,bank'],'bank_name'=>['required_if:funding_type,bank','nullable','string','max:255'],
            'branch_name'=>['required_if:funding_type,bank','nullable','string','max:255'],'loan_reference'=>['nullable','string','max:255'],
            'bank_amount'=>$money,'down_payment'=>$money,'customer_received_now'=>$money,'bank_received_now'=>$money,
            'expected_bank_date'=>['nullable','date'],'payment_date'=>['required_if:action,payment','nullable','date','before_or_equal:'.now('Asia/Kolkata')->toDateString()],
            'payment_method'=>['required_if:action,payment','nullable','in:cash,upi,bank_transfer,cheque,card,other'],
            'customer_reference'=>['nullable','string','max:255'],'bank_reference'=>['nullable','string','max:255'],'notes'=>['nullable','string','max:4000'],
        ]);
        if($data['funding_type']==='bank') {
            $bankId=\Illuminate\Support\Facades\DB::table('finance_banks')->where('name',$data['bank_name'])->value('id');
            if(!$bankId || !\Illuminate\Support\Facades\DB::table('finance_bank_branches')->where('bank_id',$bankId)->where('name',$data['branch_name'])->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['branch_name'=>'Select a bank and one of its branches, or add them using the + buttons.']);
            }
        }
        return response()->json(['success'=>true,'message'=>'Finance details saved.','summary'=>$finance->save($invoice,$data,auth()->id())]);
    }
    public function void(Request $request,Payment $payment,InvoiceFinanceService $finance) {
        $invoice=$payment->invoice;$this->authorize('update',$invoice);
        $data=$request->validate(['reason'=>['required','string','max:255']]);$finance->void($payment,$data['reason'],auth()->id());
        return response()->json(['success'=>true]);
    }
    public function index(Request $request,InvoiceFinanceService $finance) {
        $request->validate(['search'=>['nullable','string','max:255'],'bank_id'=>['nullable','integer','exists:finance_banks,id'],'branch_id'=>['nullable','integer','exists:finance_bank_branches,id'],'status'=>['nullable','in:unpaid,partial,paid'],'funding'=>['nullable','in:bank,customer'],'customer_id'=>['nullable','integer'],'per_page'=>['nullable','integer','in:10,25,50,100']]);
        $currency=\App\Support\DocumentCurrency::defaultCode();
        $query=$this->scopeOwnedRecords(Invoice::with(['customer','currency','financePlan','payments']))->whereNotIn('status',['draft','cancelled','void'])->where(fn($q)=>$q->where('is_quotation',false)->orWhereNull('is_quotation'));
        if($request->filled('customer_id'))$query->where('customer_id',$request->integer('customer_id'));
        $default=\App\Support\DocumentCurrency::defaultCode();
        $query->where(function($q)use($currency,$default){$q->whereHas('currency',fn($c)=>$c->where('code',$currency));if($currency===$default)$q->orWhereNull('currency_id');});
        if($request->filled('search')){$search=$request->input('search');$query->where(fn($q)=>$q->where('invoice_name','like','%'.$search.'%')->orWhere('invoice_no','like','%'.$search.'%')->orWhereHas('customer',fn($c)=>$c->where('name','like','%'.$search.'%')->orWhere('phone','like','%'.$search.'%'))->orWhereHas('financePlan',fn($p)=>$p->where('bank_name','like','%'.$search.'%')->orWhere('branch_name','like','%'.$search.'%')));}
        if($request->filled('funding')){$type=$request->funding;$query->where(function($q)use($type){$q->whereHas('financePlan',fn($p)=>$p->where('funding_type',$type));if($type==='customer')$q->orWhereDoesntHave('financePlan');});}
        if($request->filled('bank_id')) { $bankName=\Illuminate\Support\Facades\DB::table('finance_banks')->where('id',$request->integer('bank_id'))->value('name'); $query->whereHas('financePlan',fn($q)=>$q->where('bank_name',$bankName)); }
        if($request->filled('branch_id')) {
            $branch=\Illuminate\Support\Facades\DB::table('finance_bank_branches')->join('finance_banks','finance_banks.id','=','finance_bank_branches.bank_id')->where('finance_bank_branches.id',$request->integer('branch_id'))->select('finance_bank_branches.name','finance_banks.name as bank_name')->first();
            $query->whereHas('financePlan',fn($q)=>$q->where('bank_name',$branch->bank_name)->where('branch_name',$branch->name));
        }
        if($request->filled('status')) $query->where('status',$request->status);
        $totals=array_fill_keys(['received','customer_pending','bank_received','bank_pending','legacy_unallocated'],0);
        $bankTotals=[];
        (clone $query)->chunkById(200,function($batch)use($finance,&$totals,&$bankTotals){foreach($batch as $invoice){$summary=$finance->summary($invoice);if($summary['plan']?->funding_type==='bank'){$bank=$summary['plan']->bank_name;$branch=$summary['plan']->branch_name;$name=json_encode([$bank,$branch]);$bankTotals[$name]??=['bank'=>$bank,'branch'=>$branch,'invoices'=>0,'loan'=>0,'received'=>0,'pending'=>0];$bankTotals[$name]['invoices']++;foreach(['loan'=>'bank_expected','received'=>'bank_received','pending'=>'bank_pending'] as $target=>$source)$bankTotals[$name][$target]+=(int)round(($summary[$source]??0)*100);}foreach($totals as $key=>$value)$totals[$key]+=(int)round((float)($summary[$key]??0)*100);}});
        $totals=array_map(fn($value)=>$value/100,$totals);
        $invoices=$query->latest('id')->paginate($request->integer('per_page',10))->withQueryString();
        $rows=$invoices->getCollection()->map(fn($invoice)=>['invoice'=>$invoice,'finance'=>$finance->summary($invoice)]);
        $banks=\Illuminate\Support\Facades\DB::table('finance_banks')->orderBy('name')->get();
        $branches=\Illuminate\Support\Facades\DB::table('finance_bank_branches')->when($request->filled('bank_id'),fn($q)=>$q->where('bank_id',$request->integer('bank_id')))->orderBy('name')->get();
        return view('crm.invoices.receivables',compact('invoices','rows','totals','currency','banks','branches','bankTotals'));
    }
}
