<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lead_sources', function (Blueprint $table) {
            $table->string('kind', 30)->default('standard');
        });
        Schema::create('channel_partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 50);
            $table->text('address')->nullable();
            $table->timestamps();
        });
        Schema::table('leads', function (Blueprint $table) {
            $table->string('referral_name')->nullable();
            $table->foreignId('channel_partner_id')->nullable()->constrained()->restrictOnDelete();
        });
        foreach (['Referral' => 'referral', 'Channel Partner' => 'channel_partner'] as $name => $kind) {
            $existing = DB::table('lead_sources')->whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
            if ($existing) DB::table('lead_sources')->where('id', $existing->id)->update(['kind' => $kind]);
            else DB::table('lead_sources')->insert(['name' => $name, 'kind' => $kind, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
        // Preserve free-text sources already saved by the previous lead form.
        foreach (DB::table('leads')->whereNotNull('source')->where('source', '<>', '')->distinct()->pluck('source') as $name) {
            $source = DB::table('lead_sources')->where('name', $name)->first();
            $id = $source?->id ?? DB::table('lead_sources')->insertGetId(['name' => $name, 'kind' => 'standard', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('leads')->whereNull('lead_source_id')->where('source', $name)->update(['lead_source_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('channel_partner_id');
            $table->dropColumn('referral_name');
        });
        Schema::dropIfExists('channel_partners');
        Schema::table('lead_sources', fn (Blueprint $table) => $table->dropColumn('kind'));
    }
};
