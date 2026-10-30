<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Product;
use App\Models\ProductInventory;

return new class extends Migration
{
    public function up(): void
    {
        Product::all()->each(function ($product) {
            if ($product->inventories()->count() === 0) {
                ProductInventory::create([
                    'product_id' => $product->id,
                    'initial_stock' => $product->quantity,
                    'current_stock' => $product->quantity,
                    'type' => 'create',
                    'date' => $product->created_at->toDateString(),
                    'created_by' => 1,
                ]);
            }
        });
    }

    public function down(): void
    {
        ProductInventory::where('type', 'create')->delete();
    }
};
