<?php
namespace Tests\Feature;
use App\Models\{Invoice,Payment,InvoiceFinanceEvent};
use App\Services\InvoiceFinanceService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
class InvoiceFinanceTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']);DB::purge('sqlite');
        Schema::create('customers',function(Blueprint $t){$t->id();$t->string('name');$t->string('phone')->nullable();$t->timestamps();$t->softDeletes();});
        Schema::create('users',function(Blueprint $t){$t->id();$t->string('name');});
        Schema::create('currencies',function(Blueprint $t){$t->id();$t->string('code');});
        Schema::create('invoices',function(Blueprint $t){$t->id();$t->integer('customer_id');$t->integer('user_id')->nullable();$t->integer('created_by')->nullable();$t->integer('currency_id')->nullable();$t->string('invoice_name');$t->string('invoice_no');$t->decimal('amount',14,2);$t->string('status');$t->boolean('is_quotation')->default(false);$t->timestamps();$t->softDeletes();});
        (require database_path('migrations/2026_03_11_112548_create_payments_table.php'))->up();
        (require database_path('migrations/2026_10_26_000010_create_invoice_finance_tracking.php'))->up();
        DB::table('customers')->insert(['id'=>1,'name'=>'Customer 1']);DB::table('currencies')->insert(['id'=>1,'code'=>'INR']);DB::table('invoices')->insert(['id'=>1,'customer_id'=>1,'user_id'=>1,'currency_id'=>1,'invoice_name'=>'Installation','invoice_no'=>'INV-1','amount'=>300000,'status'=>'unpaid']);
    }
    private function data(array $changes=[]): array {return array_merge(['request_key'=>(string)Str::uuid(),'action'=>'plan','settlement'=>'partial','funding_type'=>'bank','bank_amount'=>200000,'down_payment'=>50000,'bank_name'=>'Test Bank','branch_name'=>'Surat','loan_reference'=>'LN-1','customer_received_now'=>0,'bank_received_now'=>0,'payment_date'=>'2020-01-01','payment_method'=>'upi'],$changes);}
    public function test_partial_payments_have_separate_customer_and_bank_receipt_downloads(): void {
        Schema::create('settings', function (Blueprint $t) { $t->id(); $t->string('key'); $t->string('value')->nullable(); $t->softDeletes(); });
        Invoice::withoutEvents(fn () => app(InvoiceFinanceService::class)->save(Invoice::first(), $this->data([
            'action'=>'payment', 'customer_received_now'=>25000, 'bank_received_now'=>50000,
        ]), 1));
        $controller=\Mockery::mock(\App\Http\Controllers\InvoiceReceiptController::class)->makePartial();
        $controller->shouldReceive('authorize')->with('view', \Mockery::type(Invoice::class))->andReturn(true);
        foreach (['customer', 'bank'] as $source) {
            $payment=Payment::where('source',$source)->firstOrFail();
            $response=$controller->pdf(new \Illuminate\Http\Request(['source'=>$source, 'payment'=>$payment->id]), Invoice::first(), app(InvoiceFinanceService::class));
            $this->assertStringStartsWith('%PDF-', $response->getContent());
            $this->assertStringContainsString('RCT-', $response->headers->get('Content-Disposition'));
        }
        $invoice=Invoice::first();$summary=app(InvoiceFinanceService::class)->summary($invoice);
        $html=view('crm.invoices.receipt-pdf', ['invoice'=>$invoice,'summary'=>$summary,'payments'=>Payment::where('source','bank')->get(), 'source'=>'bank','settings'=>collect(['company_name'=>'Test Solar']),'logo'=>null,'single'=>true,'reference'=>'RCT-TEST'])->render();
        foreach (['75,000.00', '150,000.00', '225,000.00', 'Test Bank', 'PAYMENT RECEIPT'] as $text) $this->assertStringContainsString($text,$html);
        $payment=Payment::where('source','bank')->first();$payment->update(['voided_at'=>now()]);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $controller->pdf(new \Illuminate\Http\Request(['source'=>'bank','payment'=>$payment->id]), Invoice::first(), app(InvoiceFinanceService::class));
    }
    public function test_daily_overview_shows_only_authorized_unreceived_bank_funding(): void {
        $user = new class extends \App\Models\User {
            public bool $financeAllowed = true;
            public function isAdmin(): bool { return false; }
            public function hasMatrixPermission(string $permission): bool { return $this->financeAllowed && $permission === 'view_invoices'; }
            public function can($abilities, $arguments = []) { return app(\App\Policies\InvoicePolicy::class)->view($this, $arguments); }
        };
        $user->id = 1;
        $this->actingAs($user);
        Invoice::withoutEvents(fn () => app(InvoiceFinanceService::class)->save(Invoice::first(), $this->data([
            'action'=>'payment', 'bank_received_now'=>50000, 'expected_bank_date'=>today()->subDay()->toDateString(),
        ]), 1));
        DB::table('invoices')->insert(['id'=>2,'customer_id'=>1,'user_id'=>2,'currency_id'=>1,'invoice_name'=>'Private','invoice_no'=>'INV-2','amount'=>300000,'status'=>'unpaid']);
        DB::table('invoice_finance_plans')->insert(['invoice_id'=>2,'funding_type'=>'bank','bank_name'=>'Private Bank','bank_amount'=>200000,'down_payment'=>0]);
        $overview = app(\App\Services\DailyOverviewService::class);
        $banks = $overview->bankReceivables($user);
        $this->assertCount(1, $banks);
        $this->assertSame('Test Bank', $banks[0]['bank']);
        $this->assertEquals(150000, $banks[0]['pending']);
        $this->assertEquals(150000, $banks[0]['overdue']);
        $this->assertSame('Customer 1', $banks[0]['items'][0]['customer']);
        $payment = Payment::where('source', 'bank')->first();
        Invoice::withoutEvents(fn () => app(InvoiceFinanceService::class)->void($payment, 'Reversed', 1));
        $this->assertEquals(200000, $overview->bankReceivables($user)[0]['pending']);
        $user->financeAllowed = false;
        $this->assertCount(0, $overview->bankReceivables($user));
    }
    public function test_plan_does_not_count_as_collection_and_receipts_split_correctly(): void {
        $service=app(InvoiceFinanceService::class);$invoice=Invoice::first();
        $summary=$service->save($invoice,$this->data(),1);$this->assertEquals(100000,$summary['customer_pending']);$this->assertEquals(200000,$summary['bank_pending']);$this->assertEquals(0,$summary['received']);
        $summary=$service->save($invoice,$this->data(['action'=>'payment','customer_received_now'=>50000]),1);$this->assertSame('partial',$summary['status']);$this->assertEquals(50000,$summary['customer_pending']);
        $summary=$service->save($invoice,$this->data(['action'=>'payment','bank_received_now'=>100000]),1);$this->assertEquals(100000,$summary['bank_received']);$this->assertEquals(100000,$summary['bank_pending']);
        $summary=$service->save($invoice,$this->data(['action'=>'payment','settlement'=>'full','customer_received_now'=>50000,'bank_received_now'=>100000]),1);
        $this->assertSame('paid',$summary['status']);$this->assertEquals(0,$summary['outstanding']);$this->assertEquals(0,$summary['bank_pending']);$this->assertEquals(0,$summary['customer_pending']);$this->assertSame(4,Payment::count());
    }
    public function test_duplicate_submissions_do_not_create_duplicate_receipts(): void {
        $service=app(InvoiceFinanceService::class);$data=$this->data(['action'=>'payment','customer_received_now'=>25000]);$service->save(Invoice::first(),$data,1);$service->save(Invoice::first(),$data,1);
        $this->assertSame(1,Payment::count());$this->assertSame(1,InvoiceFinanceEvent::count());
        $this->expectException(ValidationException::class);$data['customer_received_now']=50000;$service->save(Invoice::first(),$data,1);
    }
    public function test_overpayment_and_false_full_payment_are_rejected_atomically(): void {
        $service=app(InvoiceFinanceService::class);
        foreach([['action'=>'payment','customer_received_now'=>150000],['action'=>'payment','bank_received_now'=>250000],['action'=>'payment','settlement'=>'full','customer_received_now'=>1]]as$changes){try{$service->save(Invoice::first(),$this->data($changes),1);$this->fail('Invalid receipt accepted');}catch(ValidationException $e){$this->assertNotEmpty($e->errors());}}
        $this->assertSame(0,Payment::count());$this->assertSame(0,InvoiceFinanceEvent::count());
    }
    public function test_void_retains_audit_and_reopens_bank_balance(): void {
        $service=app(InvoiceFinanceService::class);$service->save(Invoice::first(),$this->data(['action'=>'payment','settlement'=>'full','customer_received_now'=>100000,'bank_received_now'=>200000]),1);
        $payment=Payment::where('source','bank')->first();$service->void($payment,'Wrong bank reference',1);$summary=$service->summary(Invoice::first());
        $this->assertSame('partial',$summary['status']);$this->assertEquals(200000,$summary['bank_pending']);$this->assertEquals(0,$summary['bank_received']);$this->assertSame(2,Payment::count());$this->assertNotNull($payment->fresh()->voided_at);$this->assertSame(2,InvoiceFinanceEvent::count());
    }
    public function test_legacy_paid_records_are_flagged_until_explicitly_reconciled(): void {
        DB::table('invoices')->where('id',1)->update(['status'=>'paid']);$service=app(InvoiceFinanceService::class);$summary=$service->summary(Invoice::first());
        $this->assertEquals(300000,$summary['legacy_unallocated']);$this->assertNull($summary['bank_pending']);
        try{$service->save(Invoice::first(),$this->data(),1);$this->fail('Legacy source silently assumed');}catch(ValidationException $e){$this->assertNotEmpty($e->errors());}
        $summary=$service->save(Invoice::first(),$this->data(['action'=>'payment','settlement'=>'full','customer_received_now'=>100000,'bank_received_now'=>200000]),1);
        $this->assertEquals(0,$summary['legacy_unallocated']);$this->assertEquals(200000,$summary['bank_received']);
    }

    public function test_bank_master_imports_existing_names_and_branches_belong_to_their_bank(): void {
        app(InvoiceFinanceService::class)->save(Invoice::first(),$this->data(),1);
        (require database_path('migrations/2026_10_26_000011_create_finance_bank_masters.php'))->up();
        $bank=DB::table('finance_banks')->first();$this->assertSame('Test Bank',$bank->name);
        $this->assertSame('Surat',DB::table('finance_bank_branches')->where('bank_id',$bank->id)->value('name'));
        $controller=app(\App\Http\Controllers\FinanceBankController::class);
        $controller->store(\Illuminate\Http\Request::create('/finance-banks','POST',['bank_id'=>$bank->id,'name'=>'Ahmedabad']));
        $controller->store(\Illuminate\Http\Request::create('/finance-banks','POST',['bank_id'=>$bank->id,'name'=>'Ahmedabad']));
        $this->assertSame(2,DB::table('finance_bank_branches')->where('bank_id',$bank->id)->count());
    }
    public function test_bank_filter_changes_report_rows_and_kpis_and_excludes_other_owners(): void {
        (require database_path('migrations/2026_10_26_000011_create_finance_bank_masters.php'))->up();
        $user=new class extends \App\Models\User {public function isAdmin(): bool{return false;}};
        $user->id=1;$this->actingAs($user);
        $service=app(InvoiceFinanceService::class);Invoice::withoutEvents(fn()=>$service->save(Invoice::first(),$this->data(['action'=>'payment','customer_received_now'=>50000,'bank_received_now'=>100000]),1));
        DB::table('finance_banks')->insert(['id'=>1,'name'=>'Test Bank']);
        DB::table('invoices')->insert(['id'=>2,'customer_id'=>1,'user_id'=>1,'currency_id'=>1,'invoice_name'=>'Other','invoice_no'=>'INV-2','amount'=>500000,'status'=>'unpaid']);
        DB::table('invoices')->insert(['id'=>3,'customer_id'=>1,'user_id'=>2,'currency_id'=>1,'invoice_name'=>'Restricted','invoice_no'=>'INV-3','amount'=>500000,'status'=>'unpaid']);
        config(['app.currency'=>'INR']);
        $request=\Illuminate\Http\Request::create('/invoice-receivables','GET',['bank_id'=>1]);
        $view=app(\App\Http\Controllers\InvoiceFinanceController::class)->index($request,$service)->getData();
        $this->assertSame(1,$view['invoices']->total());$this->assertSame('Surat',array_values($view['bankTotals'])[0]['branch']);$this->assertSame(1,array_values($view['bankTotals'])[0]['invoices']);$this->assertEquals(10000000,array_values($view['bankTotals'])[0]['pending']);
        $this->assertEquals(50000,$view['totals']['customer_pending']);$this->assertEquals(100000,$view['totals']['bank_pending']);$this->assertEquals(150000,$view['totals']['received']);
        DB::table('finance_bank_branches')->insert([['id'=>1,'bank_id'=>1,'name'=>'Surat'],['id'=>2,'bank_id'=>1,'name'=>'Other branch']]);
        $controller=app(\App\Http\Controllers\InvoiceFinanceController::class);
        $matching=$controller->index(\Illuminate\Http\Request::create('/invoice-receivables','GET',['branch_id'=>1]),$service)->getData();
        $this->assertSame(1,$matching['invoices']->total());$this->assertEquals(100000,$matching['totals']['bank_pending']);
        $empty=$controller->index(\Illuminate\Http\Request::create('/invoice-receivables','GET',['branch_id'=>2]),$service)->getData();
        $this->assertSame(0,$empty['invoices']->total());$this->assertEquals(0,$empty['totals']['received']);

    }

    public function test_customer_down_payment_and_seventy_percent_bank_release_leave_only_final_bank_balance(): void {
        $service=app(InvoiceFinanceService::class);
        $summary=$service->save(Invoice::first(),$this->data(['action'=>'payment','bank_amount'=>240000,'down_payment'=>60000,'customer_received_now'=>60000,'bank_received_now'=>168000]),1);
        $this->assertSame('partial',$summary['status']);$this->assertEquals(0,$summary['customer_pending']);$this->assertEquals(72000,$summary['bank_pending']);
        $summary=$service->save(Invoice::first(),$this->data(['action'=>'payment','settlement'=>'full','bank_amount'=>240000,'down_payment'=>60000,'bank_received_now'=>72000]),1);
        $this->assertSame('paid',$summary['status']);$this->assertEquals(0,$summary['outstanding']);$this->assertEquals(240000,$summary['bank_received']);
    }
}
