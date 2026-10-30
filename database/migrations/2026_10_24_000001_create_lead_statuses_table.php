<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Custom master values must not be constrained to the original enum.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE leads MODIFY COLUMN status VARCHAR(255) NOT NULL DEFAULT 'new'");
        }
        // SQLite stores the existing status column as text already.
        Schema::create('lead_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('code', 100)->unique();
            $table->timestamps();
        });
        foreach (\App\Models\LeadStatus::DEFAULTS as $code => $name) {
            DB::table('lead_statuses')->insert([
                'code' => $code, 'name' => $name, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        // Preserve any additional values already present in imported leads.
        foreach (DB::table('leads')->whereNotNull('status')->distinct()->pluck('status') as $code) {
            DB::table('lead_statuses')->insertOrIgnore([
                'code' => $code, 'name' => ucwords(str_replace('_', ' ', $code)),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_statuses');
        // Keep the string column so rollback never truncates custom lead values.
    }
};
