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
        Schema::table('product', function (Blueprint $table) {
            // Add tax fields after price
            if (!Schema::hasColumn('product', 'tax_type')) {
                $table->string('tax_type')->nullable()->after('price')->comment('Tax Type (e.g., GST, VAT, etc.)');
            }
            
            if (!Schema::hasColumn('product', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->nullable()->after('tax_type')->comment('Tax Rate (%)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product', function (Blueprint $table) {
            if (Schema::hasColumn('product', 'tax_type')) {
                $table->dropColumn('tax_type');
            }
            
            if (Schema::hasColumn('product', 'tax_rate')) {
                $table->dropColumn('tax_rate');
            }
        });
    }
};
