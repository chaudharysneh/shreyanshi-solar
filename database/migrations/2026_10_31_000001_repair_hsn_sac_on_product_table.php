<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('product', 'hsn_sac')) {
            Schema::table('product', function (Blueprint $table) {
                $table->string('hsn_sac')->nullable()->after('product_name');
            });
        }
    }

    public function down(): void
    {
        // The original HSN/SAC migration owns this column. Preserve existing data.
    }
};
