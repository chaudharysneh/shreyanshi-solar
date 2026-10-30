<?php

namespace Tests\Feature;

use App\Models\ChannelPartner;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PartnerModuleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\Authenticate::class);
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) { $table->id(); $table->string('name'); });
        DB::table('users')->insert(['id' => 1, 'name' => 'Assigned Staff']);
        Schema::create('leads', function (Blueprint $table) {
            $table->id(); $table->string('name')->nullable(); $table->string('phone')->nullable();
            $table->string('source')->nullable(); $table->unsignedBigInteger('lead_source_id')->nullable();
            $table->string('status')->default('new'); $table->boolean('is_converted')->default(false);
            $table->unsignedBigInteger('converted_customer_id')->nullable();
            $table->timestamps(); $table->softDeletes();
        });
        (require database_path('migrations/2026_03_05_115941_create_lead_sources_table.php'))->up();
        (require database_path('migrations/2026_10_26_000001_add_lead_source_details_and_channel_partners.php'))->up();
        (require database_path('migrations/2026_10_26_000002_expand_channel_partner_master.php'))->up();
        (require database_path('migrations/2026_10_26_000003_add_partner_payment_reporting.php'))->up();
        // Exercise this module's views without requiring unrelated dashboard settings or tables.
        app('view')->getFinder()->prependLocation(base_path('tests/Fixtures/views'));
        Storage::fake('local');
        $this->login();
    }

    private function login(array $permissions = ['view_partners','create_partners','edit_partners','delete_partners','create_leads','view_leads']): void
    {
        $user = new class extends User {
            public array $allowed = [];
            public function isAdmin(): bool { return true; }
            public function hasMatrixPermission(string $permission): bool { return in_array($permission, $this->allowed); }
        };
        $user->id = 1; $user->allowed = $permissions;
        $this->actingAs($user);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name'=>'Solar Associates', 'phone'=>'9876543210', 'whatsapp'=>'9876543210',
            'email'=>'partner@example.test', 'address'=>'Ahmedabad', 'company_name'=>'Solar Agency',
            'status'=>'active', 'assigned_user_id'=>1, 'joining_date'=>'2026-10-26',
            'commission_type'=>'percent', 'commission_value'=>'5.50', 'commission_basis'=>'amount_received',
            'payable_on'=>'payment_collection', 'notes'=>'Partner notes',
        ], $overrides);
    }

    public function test_create_edit_and_profile_preserve_the_partner_fields_and_unique_codes(): void
    {
        $this->get('/partners/create')->assertOk()->assertSee('Copy Phone')->assertSee('partner-select');
        $this->post('/partners', $this->payload())->assertRedirect();
        $partner = ChannelPartner::firstOrFail();
        $this->assertSame('CP-000001', $partner->partner_code);
        $this->assertSame('5.50', $partner->commission_value);
        $this->assertSame('2026-10-26', $partner->joining_date->toDateString());
        $this->get('/partners/'.$partner->id)->assertOk()->assertSee('Solar Associates')->assertSee('Referred Leads');
        $this->get('/partners/'.$partner->id.'/edit')->assertOk()->assertSee('partner@example.test');
        $this->put('/partners/'.$partner->id, $this->payload(['name'=>'Updated Partner', 'commission_type'=>'fixed','commission_value'=>'1200','status'=>'inactive']))->assertRedirect();
        $this->assertSame('Updated Partner', $partner->fresh()->name);
        $this->assertSame('1200.00', $partner->fresh()->commission_value);
        $this->assertSame('CP-000001', $partner->fresh()->partner_code);
    }

    public function test_validation_rejects_invalid_commission_email_assignment_and_agreements(): void
    {
        $this->postJson('/partners', $this->payload(['commission_value'=>101]))->assertUnprocessable()->assertJsonValidationErrors('commission_value');
        $this->postJson('/partners', $this->payload(['commission_value'=>-1,'email'=>'bad','assigned_user_id'=>999,'agreement'=>UploadedFile::fake()->create('bad.exe',10)]))->assertUnprocessable()->assertJsonValidationErrors(['commission_value','email','assigned_user_id','agreement']);
        $this->assertSame(0, ChannelPartner::count());
    }

    public function test_listing_kpis_filters_pagination_and_export_use_saved_partner_records(): void
    {
        $first = ChannelPartner::create($this->payload());
        ChannelPartner::create($this->payload(['name'=>'Inactive Partner','status'=>'inactive']));
        DB::table('leads')->insert([
            ['name'=>'Lead one','channel_partner_id'=>$first->id,'is_converted'=>false],
            ['name'=>'Lead two','channel_partner_id'=>$first->id,'is_converted'=>true],
        ]);
        $list = $this->getJson('/partners')->assertOk()->assertJsonPath('kpis', [2,1,2,1]);
        $this->assertStringContainsString('Solar Associates', $list->json('html'));
        $this->assertStringContainsString('Inactive Partner', $list->json('html'));
        $response = $this->getJson('/partners?status=active&search=Solar');
        $response->assertOk()->assertJsonPath('kpis',[1,1,2,1]);
        $this->assertStringNotContainsString('Inactive Partner', $response->json('html'));
        $this->get('/partners')->assertOk()->assertSee('Manage Partners')->assertSee('Converted Leads');
        $csv = $this->get('/partners/export?status=active')->assertOk()->streamedContent();
        $this->assertStringContainsString('Solar Associates',$csv);
        $this->assertStringNotContainsString('Inactive Partner',$csv);
    }

    public function test_used_partners_are_not_deleted_and_inactive_partners_are_not_offered_for_new_leads(): void
    {
        $partner = ChannelPartner::create($this->payload(['status'=>'inactive']));
        DB::table('leads')->insert(['channel_partner_id'=>$partner->id]);
        $this->delete('/partners/'.$partner->id)->assertSessionHas('error');
        $this->assertNotNull($partner->fresh());
        $this->getJson('/api/channel-partners')->assertOk()->assertJsonCount(0,'results');
        $unused = ChannelPartner::create($this->payload(['name'=>'Unused']));
        $this->delete('/partners/'.$unused->id)->assertRedirect(route('partners.index'));
        $this->assertNull($unused->fresh());
    }

    public function test_agreements_are_stored_privately_and_can_be_replaced_and_downloaded(): void
    {
        $this->post('/partners',$this->payload(['agreement'=>UploadedFile::fake()->create('agreement.pdf',20,'application/pdf')]))->assertRedirect();
        $partner = ChannelPartner::firstOrFail();
        $oldPath = $partner->agreement_path;
        Storage::disk('local')->assertExists($oldPath);
        $this->get('/partners/'.$partner->id.'/agreement')->assertOk()->assertDownload('agreement.pdf');
        $this->put('/partners/'.$partner->id,$this->payload(['agreement'=>UploadedFile::fake()->create('updated.pdf',20,'application/pdf')]))->assertRedirect();
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($partner->fresh()->agreement_path);
    }

    public function test_partner_permissions_protect_read_write_and_download_routes(): void
    {
        $partner = ChannelPartner::create($this->payload());
        $this->login([]);
        $this->get('/partners')->assertForbidden();
        $this->get('/partners/create')->assertForbidden();
        $this->post('/partners',$this->payload())->assertForbidden();
        $this->put('/partners/'.$partner->id,$this->payload())->assertForbidden();
        $this->delete('/partners/'.$partner->id)->assertForbidden();
        $this->get('/partners/'.$partner->id.'/agreement')->assertForbidden();
        $this->post('/partners/'.$partner->id.'/payments',[])->assertForbidden();
    }

    private function financialFixture(): ChannelPartner
    {
        Schema::create('customers',function (Blueprint $table) { $table->id();$table->string('name');$table->softDeletes(); });
        Schema::create('currencies',function (Blueprint $table) { $table->id();$table->string('code'); });
        Schema::create('estimates',function (Blueprint $table) {
            $table->id('estimate_id');$table->integer('customer_id');$table->decimal('total',14,2);$table->string('currency')->nullable();$table->string('status')->default('approved');$table->boolean('isDeleted')->nullable()->default(false);$table->softDeletes();
        });
        Schema::create('invoices',function (Blueprint $table) {
            $table->timestamps();
            $table->id();$table->integer('customer_id');$table->integer('currency_id')->nullable();$table->integer('estimate_id')->nullable();$table->integer('updated_by')->nullable();$table->decimal('amount',14,2);$table->string('status')->default('unpaid');$table->boolean('is_quotation')->default(false);$table->softDeletes();
        });
        Schema::create('payments',function (Blueprint $table) { $table->id();$table->integer('invoice_id');$table->decimal('amount',14,2); });
        Schema::create('installations',function (Blueprint $table) { $table->id();$table->integer('customer_id');$table->string('status'); });
        DB::table('currencies')->insert([['id'=>1,'code'=>'INR'],['id'=>2,'code'=>'USD']]);
        DB::table('customers')->insert([['id'=>100,'name'=>'First customer'],['id'=>200,'name'=>'Second customer']]);
        $partner = ChannelPartner::create($this->payload(['commission_value'=>5]));
        foreach ([100,100,200] as $customerId) DB::table('leads')->insert(['name'=>'Referred lead','channel_partner_id'=>$partner->id,'is_converted'=>true,'converted_customer_id'=>$customerId,'created_at'=>'2026-10-01']);
        foreach ([100=>1000,200=>500] as $id=>$amount) DB::table('estimates')->insert(['customer_id'=>$id,'total'=>$amount,'currency'=>'INR']);
        DB::table('estimates')->insert(['customer_id'=>100,'total'=>9000,'currency'=>'INR','status'=>'draft']);
        foreach ([[100,1,1180],[200,1,590],[100,2,100]] as [$customer,$currency,$amount]) {
            $id=DB::table('invoices')->insertGetId(['customer_id'=>$customer,'currency_id'=>$currency,'amount'=>$amount]);
            DB::table('payments')->insert(['invoice_id'=>$id,'amount'=>$currency===2 ? 100 : 590]);
        }
        DB::table('installations')->insert(['customer_id'=>100,'status'=>'completed']);
        return $partner;
    }

    public function test_dashboard_calculates_revenue_and_commission_once_per_customer_without_mixing_currencies(): void
    {
        $partner=$this->financialFixture();
        $report=app(\App\Services\PartnerReportService::class)->build($partner);
        $this->assertSame(3,$report['leads']);$this->assertSame(3,$report['converted']);
        $this->assertSame(2,$report['attributed_customers']);
        $this->assertEquals(1500,$report['approved_subtotal']);$this->assertEquals(1770,$report['billed']);
        $this->assertEquals(1180,$report['collected']);$this->assertEquals(59,$report['earned']);
        $this->assertEquals(59,$report['pending']);$this->assertEquals(1121,$report['net_after_commission']);
        $this->assertSame(1,$report['excluded_invoices']);
        $this->get('/partners/'.$partner->id)->assertOk()->assertSee('Lead &amp; Conversion Trend',false)->assertSee('1,121.00 INR')->assertSee('Record Payment');
    }

    public function test_legacy_currency_and_status_only_invoice_payment_follow_the_real_workflow(): void
    {
        $partner=$this->financialFixture();
        $service=app(\App\Services\PartnerReportService::class);
        DB::table('estimates')->where('status','approved')->update(['currency'=>null,'isDeleted'=>null]);
        DB::table('estimates')->where('estimate_id',1)->update(['status'=>'pending']);
        DB::table('invoices')->whereIn('id',[1,2])->update(['currency_id'=>null]);
        DB::table('invoices')->where('id',1)->update(['estimate_id'=>1]);
        DB::table('payments')->where('invoice_id',1)->delete();
        $report=$service->build($partner);
        $this->assertEquals(1500,$report['estimated']);
        $this->assertEquals(500,$report['approved_subtotal']);
        $this->assertEquals(1770,$report['billed']);
        $this->assertEquals(590,$report['collected']);
        DB::table('estimates')->where('estimate_id',1)->update(['status'=>'approved']);
        DB::table('invoices')->where('id',1)->update(['status'=>'paid']);
        $report=$service->build($partner);
        $this->assertEquals(1500,$report['approved_subtotal']);
        $this->assertEquals(1770,$report['collected']);
        $this->assertEquals(88.5,$report['earned']);
        DB::table('payments')->insert(['invoice_id'=>1,'amount'=>100]);
        $this->assertEquals(1770,$service->build($partner)['collected']);
        $this->assertEquals(1,$report['excluded_invoices']); // Explicit USD remains excluded.
    }

    public function test_legacy_converted_invoice_inherits_explicit_estimate_currency_before_default(): void
    {
        $partner=$this->financialFixture();
        DB::table('estimates')->where('estimate_id',1)->update(['currency'=>'USD']);
        DB::table('invoices')->where('id',1)->update(['currency_id'=>null,'estimate_id'=>1,'status'=>'paid']);
        $report=app(\App\Services\PartnerReportService::class)->build($partner);
        $this->assertEquals(590,$report['billed']);
        $this->assertEquals(590,$report['collected']);
        $this->assertEquals(500,$report['approved_subtotal']);
        $this->assertSame(2,$report['excluded_invoices']);
    }

    public function test_marking_an_invoice_unpaid_preserves_the_original_estimate_link(): void
    {
        $this->financialFixture();
        DB::table('invoices')->where('id',1)->update(['estimate_id'=>1,'status'=>'paid']);
        $this->mock(\App\Services\UserLogService::class)->shouldReceive('updated')->once();
        $response=\App\Models\Invoice::withoutEvents(fn () => (new \App\Http\Controllers\Api\InvoiceController)->updateStatus(new \Illuminate\Http\Request(['status'=>'unpaid']),\App\Models\Invoice::findOrFail(1)));
        $this->assertSame(200,$response->status());
        $this->assertEquals(1,DB::table('invoices')->where('id',1)->value('estimate_id'));
    }

    public function test_direct_customer_sources_validate_save_and_appear_in_partner_reports(): void
    {
        $partner=$this->financialFixture();
        (require database_path('migrations/2026_10_26_000004_add_customer_source_details.php'))->up();
        $source=\App\Models\LeadSource::where('kind','channel_partner')->firstOrFail();
        $request=new \Illuminate\Http\Request(['lead_source_id'=>$source->id,'channel_partner_id'=>$partner->id]);
        $rules=\App\Support\CustomerSourceDetails::rules($request);
        $this->assertTrue(\Illuminate\Support\Facades\Validator::make($request->all(),$rules)->passes());
        $this->assertFalse(\Illuminate\Support\Facades\Validator::make(['lead_source_id'=>$source->id],$rules)->passes());
        $data=\App\Support\CustomerSourceDetails::normalize($request->all()+['referral_name'=>'Stale']);
        $this->assertNull($data['referral_name']);
        DB::table('customers')->insert($data+['id'=>300,'name'=>'Direct partner customer']);
        DB::table('estimates')->insert(['customer_id'=>300,'total'=>1000,'currency'=>'INR']);
        DB::table('invoices')->insert(['customer_id'=>300,'currency_id'=>1,'amount'=>1000,'status'=>'paid']);
        $report=app(\App\Services\PartnerReportService::class)->build($partner);
        $this->assertSame(3,$report['attributed_customers']);
        $this->assertEquals(2180,$report['collected']);
        $this->assertEquals(109,$report['earned']);
        $this->assertSame(0,$report['excluded_customers']);
        $customer=\App\Models\Customer::findOrFail(300);
        $html=view('masters.customers.partials.source-fields',['customer'=>$customer,'errors'=>new \Illuminate\Support\ViewErrorBag])->render();
        $this->assertStringContainsString('Customer Source',$html);
        $this->assertStringContainsString('selected>Solar Associates</option>',$html);
        $cleared=\App\Support\CustomerSourceDetails::normalize(['lead_source_id'=>null],$customer);
        $this->assertNull($cleared['channel_partner_id']);
        $referral=\App\Models\LeadSource::where('kind','referral')->firstOrFail();
        $referralRequest=new \Illuminate\Http\Request(['lead_source_id'=>$referral->id]);
        $this->assertArrayHasKey('referral_name',\Illuminate\Support\Facades\Validator::make($referralRequest->all(),\App\Support\CustomerSourceDetails::rules($referralRequest))->errors()->toArray());
    }

    public function test_commission_milestones_and_attribution_avoid_double_crediting_different_partners(): void
    {
        $partner=$this->financialFixture();
        $partner->update(['commission_type'=>'fixed','commission_value'=>100,'payable_on'=>'conversion']);
        $service=app(\App\Services\PartnerReportService::class);
        $this->assertEquals(200,$service->build($partner)['earned']);
        $partner->update(['payable_on'=>'installation']);
        $this->assertEquals(100,$service->build($partner)['earned']);
        $partner->update(['commission_type'=>'percent','commission_value'=>5,'commission_basis'=>'estimate_subtotal']);
        $this->assertEquals(50,$service->build($partner)['earned']);
        $other=ChannelPartner::create($this->payload(['name'=>'Other partner']));
        DB::table('leads')->insert(['channel_partner_id'=>$other->id,'is_converted'=>true,'converted_customer_id'=>100,'created_at'=>'2026-09-01']);
        $report=$service->build($partner);
        $this->assertSame(1,$report['excluded_customers']);
        $this->assertEquals(0,$report['earned']);
        $this->assertEquals(590,$report['collected']);
    }

    public function test_recording_and_voiding_payments_updates_balances_and_blocks_duplicates_and_overpayment(): void
    {
        $partner=$this->financialFixture();
        $payment=['amount'=>20,'payment_date'=>now()->timezone('Asia/Kolkata')->toDateString(),'method'=>'upi','reference'=>'TX-1','submission_key'=>(string) \Illuminate\Support\Str::uuid()];
        $url='/partners/'.$partner->id.'/payments';
        $this->post($url,$payment)->assertRedirect();$this->post($url,$payment)->assertRedirect();
        $this->assertSame(1,$partner->payments()->count());
        $report=app(\App\Services\PartnerReportService::class)->build($partner);
        $this->assertEquals(20,$report['paid']);$this->assertEquals(39,$report['pending']);
        $this->postJson($url,array_merge($payment,['amount'=>40,'submission_key'=>(string) \Illuminate\Support\Str::uuid()]))->assertUnprocessable()->assertJsonValidationErrors('amount');
        $this->put('/partners/'.$partner->id,$this->payload(['commission_currency'=>'USD']))->assertSessionHasErrors('commission_currency');
        $record=$partner->payments()->firstOrFail();
        $this->post($url.'/'.$record->id.'/void',['void_reason'=>'Entered in error'])->assertRedirect();
        $this->assertNotNull($record->fresh()->voided_at);$this->assertSame(1,$partner->payments()->count());
        $this->assertEquals(59,app(\App\Services\PartnerReportService::class)->build($partner)['pending']);
        $this->get('/partners/'.$partner->id)->assertOk()->assertSee('Entered in error');
    }
}
