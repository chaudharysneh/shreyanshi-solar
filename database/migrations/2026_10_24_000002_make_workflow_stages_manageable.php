<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['workflow_stages', 'finance_stages', 'installation_stages'] as $table) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->string('system_key', 30)->nullable());
        }
        foreach (['finances', 'installations'] as $table) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->string('current_stage_type', 20)->nullable());
        }
        $catalog = app(\App\Services\WorkflowStageCatalog::class);
        foreach (array_keys($catalog::TYPES) as $type) $catalog->ensureDefaults($catalog->context($type));
        foreach (\App\Models\FinanceMaster::pluck('id') as $id) $catalog->ensureDefaults($catalog->context('finance', $id));
        foreach (\App\Models\InstallationMaster::pluck('id') as $id) $catalog->ensureDefaults($catalog->context('installations', $id));
    }

    public function down(): void
    {
        foreach (['workflow_stages', 'finance_stages', 'installation_stages'] as $table) Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn('system_key'));
        foreach (['finances', 'installations'] as $table) Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn('current_stage_type'));
    }
};
