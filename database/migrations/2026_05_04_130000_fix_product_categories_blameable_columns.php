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
        Schema::table('product_categories', function (Blueprint $table) {
            // Check if modified_by exists and updated_by doesn't
            if (Schema::hasColumn('product_categories', 'modified_by') && !Schema::hasColumn('product_categories', 'updated_by')) {
                DB::statement('ALTER TABLE product_categories CHANGE modified_by updated_by BIGINT UNSIGNED NULL');
            }
            
            // Add deleted_by if it doesn't exist
            if (!Schema::hasColumn('product_categories', 'deleted_by')) {
                $table->unsignedBigInteger('deleted_by')->nullable()->after('updated_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            if (Schema::hasColumn('product_categories', 'updated_by')) {
                DB::statement('ALTER TABLE product_categories CHANGE updated_by modified_by BIGINT UNSIGNED NULL');
            }
            
            if (Schema::hasColumn('product_categories', 'deleted_by')) {
                $table->dropColumn('deleted_by');
            }
        });
    }
};
