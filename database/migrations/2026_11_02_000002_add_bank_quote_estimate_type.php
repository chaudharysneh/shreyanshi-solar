<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE estimates MODIFY COLUMN type ENUM('residential', 'commercial', 'industrial', 'common meter', 'ground mounted', 'ux template', 'estimate', 'quotation', 'bank quote') DEFAULT 'residential'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            if (DB::table('estimates')->where('type', 'bank quote')->exists()) {
                throw new \RuntimeException('Bank quotations exist. Reassign their type before rolling back this migration.');
            }
            DB::statement("ALTER TABLE estimates MODIFY COLUMN type ENUM('residential', 'commercial', 'industrial', 'common meter', 'ground mounted', 'ux template', 'estimate', 'quotation') DEFAULT 'residential'");
        }
    }
};
