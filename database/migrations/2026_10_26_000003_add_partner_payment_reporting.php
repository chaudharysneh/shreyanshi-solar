<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('channel_partners', fn (Blueprint $table) => $table->string('commission_currency', 3)->default('INR'));
        Schema::create('channel_partner_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_partner_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3);
            $table->date('payment_date');
            $table->string('method', 30);
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('submission_key')->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_partner_payments');
        Schema::table('channel_partners', fn (Blueprint $table) => $table->dropColumn('commission_currency'));
    }
};
