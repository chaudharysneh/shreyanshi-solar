<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\InvoiceController;
use App\Models\BomProduct;
use App\Models\Invoice;
use App\Models\PdfBuilderForm;
use App\Models\User;
use App\Services\UserLogService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class InvoiceFormParityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach (['customers', 'users', 'pdf_builder_forms', 'products', 'currencies', 'technology', 'warranty', 'product'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('title')->nullable();
                $table->json('form_data')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
        Schema::create('taxes', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->decimal('rate');
            $table->boolean('is_active'); $table->softDeletes();
        });
        Schema::create('invoice_finance_plans', fn (Blueprint $t) => [$t->id(), $t->integer('invoice_id')]);
        Schema::create('payments', fn (Blueprint $t) => [$t->id(), $t->integer('invoice_id'), $t->timestamp('voided_at')->nullable()]);
        (require database_path('migrations/2026_04_14_134000_recreate_invoices_table.php'))->up();
        (require database_path('migrations/2026_10_30_000001_add_estimate_form_fields_to_invoices.php'))->up();
        foreach (['customers', 'users', 'pdf_builder_forms', 'currencies'] as $table) DB::table($table)->insert(['id' => 1, 'name' => 'Test']);
        DB::table('taxes')->insert(['name' => 'CGST + SGST', 'rate' => 18, 'is_active' => true]);
        $actor = new class extends User {
            public function isAdmin(): bool { return true; }
            public function checkPermissionTo($permission, $guardName = null): bool { return false; }
            public function hasMatrixPermission(string $permission): bool { return false; }
        };
        $actor->id = 1;
        $this->actingAs($actor);
        Gate::before(fn () => true);
        $this->instance(UserLogService::class, \Mockery::mock(UserLogService::class)->shouldIgnoreMissing());
    }

    private function payload(array $changes = []): array
    {
        return array_replace([
            'customer_id' => 1, 'invoice_name' => 'Test invoice', 'type' => 'residential',
            'quantity' => 3, 'price' => 0, 'price_mode' => 'bom', 'template_id' => 1,
            'currency_id' => 1, 'solar_meter_charges' => 'included', 'solar_structure_charges' => 50,
            'apply_gst' => 1, 'discount' => 10, 'subsidy_amount' => 20,
            'reference_no' => 'REF-001', 'terms_conditions' => 'Invoice-specific terms',
            'bom_specifications' => [7 => ['make' => 'Brand A', 'technical' => 'Custom specifications']],
            'products' => json_encode([['product_id' => '7', 'name' => 'Panel', 'quantity' => 2,
                'price' => 100, 'tax_rate' => 18, 'tax_label' => 'CGST + SGST', 'hsn_sac' => '008541',
                'description' => 'Custom panel description', 'technology_id' => '2', 'capacity' => '550 W']]),
        ], $changes);
    }

    private function store(array $changes = []): Invoice
    {
        $response = Invoice::withoutEvents(fn () => app(InvoiceController::class)->store(new Request($this->payload($changes))));
        $this->assertSame(201, $response->status(), $response->getContent());
        return Invoice::latest('id')->firstOrFail();
    }

    public function test_price_suggestions_are_calculated_by_capacity_and_do_not_save_an_invoice(): void
    {
        foreach ([10 => [300000, 400000, 450000], 3 => [90000, 120000, 135000]] as $kw => $expected) {
            $response = app(InvoiceController::class)->priceSuggestions(new Request(['quantity' => $kw]));
            $this->assertSame($expected, array_column($response->getData(true)['suggestions'], 'price'));
        }
        $response = app(InvoiceController::class)->priceSuggestions(new Request(['quantity' => 2.5]));
        $this->assertSame([75000, 100000, 112500], array_column($response->getData(true)['suggestions'], 'price'));
        $this->assertSame(0, Invoice::count());
        $route = app('router')->getRoutes()->match(Request::create('/api/invoices/price-suggestions', 'GET'));
        $this->assertSame('priceSuggestions', $route->getActionMethod());
    }

    public function test_price_suggestions_reject_invalid_capacity(): void
    {
        foreach ([0, -1, 'invalid'] as $quantity) {
            try {
                app(InvoiceController::class)->priceSuggestions(new Request(['quantity' => $quantity]));
                $this->fail('Invalid capacity was accepted');
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->assertArrayHasKey('quantity', $e->errors());
            }
        }
    }

    public function test_bom_mode_saves_all_details_and_row_taxes_without_adding_base_price(): void
    {
        $invoice = $this->store(['price' => 9999]);
        $this->assertEquals(0, $invoice->price);
        $this->assertEquals(250, $invoice->total);
        $this->assertEquals(36, $invoice->gst_amount);
        $this->assertEquals(256, $invoice->amount);
        $this->assertSame('bom', $invoice->price_mode);
        $this->assertSame('REF-001', $invoice->reference_no);
        $this->assertSame('Invoice-specific terms', $invoice->terms_conditions);
        $this->assertSame('Custom specifications', $invoice->bom_specifications[7]['technical']);
        $this->assertSame('008541', $invoice->product_name[0]['hsn_sac']);
        $this->assertSame('Custom panel description', $invoice->product_name[0]['description']);
        $this->assertSame('550 W', $invoice->product_name[0]['capacity']);
        $this->assertCount(2, $invoice->gst_breakdown['groups'][0]['lines']);
    }

    public function test_edit_switches_to_base_pricing_and_retains_updated_fields_on_reload(): void
    {
        $invoice = $this->store();
        $request = new Request($this->payload(['price_mode' => 'base', 'price' => 1000,
            'global_tax_rate' => 18, 'reference_no' => 'REF-002', 'terms_conditions' => 'Updated terms',
            'update_template_comment' => 1, 'comment' => 'Updated template comment']));
        $response = Invoice::withoutEvents(fn () => app(InvoiceController::class)->update($request, $invoice));
        $this->assertSame(200, $response->status(), $response->getContent());
        $invoice->refresh();
        $this->assertSame('base', $invoice->price_mode);
        $this->assertEquals(1050, $invoice->total);
        $this->assertEquals(189, $invoice->gst_amount);
        $this->assertEquals(1209, $invoice->amount);
        $this->assertEquals(0, $invoice->product_name[0]['price']);
        $this->assertEquals(0, $invoice->product_name[0]['tax_rate']);
        $this->assertSame('REF-002', $invoice->reference_no);
        $this->assertSame('Updated terms', $invoice->terms_conditions);
        $this->assertSame('Updated template comment', PdfBuilderForm::find(1)->form_data['estimate_comment']['content']);
    }

    public function test_base_mode_create_and_disabled_tax_match_the_form_totals(): void
    {
        $invoice = $this->store(['price_mode' => 'base', 'price' => 1000, 'global_tax_rate' => 18, 'apply_gst' => 0]);
        $this->assertEquals(1050, $invoice->total);
        $this->assertEquals(0, $invoice->gst_amount);
        $this->assertEquals(1020, $invoice->amount);
        $this->assertNull($invoice->gst_breakdown);
    }

    public function test_finance_protection_rejects_total_changes_and_rolls_back_new_fields(): void
    {
        $invoice = $this->store();
        DB::table('invoice_finance_plans')->insert(['invoice_id' => $invoice->id]);
        $response = Invoice::withoutEvents(fn () => app(InvoiceController::class)->update(new Request(
            $this->payload(['discount' => 100, 'reference_no' => 'Must not save'])
        ), $invoice));
        $this->assertSame(422, $response->status());
        $this->assertEquals(256, $invoice->fresh()->amount);
        $this->assertSame('REF-001', $invoice->fresh()->reference_no);
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_missing_bom_does_not_leave_an_open_transaction(): void
    {
        $response = app(InvoiceController::class)->store(new Request($this->payload(['products' => '[]'])));
        $this->assertSame(422, $response->status());
        $this->assertSame(0, DB::transactionLevel());
        $this->assertSame(0, Invoice::count());
    }

    public function test_invoice_create_and_edit_render_the_estimate_sections_with_invoice_endpoints(): void
    {
        $this->app['view']->getFinder()->prependLocation(base_path('tests/Fixtures/views'));
        view()->share('errors', new ViewErrorBag());
        $invoice = $this->store();
        $bom = new BomProduct(['product_name' => 'Panel', 'price' => 100]);
        $bom->id = 7; $bom->setRelation('categories', collect());
        $data = ['customers' => collect(), 'templates' => collect(), 'bomProducts' => collect([$bom]),
            'categories' => collect(), 'subsidies' => collect(), 'gstTaxes' => collect(),
            'gstRate' => 18, 'estimatePriceMode' => 'bom', 'defaultCurrencyId' => 1, 'invoice' => $invoice];
        foreach (['create', 'edit'] as $mode) {
            $html = view('crm.invoices.'.$mode, $data)->render();
            foreach (['Pricing Method', 'Reference#', 'Invoice Format', 'data-price-suggestions-url=', 'HSN/SAC Code', 'Terms &amp; Conditions',
                'invoiceEditBomModal', 'invoiceQuickAddBomModal', 'invoice-form.js', 'name="invoice_name"',
                'name="invoice_date"', 'name="bom_specifications[7][technical]"'] as $section) {
                $this->assertTrue(str_contains($html, $section), $mode . ' form is missing: ' . $section);
            }
            $this->assertStringContainsString($mode === 'create' ? 'action="/api/invoices"' : 'action="/api/invoices/'.$invoice->id.'"', $html);
            $this->assertStringNotContainsString('name="estimate_name"', $html);
            $this->assertStringNotContainsString('action="/api/estimates', $html);
        }
    }
}
