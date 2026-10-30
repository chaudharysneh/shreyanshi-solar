<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if(!Schema::hasTable('invoice_finance_plans')) Schema::create('invoice_finance_plans',function(Blueprint $t){
            $t->id();$t->unsignedBigInteger('invoice_id')->unique();$t->string('funding_type')->default('customer');
            $t->string('bank_name')->nullable();$t->string('branch_name')->nullable();$t->string('loan_reference')->nullable();
            $t->decimal('bank_amount',14,2)->default(0);$t->decimal('down_payment',14,2)->default(0);$t->date('expected_bank_date')->nullable();$t->text('notes')->nullable();$t->timestamps();
        });
        Schema::table('payments',function(Blueprint $t){if(!Schema::hasColumn('payments','source'))$t->string('source')->default('customer');if(!Schema::hasColumn('payments','created_by'))$t->unsignedBigInteger('created_by')->nullable();if(!Schema::hasColumn('payments','voided_at'))$t->dateTime('voided_at')->nullable();if(!Schema::hasColumn('payments','voided_by'))$t->unsignedBigInteger('voided_by')->nullable();if(!Schema::hasColumn('payments','void_reason'))$t->string('void_reason')->nullable();});
        if(!Schema::hasTable('invoice_finance_events')) Schema::create('invoice_finance_events',function(Blueprint $t){$t->id();$t->unsignedBigInteger('invoice_id')->index();$t->uuid('request_key')->unique();$t->string('payload_hash',64);$t->string('action');$t->json('details');$t->unsignedBigInteger('created_by')->nullable();$t->timestamps();});
    }
    public function down(): void {Schema::dropIfExists('invoice_finance_events');Schema::dropIfExists('invoice_finance_plans');Schema::table('payments',fn(Blueprint $t)=>$t->dropColumn(['source','voided_at','voided_by','void_reason']));}
};
