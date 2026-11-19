<?php
namespace Tests\Feature;

use App\Models\{Deal, Invoice, Task};
use App\Services\InvoiceDeletionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Tests\TestCase;

class InvoiceDeletionTest extends TestCase
{
    public function test_deletion_removes_only_records_for_the_selected_invoice(): void
    {
        config(['database.default'=>'sqlite', 'database.connections.sqlite.database'=>':memory:']); DB::purge('sqlite');
        Schema::create('invoices', function (Blueprint $t) { $t->id(); $t->integer('customer_id'); $t->integer('deleted_by')->nullable(); $t->timestamps(); $t->softDeletes(); });
        foreach (['tasks', 'deals'] as $table) Schema::create($table, function (Blueprint $t) { $t->id(); $t->integer('invoice_id'); });
        foreach (['payments', 'invoice_finance_events', 'invoice_finance_plans'] as $table) Schema::create($table, function (Blueprint $t) { $t->id(); $t->integer('invoice_id'); });
        Schema::create('task_workflow_events', function (Blueprint $t) { $t->id(); $t->integer('task_id'); });
        Schema::create('documents', function (Blueprint $t) { $t->id(); $t->integer('documentable_id'); $t->string('documentable_type'); });
        Schema::create('module_status_histories', function (Blueprint $t) { $t->id(); $t->integer('historable_id'); $t->string('historable_type'); });
        DB::table('invoices')->insert([['id'=>1,'customer_id'=>7], ['id'=>2,'customer_id'=>7]]);
        foreach (['tasks', 'deals', 'payments', 'invoice_finance_events', 'invoice_finance_plans'] as $table) DB::table($table)->insert([['id'=>1,'invoice_id'=>1], ['id'=>2,'invoice_id'=>2]]);
        DB::table('task_workflow_events')->insert([['id'=>1,'task_id'=>1], ['id'=>2,'task_id'=>2]]);
        DB::table('documents')->insert([['id'=>1,'documentable_id'=>1,'documentable_type'=>(new Task)->getMorphClass()], ['id'=>2,'documentable_id'=>2,'documentable_type'=>(new Task)->getMorphClass()]]);
        DB::table('module_status_histories')->insert([['id'=>1,'historable_id'=>1,'historable_type'=>(new Deal)->getMorphClass()], ['id'=>2,'historable_id'=>2,'historable_type'=>(new Deal)->getMorphClass()]]);
        app(InvoiceDeletionService::class)->delete(Invoice::findOrFail(1), 5);
        $this->assertSoftDeleted('invoices', ['id'=>1]);
        $this->assertDatabaseHas('invoices', ['id'=>2, 'deleted_at'=>null]);
        foreach (['tasks', 'deals', 'payments', 'invoice_finance_events', 'invoice_finance_plans', 'task_workflow_events', 'documents', 'module_status_histories'] as $table) {
            $this->assertDatabaseMissing($table, ['id'=>1]); $this->assertDatabaseHas($table, ['id'=>2]);
        }
    }
}
