<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TableTruncateController extends Controller
{
    // Only allow these specific module tables to be truncated
    public static $allowedModulesTables = [
        // Staff
        'users',
        // Customers
        'customers',
        // Leads
        'leads',
        // Follow up
        'follow_ups',
        // Meetings
        'meetings',
        // BOM
        'product',
        'makes',
        'technology',
        'warranty',
        // Estimates
        'estimates',
        // Deals
        'deals',
        // Inventory / Sales / Purchases / Invoices
        'products',
        'product_categories',
        'sales',
        'purchases',
        'product_inventory',
        'vendors',
        'handover_persons',
        'invoices',
        // Tasks
        'tasks',
        // Tickets
        'support_tickets'
    ];

    public function truncate(Request $request, $table)
    {
        if (!in_array($table, self::$allowedModulesTables)) {
            return response()->json([
                'success' => false,
                'message' => 'Table not allowed to be truncated.'
            ], 403);
        }

        try {
            if ($table === 'users') {
                // Delete all non-admin users
                Schema::disableForeignKeyConstraints();
                User::nonAdmin()->delete();
                Schema::enableForeignKeyConstraints();
            } else {
                Schema::disableForeignKeyConstraints();
                DB::table($table)->truncate();
                Schema::enableForeignKeyConstraints();
            }

            return response()->json([
                'success' => true,
                'message' => "Table '{$table}' has been truncated successfully."
            ]);
        } catch (\Exception $e) {
            Schema::enableForeignKeyConstraints();
            return response()->json([
                'success' => false,
                'message' => 'Error truncating table: ' . $e->getMessage()
            ], 500);
        }
    }

    public function truncateAll(Request $request)
    {
        try {
            $tables = DB::select('SHOW TABLES');
            $allTables = array_map('current', $tables);
            
            // Only take tables that exist in DB AND are in our whitelist
            $allowedTables = array_intersect($allTables, self::$allowedModulesTables);

            Schema::disableForeignKeyConstraints();

            foreach ($allowedTables as $table) {
                if ($table === 'users') {
                    User::nonAdmin()->delete();
                } else {
                    DB::table($table)->truncate();
                }
            }

            Schema::enableForeignKeyConstraints();

            return response()->json([
                'success' => true,
                'message' => 'All allowed tables have been truncated successfully.'
            ]);
        } catch (\Exception $e) {
            Schema::enableForeignKeyConstraints();
            return response()->json([
                'success' => false,
                'message' => 'Error truncating tables: ' . $e->getMessage()
            ], 500);
        }
    }
}
