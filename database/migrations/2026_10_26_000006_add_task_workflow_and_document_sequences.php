<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->json('workflow_steps')->nullable();
        });
        Schema::create('customer_document_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('type', 10);
            $table->unsignedInteger('value')->default(0);
            $table->unique(['customer_id', 'type']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('customer_document_sequences');
        Schema::table('tasks', fn (Blueprint $table) => $table->dropColumn('workflow_steps'));
    }
};
