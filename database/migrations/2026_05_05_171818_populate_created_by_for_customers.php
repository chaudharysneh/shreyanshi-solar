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
        // Populate created_by for customers that have NULL created_by
        // Set to the first user (usually admin)
        $firstUser = DB::table('users')->first();
        $userId = $firstUser?->id ?? 1;

        DB::table('customers')
            ->whereNull('created_by')
            ->update(['created_by' => $userId]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a data migration, so we don't reverse it
        // Uncomment below if you want to revert to NULL
        // DB::table('customers')->update(['created_by' => null]);
    }
};
