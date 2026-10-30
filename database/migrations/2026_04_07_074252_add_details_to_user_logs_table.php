<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_logs') || Schema::hasColumn('user_logs', 'details')) {
            return;
        }

        Schema::table('user_logs', function (Blueprint $table) {
            $table->json('details')->nullable()->after('message');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('user_logs') || !Schema::hasColumn('user_logs', 'details')) {
            return;
        }

        Schema::table('user_logs', function (Blueprint $table) {
            $table->dropColumn('details');
        });
    }
};
