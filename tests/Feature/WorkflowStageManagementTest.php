<?php

namespace Tests\Feature;

use App\Models;
use App\Services\WorkflowBoard;
use App\Services\WorkflowStageCatalog;
use App\Services\WorkflowService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WorkflowStageManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->withoutMiddleware(\App\Http\Middleware\Authenticate::class);
        Schema::create('customers', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->integer('finance_id')->nullable(); $table->integer('installation_master_id')->nullable(); $table->softDeletes();
        });
        foreach (['finance_masters', 'installation_masters'] as $name) Schema::create($name, function (Blueprint $table) { $table->id(); $table->string('name'); });
        foreach (['workflow_stages', 'finance_stages', 'installation_stages'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name) {
                $table->id(); $table->string('system_key')->nullable(); $table->boolean('is_active')->default(true); $table->timestamps();
                if ($name === 'workflow_stages') { $table->string('workflow_type'); $table->string('name'); $table->integer('order'); }
                else { $table->integer($name === 'finance_stages' ? 'finance_master_id' : 'installation_master_id'); $table->string('stage_name'); $table->integer('stage_order'); }
            });
        }
        foreach (WorkflowStageCatalog::TYPES as $model) {
            Schema::create((new $model)->getTable(), function (Blueprint $table) {
                $table->id(); $table->integer('customer_id'); $table->string('status'); $table->integer('current_stage_id')->nullable();
                $table->string('current_stage_type')->nullable(); $table->integer('assigned_to')->nullable(); $table->timestamps(); $table->softDeletes();
            });
        }
        $user = new class extends Models\User { public function isAdmin(): bool { return true; } };
        $user->id = 1;
        $this->actingAs($user);
    }

    public function test_board_search_matches_customer_name_phone_city_and_aspl(): void
    {
        Schema::table('customers', function (Blueprint $table) { $table->string('phone')->nullable(); $table->integer('city_id')->nullable(); });
        Schema::create('cities', function (Blueprint $table) { $table->id(); $table->string('name'); });
        Schema::table('document_verifications', function (Blueprint $table) { $table->string('aspl_id')->nullable(); });
        DB::table('cities')->insert(['id' => 1, 'name' => 'Surat']);
        DB::table('customers')->insert([['id' => 1, 'name' => 'Raj Singh', 'phone' => '9824734531', 'city_id' => 1], ['id' => 2, 'name' => 'Other Customer', 'phone' => '1111111111', 'city_id' => null]]);
        DB::table('document_verifications')->insert([['id' => 1, 'customer_id' => 1, 'status' => 'pending', 'aspl_id' => 'ASPL-0026'], ['id' => 2, 'customer_id' => 2, 'status' => 'pending', 'aspl_id' => 'ASPL-0099']]);
        foreach (['Raj', '982473', 'Surat', 'ASPL-0026', '  Raj  ', 'No match', ''] as $search) {
            $response = app(\App\Http\Controllers\Workflow\DocumentVerificationController::class)->index(\Illuminate\Http\Request::create('/api/workflow/document-verification', 'GET', ['search' => $search]));
            $groups = $response->getData(true)['data'];
            $records = collect($groups)->flatMap(fn ($group) => $group['records']);
            $this->assertSame($search === '' ? [1, 2] : ($search === 'No match' ? [] : [1]), $records->pluck('id')->sort()->values()->all(), $search);
        }
    }

    public function test_every_workflow_has_editable_ordered_defaults_and_appends_new_stages(): void
    {
        $catalog = app(WorkflowStageCatalog::class);
        foreach (array_keys(WorkflowStageCatalog::TYPES) as $type) {
            $scope = $catalog->context($type);
            $stages = $catalog->stages($scope);
            $this->assertSame('pending', $stages->first()->system_key);
            $pending = $stages->first();
            $catalog->save($scope, ['name' => 'Waiting for review', 'order' => 2], $pending->id);
            $stages = $catalog->stages($scope);
            $this->assertSame($pending->id, $stages[1]->id);
            $this->assertSame('Waiting for review', $stages[1]->name);
            [, $groups] = app(WorkflowBoard::class)->group(collect([(object) ['status' => 'pending', 'current_stage_id' => null]]), $stages);
            $this->assertCount(1, $groups['stage_'.$pending->id]);
            $created = $catalog->save($scope, ['name' => 'Additional review']);
            $this->assertSame($created->id, $catalog->stages($scope)->last()->id);
            $this->assertSame(range(1, $stages->count() + 1), $catalog->stages($scope)->pluck('order')->all());
        }
    }

    public function test_used_stages_cannot_be_deleted_and_unused_defaults_do_not_reappear(): void
    {
        $catalog = app(WorkflowStageCatalog::class); $scope = $catalog->context('registration');
        $stages = $catalog->stages($scope);
        DB::table('registrations')->insert(['customer_id' => 1, 'status' => 'pending']);
        try { $catalog->delete($scope, $stages->first()->id); $this->fail('Expected occupied-stage validation.'); }
        catch (ValidationException $exception) { $this->assertArrayHasKey('stage', $exception->errors()); }
        $catalog->delete($scope, $stages->last()->id);
        $this->assertCount(2, $catalog->stages($scope));
        $this->assertFalse($catalog->stages($scope)->contains('system_key', 'completed'));
    }

    public function test_finance_and_installation_masters_keep_separate_stage_lists(): void
    {
        $catalog = app(WorkflowStageCatalog::class);
        foreach (['finance', 'installations'] as $type) {
            $table = $type === 'finance' ? 'finance_masters' : 'installation_masters';
            DB::table($table)->insert([['id' => 1, 'name' => 'A'], ['id' => 2, 'name' => 'B']]);
            $a = $catalog->context($type, 1); $b = $catalog->context($type, 2);
            $stageA = $catalog->stages($a)->first(); $catalog->stages($b);
            $catalog->save($a, ['name' => 'Only A'], $stageA->id);
            $this->assertFalse($catalog->stages($b)->contains('name', 'Only A'));
            $this->putJson('/api/workflow/stages/'.$stageA->id, ['workflow_type' => $type, 'finance_id' => 2, 'name' => 'Wrong scope'])->assertNotFound();
        }
    }

    public function test_new_records_start_at_first_ordered_stage_and_finance_moves_stay_scoped(): void
    {
        $catalog = app(WorkflowStageCatalog::class);
        DB::table('finance_masters')->insert(['id' => 1, 'name' => 'Bank']);
        DB::table('customers')->insert(['id' => 1, 'name' => 'Customer', 'finance_id' => 1]);
        $scope = $catalog->context('finance', 1);
        $stages = $catalog->stages($scope);
        $catalog->save($scope, ['order' => 1], $stages->last()->id);
        $state = $catalog->initialState(Models\Finance::class, 1);
        $this->assertSame($stages->last()->id, $state['current_stage_id']);
        $this->assertSame('master', $state['current_stage_type']);
        DB::table('finances')->insert(['id' => 1, 'customer_id' => 1, 'status' => 'pending']);
        $target = $catalog->stages($scope)[1];
        $this->putJson('/api/workflow/finance/1', ['status' => 'stage_'.$target->id, 'finance_id' => 1])->assertOk();
        $this->assertSame($target->id, DB::table('finances')->value('current_stage_id'));
        $this->assertSame('master', DB::table('finances')->value('current_stage_type'));
        $this->assertSame('in_progress', DB::table('finances')->value('status'));
        $response = $this->getJson('/api/workflow/finance?finance_id=1')->assertOk()->json();
        $this->assertSame($target->name, $response['columns'][1]['name']);
        $this->assertSame(1, $response['data']['stage_'.$target->id]['count']);
    }

    public function test_add_validation_and_order_based_workflow_completion(): void
    {
        $catalog = app(WorkflowStageCatalog::class); $scope = $catalog->context('document-verification');
        $stages = $catalog->stages($scope);
        $final = $catalog->save($scope, ['name' => 'Final sign-off']);
        DB::table('customers')->insert(['id' => 1, 'name' => 'Customer']);
        DB::table('document_verifications')->insert(['id' => 1, 'customer_id' => 1, 'status' => 'pending']);
        $this->postJson('/api/workflow/stages', ['workflow_type' => 'document-verification', 'name' => ''])->assertUnprocessable();
        $this->mock(WorkflowService::class)->shouldReceive('advanceIfCompleted')->once()->andReturn(true);
        $this->putJson('/api/workflow/document-verification/1', ['status' => 'stage_'.$stages->last()->id])->assertOk();
        $this->assertSame('in_progress', DB::table('document_verifications')->value('status'));
        $this->putJson('/api/workflow/document-verification/1', ['status' => 'stage_'.$final->id])->assertOk();
        $this->assertSame('completed', DB::table('document_verifications')->value('status'));
    }
}
