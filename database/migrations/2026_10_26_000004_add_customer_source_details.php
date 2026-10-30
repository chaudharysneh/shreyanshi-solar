<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customers',function (Blueprint $table) {
            $table->foreignId('lead_source_id')->nullable()->constrained('lead_sources')->restrictOnDelete();
            $table->string('source')->nullable();
            $table->string('referral_name')->nullable();
            $table->foreignId('channel_partner_id')->nullable()->constrained()->restrictOnDelete();
        });
        DB::table('leads')->whereNull('deleted_at')->where('is_converted',true)->whereNotNull('converted_customer_id')->orderBy('created_at')->orderBy('id')->get()->unique('converted_customer_id')->each(function ($lead) {
            DB::table('customers')->where('id',$lead->converted_customer_id)->update([
                'lead_source_id'=>$lead->lead_source_id,'source'=>$lead->source,
                'referral_name'=>$lead->referral_name,'channel_partner_id'=>$lead->channel_partner_id,
            ]);
        });
    }
    public function down(): void
    {
        Schema::table('customers',function (Blueprint $table) {
            $table->dropConstrainedForeignId('lead_source_id');$table->dropConstrainedForeignId('channel_partner_id');
            $table->dropColumn(['source','referral_name']);
        });
    }
};
