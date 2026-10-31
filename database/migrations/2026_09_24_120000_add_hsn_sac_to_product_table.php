<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('product', 'hsn_sac')) {
            return;
        }

        Schema::table('product', function (Blueprint $table) {
            $table->string('hsn_sac')->nullable()->after('product_name');
        });
    }

    public function down(): void
    {
        Schema::table('product', function (Blueprint $table) {
            $table->dropColumn('hsn_sac');
        });
    }
};
