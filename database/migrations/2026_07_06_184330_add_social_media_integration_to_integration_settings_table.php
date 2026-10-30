<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('integration_settings', function (Blueprint $table) {
            $table->boolean('social_media_integration')->default(true)->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('integration_settings', function (Blueprint $table) {
            $table->dropColumn('social_media_integration');
        });
    }
};
