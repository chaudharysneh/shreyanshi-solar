<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schema::dropIfExists('products');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        /**
         * IMPORTANT:
         * Your categories.id is likely INT UNSIGNED (old table),
         * not BIGINT UNSIGNED.
         *
         * So category_id must match exactly:
         * unsignedInteger('category_id')
         */

        Schema::create('products', function (Blueprint $table) {

            $table->id();

            // FIXED foreign key type
            $table->unsignedBigInteger('category_id')->nullable();

            $table->string('serial_no')->nullable()->unique();
            $table->string('name');
            $table->text('description')->nullable();

            $table->decimal('price', 10, 2)->default(0);
            $table->integer('quantity')->default(0);

            $table->enum('status', ['active', 'inactive'])->nullable();
            $table->enum('availability', ['in_stock', 'out_of_stock'])->nullable();

            $table->string('image')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        // Add foreign keys after create
        Schema::table('products', function (Blueprint $table) {

            $table->foreign('category_id')
                ->references('id')
                ->on('product_categories')
                ->nullOnDelete();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('deleted_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};