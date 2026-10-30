<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('module_status_histories', function (Blueprint $table) {
            if (!Schema::hasColumn('module_status_histories', 'location_address')) {
                $table->text('location_address')->nullable()->after('comment');
            }

            if (!Schema::hasColumn('module_status_histories', 'location_latitude')) {
                $table->decimal('location_latitude', 10, 7)->nullable()->after('location_address');
            }

            if (!Schema::hasColumn('module_status_histories', 'location_longitude')) {
                $table->decimal('location_longitude', 10, 7)->nullable()->after('location_latitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('module_status_histories', function (Blueprint $table) {
            $dropColumns = [];

            foreach (['location_address', 'location_latitude', 'location_longitude'] as $column) {
                if (Schema::hasColumn('module_status_histories', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
