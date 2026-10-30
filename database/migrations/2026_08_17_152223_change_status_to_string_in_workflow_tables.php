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
            'document_verifications', 'registrations', 'feasibilities', 
            'finances', 'waiting_floors', 'dispatches', 'installations', 
            'intimations', 'net_meterings', 'subsidies'
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                // Change status from enum to string
                $table->string('status')->default('pending')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workflow_tables', function (Blueprint $table) {
            //
        });
    }
};
