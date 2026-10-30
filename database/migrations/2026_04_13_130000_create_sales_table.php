<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {

            $table->id();

            $table->integer('invoice_id')->nullable();
            $table->unsignedBigInteger('customer_id');
            $table->integer('user_id');
            $table->string('invoice_name', 200)->nullable();
            $table->string('type', 100)->nullable();
            $table->text('attach_file')->nullable();
            $table->string('quantity', 100)->nullable();
            $table->string('price', 100)->nullable();
            $table->string('solar_structure_charges', 100)->nullable();
            $table->string('solar_meter_charges', 100)->nullable();
            $table->integer('template_id')->nullable();
            $table->integer('product_id');
            $table->integer('handover_id')->nullable();
            $table->string('invoice_no', 255)->unique();
            $table->string('invoice_date', 50);
            $table->string('due_date', 50)->nullable();
            $table->string('currency', 50);
            $table->string('total', 30)->nullable();
            $table->string('gst', 100)->nullable();
            $table->string('other_charges', 100)->nullable();
            $table->string('discount', 100)->nullable();
            $table->string('subsidy_amount', 100)->nullable();
            $table->integer('amount')->nullable();
            $table->string('product_name', 255)->nullable();
            $table->string('status', 50);
            $table->string('comment', 255)->nullable();
            $table->text('customer_docs')->nullable();
            $table->integer('isDeleted')->default(0);

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);

            $table->date('sale_date')->nullable();
            $table->text('notes')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};