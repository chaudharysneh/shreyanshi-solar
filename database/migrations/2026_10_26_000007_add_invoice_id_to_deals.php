<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, DB};
return new class extends Migration {
    public function up(): void
    {
        Schema::table('deals', fn (Blueprint $table) => $table->unsignedBigInteger('invoice_id')->nullable()->index());
        DB::table('deals')->whereNotNull('estimate_id')->orderBy('id')->chunkById(100, function ($deals) {
            foreach ($deals as $deal) {
                $invoiceId = DB::table('invoices')->where('estimate_id', $deal->estimate_id)->where('customer_id', $deal->customer_id)->whereNull('deleted_at')->latest('id')->value('id');
                if ($invoiceId) DB::table('deals')->where('id', $deal->id)->update(['invoice_id' => $invoiceId]);
            }
        });
    }
    public function down(): void { Schema::table('deals', fn (Blueprint $table) => $table->dropColumn('invoice_id')); }
};
