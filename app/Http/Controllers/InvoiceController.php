<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PdfBuilderForm;
use App\Models\BomProduct;
use App\Models\Product;
use App\Models\Technology;
use App\Models\Warranty;
use App\Models\Subsidy;
use App\Models\Tax;
use App\Support\DocumentSummaryPresenter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Invoice::class);
        return view('crm.invoices.index');
    }

    public function create()
    {
        $this->authorize('create', Invoice::class);
        $customers = Customer::visibleTo(auth()->user())->orderBy('name')->get();
        $bomProducts = BomProduct::with('categories')
            ->orderByRaw("CASE UPPER(TRIM(product_name)) WHEN 'SUPPLY AND INSTALLATION' THEN 0 WHEN 'STRUCTURE FABRICATION WORK' THEN 1 ELSE 2 END")
            ->orderBy('product_name')->get();
        $templates = PdfBuilderForm::orderBy('template_name')->get();
        $categories = Category::orderBy('name')->get();
        $subsidies = Subsidy::active()->get();
        $gstTaxes = Tax::active()->orderBy('name')->orderBy('rate')->get();
        $gstRate = (float) $gstTaxes->sum('rate');
        if ($gstRate <= 0) {
            $gstRate = 18;
        }
        $defaultCurrencyId = $this->defaultInvoiceCurrencyId();

        $estimatePriceMode = \App\Models\Setting::where('key', 'estimate_price_mode')->value('value') === 'base' ? 'base' : 'bom';

        return view('crm.invoices.create', compact('customers', 'bomProducts', 'templates', 'categories', 'subsidies', 'gstRate', 'gstTaxes', 'defaultCurrencyId', 'estimatePriceMode'));
    }

    public function edit(Invoice $invoice)
    {
        $this->authorize('update', $invoice);
        $invoice->load('items');
        $customers = Customer::visibleTo(auth()->user())->orderBy('name')->get();
        $bomProducts = BomProduct::with('categories')
            ->orderByRaw("CASE UPPER(TRIM(product_name)) WHEN 'SUPPLY AND INSTALLATION' THEN 0 WHEN 'STRUCTURE FABRICATION WORK' THEN 1 ELSE 2 END")
            ->orderBy('product_name')->get();
        $templates = PdfBuilderForm::orderBy('template_name')->get();
        $categories = Category::orderBy('name')->get();
        $subsidies = Subsidy::active()->get();
        $gstTaxes = Tax::active()->orderBy('name')->orderBy('rate')->get();
        $gstRate = (float) $gstTaxes->sum('rate');
        if ($gstRate <= 0) {
            $gstRate = 18;
        }
        $defaultCurrencyId = $this->defaultInvoiceCurrencyId();

        $estimatePriceMode = $this->resolveInvoicePriceMode($invoice);

        return view('crm.invoices.edit', compact('invoice', 'customers', 'bomProducts', 'templates', 'categories', 'subsidies', 'gstRate', 'gstTaxes', 'defaultCurrencyId', 'estimatePriceMode'));
    }

    // status update
    public function updateStatus(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);
        $request->validate([
            'status' => 'required|in:paid,unpaid,cancelled'
        ]);

        $invoice->update([
            'status' => $request->status,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'status' => $invoice->status,
        ]);
    }

    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);
        $invoice->load(['customer', 'items', 'creator']);

        // Get user/company info to match estimate show pattern
        $user = auth()->user();
        $settings = \App\Models\Setting::pluck('value', 'key');

        $documentSummary = DocumentSummaryPresenter::forView(
            $invoice,
            $invoice->customer,
            $settings->all(),
            $user,
            [
                'document_no' => $invoice->invoice_no,
                'document_date' => $invoice->invoice_date?->format('Y-m-d') ?? date('Y-m-d'),
                'quantity' => (string) ($invoice->quantity ?? '0'),
            ]
        );

        return view('crm.invoices.show', compact('invoice', 'user', 'settings', 'documentSummary'));
    }

    public function export(Request $request)
    {
        $this->authorize('viewAny', Invoice::class);
        $fileName = 'Invoice_' . date('Y-m-d_H-i-s') . '.csv';
        $query = $this->scopeOwnedRecords(
            Invoice::with(['customer', 'currency'])
        );

        $invoices = $query->latest('invoice_date')->get();

        $fileName = 'invoices_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = ['ID', 'Invoice #', 'Customer', 'Invoice Date', 'Due Date', 'Amount', 'Status', 'Currency'];

        $callback = function () use ($invoices, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($invoices as $invoice) {
                fputcsv($file, [
                    $invoice->id,
                    $invoice->invoice_no,
                    $invoice->customer?->name,
                    optional($invoice->invoice_date)->format('Y-m-d'),
                    optional($invoice->due_date)->format('Y-m-d'),
                    $invoice->amount,
                    ucfirst($invoice->status ?? 'unpaid'),
                    $invoice->currency?->code ?? '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function pdf(Invoice $invoice)
    {
        $this->authorize('view', $invoice);
        $invoice->load(['customer', 'creator', 'items', 'currency']);

        // Get user/company info
        $user = auth()->user();
        
        // Replicate profile settings logic for the template
        $settings = \App\Models\Setting::query()->whereIn('key', [
            'company_name',
            'company_tagline',
            'company_address',
            'company_tax_id',
            'company_logo_path',
            'sidebar_icon_path',
            'company_qr_code_path',
            'phone',
            'email',
            'social_instagram',
            'social_facebook',
            'social_linkedin',
            'bank_name',
            'account_name',
            'account_number',
            'ifsc_code',
            'branch_name',
            'show_bank_details',
            'show_company_qr_code',
        ])->pluck('value', 'key');

        // Get all products for BOM specifications
        $product_data = BomProduct::with('categories')->get()->toArray();

        // Load technology and warranty maps
        $technologyList = Technology::all();
        $warrantyList = Warranty::all();

        $technology_map = [];
        foreach ($technologyList as $tech) {
            $technology_map[$tech->id] = $tech->title;
        }

        $warranty_map = [];
        foreach ($warrantyList as $war) {
            $warranty_map[$war->id] = $war->title;
        }

        $quotation_html = '';

        // Get the selected template or default to the first one (aligned with EstimateController)
        $template = null;
        if ($invoice->template_id) {
            $template = PdfBuilderForm::find($invoice->template_id);
        }
        if (!$template) {
            $template = PdfBuilderForm::first() ?? new PdfBuilderForm();
        }

        // Inject an alias for estimate_date so the template can render invoice_date instead of today's date
        if ($invoice->invoice_date) {
            $invoice->estimate_date = $invoice->invoice_date->format('Y-m-d');
        }

        if ($template) {
            $form_data = $template->form_data ?? [];

            // Prepare data for the new template wrapper
            // Pass $invoice AS the 'estimate' key, and set 'estimate_no' as the invoice no so pdf.blade.php handles it correctly
            $pdfData = [
                'invoiceOnly' => true,
                'estimate' => $invoice,
                'estimate_no' => $invoice->invoice_no,
                'companySettings' => $settings,
                'companyLogoPath' => ($settings['company_logo_path'] ?? null) ? Storage::disk('public')->path($settings['company_logo_path']) : null,
                'companyQrCodePath' => ($settings['company_qr_code_path'] ?? null) ? Storage::disk('public')->path($settings['company_qr_code_path']) : null,
                'profileUser' => $user,
                'template_id' => $template->id,
                'template_name' => $template->template_name,
                'quotation_html' => $quotation_html,
                'header_image' => !empty($template->first_img) ? asset($template->first_img) : 'https://solar-crm.fableadtech.com/public/assets/img/profile/1760436391_b4bc9a00389df8eac539.jpg',
                'footer_image' => asset('/assets/pdfFooter.png'),
                'generated_at' => now()->format('d M Y h:i A'),
                'before_blocks' => $form_data['before_blocks'] ?? [],
                'after_blocks' => $form_data['after_blocks'] ?? [],
                'companyInfo' => $template->company_information ?? [],
                'timeLine' => $template->time_line ?? [],
                'components' => $template->components ?? ($form_data['components'] ?? []),
                'payment_terms' => $template->payment_terms ?? ($form_data['payment_terms'] ?? []),
                'environment_impact' => $template->environment_impact ?? ($form_data['environment_impact'] ?? []),
                'environmentImpact' => $template->environment_impact ?? ($form_data['environment_impact'] ?? []),
                'footer' => $template->footer ?? ($form_data['footer'] ?? []),
                'generationSection' => $form_data['generation'] ?? [],
                'ongridRoiSection' => $form_data['ongrid_roi'] ?? [],
                'estimateCommentSection' => $form_data['estimate_comment'] ?? [],
            ];

            $pdfView = 'pdfbuilder.pdf';
            $pdf = Pdf::loadView($pdfView, $pdfData);
            $pdf->setPaper('A4', 'portrait');
        } else {
            $pdf = Pdf::loadView('crm.invoices.pdf', compact('invoice', 'user', 'settings', 'product_data', 'technology_map', 'warranty_map'));
            $pdf->setPaper('A4', 'portrait');
        }

        $filename = 'invoice-' . ($invoice->invoice_no ?: $invoice->id) . '.pdf';
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    private function defaultInvoiceCurrencyId(): ?int
    {
        $currencyId = Currency::query()
            ->where('is_active', true)
            ->where('code', 'INR')
            ->value('id');

        if ($currencyId) {
            return (int) $currencyId;
        }

        $currencyId = Currency::query()
            ->where('is_active', true)
            ->where('is_default', true)
            ->value('id');

        if ($currencyId) {
            return (int) $currencyId;
        }

        $currencyId = Currency::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');

        return $currencyId ? (int) $currencyId : null;
    }
    private function resolveInvoicePriceMode(Invoice $estimate): string
    {
        if (in_array($estimate->price_mode, ['base', 'bom'], true)) {
            return $estimate->price_mode;
        }

        $breakdown = is_array($estimate->gst_breakdown)
            ? $estimate->gst_breakdown
            : (json_decode((string) $estimate->gst_breakdown, true) ?: []);

        foreach (($breakdown['groups'] ?? []) as $group) {
            $taxType = (string) ($group['tax_type'] ?? '');
            if ($taxType === 'global_tax') {
                return 'base';
            }
            if ($taxType === 'bom_selected_tax') {
                return 'bom';
            }
        }

        $products = is_array($estimate->product_name)
            ? $estimate->product_name
            : (json_decode((string) $estimate->product_name, true) ?: []);
        $bomTotal = collect($products)->sum(function ($product) {
            return (float) ($product['quantity'] ?? 0) * (float) ($product['price'] ?? 0);
        });

        return (float) $estimate->price > 0 && $bomTotal <= 0 ? 'base' : 'bom';
    }

}
