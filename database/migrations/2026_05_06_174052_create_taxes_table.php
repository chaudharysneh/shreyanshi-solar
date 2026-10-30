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
        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "GST (CGST + SGST)", "GST (IGST)"
            $table->decimal('rate', 5, 2); // e.g., 5.00, 12.00, 18.00
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes(); // Add soft deletes column
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taxes');
    }
};
