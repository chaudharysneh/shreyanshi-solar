<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->string('price_mode', 10)->nullable()->after('price');
        });

        DB::table('estimates')->select('estimate_id', 'price', 'product_name', 'gst_breakdown')
            ->orderBy('estimate_id')
            ->chunkById(200, function ($estimates) {
                foreach ($estimates as $estimate) {
                    $breakdown = json_decode((string) $estimate->gst_breakdown, true) ?: [];
                    $mode = null;

                    foreach (($breakdown['groups'] ?? []) as $group) {
                        $taxType = (string) ($group['tax_type'] ?? '');
                        if ($taxType === 'global_tax') {
                            $mode = 'base';
                            break;
                        }
                        if ($taxType === 'bom_selected_tax') {
                            $mode = 'bom';
                            break;
                        }
                    }

                    if ($mode === null) {
                        $products = json_decode((string) $estimate->product_name, true) ?: [];
                        $bomTotal = collect($products)->sum(fn ($product) =>
                            (float) ($product['quantity'] ?? 0) * (float) ($product['price'] ?? 0)
                        );
                        $mode = (float) $estimate->price > 0 && $bomTotal <= 0 ? 'base' : 'bom';
                    }

                    DB::table('estimates')->where('estimate_id', $estimate->estimate_id)
                        ->update(['price_mode' => $mode]);
                }
            }, 'estimate_id');
    }

    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->dropColumn('price_mode');
        });
    }
};
