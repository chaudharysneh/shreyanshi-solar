<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE estimates MODIFY COLUMN type ENUM('residential', 'commercial', 'industrial', 'common meter', 'ground mounted', 'ux template', 'estimate', 'quotation') DEFAULT 'residential'");
        DB::statement("ALTER TABLE invoices MODIFY COLUMN type ENUM('residential', 'commercial', 'industrial', 'common meter', 'ground mounted', 'ux template', 'estimate', 'quotation') DEFAULT 'residential'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estimates_and_invoices_tables', function (Blueprint $table) {
            //
        });
    }
};
