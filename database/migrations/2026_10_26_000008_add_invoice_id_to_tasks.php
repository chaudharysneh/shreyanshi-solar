<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
return new class extends Migration {
    public function up(): void
    {
        Schema::table('tasks', fn (Blueprint $table) => $table->unsignedBigInteger('invoice_id')->nullable()->index());
        DB::table('tasks')->whereNotNull('estimate_id')->orderBy('id')->chunkById(100, function ($tasks) {
            foreach ($tasks as $task) {
                $invoice = DB::table('invoices')->where('estimate_id', $task->estimate_id)->whereNull('deleted_at')->latest('id')->first();
                if (!$invoice) continue;
                $data = ['invoice_id' => $invoice->id];
                if ($task->task_type === 'solar_workflow') {
                    $data['title'] = 'Solar installation - '.($invoice->invoice_name ?: $invoice->invoice_no);
                    $data['description'] = 'Installation workflow for invoice #'.$invoice->invoice_no.'. Complete the stages in order.';
                }
                DB::table('tasks')->where('id', $task->id)->update($data);
            }
        });
    }
    public function down(): void { Schema::table('tasks', fn (Blueprint $table) => $table->dropColumn('invoice_id')); }
};
