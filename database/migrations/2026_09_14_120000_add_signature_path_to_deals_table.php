<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('deals', 'signature_path')) {
            Schema::table('deals', function (Blueprint $table) {
                $table->string('signature_path')->nullable()->after('assigned_user_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('deals', 'signature_path')) {
            Schema::table('deals', function (Blueprint $table) {
                $table->dropColumn('signature_path');
            });
        }
    }
};
