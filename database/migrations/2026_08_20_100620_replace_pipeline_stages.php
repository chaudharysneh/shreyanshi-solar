<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Delete all existing stages
        DB::table('stages')->delete();

        // 2. Insert new stages
        $stages = [
            ['name' => 'Document Verification', 'sort_order' => 1, 'is_default' => true],
            ['name' => 'Registration', 'sort_order' => 2, 'is_default' => false],
            ['name' => 'Meter Charge', 'sort_order' => 3, 'is_default' => false],
            ['name' => 'Finance', 'sort_order' => 4, 'is_default' => false],
            ['name' => 'Waiting Floor', 'sort_order' => 5, 'is_default' => false],
            ['name' => 'Dispatch', 'sort_order' => 6, 'is_default' => false],
            ['name' => 'Installations', 'sort_order' => 7, 'is_default' => false],
            ['name' => 'Intimation', 'sort_order' => 8, 'is_default' => false],
            ['name' => 'Net Metering', 'sort_order' => 9, 'is_default' => false],
            ['name' => 'Subsidy', 'sort_order' => 10, 'is_default' => false],
        ];

        $firstStageId = null;
        $now = Carbon::now();

        foreach ($stages as $stage) {
            $id = DB::table('stages')->insertGetId([
                'name' => $stage['name'],
                'sort_order' => $stage['sort_order'],
                'is_default' => $stage['is_default'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            
            if ($stage['sort_order'] === 1) {
                $firstStageId = $id;
            }
        }

        // 3. Map any existing customers to the first stage
        if ($firstStageId) {
            DB::table('customers')->update(['pipeline_stage_id' => $firstStageId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback to old stages
        DB::table('stages')->delete();

        $oldStages = [
            ['name' => 'New', 'sort_order' => 1, 'is_default' => true],
            ['name' => 'Contacted', 'sort_order' => 2, 'is_default' => false],
            ['name' => 'Proposal Sent', 'sort_order' => 3, 'is_default' => false],
            ['name' => 'Negotiation', 'sort_order' => 4, 'is_default' => false],
            ['name' => 'Won', 'sort_order' => 5, 'is_default' => false],
            ['name' => 'Lost', 'sort_order' => 6, 'is_default' => false],
        ];
        
        $firstStageId = null;
        $now = Carbon::now();

        foreach ($oldStages as $stage) {
            $id = DB::table('stages')->insertGetId([
                'name' => $stage['name'],
                'sort_order' => $stage['sort_order'],
                'is_default' => $stage['is_default'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            
            if ($stage['sort_order'] === 1) {
                $firstStageId = $id;
            }
        }
        
        if ($firstStageId) {
            DB::table('customers')->update(['pipeline_stage_id' => $firstStageId]);
        }
    }
};
