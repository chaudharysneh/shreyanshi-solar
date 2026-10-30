<?php
namespace Tests\Feature;

use App\Models\{Customer, Estimate, Invoice, Stage, Task, Deal};
use App\Services\{CustomerDocumentName, EstimateFulfilmentService};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, DB};
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EstimateFulfilmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('customers', function (Blueprint $t) { $t->id(); $t->string('name'); $t->timestamps(); $t->softDeletes(); });
        Schema::create('users', function (Blueprint $t) { $t->id(); $t->string('name'); });
        Schema::create('estimates', function (Blueprint $t) { $t->id('estimate_id'); $t->integer('customer_id'); $t->integer('user_id')->nullable(); $t->integer('created_by')->nullable(); $t->string('estimate_name'); $t->string('estimate_no'); $t->string('status'); $t->string('currency')->default('INR'); $t->decimal('amount')->default(90000); $t->timestamps(); $t->softDeletes(); });
        Schema::create('invoices', function (Blueprint $t) { $t->id(); $t->integer('customer_id'); $t->string('invoice_name'); $t->integer('estimate_id')->nullable(); $t->integer('currency_id')->nullable(); $t->decimal('amount')->default(0); $t->timestamps(); $t->softDeletes(); });
        Schema::create('stages', function (Blueprint $t) { $t->id(); $t->string('name'); $t->boolean('is_active')->default(true); $t->integer('sort_order')->default(0); $t->timestamps(); });
        Schema::create('statuses', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('type'); $t->boolean('is_active')->default(true); $t->timestamps(); });
        Schema::create('currencies', function (Blueprint $t) { $t->id(); $t->string('code'); });
        DB::table('currencies')->insert(['code' => 'INR']);
        Schema::create('deals', function (Blueprint $t) { $t->id(); foreach (['invoice_id','estimate_id','customer_id','currency_id','assigned_user_id','user_id','created_by','status_id','stage_id'] as $key) $t->integer($key)->nullable(); $t->string('title'); $t->decimal('amount'); $t->integer('probability'); $t->timestamps(); });
        Schema::create('tasks', function (Blueprint $t) { $t->id(); foreach (['invoice_id','estimate_id','related_id','assigned_user_id','user_id'] as $key) $t->integer($key)->nullable(); foreach (['title','description','related_type','status','priority','task_type'] as $key) $t->string($key)->nullable(); $t->timestamps(); });
        Schema::create('module_status_histories', function (Blueprint $t) { $t->id(); $t->string('historable_type'); $t->integer('historable_id'); $t->string('status'); $t->text('comment')->nullable(); $t->integer('updated_by')->nullable(); $t->timestamps(); });
        (require database_path('migrations/2026_10_26_000006_add_task_workflow_and_document_sequences.php'))->up();
        (require database_path('migrations/2026_10_26_000009_create_task_workflow_events_table.php'))->up();
        Schema::create('workflow_stages', function (Blueprint $t) { $t->id();$t->string('workflow_type');$t->string('name');$t->string('system_key')->nullable();$t->integer('order')->default(0);$t->boolean('is_active')->default(true);$t->timestamps(); });
        DB::table('customers')->insert(['id' => 1, 'name' => 'User1']);
        DB::table('stages')->insert([['id'=>1,'name'=>'Registration','is_active'=>true,'sort_order'=>1], ['id'=>2,'name'=>'Installation','is_active'=>true,'sort_order'=>2], ['id'=>3,'name'=>'Hidden','is_active'=>false,'sort_order'=>3]]);
    }

    private function estimate(): Estimate
    {
        return Estimate::create(['customer_id'=>1, 'estimate_name'=>'EST-User1','estimate_no'=>'000001','status'=>'approved']);
    }

    public function test_approval_fulfilment_is_idempotent_and_excludes_inactive_stages(): void
    {
        $estimate = $this->estimate();
        $service = app(EstimateFulfilmentService::class);
        $task = $service->ensure($estimate);
        $service->ensure($estimate);
        $this->assertSame(1, Task::count());
        $this->assertSame(1, Deal::count());
        $this->assertSame(90000.0, (float) Deal::first()->amount);
        $this->assertSame(['Registration','Installation'], array_column($task->workflow_steps, 'name'));
        $this->assertSame($estimate->getKey(), $task->estimate_id);
        $this->assertSame(1, $task->related_id);
    }

    public function test_workflow_is_sequential_tracks_history_and_updates_overall_status(): void
    {
        $service = app(EstimateFulfilmentService::class);
        $task = $service->ensure($this->estimate());
        try { $service->updateStep($task, 2, 'completed', null, 1); $this->fail('Skipped earlier stage'); }
        catch (ValidationException $e) { $this->assertArrayHasKey('status', $e->errors()); }
        $service->updateStep($task, 1, 'completed', 'Verified', 1);
        $this->assertSame('in_progress', $task->fresh()->status);
        $service->updateStep($task, 2, 'completed', 'Installed', 1);
        $this->assertSame('completed', $task->fresh()->status);
        $this->assertCount(3, $task->statusHistories()->get());
        $this->assertStringContainsString('Installed', $task->statusHistories()->latest('id')->first()->comment);
        try { $service->updateStep($task, 1, 'pending', null, 1); $this->fail('Reopened prior to later stage'); }
        catch (ValidationException $e) { $this->assertArrayHasKey('status', $e->errors()); }
    }

    public function test_document_numbers_are_per_customer_and_preview_does_not_reserve(): void
    {
        $names = app(CustomerDocumentName::class);
        $this->assertSame('#01EST-User1', $names->next(1, 'EST'));
        $this->assertSame('#01EST-User1', $names->next(1, 'EST'));
        $first = $this->estimate();
        $this->assertSame('#01EST-User1', $first->estimate_name);
        $first->delete();
        $this->assertSame('#02EST-User1', $this->estimate()->estimate_name);
        $invoice = Invoice::create(['customer_id'=>1,'invoice_name'=>'INV-User1']);
        $this->assertSame('#01INV-User1', $invoice->invoice_name);
        DB::table('customers')->insert(['id'=>2,'name'=>'User2']);
        $this->assertSame('#01EST-User2', $names->next(2, 'EST'));
        $custom = Estimate::create(['customer_id'=>1,'estimate_name'=>'Roof extension','estimate_no'=>'000003','status'=>'pending']);
        $this->assertSame('Roof extension', $custom->estimate_name);
    }

    public function test_approval_creates_invoice_task_and_deal_once(): void
    {
        $this->mock(\App\Services\UserLogService::class, fn ($mock) => $mock->shouldReceive('created')->andReturnNull());
        $columns = Schema::getColumnListing('invoices');
        Schema::table('invoices', function (Blueprint $table) use ($columns) {
            foreach ((new Invoice)->getFillable() as $field) {
                if (!in_array($field, $columns)) $table->text($field)->nullable();
            }
        });
        $estimate = $this->estimate();
        $estimate->update(['status' => 'pending']);
        $controller = \Mockery::mock(\App\Http\Controllers\Api\EstimateController::class)->makePartial();
        $controller->shouldReceive('authorize')->andReturn(true);
        $response = $controller->updateStatus(new \Illuminate\Http\Request(['status' => 'approved']), (string) $estimate->getKey());
        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $controller->updateStatus(new \Illuminate\Http\Request(['status' => 'approved']), (string) $estimate->getKey());
        $this->assertSame(1, Invoice::count());
        $this->assertSame(1, Task::count());
        $this->assertSame(1, Deal::count());
        $this->assertSame('#01INV-User1', Invoice::first()->invoice_name);
        $this->assertSame(Invoice::first()->id, Task::first()->invoice_id);
    }

    public function test_deal_invoice_comes_from_its_own_estimate_and_ignores_deleted_invoices(): void
    {
        $estimate = $this->estimate();
        app(EstimateFulfilmentService::class)->ensure($estimate);
        $invoice = Invoice::create(['customer_id'=>1, 'estimate_id'=>$estimate->getKey(), 'invoice_name'=>'Installation invoice', 'amount'=>85000]);
        Invoice::create(['customer_id'=>1, 'estimate_id'=>999, 'invoice_name'=>'Different installation', 'amount'=>999]);
        $deleted = Invoice::create(['customer_id'=>1, 'estimate_id'=>$estimate->getKey(), 'invoice_name'=>'Deleted replacement', 'amount'=>123]);
        $deleted->delete();
        Deal::query()->update(['invoice_id' => $invoice->id]);
        $deal = Deal::with('invoice')->firstOrFail();
        $this->assertSame($invoice->id, $deal->invoice->id);
        Task::query()->update(['invoice_id' => $invoice->id]);
        $task = Task::with('invoice')->firstOrFail();
        $this->assertSame($invoice->id, $task->invoice->id);
        $task->invoice_id = null;
        $this->assertNull($task->invoice()->first());
        $this->assertSame(85000.0, (float) $deal->invoice->amount);
        $deal->invoice_id = null;
        $this->assertNull($deal->invoice()->first());
    }

    public function test_deal_choices_are_limited_and_legacy_won_is_consolidated(): void
    {
        app(EstimateFulfilmentService::class)->ensure($this->estimate());
        $legacy = \App\Models\Status::create(['name'=>'Won','type'=>'deal','is_active'=>true]);
        \App\Models\Status::create(['name'=>'Proposal','type'=>'deal','is_active'=>true]);
        Deal::query()->update(['status_id'=>$legacy->id]);
        Schema::table('statuses', fn (Blueprint $table) => $table->string('color')->nullable());
        app(\App\Http\Controllers\DealController::class)->index();
        $choices = \App\Models\Status::dealChoices()->pluck('name')->all();
        $this->assertSame(\App\Models\Status::DEAL_NAMES, $choices);
        $this->assertSame('Won/Confirm', Deal::first()->status->name);
        $this->assertFalse((bool) $legacy->fresh()->is_active);
    }

    public function test_deal_invoice_selection_supports_standalone_invoices_and_rejects_other_customers(): void
    {
        $user = new class extends \App\Models\User { public function isAdmin(): bool { return true; } };
        $user->id = 1;
        $this->actingAs($user);
        $invoice = Invoice::withoutEvents(fn () => Invoice::create(['customer_id'=>1,'invoice_name'=>'Standalone invoice','amount'=>12345,'currency_id'=>1]));
        $controller = new \App\Http\Controllers\Api\DealController;
        $method = new \ReflectionMethod($controller, 'prepareDealData');
        $method->setAccessible(true);
        $data = $method->invoke($controller, ['customer_id'=>1,'invoice_id'=>$invoice->id,'amount'=>1]);
        $this->assertSame('Standalone invoice', $data['title']);
        $this->assertSame(12345.0, (float) $data['amount']);
        $this->assertNull($data['estimate_id']);
        $this->expectException(ValidationException::class);
        $method->invoke($controller, ['customer_id'=>2,'invoice_id'=>$invoice->id,'amount'=>1]);
    }

    public function test_standalone_invoice_can_link_to_manual_task(): void
    {
        $invoice = Invoice::create(['customer_id'=>1, 'invoice_name'=>'Standalone installation', 'amount'=>12345]);
        $task = Task::create(['title'=>'Site survey', 'status'=>'pending', 'invoice_id'=>$invoice->id, 'related_type'=>'customer', 'related_id'=>1]);
        $this->assertSame($invoice->id, $task->invoice->id);
        $this->assertNull($task->estimate_id);
    }

    public function test_detailed_workflow_uses_custom_status_and_keeps_private_upload_history(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        DB::table('stages')->where('id',1)->update(['name'=>'Document Verification']);
        $task=app(EstimateFulfilmentService::class)->ensure($this->estimate());
        $service=app(\App\Services\TaskWorkflowService::class);
        $options=$service->steps($task)->first()['options'];
        $custom=\App\Models\WorkflowStage::create(['workflow_type'=>'document-verification','name'=>'Awaiting electricity bill','order'=>4,'is_active'=>true]);
        $customValue='document-verification:0:'.$custom->id;
        $file=\Illuminate\Http\UploadedFile::fake()->create('site-report.pdf',10,'application/pdf');
        $service->update($task,['stage_id'=>1,'status'=>$customValue,'note'=>'Documents requested','check_in_at'=>'2020-01-01T10:00','location'=>'Roof A'],[$file],1);
        $task->refresh();
        $this->assertSame('in_progress',$task->status);
        $this->assertSame('Awaiting electricity bill',$task->workflow_steps[0]['workflow_status_label']);
        $event=$task->workflowEvents()->firstOrFail();
        $this->assertSame('site-report.pdf',$event->attachments[0]['name']);
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($event->attachments[0]['path']);
        $this->assertSame('2020-01-01 04:30',$event->check_in_at->format('Y-m-d H:i'));
        $completed=collect($options)->firstWhere('key','completed')['value'];
        $service->update($task,['stage_id'=>1,'status'=>$completed,'check_out_at'=>'2020-01-01T11:00'],[],1);
        $this->assertSame('completed',$task->fresh()->workflow_steps[0]['status']);
        $this->assertCount(2,$task->workflowEvents()->get());
        $this->assertSame('Awaiting electricity bill',$task->workflowEvents()->first()->previous_status);
        $this->assertSame('Roof A',$task->workflowEvents()->first()->location);
    }

    public function test_detailed_workflow_rejects_wrong_status_and_checkout_before_checkin(): void
    {
        $task=app(EstimateFulfilmentService::class)->ensure($this->estimate());
        $service=app(\App\Services\TaskWorkflowService::class);
        foreach ([['stage_id'=>1,'status'=>'unrelated:999'], ['stage_id'=>1,'status'=>'in_progress','check_in_at'=>'2020-01-01T12:00','check_out_at'=>'2020-01-01T11:00']] as $data) {
            try { $service->update($task,$data,[],1);$this->fail('Invalid update accepted'); }
            catch (ValidationException $error) { $this->assertNotEmpty($error->errors()); }
        }
        $this->assertSame(0,$task->workflowEvents()->count());
        $this->assertSame('pending',$task->fresh()->status);
    }

    public function test_finance_stage_uses_customer_master_statuses(): void
    {
        Schema::table('customers', fn (Blueprint $t)=>$t->integer('finance_id')->nullable());
        Schema::create('finance_masters', function (Blueprint $t) { $t->id();$t->string('name'); });
        Schema::create('finance_stages', function (Blueprint $t) { $t->id();$t->integer('finance_master_id');$t->string('stage_name');$t->integer('stage_order')->default(0);$t->string('system_key')->nullable();$t->boolean('is_active')->default(true);$t->timestamps(); });
        DB::table('finance_masters')->insert(['id'=>1,'name'=>'Bank loan']);
        DB::table('customers')->where('id',1)->update(['finance_id'=>1]);
        DB::table('stages')->where('id',1)->update(['name'=>'Finance']);
        DB::table('finance_stages')->insert([
            ['finance_master_id'=>1,'stage_name'=>'Application pending','system_key'=>'pending','is_active'=>true,'stage_order'=>1],
            ['finance_master_id'=>1,'stage_name'=>'Bank verification','system_key'=>null,'is_active'=>true,'stage_order'=>2],
            ['finance_master_id'=>1,'stage_name'=>'Loan cleared','system_key'=>'completed','is_active'=>true,'stage_order'=>3],
            ['finance_master_id'=>1,'stage_name'=>'Inactive status','system_key'=>null,'is_active'=>false,'stage_order'=>4],
        ]);
        $task=app(EstimateFulfilmentService::class)->ensure($this->estimate());
        $options=app(\App\Services\TaskWorkflowService::class)->steps($task)->first()['options'];
        $this->assertSame(['Application pending','Bank verification','Loan cleared'],array_column($options,'label'));
        $this->assertStringStartsWith('finance:1:',$options[1]['value']);
    }

    public function test_stage_attachments_cannot_be_downloaded_through_another_task(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $task=app(EstimateFulfilmentService::class)->ensure($this->estimate());
        $service=app(\App\Services\TaskWorkflowService::class);
        $status=collect($service->steps($task)->first()['options'])->firstWhere('key','in_progress')['value'];
        $service->update($task,['stage_id'=>1,'status'=>$status],[\Illuminate\Http\UploadedFile::fake()->create('report.pdf',10,'application/pdf')],1);
        $entry=$task->workflowEvents()->firstOrFail();
        $controller=\Mockery::mock(\App\Http\Controllers\TaskController::class)->makePartial();
        $controller->shouldReceive('authorize')->with('view',\Mockery::type(Task::class))->andReturnTrue();
        $this->assertSame(200,$controller->workflowAttachment($task,$entry->id,0)->getStatusCode());
        $other=Task::create(['title'=>'Other task','status'=>'pending']);
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $controller->workflowAttachment($other,$entry->id,0);
    }

    public function test_stage_endpoint_accepts_multiple_files_with_method_spoofing(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $task=app(EstimateFulfilmentService::class)->ensure($this->estimate());
        $status=collect(app(\App\Services\TaskWorkflowService::class)->steps($task)->first()['options'])->firstWhere('key','in_progress')['value'];
        $user=new class extends \App\Models\User {
            public function isAdmin(): bool { return true; }
            public function hasMatrixPermission(string $permission): bool { return true; }
        };
        $user->id=1;$this->actingAs($user);$this->withoutMiddleware([\App\Http\Middleware\Authenticate::class,\App\Http\Middleware\CheckMatrixPermission::class]);
        $controller=\Mockery::mock(\App\Http\Controllers\Api\TaskController::class)->makePartial();
        $controller->shouldReceive('authorize')->andReturnTrue();
        $this->app->instance(\App\Http\Controllers\Api\TaskController::class,$controller);
        Task::withoutEvents(fn ()=>$this->post('/api/tasks/'.$task->id.'/workflow-step',[
            '_method'=>'PATCH','stage_id'=>1,'status'=>$status,'note'=>'Site inspection',
            'attachments'=>[\Illuminate\Http\UploadedFile::fake()->create('first.pdf',10,'application/pdf'),\Illuminate\Http\UploadedFile::fake()->create('second.pdf',10,'application/pdf')],
        ],['Accept'=>'application/json'])->assertOk());
        $this->assertCount(2,$task->workflowEvents()->first()->attachments);
    }

    public function test_task_cannot_be_completed_without_finishing_stages(): void
    {
        $task = app(EstimateFulfilmentService::class)->ensure($this->estimate());
        $this->expectException(ValidationException::class);
        $task->update(['status'=>'completed']);
    }
}
