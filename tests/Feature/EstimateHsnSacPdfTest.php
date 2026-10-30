<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\EstimateController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class EstimateHsnSacPdfTest extends TestCase
{
    public function test_saved_product_codes_appear_in_the_basic_estimate_pdf_view(): void
    {
        // Use an isolated database; never modify the application's BOM records.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        Schema::create('product', function (Blueprint $table) {
            $table->id();
            $table->string('product_name');
            $table->softDeletes();
        });

        $method = new ReflectionMethod(EstimateController::class, 'normalizeEstimateProducts');
        $products = $method->invoke(new EstimateController(), new Request([
            'products' => json_encode([
                ['product_id' => '1', 'name' => 'Installation', 'hsn_sac' => '009954', 'quantity' => 1, 'price' => 100],
                ['product_id' => '2', 'name' => 'Structure', 'hsn_sac' => '995429', 'quantity' => 2, 'price' => 50],
            ]),
        ]));

        $html = view('pdfbuilder.basic-template-pdf', [
            'estimate' => (object) ['product_name' => json_encode($products)],
            'companySettings' => ['sidebar_icon_path' => ''],
        ])->render();

        $this->assertStringContainsString('<td>009954</td>', $html);
        $this->assertStringContainsString('<td>995429</td>', $html);
    }
}
