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
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'finance_id')) {
                $table->unsignedBigInteger('finance_id')->nullable()->after('type');
            }
            if (!Schema::hasColumn('customers', 'installation_master_id')) {
                $table->unsignedBigInteger('installation_master_id')->nullable()->after('finance_id');
            }
            
            // Optionally add foreign keys if tables exist and you want constraints
            // $table->foreign('finance_id')->references('id')->on('finance_masters')->onDelete('set null');
            // $table->foreign('installation_master_id')->references('id')->on('installation_masters')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('finance_id');
            $table->dropColumn('installation_master_id');
        });
    }
};
