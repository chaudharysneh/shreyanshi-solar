<?php
namespace Tests\Feature;

use App\Http\Controllers\Api\EstimateController;
use App\Models\Estimate;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EstimateEquipmentTest extends TestCase
{
    private function controller()
    {
        $controller=\Mockery::mock(EstimateController::class)->makePartial();
        $controller->shouldReceive('authorize')->andReturn(true);
        return $controller;
    }
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']);DB::purge('sqlite');
        Schema::create('estimates',function (Blueprint $table) {
            $table->id('estimate_id');$table->decimal('quantity',10,2);$table->string('status');$table->json('equipment_serials')->nullable();$table->timestamps();$table->softDeletes();
        });
        DB::table('estimates')->insert(['estimate_id'=>1,'quantity'=>3,'status'=>'approved']);
    }
    public function test_capacity_generates_panel_count(): void
    {
        $this->assertSame(6,$this->controller()->equipment('1')->getData(true)['count']);
        DB::table('estimates')->where('estimate_id',1)->update(['quantity'=>3.2]);
        $this->assertSame(7,$this->controller()->equipment('1')->getData(true)['count']);
    }
    public function test_optional_serials_and_notes_are_saved_and_survive_skipping(): void
    {
        $equipment=['panels'=>['P-1','',null],'inverters'=>['I-1'],'notes'=>'Equipment remarks'];
        $response=Estimate::withoutEvents(fn ()=>$this->controller()->updateStatus(new Request(['status'=>'approved','equipment_serials'=>$equipment]),'1'));
        $this->assertSame(200,$response->status());
        $this->assertSame($equipment,Estimate::findOrFail(1)->equipment_serials);
        $response=Estimate::withoutEvents(fn ()=>$this->controller()->updateStatus(new Request(['status'=>'approved']),'1'));
        $this->assertSame(200,$response->status());
        $this->assertSame($equipment,Estimate::findOrFail(1)->equipment_serials);
    }
    public function test_custom_panel_ratio_is_saved_and_controls_serial_field_limits(): void
    {
        $equipment=['panels_per_kw'=>1,'panels'=>['P1','P2','P3'],'inverters'=>['I1','I2','I3']];
        $response=Estimate::withoutEvents(fn ()=>$this->controller()->updateStatus(new Request(['status'=>'approved','equipment_serials'=>$equipment]),'1'));
        $this->assertSame(200,$response->status());
        $this->assertSame(1,Estimate::findOrFail(1)->equipment_serials['panels_per_kw']);
        $this->assertSame(3,$this->controller()->equipment('1')->getData(true)['count']);
        $equipment['panels'][]='P4';
        $this->assertSame(422,$this->controller()->updateStatus(new Request(['status'=>'approved','equipment_serials'=>$equipment]),'1')->status());
        $equipment['panels_per_kw']=9;
        $this->assertSame(422,$this->controller()->updateStatus(new Request(['status'=>'approved','equipment_serials'=>$equipment]),'1')->status());
    }

    public function test_shared_editor_updates_estimate_invoice_and_customer_without_reapproving(): void
    {
        Schema::table('estimates',fn (Blueprint $table)=>$table->integer('customer_id')->default(1));
        Schema::create('customers',function (Blueprint $table) { $table->id();$table->softDeletes(); });
        Schema::create('invoices',function (Blueprint $table) { $table->id();$table->integer('estimate_id');$table->integer('customer_id');$table->string('status');$table->softDeletes(); });
        DB::table('customers')->insert(['id'=>1]);
        DB::table('invoices')->insert(['id'=>1,'estimate_id'=>1,'customer_id'=>1,'status'=>'paid']);
        $controller=\Mockery::mock(\App\Http\Controllers\Api\EquipmentController::class)->makePartial();
        $controller->shouldReceive('authorize')->andReturn(true);
        $actor=new class { public function hasMatrixPermission($permission) { return true; } };
        $equipment=['panels_per_kw'=>1,'panels'=>['P1','P2','P3'],'inverters'=>['I1'],'notes'=>'Saved from invoice'];
        $request=new Request(['invoice_id'=>1,'equipment_serials'=>$equipment]);$request->setUserResolver(fn ()=>$actor);
        $response=Estimate::withoutEvents(fn ()=>$controller->update($request,Estimate::findOrFail(1)));
        $this->assertSame('Complete',$response->getData(true)['progress']);
        $this->assertSame('Complete',\App\Models\Invoice::findOrFail(1)->estimate->equipment_progress);
        $this->assertSame('approved',Estimate::findOrFail(1)->status);
        $this->assertSame('paid',DB::table('invoices')->where('id',1)->value('status'));
        $this->assertSame(1,DB::table('invoices')->count());
        $read=new Request(['customer_id'=>1]);$read->setUserResolver(fn ()=>$actor);
        $this->assertSame($equipment,$controller->show($read,Estimate::findOrFail(1))->getData(true)['equipment']);
        $equipment['inverters'][2]='';
        $request=new Request(['customer_id'=>1,'equipment_serials'=>$equipment]);$request->setUserResolver(fn ()=>$actor);
        $response=Estimate::withoutEvents(fn ()=>$controller->update($request,Estimate::findOrFail(1)));
        $this->assertSame('Partial',$response->getData(true)['progress']);
        $this->assertSame('Partial',\App\Models\Invoice::findOrFail(1)->estimate->equipment_progress);
    }

    public function test_progress_counts_all_required_serials_and_ignores_notes(): void
    {
        $estimate=Estimate::findOrFail(1);
        $this->assertSame('Pending',$estimate->equipment_progress);
        $estimate->equipment_serials=['panels_per_kw'=>1,'panels'=>['P1','P2','P3'],'inverters'=>['I1','I2','  '],'notes'=>'Done'];
        $this->assertSame('Partial',$estimate->equipment_progress);
        $estimate->equipment_serials=['panels_per_kw'=>1,'panels'=>['P1','P2','P3'],'inverters'=>['I1','I2','I3']];
        $this->assertSame('Complete',$estimate->equipment_progress);
    }

    public function test_inverter_quantity_is_independent_of_panel_quantity(): void
    {
        $equipment=['panels_per_kw'=>1,'panels'=>['P1','P2','P3'],'inverters'=>['I1']];
        $response=Estimate::withoutEvents(fn ()=>$this->controller()->updateStatus(new Request(['status'=>'approved','equipment_serials'=>$equipment]),'1'));
        $this->assertSame(200,$response->status());
        $this->assertSame('Complete',Estimate::findOrFail(1)->equipment_progress);
        $equipment['inverters']=['I1','I2','I3','I4'];
        $response=Estimate::withoutEvents(fn ()=>$this->controller()->updateStatus(new Request(['status'=>'approved','equipment_serials'=>$equipment]),'1'));
        $this->assertSame(200,$response->status());
        $this->assertSame($equipment,Estimate::findOrFail(1)->equipment_serials);
    }

    public function test_shared_editor_rejects_a_different_customer_context(): void
    {
        Schema::table('estimates',fn (Blueprint $table)=>$table->integer('customer_id')->default(1));
        Schema::create('customers',function (Blueprint $table) { $table->id();$table->softDeletes(); });
        DB::table('customers')->insert(['id'=>2]);
        $request=new Request(['customer_id'=>2]);$request->setUserResolver(fn ()=>new class {public function hasMatrixPermission($permission){return true;}});
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        (new \App\Http\Controllers\Api\EquipmentController)->show($request,Estimate::findOrFail(1));
    }

    public function test_duplicate_serials_and_excess_entries_are_rejected(): void
    {
        $response=$this->controller()->updateStatus(new Request(['status'=>'approved','equipment_serials'=>['panels'=>['P-1','P-1']]]),'1');
        $this->assertSame(422,$response->status());
        $this->assertArrayHasKey('equipment_serials.panels',$response->getData(true)['errors']);
        $response=$this->controller()->updateStatus(new Request(['status'=>'approved','equipment_serials'=>['panels'=>array_fill(0,7,'')]]),'1');
        $this->assertSame(422,$response->status());
        $this->assertNull(Estimate::findOrFail(1)->equipment_serials);
    }
}
