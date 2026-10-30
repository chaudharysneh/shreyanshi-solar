<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('product_name')->nullable();
            $table->decimal('price', 10, 2)->nullable();

            $table->foreignId('category_id')->nullable()->constrained('category')->nullOnDelete();
            $table->foreignId('technology_id')->nullable()->constrained('technology')->nullOnDelete();
            $table->foreignId('warranty_id')->nullable()->constrained('warranty')->nullOnDelete();

            $table->text('description')->nullable();
            $table->string('height')->nullable();
            $table->string('fitting_material')->nullable();
            $table->string('fitting_type')->nullable();
            $table->string('thickness')->nullable();
            $table->string('size_of_pipe')->nullable();
            $table->string('capacity')->nullable();
            $table->string('meter')->nullable();
            $table->string('nos')->nullable();
            $table->string('image')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product');
    }
};
