<?php

namespace App\Http\Controllers;

use App\Models\BomProduct;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\PdfBuilderForm;
use App\Models\Setting;
use App\Models\Tax;
use App\Models\User;
use App\Models\Product;
use App\Models\Technology;
use App\Models\Warranty;
use App\Models\Subsidy;
use App\Support\DocumentSummaryPresenter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EstimateController extends Controller
{
    public function index()
    {
        $customers = Customer::visibleTo(auth()->user())->orderBy('name')->get();
        $templates = PdfBuilderForm::orderBy('template_name')->get();
        $bomProducts = BomProduct::with('categories')
            ->orderByRaw("CASE UPPER(TRIM(product_name)) WHEN 'SUPPLY AND INSTALLATION' THEN 0 WHEN 'STRUCTURE FABRICATION WORK' THEN 1 ELSE 2 END")
            ->orderBy('product_name')->get();
        $categories = Category::orderBy('name')->get();
        $gstTaxes = Tax::active()->orderBy('name')->orderBy('rate')->get();
        $gstRate = (float) $gstTaxes->sum('rate');
        $subsidies = Subsidy::active()->get();
        $estimatePriceMode = Setting::where('key', 'estimate_price_mode')->value('value') === 'base' ? 'base' : 'bom';

        return view('crm.estimates.index', compact('customers', 'templates', 'bomProducts', 'categories', 'gstTaxes', 'gstRate', 'subsidies', 'estimatePriceMode'));
    }

    public function create()
    {
        $customers = Customer::visibleTo(auth()->user())->orderBy('name')->get();
        $templates = PdfBuilderForm::orderBy('template_name')->get();
        $bomProducts = BomProduct::with('categories')
            ->orderByRaw("CASE UPPER(TRIM(product_name)) WHEN 'SUPPLY AND INSTALLATION' THEN 0 WHEN 'STRUCTURE FABRICATION WORK' THEN 1 ELSE 2 END")
            ->orderBy('product_name')->get();
        $categories = Category::orderBy('name')->get();

        if (auth()->user()->isAdmin()) {
            $users = User::orderBy('name')->get();
        } else {
            $users = User::where('id', auth()->id())->orderBy('name')->get();
        }
        $subsidies = Subsidy::active()->get();
        $gstTaxes = Tax::active()->orderBy('name')->orderBy('rate')->get();
        $gstRate = (float) $gstTaxes->sum('rate');
        if ($gstRate <= 0) {
            $gstRate = 18;
        }
        $estimatePriceMode = Setting::where('key', 'estimate_price_mode')->value('value') === 'base' ? 'base' : 'bom';

        return view('crm.estimates.create', compact('customers', 'users', 'templates', 'bomProducts', 'categories', 'subsidies', 'gstRate', 'gstTaxes', 'estimatePriceMode'));
    }

    public function show(Estimate $estimate)
    {
        $this->authorize('view', $estimate);
        $estimate->load(['customer', 'product', 'creator']);

        // Get user/company info
        $user = auth()->user();
        $settings = Setting::pluck('value', 'key');

        // Get all products for BOM specifications
        $product_data = BomProduct::all()->toArray();

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

        $documentSummary = DocumentSummaryPresenter::forView(
            $estimate,
            $estimate->customer,
            $settings->all(),
            $user,
            [
                'document_no' => $estimate->estimate_no,
                'document_date' => $estimate->estimate_date?->format('Y-m-d') ?? date('Y-m-d'),
                'quantity' => (string) ($estimate->quantity ?? '0'),
            ]
        );

        return view('crm.estimates.show', compact('estimate', 'user', 'settings', 'product_data', 'technology_map', 'warranty_map', 'documentSummary'));
    }

    public function edit(Estimate $estimate)
    {
        if (($estimate->status ?? '') === 'approved') {
            return redirect()->route('estimates.index')->with('error', 'Approved estimates cannot be edited.');
        }

        $this->authorize('update', $estimate);
        $estimate->load(['customer', 'product', 'creator']);

        $customers = Customer::visibleTo(auth()->user())->orderBy('name')->get();
        $templates = PdfBuilderForm::orderBy('template_name')->get();
        $bomProducts = BomProduct::with('categories')
            ->orderByRaw("CASE UPPER(TRIM(product_name)) WHEN 'SUPPLY AND INSTALLATION' THEN 0 WHEN 'STRUCTURE FABRICATION WORK' THEN 1 ELSE 2 END")
            ->orderBy('product_name')->get();
        $categories = Category::orderBy('name')->get();

        if (auth()->user()->isAdmin()) {
            $users = User::orderBy('name')->get();
        } else {
            $users = User::where('id', auth()->id())->orderBy('name')->get();
        }
        $subsidies = Subsidy::active()->get();
        $gstTaxes = Tax::active()->orderBy('name')->orderBy('rate')->get();
        $gstRate = (float) $gstTaxes->sum('rate');
        if ($gstRate <= 0) {
            $gstRate = 18;
        }
        $estimatePriceMode = $this->resolveEstimatePriceMode($estimate);
// dd($estimate);
        return view('crm.estimates.edit', compact('estimate', 'customers', 'users', 'templates', 'bomProducts', 'categories', 'subsidies', 'gstRate', 'gstTaxes', 'estimatePriceMode'));
    }

    public function generate_estimate_pdf(Estimate $estimate)
    {
        $this->authorize('view', $estimate);
        $estimate->load(['customer', 'product', 'creator']);

        // Get user/company info
        $user = auth()->user();

        // Replicate profile settings logic for the template
        $settings = Setting::query()->whereIn('key', [
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
        $product_data = BomProduct::all()->toArray();

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

        // Render the quotation HTML from the original PDF view
        $quotation_html = view('crm.estimates.pdf', compact('estimate', 'user', 'settings', 'product_data', 'technology_map', 'warranty_map'))->render();

        // Get the selected template or default to the first one
        $template = null;
        if ($estimate->template_id) {
            $template = PdfBuilderForm::find($estimate->template_id);
        }

        if (!$template) {
            $template = PdfBuilderForm::first();
        }

        if ($template) {
            $form_data = $template->form_data ?? [];

            // Prepare data for the new template wrapper
            $pdfData = [
                'estimate' => $estimate,
                'estimate_no' => $estimate->estimate_no,
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
                'components' => (in_array(Str::lower(trim((string) $template->template_name)), ['solar proposal', 'ux template']) || Str::lower(trim((string) $estimate->type)) === 'ux template') 
                    ? (is_array($estimate->product_name) ? $estimate->product_name : json_decode($estimate->product_name ?? '[]', true))
                    : ($template->components ?? ($form_data['components'] ?? [])),
                'payment_terms' => $template->payment_terms ?? ($form_data['payment_terms'] ?? []),
                'environment_impact' => $template->environment_impact ?? ($form_data['environment_impact'] ?? []),
                'environmentImpact' => $template->environment_impact ?? ($form_data['environment_impact'] ?? []),
                'footer' => $template->footer ?? ($form_data['footer'] ?? []),
                'generationSection' => $form_data['generation'] ?? [],
                'ongridRoiSection' => $form_data['ongrid_roi'] ?? [],
                'estimateCommentSection' => $form_data['estimate_comment'] ?? [],
            ];

            $templateName = Str::lower(trim((string) $template->template_name));
            if ($templateName === 'basic template') {
                $pdfView = 'pdfbuilder.basic-template-pdf';
            } elseif (in_array($templateName, ['solar proposal', 'ux template']) || Str::lower(trim((string) $estimate->type)) === 'ux template') {
                $pdfView = 'pdfbuilder.qt-000150-pdf';
            } else {
                $pdfView = 'pdfbuilder.pdf';
            }
            $pdf = \PDF::loadView($pdfView, $pdfData);
            $pdf->setPaper('A4', 'portrait');
        } else {
            // Fallback to original behavior if absolutely no template exists
            $pdf = \PDF::loadView('crm.estimates.pdf', compact('estimate', 'user', 'settings', 'product_data', 'technology_map', 'warranty_map'));
            $pdf->setPaper('A4', 'portrait');
        }

        // Save Dompdf output as temp PDF
        $tmpMain = tempnam(sys_get_temp_dir(), 'main_pdf_');
        file_put_contents($tmpMain, $pdf->output());

        // Now merge with customer uploaded docs
        $mergedPdf = new \setasign\Fpdi\Fpdi();

        // Add Dompdf file
        $pageCount = $mergedPdf->setSourceFile($tmpMain);
        for ($page = 1; $page <= $pageCount; $page++) {
            $tpl = $mergedPdf->importPage($page);
            $size = $mergedPdf->getTemplateSize($tpl);
            $mergedPdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $mergedPdf->useTemplate($tpl);
        }

        // Add each customer doc (if PDF/Image)
        $customer_docs = is_array($estimate->customer_docs) ? $estimate->customer_docs : [];
        foreach ($customer_docs as $doc) {
            $path = is_array($doc) ? ($doc['path'] ?? null) : $doc;
            if ($path && Storage::disk('public')->exists($path)) {
                $filePath = Storage::disk('public')->path($path);
                $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

                if ($ext === 'pdf') {
                    try {
                        $count = $mergedPdf->setSourceFile($filePath);
                        for ($p = 1; $p <= $count; $p++) {
                            $tpl = $mergedPdf->importPage($p);
                            $size = $mergedPdf->getTemplateSize($tpl);
                            $mergedPdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                            $mergedPdf->useTemplate($tpl);
                        }
                    } catch (\Exception $e) {
                        \Log::error("Failed to merge PDF customer doc {$path}: " . $e->getMessage());
                    }
                } elseif (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                    try {
                        // Convert image to a page
                        $mergedPdf->AddPage();
                        $mergedPdf->Image($filePath, 10, 10, 190, 270, strtoupper($ext === 'jpg' ? 'jpeg' : $ext));
                    } catch (\Exception $e) {
                        \Log::error("Failed to merge image customer doc {$path}: " . $e->getMessage());
                    }
                }
            }
        }

        // Clean up temp file
        if (file_exists($tmpMain)) {
            @unlink($tmpMain);
        }

        // Output final merged PDF
        $fileName = 'Estimate-' . ($estimate->estimate_no ?: $estimate->estimate_id) . '.pdf';
        return response($mergedPdf->Output('S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }

    private function resolveEstimatePriceMode(Estimate $estimate): string
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

    public function downloadCustomerDocument(Estimate $estimate, int $docIndex)
    {
        abort_unless($this->canAccessEstimateDocuments($estimate), 403);

        $docs = is_array($estimate->customer_docs) ? $estimate->customer_docs : [];
        $doc = $docs[$docIndex] ?? null;

        if (!$doc) {
            abort(404);
        }

        $path = is_array($doc) ? ($doc['path'] ?? null) : null;
        $downloadName = is_array($doc)
            ? ($doc['original_name'] ?? basename((string) $path))
            : basename((string) $doc);

        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return Storage::disk('public')->download($path, $downloadName);
    }

    private function canAccessEstimateDocuments(Estimate $estimate): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        return Customer::query()
            ->visibleToUser($user)
            ->whereKey($estimate->customer_id)
            ->exists();
    }

    public function generate_qt_000150_pdf(Estimate $estimate)
    {
        $estimate->load('customer', 'user');
        $companySettings = Setting::pluck('value', 'key')->toArray();
        $components = is_array($estimate->product_name) ? $estimate->product_name : json_decode($estimate->product_name ?? '[]', true);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdfbuilder.qt-000150-pdf', [
            'estimate' => $estimate,
            'companySettings' => $companySettings,
            'components' => $components,
        ]);
        
        $pdf->setPaper('a4', 'portrait');
        
        $fileName = 'Quote-' . ($estimate->estimate_no ?: $estimate->estimate_id) . '.pdf';
        return $pdf->stream($fileName);
    }
    
    public function preview_qt_000150_pdf()
    {
        $companySettings = Setting::pluck('value', 'key')->toArray();
        
        // Dummy estimate data for preview
        $estimate = new Estimate([
            'estimate_no' => 'QT-000150',
            'estimate_date' => now()->format('Y-m-d'),
            'quantity' => 0,
            'price' => 0,
            'solar_structure_charges' => 0,
            'gst_amount' => 0,
            'gst' => 0,
            'subsidy_amount' => 0,
            'product_name' => '[]'
        ]);
        
        $estimate->customer = new Customer([
            'name' => '[Customer Name]',
            'address' => '[Customer Address]',
            'email' => '[Customer Email]',
            'phone' => '[Customer Contact]'
        ]);
        
        $components = [
            [
                'type' => 'SOLAR PV MODULE',
                'make' => 'ADANI,WAAREE,EXIDE,PREMIER,GOLDI,RAYZON',
                'capacity' => '550/570/580/590/620/690',
                'technology' => 'BIFACIAL / MONO BIFACIAL TOPCON NTYPE',
                'warranty' => '10 MANUFACTURING +20 PERFORMANCE',
                'image_path' => ''
            ],
            [
                'type' => 'SOLAR INVERTER',
                'make' => 'PV-BLINK,VSOLE,SOLARYAAN,SUNGROW,GOODWE',
                'capacity' => 'AS PER REQUIRMENT',
                'technology' => 'ON GRIDE SOLAR SMART INVERTER',
                'warranty' => '10 YEARS',
                'image_path' => ''
            ],
            [
                'type' => 'STRUCTURE PIPE',
                'make' => 'APOLLO,HINDUSTAR,ISCON,SURYA',
                'specification' => 'HOT DIP GALVENIZED',
                'image_path' => ''
            ],
            [
                'type' => 'DB COMBO',
                'make' => 'L&T,HAVELLS,C&S',
                'specification' => 'IP 65 TRIBOX MCB WITH SPD',
                'image_path' => ''
            ],
            [
                'type' => 'AC CABLE',
                'make' => 'HAVELLS STANDARD,POLYCAB,RR,KEI',
                'specification' => '2.5MM 4MM 6MM COPPER FLEXIBLE',
                'image_path' => ''
            ],
            [
                'type' => 'DC CABLE',
                'make' => 'POLYCAB,APAR',
                'specification' => '2.5MM 4MM 6MM EN TYPE1 TYPE 2',
                'image_path' => ''
            ],
            [
                'type' => 'LA CABLE',
                'make' => 'POLYFLEX,KENBERRY,ANAND',
                'specification' => '16MM AL 25MM AL',
                'image_path' => ''
            ],
            [
                'type' => 'PVC CONDUITE',
                'make' => 'POLYCAB,PRECESION,PRESSFIT',
                'specification' => '20MM 25MM 32MM UV PROTECTED',
                'image_path' => ''
            ],
            [
                'type' => 'EARTHING AND LA ROD',
                'make' => 'E LINK TRUEBLUE',
                'specification' => '1.5MTR 17MM 25MM CU COATED',
                'image_path' => ''
            ],
            [
                'type' => 'J BOLT',
                'make' => 'AS PER ISI STANDARDS',
                'specification' => 'M8 SS 304',
                'image_path' => ''
            ],
            [
                'type' => 'SS TIE',
                'make' => 'AS PER ISI STANDARDS',
                'specification' => 'SS 304',
                'image_path' => ''
            ],
            [
                'type' => 'LUG',
                'make' => 'AS PER ISI STANDARDS',
                'specification' => 'AS PER CABLE SIZE',
                'image_path' => ''
            ],
            [
                'type' => 'MC4',
                'make' => 'ELMAX',
                'specification' => 'UV PROTECTED',
                'image_path' => ''
            ],
            [
                'type' => 'MONO RAIL',
                'make' => 'AS PER ISI STANDARDS',
                'specification' => 'AS PER REQUIRMENT',
                'image_path' => ''
            ],
            [
                'type' => 'WALKWAY',
                'make' => 'FRP MATERIAL',
                'specification' => 'AS PER ISI STANDARDS',
                'image_path' => ''
            ]
        ];

        $user = auth()->user();
        $settings = collect($companySettings);
        $product_data = \App\Models\BomProduct::all()->toArray();
        $technology_map = \App\Models\Technology::pluck('title', 'id')->toArray();
        $warranty_map = \App\Models\Warranty::pluck('title', 'id')->toArray();

        $quotation_html = view('crm.estimates.pdf', compact('estimate', 'user', 'settings', 'product_data', 'technology_map', 'warranty_map'))->render();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdfbuilder.qt-000150-pdf', [
            'estimate' => $estimate,
            'companySettings' => $companySettings,
            'components' => $components,
            'quotation_html' => $quotation_html,
        ]);
        
        $pdf->setPaper('a4', 'portrait');
        
        return $pdf->stream('QT-000150-Preview.pdf');
    }
}
