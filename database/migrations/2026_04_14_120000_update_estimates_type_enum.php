<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change the type enum to include the 4 new types
        Schema::table('estimates', function (Blueprint $table) {
            // For MySQL, we need to modify the column
            DB::statement("ALTER TABLE estimates MODIFY COLUMN type ENUM('residential', 'commercial', 'industrial', 'common meter', 'estimate', 'quotation') DEFAULT 'residential'");
        });
    }

    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            // Revert to original enum
            DB::statement("ALTER TABLE estimates MODIFY COLUMN type ENUM('estimate', 'quotation') DEFAULT 'estimate'");
        });
    }
};
