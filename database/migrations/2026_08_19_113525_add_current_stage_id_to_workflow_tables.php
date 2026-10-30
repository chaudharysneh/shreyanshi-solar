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
        $tables = [
            'document_verifications',
            'registrations',
            'feasibilities',
            'waiting_floors',
            'dispatches',
            'net_meterings',
            'subsidies',
            'intimations'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    if (!Schema::hasColumn($table->getTable(), 'current_stage_id')) {
                        $table->unsignedBigInteger('current_stage_id')->nullable()->after('status');
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'document_verifications',
            'registrations',
            'feasibilities',
            'waiting_floors',
            'dispatches',
            'net_meterings',
            'subsidies',
            'intimations'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    if (Schema::hasColumn($table->getTable(), 'current_stage_id')) {
                        $table->dropColumn('current_stage_id');
                    }
                });
            }
        }
    }
};
