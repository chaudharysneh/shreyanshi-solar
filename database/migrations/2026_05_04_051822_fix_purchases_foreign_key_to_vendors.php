<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Set customer_id to NULL for any records that don't have a matching vendor
        DB::statement('UPDATE purchases SET customer_id = NULL WHERE customer_id NOT IN (SELECT id FROM vendors) OR customer_id IS NULL');
        
        // Drop the foreign key using raw SQL if it exists
        DB::statement('ALTER TABLE purchases DROP FOREIGN KEY IF EXISTS purchases_customer_id_foreign');
        
        // Add the new foreign key pointing to vendors table
        DB::statement('ALTER TABLE purchases ADD CONSTRAINT purchases_customer_id_foreign FOREIGN KEY (customer_id) REFERENCES vendors(id) ON DELETE SET NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the new foreign key
        DB::statement('ALTER TABLE purchases DROP FOREIGN KEY IF EXISTS purchases_customer_id_foreign');
        
        // Restore the old foreign key pointing to customers
        DB::statement('ALTER TABLE purchases ADD CONSTRAINT purchases_customer_id_foreign FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL');
    }
};
