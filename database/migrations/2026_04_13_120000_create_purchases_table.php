<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id('invoice_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->string('invoice_name')->nullable();
            $table->string('invoice_no')->nullable();
            $table->enum('type', ['purchase', 'sale', 'estimate'])->nullable();
            $table->date('invoice_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('currency', 10)->nullable();
            $table->integer('quantity')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('solar_structure_charges', 12, 2)->nullable();
            $table->decimal('solar_meter_charges', 12, 2)->nullable();
            $table->decimal('total', 12, 2)->nullable();
            $table->decimal('gst', 12, 2)->nullable();
            $table->decimal('discount', 12, 2)->nullable();
            $table->decimal('subsidy_amount', 12, 2)->nullable();
            $table->decimal('other_charges', 12, 2)->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('product_name')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending');
            $table->text('comment')->nullable();
            $table->string('attach_file')->nullable();
            $table->json('customer_docs')->nullable();
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
        Schema::dropIfExists('purchases');
    }
};
