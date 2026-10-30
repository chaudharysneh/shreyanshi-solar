<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('reference_no')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->json('bom_specifications')->nullable();
            $table->string('price_mode', 10)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['reference_no', 'terms_conditions', 'bom_specifications', 'price_mode']);
        });
    }
};
