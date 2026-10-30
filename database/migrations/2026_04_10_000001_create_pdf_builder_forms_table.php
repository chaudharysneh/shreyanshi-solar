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
        Schema::create('pdf_builder_forms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('form_title')->nullable();
            $table->string('template_name')->unique();
            $table->longText('form_data')->nullable();
            $table->text('company_information')->nullable();
            $table->text('time_line')->nullable();
            $table->text('components')->nullable();
            $table->text('payment_terms')->nullable();
            $table->text('environment_impact')->nullable();
            $table->text('footer')->nullable();
            $table->text('image_paths')->nullable();
            $table->string('first_img')->nullable();
            $table->string('pdf_file')->nullable();

            //created_by, updated_by and deleted_by fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            // Soft delete column
            $table->softDeletes();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdf_builder_forms');
    }
};
