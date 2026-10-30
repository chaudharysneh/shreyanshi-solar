<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            if (!Schema::hasColumn('deals', 'estimate_id')) {
                $table->unsignedBigInteger('estimate_id')->nullable()->after('customer_id');
                $table->index('estimate_id');
            }

            if (!Schema::hasColumn('deals', 'timeline_value')) {
                $table->unsignedInteger('timeline_value')->nullable()->after('amount');
            }

            if (!Schema::hasColumn('deals', 'timeline_unit')) {
                $table->string('timeline_unit', 20)->nullable()->after('timeline_value');
            }
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            if (Schema::hasColumn('deals', 'timeline_unit')) {
                $table->dropColumn('timeline_unit');
            }

            if (Schema::hasColumn('deals', 'timeline_value')) {
                $table->dropColumn('timeline_value');
            }

            if (Schema::hasColumn('deals', 'estimate_id')) {
                $table->dropIndex(['estimate_id']);
                $table->dropColumn('estimate_id');
            }
        });
    }
};
