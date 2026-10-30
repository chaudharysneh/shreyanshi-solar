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
        Schema::table('subsidies', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('aspl_id')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->text('remarks')->nullable();
            $table->string('previous_stage')->nullable();
            $table->decimal('subsidy_amount', 10, 2)->nullable();
            $table->string('application_number')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subsidies', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['assigned_to']);
            $table->dropColumn([
                'customer_id',
                'aspl_id',
                'status',
                'assigned_to',
                'remarks',
                'previous_stage',
                'subsidy_amount',
                'application_number'
            ]);
        });
    }
};
