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
        Schema::create('subsidies', function (Blueprint $table) {
            $table->id();
            $table->string('category'); // residential_0_2, residential_2_3, residential_above_3, common_meter
            $table->string('label'); // Display label like "Residential Subsidy (0-2 kW)"
            $table->decimal('amount', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subsidies');
    }
};
