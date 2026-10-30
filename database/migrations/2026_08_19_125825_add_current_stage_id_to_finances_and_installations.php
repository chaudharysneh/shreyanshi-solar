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
        if (Schema::hasTable('finances')) {
            Schema::table('finances', function (Blueprint $table) {
                if (!Schema::hasColumn('finances', 'current_stage_id')) {
                    $table->unsignedBigInteger('current_stage_id')->nullable()->after('status');
                }
            });
        }
        
        if (Schema::hasTable('installations')) {
            Schema::table('installations', function (Blueprint $table) {
                if (!Schema::hasColumn('installations', 'current_stage_id')) {
                    $table->unsignedBigInteger('current_stage_id')->nullable()->after('status');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('finances')) {
            Schema::table('finances', function (Blueprint $table) {
                if (Schema::hasColumn('finances', 'current_stage_id')) {
                    $table->dropColumn('current_stage_id');
                }
            });
        }
        
        if (Schema::hasTable('installations')) {
            Schema::table('installations', function (Blueprint $table) {
                if (Schema::hasColumn('installations', 'current_stage_id')) {
                    $table->dropColumn('current_stage_id');
                }
            });
        }
    }
};
