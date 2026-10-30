<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
use App\Services\LeadConversionService;
use App\Services\WorkflowService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LeadConversionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach (['leads', 'customers'] as $name) {
            \Illuminate\Support\Facades\Schema::create($name, function (Blueprint $table) use ($name) {
                $table->id(); $table->string('name');
                foreach (['email', 'phone', 'whatsapp', 'address', 'company_name', 'image'] as $field) $table->string($field)->nullable();
                if ($name === 'leads') { $table->boolean('is_converted')->default(false); $table->integer('converted_customer_id')->nullable(); }
                else { $table->string('type')->nullable(); $table->boolean('is_active')->default(true); $table->integer('pipeline_stage_id')->nullable(); }
                $table->timestamps(); $table->softDeletes();
            });
        }
        \Illuminate\Support\Facades\Schema::create('stages', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->integer('sort_order'); $table->boolean('is_active');
        });
        \Illuminate\Support\Facades\Schema::create('notifications', function (Blueprint $table) {
            $table->id(); $table->integer('user_id'); $table->text('notification_text'); $table->boolean('is_read'); $table->timestamps();
        });
        DB::table('stages')->insert([
            ['id' => 1, 'name' => 'Inactive', 'sort_order' => 0, 'is_active' => false],
            ['id' => 2, 'name' => 'Document Verification', 'sort_order' => 1, 'is_active' => true],
            ['id' => 3, 'name' => 'Registration', 'sort_order' => 2, 'is_active' => true],
        ]);
        DB::table('leads')->insert(['id' => 1, 'name' => 'Conversion test', 'phone' => '9876543210']);
        $user = new class extends User { public function isAdmin(): bool { return true; } };
        $user->id = 1;
        $this->actingAs($user);
    }

    public function test_first_active_stage_is_initialized_and_retry_does_not_duplicate(): void
    {
        $workflow = $this->mock(WorkflowService::class);
        $workflow->shouldReceive('syncWorkflowToPipelineStage')->once()->with(1, 2);
        $service = app(LeadConversionService::class);
        $first = $service->convert(Lead::findOrFail(1));
        $again = $service->convert(Lead::findOrFail(1));
        $this->assertSame(2, $first['customer']->pipeline_stage_id);
        $this->assertSame($first['customer']->id, $again['customer']->id);
        $this->assertTrue($again['already_converted']);
        $this->assertSame(1, Customer::count());
    }

    public function test_default_stage_creates_the_actual_pending_workflow_record(): void
    {
        \Illuminate\Support\Facades\Schema::create('workflow_stages', function (Blueprint $table) {
            $table->id(); $table->string('workflow_type'); $table->string('name'); $table->integer('order');
            $table->string('system_key')->nullable(); $table->boolean('is_active')->default(true); $table->timestamps();
        });
        foreach (['DocumentVerification', 'Registration', 'Feasibility', 'Finance', 'WaitingFloor', 'Dispatch', 'Installation', 'Intimation', 'NetMetering', 'Subsidy'] as $type) {
            $model = 'App\\Models\\'.$type;
            \Illuminate\Support\Facades\Schema::create((new $model)->getTable(), function (Blueprint $table) {
                $table->id(); $table->integer('customer_id'); $table->string('aspl_id'); $table->string('status'); $table->integer('current_stage_id')->nullable();
                $table->text('remarks')->nullable(); $table->string('previous_stage')->nullable(); $table->timestamps(); $table->softDeletes();
            });
        }
        $result = app(LeadConversionService::class)->convert(Lead::findOrFail(1));
        $record = \App\Models\DocumentVerification::where('customer_id', $result['customer']->id)->firstOrFail();
        $this->assertSame('pending', $record->status);
        $this->assertSame(0, \App\Models\Registration::count());
    }

    public function test_existing_customer_stage_is_preserved(): void
    {
        DB::table('customers')->insert(['id' => 4, 'name' => 'Existing', 'phone' => '9876543210', 'pipeline_stage_id' => 3]);
        $this->mock(WorkflowService::class)->shouldNotReceive('syncWorkflowToPipelineStage');
        $result = app(LeadConversionService::class)->convert(Lead::findOrFail(1));
        $this->assertSame(4, $result['customer']->id);
        $this->assertSame(3, $result['customer']->pipeline_stage_id);
        $this->assertFalse($result['created']);
    }

    public function test_previously_converted_customer_without_stage_is_repaired_on_retry(): void
    {
        DB::table('customers')->insert(['id' => 4, 'name' => 'Existing', 'phone' => '9876543210']);
        DB::table('leads')->where('id', 1)->update(['is_converted' => true, 'converted_customer_id' => 4]);
        $this->mock(WorkflowService::class)->shouldReceive('syncWorkflowToPipelineStage')->once()->with(4, 2);
        $result = app(LeadConversionService::class)->convert(Lead::findOrFail(1));
        $this->assertSame(2, $result['customer']->pipeline_stage_id);
        $this->assertSame(1, Customer::count());
    }

    public function test_missing_active_stages_do_not_create_partial_records(): void
    {
        DB::table('stages')->update(['is_active' => false]);
        try { app(LeadConversionService::class)->convert(Lead::findOrFail(1)); $this->fail('Expected stage validation.'); }
        catch (ValidationException $exception) { $this->assertArrayHasKey('pipeline_stage_id', $exception->errors()); }
        $this->assertSame(0, Customer::count());
        $this->assertFalse(Lead::findOrFail(1)->is_converted);
    }

    public function test_workflow_failure_rolls_back_the_conversion(): void
    {
        $this->mock(WorkflowService::class)->shouldReceive('syncWorkflowToPipelineStage')->andThrow(new \RuntimeException('Workflow unavailable'));
        try { app(LeadConversionService::class)->convert(Lead::findOrFail(1)); $this->fail('Expected workflow failure.'); }
        catch (\RuntimeException $exception) { $this->assertSame('Workflow unavailable', $exception->getMessage()); }
        $this->assertSame(0, Customer::count());
        $this->assertFalse(Lead::findOrFail(1)->is_converted);
    }

    public function test_ajax_conversion_returns_json_instead_of_redirecting_to_customer_edit(): void
    {
        Gate::shouldReceive('authorize')->twice()->andReturn(\Illuminate\Auth\Access\Response::allow());
        Log::spy();
        $this->mock(WorkflowService::class)->shouldReceive('syncWorkflowToPipelineStage')->once();
        $request = \Illuminate\Http\Request::create('/leads/1/convert', 'POST', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = app(\App\Http\Controllers\LeadController::class)->convertToCustomer($request, Lead::findOrFail(1), app(LeadConversionService::class));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($response->getData(true)['success']);
        $this->assertSame(2, $response->getData(true)['pipeline_stage_id']);
        Log::shouldNotHaveReceived('warning', ['Lead conversion notification failed.', \Mockery::any()]);
    }
}
