<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('finance_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finance_master_id')->constrained('finance_masters')->cascadeOnDelete();
            $table->string('stage_name');
            $table->integer('stage_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_stages');
    }
};
