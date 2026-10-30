<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('channel_partners', function (Blueprint $table) {
            $table->string('partner_code')->nullable()->unique();
            $table->string('whatsapp', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('company_name')->nullable();
            $table->string('status', 20)->default('active');
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('joining_date')->nullable();
            $table->text('notes')->nullable();
            $table->string('commission_type', 20)->default('fixed');
            $table->decimal('commission_value', 14, 2)->default(0);
            $table->string('commission_basis', 30)->default('estimate_subtotal');
            $table->string('payable_on', 30)->default('payment_collection');
            $table->string('pan_number', 10)->nullable();
            $table->string('gst_number', 15)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_holder')->nullable();
            $table->string('account_number', 40)->nullable();
            $table->string('ifsc_code', 11)->nullable();
            $table->string('agreement_path')->nullable();
            $table->string('agreement_name')->nullable();
        });
        DB::table('channel_partners')->orderBy('id')->chunkById(200, function ($partners) {
            foreach ($partners as $partner) DB::table('channel_partners')->where('id', $partner->id)->update([
                'partner_code' => 'CP-'.str_pad($partner->id, 6, '0', STR_PAD_LEFT),
                'joining_date' => substr($partner->created_at ?? now()->toDateString(), 0, 10),
            ]);
        });
        if (Schema::hasTable('permissions')) {
            foreach (['view', 'create', 'edit', 'delete'] as $action) \Spatie\Permission\Models\Permission::findOrCreate($action.'_partners', 'web');
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        Schema::table('channel_partners', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_user_id');
            $table->dropColumn(['partner_code', 'whatsapp', 'email', 'company_name', 'status', 'joining_date', 'notes', 'commission_type', 'commission_value', 'commission_basis', 'payable_on', 'pan_number', 'gst_number', 'bank_name', 'account_holder', 'account_number', 'ifsc_code', 'agreement_path', 'agreement_name']);
        });
    }
};
