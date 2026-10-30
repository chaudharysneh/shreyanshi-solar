<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estimates', function (Blueprint $table) {
            $table->id('estimate_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->string('estimate_name')->nullable();
            $table->string('estimate_no')->nullable();
            $table->enum('type', ['estimate', 'quotation'])->default('estimate');
            $table->date('estimate_date')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('currency', 10)->nullable();
            $table->integer('quantity')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('solar_structure_charges', 12, 2)->nullable();
            $table->string('solar_meter_charges')->nullable();
            $table->decimal('total', 12, 2)->nullable();
            $table->decimal('gst', 12, 2)->nullable();
            $table->decimal('gst_amount', 12, 2)->nullable();
            $table->json('gst_breakdown')->nullable();
            $table->decimal('discount', 12, 2)->nullable();
            $table->decimal('subsidy_amount', 12, 2)->nullable();
            $table->decimal('other_charges', 12, 2)->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('product_name')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'converted'])->default('pending');
            $table->text('comment')->nullable();
            $table->string('attach_file')->nullable();
            $table->json('customer_docs')->nullable();
            $table->json('generation_data')->nullable();
            $table->boolean('is_quotation')->default(false);
            $table->boolean('isDeleted')->default(false);
            
            // Audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estimates');
    }
};
