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
        Schema::table('intimations', function (Blueprint $table) {
            $table->string('self_certificate_status')->default('pending')->nullable();
            $table->string('self_certificate_file')->nullable();
            $table->string('gps_photo_status')->default('pending')->nullable();
            $table->string('gps_photo_file')->nullable();
            $table->string('dcr_certificate_status')->default('pending')->nullable();
            $table->string('dcr_certificate_file')->nullable();
            $table->string('model_agreement_status')->default('pending')->nullable();
            $table->string('model_agreement_file')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intimations', function (Blueprint $table) {
            $table->dropColumn([
                'self_certificate_status', 'self_certificate_file',
                'gps_photo_status', 'gps_photo_file',
                'dcr_certificate_status', 'dcr_certificate_file',
                'model_agreement_status', 'model_agreement_file'
            ]);
        });
    }
};
