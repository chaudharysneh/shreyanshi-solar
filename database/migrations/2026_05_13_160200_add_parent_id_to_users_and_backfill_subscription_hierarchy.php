<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'parent_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('parent_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            });
        }

        $rolesTable = config('permission.table_names.roles', 'roles');
        $modelHasRolesTable = config('permission.table_names.model_has_roles', 'model_has_roles');
        $userModel = \App\Models\User::class;

        if (
            !Schema::hasTable($rolesTable) ||
            !Schema::hasTable($modelHasRolesTable) ||
            !Schema::hasTable('subscription_plan') ||
            !Schema::hasTable('subscription_user_plan')
        ) {
            return;
        }

        $adminRoleIds = DB::table($rolesTable)
            ->whereIn('name', ['admin', 'super-admin'])
            ->pluck('id');

        if ($adminRoleIds->isEmpty()) {
            return;
        }

        $adminIds = DB::table($modelHasRolesTable)
            ->where('model_type', $userModel)
            ->whereIn('role_id', $adminRoleIds)
            ->pluck('model_id')
            ->unique()
            ->values();

        if ($adminIds->isEmpty()) {
            return;
        }

        if (Schema::hasColumn('users', 'created_by')) {
            DB::table('users')
                ->whereNull('parent_id')
                ->whereNotIn('id', $adminIds->all())
                ->whereIn('created_by', $adminIds->all())
                ->update(['parent_id' => DB::raw('created_by')]);
        }

        $basicPlanId = DB::table('subscription_plan')
            ->where('name', 'Basic Plan')
            ->value('id');

        if ($basicPlanId) {
            $now = now();

            foreach ($adminIds as $adminId) {
                $exists = DB::table('subscription_user_plan')
                    ->where('user_id', $adminId)
                    ->exists();

                if (!$exists) {
                    DB::table('subscription_user_plan')->insert([
                        'user_id' => $adminId,
                        'subscription_id' => $basicPlanId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'parent_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('parent_id');
            });
        }
    }
};
