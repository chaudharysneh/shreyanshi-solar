<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop table if it exists
        Schema::dropIfExists('tasks');

        // Create fresh table with all columns
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('related_type')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->unsignedBigInteger('estimate_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('assigned_user_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->date('due_date')->nullable();
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->timestamps();

            // Indexes
            $table->index(['related_type', 'related_id']);
            $table->index(['assigned_user_id', 'status']);
            $table->index('estimate_id');
            $table->index('project_id');
            $table->index('user_id');

            // Foreign Keys
            $table->foreign('estimate_id')->references('estimate_id')->on('estimates')->onDelete('set null');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('set null');
            $table->foreign('assigned_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
