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
        Schema::dropIfExists('invoices');

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('currency_id')->nullable();

            // Metadata
            $table->string('invoice_name')->nullable();
            $table->string('type')->nullable(); // e.g., 'quotation', 'invoice'
            $table->string('attach_file')->nullable();
            $table->string('invoice_no')->nullable()->unique();
            $table->date('invoice_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status')->default('unpaid');
            $table->boolean('is_quotation')->default(false);
            $table->text('comment')->nullable();

            // Product & Charges
            $table->decimal('quantity', 12, 2)->default(0);
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('solar_structure_charges', 12, 2)->default(0);
            $table->string('solar_meter_charges')->nullable();
            $table->decimal('other_charges', 12, 2)->default(0);
            $table->text('product_name')->nullable();

            // Financials
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('gst', 12, 2)->default(0); // GST percentage or flat rate?
            $table->decimal('gst_amount', 12, 2)->default(0);
            $table->text('gst_breakdown')->nullable(); // JSON storage
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('subsidy_amount', 12, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0); // Final amount after tax/discount

            // Data storage
            $table->text('generation_data')->nullable(); // JSON storage
            $table->text('customer_docs')->nullable(); // JSON storage

            // Blameable columns
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('template_id')->references('id')->on('pdf_builder_forms')->onDelete('set null');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
