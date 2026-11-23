<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('label', 100);
            $table->longText('value');
            $table->boolean('enabled')->default(true);
            $table->boolean('is_backup')->default(false);
            $table->timestamps();

            $table->unique(['label', 'is_backup']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_api_keys');
    }
};
