<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\EstimateController;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class EstimateHsnSacTest extends TestCase
{
    public function test_product_json_preserves_hsn_sac_including_leading_zeroes(): void
    {
        $method = new ReflectionMethod(EstimateController::class, 'normalizeEstimateProducts');
        $request = new Request([
            'products' => json_encode([
                ['product_id' => '1', 'hsn_sac' => '009954'],
                ['product_id' => '2', 'hsn_sac' => '9954'],
                ['product_id' => '3', 'hsn_sac' => ''],
                ['product_id' => '4', 'hsn' => '8541'],
                ['product_id' => '5'],
            ]),
        ]);

        $products = $method->invoke(new EstimateController(), $request);

        $this->assertSame(['009954', '9954', '', '8541', ''], array_column($products, 'hsn_sac'));
    }
}
