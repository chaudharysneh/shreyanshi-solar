<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('task_workflow_events', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('task_id')->index(); $table->unsignedBigInteger('stage_id');
            $table->string('stage_name'); $table->string('workflow_type')->nullable();
            $table->string('previous_status')->nullable(); $table->string('status'); $table->string('status_key');
            $table->text('note')->nullable(); $table->string('location', 1000)->nullable();
            $table->dateTime('check_in_at')->nullable(); $table->dateTime('check_out_at')->nullable();
            $table->json('attachments')->nullable(); $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps(); $table->index(['task_id','stage_id','id']);
        });
    }
    public function down(): void { Schema::dropIfExists('task_workflow_events'); }
};
