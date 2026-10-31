<?php

namespace Tests\Feature;

use App\Models\BomProduct;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BomHsnSacSchemaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('product', function (Blueprint $table) {
            $table->id();
            $table->string('product_name');
            $table->decimal('price', 10, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_repair_allows_bom_saves_and_preserves_existing_products(): void
    {
        DB::table('product')->insert(['product_name' => 'Existing BOM']);
        $migration = require database_path('migrations/2026_10_31_000001_repair_hsn_sac_on_product_table.php');
        $migration->up();

        $product = BomProduct::withoutEvents(fn () => BomProduct::create([
            'product_name' => 'New BOM',
            'hsn_sac' => '008541',
            'price' => 100,
        ]));

        $this->assertSame('008541', $product->fresh()->hsn_sac);
        $this->assertNull(BomProduct::where('product_name', 'Existing BOM')->firstOrFail()->hsn_sac);

        // Re-running the repair on an already-correct database must retain codes.
        $migration->up();
        $migration->down();
        $this->assertSame('008541', $product->fresh()->hsn_sac);
    }
}
