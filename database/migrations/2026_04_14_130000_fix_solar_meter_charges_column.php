<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change solar_meter_charges from decimal to string
        Schema::table('estimates', function (Blueprint $table) {
            DB::statement("ALTER TABLE estimates MODIFY COLUMN solar_meter_charges VARCHAR(255) NULL");
        });
    }

    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            DB::statement("ALTER TABLE estimates MODIFY COLUMN solar_meter_charges DECIMAL(12, 2) NULL");
        });
    }
};
