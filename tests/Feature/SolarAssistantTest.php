<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SolarAssistantTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\Authenticate::class);
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']);DB::purge('sqlite');
        Schema::create('customers',function (Blueprint $t) {$t->id();$t->string('name');$t->string('phone')->nullable();$t->string('email')->nullable();$t->timestamps();$t->softDeletes();});
        Schema::create('estimates',function (Blueprint $t) {$t->id('estimate_id');$t->integer('user_id');$t->string('estimate_name');$t->string('estimate_no');$t->string('status');$t->integer('quantity')->default(1);$t->decimal('amount')->default(1000);$t->string('currency')->default('INR');$t->json('equipment_serials')->nullable();$t->timestamps();$t->softDeletes();});
        Schema::create('invoices',function (Blueprint $t) {$t->id();$t->integer('user_id');$t->string('invoice_name');$t->string('invoice_no');$t->string('status');$t->date('due_date');$t->integer('currency_id')->nullable();$t->integer('estimate_id')->nullable();$t->decimal('amount')->default(1000);$t->timestamps();$t->softDeletes();});
        Schema::create('currencies',function (Blueprint $t) {$t->id();$t->string('code');$t->boolean('is_active')->default(true);});
        DB::table('currencies')->insert(['id'=>1,'code'=>'INR']);
        Schema::create('channel_partners',function (Blueprint $t) {$t->id();$t->string('name');$t->string('phone')->nullable();$t->string('status')->default('active');$t->timestamps();});
        Carbon::setTestNow(Carbon::parse('2028-10-24 10:00:00','Asia/Kolkata'));
        $this->login(['view_customers','view_estimates','view_invoices','view_partners']);
    }
    protected function tearDown(): void {Carbon::setTestNow();parent::tearDown();}
    private function login(array $permissions): void
    {
        $user=new class extends User {
            public array $allowed=[];
            public function isAdmin(): bool {return false;}
            public function hasMatrixPermission(string $permission): bool {return in_array($permission,$this->allowed);}
            public function can($abilities,$arguments=[]): bool {return true;}
        };
        $user->id=1;$user->allowed=$permissions;$this->actingAs($user);
    }
    public function test_catalog_respects_module_permissions_and_queries_cannot_bypass_them(): void
    {
        $this->login(['view_customers']);
        $this->postJson('/crm-assistant',['action'=>'catalog'])->assertOk()->assertJsonCount(1,'modules')->assertJsonPath('modules.0.key','customers');
        $this->postJson('/crm-assistant',['message'=>'unpaid invoices'])->assertOk()->assertJsonPath('data',[])->assertSee('permission');
        $this->postJson('/crm-assistant',['module'=>'invoices','period'=>'all'])->assertOk()->assertJsonPath('data',[]);
        $this->postJson('/crm-assistant',['message'=>'partner 1 report'])->assertOk()->assertSee('permission');
    }
    public function test_structured_queries_search_pagination_and_future_relative_dates(): void
    {
        for($i=0;$i<13;$i++) DB::table('customers')->insert(['name'=>'Customer '.$i,'created_at'=>'2028-10-24 06:00:00']);
        DB::table('customers')->insert(['name'=>'Previous month','created_at'=>'2028-09-15 06:00:00']);
        $this->postJson('/crm-assistant',['module'=>'customers','period'=>'today'])->assertOk()->assertJsonCount(10,'data')->assertJsonPath('next.page',2)->assertSee('2028');
        $this->postJson('/crm-assistant',['module'=>'customers','period'=>'today','page'=>2])->assertOk()->assertJsonCount(3,'data')->assertJsonPath('next',null);
        $this->postJson('/crm-assistant',['message'=>'customers last month'])->assertOk()->assertJsonCount(1,'data')->assertJsonPath('data.0.name','Previous month');
        $this->postJson('/crm-assistant',['message'=>'find customer Customer 12'])->assertOk()->assertJsonCount(1,'data')->assertJsonPath('data.0.name','Customer 12');
    }
    public function test_estimate_queries_are_scoped_and_equipment_progress_uses_saved_serials(): void
    {
        foreach([[1,'Visible'],[2,'Private']] as [$owner,$name]) DB::table('estimates')->insert(['user_id'=>$owner,'estimate_name'=>$name,'estimate_no'=>'000001','status'=>'approved']);
        $this->postJson('/crm-assistant',['message'=>'approved estimates'])->assertOk()->assertJsonCount(1,'data')->assertJsonPath('data.0.name','Visible');
        $this->postJson('/crm-assistant',['message'=>'equipment pending'])->assertOk()->assertJsonCount(1,'data');
        DB::table('estimates')->where('user_id',1)->update(['equipment_serials'=>json_encode(['panels_per_kw'=>1,'panels'=>['P1'],'inverters'=>['I1']])]);
        $this->postJson('/crm-assistant',['message'=>'equipment complete'])->assertOk()->assertJsonCount(1,'data');
        $this->postJson('/crm-assistant',['message'=>'equipment pending'])->assertOk()->assertJsonCount(0,'data');
    }
    public function test_overdue_queries_exclude_paid_records_and_assistant_does_not_write_status_changes(): void
    {
        foreach(['paid','unpaid'] as $status) DB::table('invoices')->insert(['user_id'=>1,'invoice_name'=>$status,'invoice_no'=>'INV1','status'=>$status,'due_date'=>'2028-10-20','currency_id'=>1]);
        $this->postJson('/crm-assistant',['message'=>'overdue invoices'])->assertOk()->assertJsonCount(1,'data')->assertJsonPath('data.0.name','unpaid');
        $this->postJson('/crm-assistant',['message'=>'mark invoice 2 paid'])->assertOk()->assertSee('CRM page');
        $this->assertSame('unpaid',DB::table('invoices')->where('id',2)->value('status'));
        $this->postJson('/crm-assistant',['message'=>str_repeat('x',1001)])->assertUnprocessable();
    }
    public function test_partner_report_reuses_shared_financial_calculations(): void
    {
        DB::table('channel_partners')->insert(['id'=>1,'name'=>'Partner One']);
        $this->mock(\App\Services\PartnerReportService::class)->shouldReceive('build')->once()->andReturn(['leads'=>4,'converted'=>2,'collected'=>10000,'earned'=>1000,'paid'=>250,'pending'=>750,'net_after_commission'=>9000,'currency'=>'INR']);
        $this->postJson('/crm-assistant',['message'=>'Partner 1 report'])->assertOk()->assertJsonPath('data.0.name','Partner One')->assertSee('750.00 INR')->assertSee('not net profit');
    }

    public function test_global_search_groups_results_links_to_records_and_respects_permissions(): void
    {
        DB::table('customers')->insert(['id'=>1,'name'=>'Search Customer','phone'=>'9876543210']);
        DB::table('channel_partners')->insert(['name'=>'Search Partner']);
        $this->login(['view_customers']);
        $this->getJson('/global-search?q=Search')->assertOk()->assertJsonCount(1,'groups')->assertJsonPath('groups.0.key','customers')->assertJsonPath('groups.0.items.0.name','Search Customer')->assertJsonPath('groups.0.items.0.url',route('masters.customers.show',1));
        $this->getJson('/global-search?q=Search&module=partners')->assertOk()->assertJsonCount(0,'groups');
        $this->getJson('/global-search?q=987654')->assertOk()->assertJsonPath('groups.0.items.0.id',1);
        $this->getJson('/global-search?q=S')->assertOk()->assertJsonCount(0,'groups');
    }
    public function test_global_search_paginates_module_results(): void
    {
        for($i=1;$i<=11;$i++)DB::table('customers')->insert(['name'=>'Matching customer '.$i]);
        $this->login(['view_customers']);
        $this->getJson('/global-search?q=Matching&module=customers')->assertOk()->assertJsonCount(8,'groups.0.items')->assertJsonPath('groups.0.has_more',true);
        $this->getJson('/global-search?q=Matching&module=customers&page=2')->assertOk()->assertJsonCount(3,'groups.0.items')->assertJsonPath('groups.0.has_more',false);
    }

    public function test_global_search_finds_module_names_and_aliases_even_without_named_records(): void
    {
        $this->login(['view_customers','view_partners']);
        DB::table('customers')->insert(['name'=>'Ravi']);
        $this->getJson('/global-search?q=customers')->assertOk()->assertJsonPath('groups.0.key','customers')->assertJsonPath('groups.0.items.0.name','Ravi');
        $this->getJson('/global-search?q=Manage%20Customers')->assertOk()->assertJsonPath('groups.0.key','customers');
        $this->getJson('/global-search?q=channel%20partner')->assertOk()->assertJsonPath('groups.0.key','partners')->assertJsonCount(0,'groups.0.items');
        $this->getJson('/global-search?q=deals')->assertOk()->assertJsonCount(0,'groups');
    }
}
