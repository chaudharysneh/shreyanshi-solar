<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PdfBuilderForm;
use App\Models\PdfType;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PdfbuilderController extends Controller
{
    private function isBasicTemplate(PdfBuilderForm $template): bool
    {
        return Str::lower(trim((string) $template->template_name)) === 'basic template';
    }

    private function pdfDownloadFilename(PdfBuilderForm $template): string
    {
        $name = trim((string) $template->template_name);
        $slug = $name !== '' ? Str::slug($name, '-') : 'template-' . $template->id;

        return $slug . '.pdf';
    }

    private function applyPdfDocumentTitle($pdf, string $title): void
    {
        $title = trim($title);
        if ($title === '') {
            return;
        }

        try {
            $dompdf = $pdf->getDomPDF();
            if (method_exists($dompdf, 'add_info')) {
                $dompdf->add_info('Title', $title);
            }
        } catch (\Throwable $e) {
            // Ignore metadata failures; HTML <title> still applies.
        }
    }

    private function buildTemplateViewPdfData(PdfBuilderForm $template, ?string $estimateNo = null): array
    {
        $estimateNo = $estimateNo ?: '--';
        $estdata = $this->getEstimateDetails($estimateNo);
        $form_data = $template->form_data ?? [];

        return array_merge($this->pdfCompanyData(), [
            'before_blocks' => $form_data['before_blocks'] ?? [],
            'after_blocks' => $form_data['after_blocks'] ?? [],
            'companyInfo' => $template->company_information ?? [],
            'time_line' => $template->time_line ?? [],
            'timeLine' => $template->time_line ?? [],
            'components' => $template->components ?? ($form_data['components'] ?? []),
            'payment_terms' => $template->payment_terms ?? ($form_data['payment_terms'] ?? []),
            'paymentTerms' => $template->payment_terms ?? ($form_data['payment_terms'] ?? []),
            'environment_impact' => $template->environment_impact ?? ($form_data['environment_impact'] ?? []),
            'environmentImpact' => $template->environment_impact ?? ($form_data['environment_impact'] ?? []),
            'footer' => $template->footer ?? ($form_data['footer'] ?? []),
            'generationSection' => $form_data['generation'] ?? [],
            'ongridRoiSection' => $form_data['ongrid_roi'] ?? [],
            'estimateCommentSection' => $form_data['estimate_comment'] ?? [],
            'header_image' => !empty($template->first_img) ? asset($template->first_img) : 'https://solar-crm.fableadtech.com/public/assets/img/profile/1760436391_b4bc9a00389df8eac539.jpg',
            'footer_image' => asset('/assets/pdfFooter.png'),
            'template_id' => $template->id,
            'template_name' => $template->template_name,
            'user' => Auth::user(),
            'generated_at' => now()->format('d M Y h:i A'),
            'estimate_no' => $estimateNo,
            'estdata' => $estdata,
        ]);
    }

    private function profileSettings()
    {
        return Setting::query()->whereIn('key', [
            'company_name',
            'company_tagline',
            'company_address',
            'company_tax_id',
            'company_logo_path',
            'company_qr_code_path',
            'show_bank_details',
            'show_company_qr_code',
            'bank_name',
            'account_name',
            'account_number',
            'ifsc_code',
            'branch_name',
            'social_instagram',
            'social_facebook',
            'social_linkedin',
            'phone',
            'email',
        ])->pluck('value', 'key');
    }

    private function resolvePublicStoragePath(?string $path): ?string
    {
        if (!$path || !Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->path($path);
    }

    private function pdfCompanyData(): array
    {
        $settings = $this->profileSettings();
        $user = Auth::user();

        return [
            'companySettings' => $settings,
            'companyLogoPath' => $this->resolvePublicStoragePath($settings['company_logo_path'] ?? null),
            'companyQrCodePath' => $this->resolvePublicStoragePath($settings['company_qr_code_path'] ?? null),
            'profileUser' => $user,
        ];
    }

    private function getEstimateDetails(?string $estimateNo): ?\App\Models\Estimate
    {
        if (!$estimateNo || $estimateNo === '--') {
            return null;
        }

        try {
            return \App\Models\Estimate::with('customer')
                ->where('estimate_no', $estimateNo)
                ->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Upload helper used by PdfBuilder templates.
     */
    private function storeUploadedImage(Request $request, string $inputName, string $targetDir, ?string $existingPath = null): ?string
    {
        if (!$request->hasFile($inputName) || !$request->file($inputName)->isValid()) {
            return $existingPath;
        }

        $file = $request->file($inputName);
        $newName = time() . '_' . $file->getClientOriginalName();

        // Ensure directory exists in public folder
        $publicPath = public_path($targetDir);
        if (!File::isDirectory($publicPath)) {
            File::makeDirectory($publicPath, 0777, true);
        }

        $file->move($publicPath, $newName);

        // Return path relative to public/
        return rtrim($targetDir, '/\\') . '/' . $newName;
    }

    private function storeBlockImage(Request $request, string $type, string $key, int $index): array
    {
        $uploaded = null;
        $files = $request->file("{$type}_image");

        // Try direct index/key match from the files array for robustness
        if (is_array($files)) {
            if (isset($files[$key]) && $request->hasFile("{$type}_image.{$key}")) {
                $uploaded = $files[$key];
            } elseif (isset($files[$index]) && $request->hasFile("{$type}_image.{$index}")) {
                $uploaded = $files[$index];
            }
        }

        // Fallback to dot-notation retrieval if still not found
        if (!$uploaded) {
            if ($request->hasFile("{$type}_image.$key")) {
                $uploaded = $request->file("{$type}_image.$key");
            } elseif ($request->hasFile("{$type}_image.$index")) {
                $uploaded = $request->file("{$type}_image.$index");
            }
        }

        if (!$uploaded || !$uploaded->isValid()) {
            return [null, null];
        }

        $newName = time() . '_' . $uploaded->getClientOriginalName();
        $relPath = 'uploads/pdf_images/' . $newName;
        $targetDir = public_path('uploads/pdf_images/');

        if (!File::isDirectory($targetDir)) {
            File::makeDirectory($targetDir, 0777, true);
        }

        $uploaded->move($targetDir, $newName);

        return [
            asset($relPath),
            public_path($relPath),
        ];
    }

    public function index()
    {
        return view('pdfbuilder.index');
    }

    public function create()
    {
        $types = PdfType::orderBy('name')->get();
        return view('pdfbuilder.create', compact('types'));
    }

    public function edit($id)
    {
        $template = PdfBuilderForm::findOrFail($id);

        if ($this->isBasicTemplate($template)) {
            return redirect()->route('pdfbuilder.index')->with('error', 'Basic Template is a static PDF and cannot be edited.');
        }

        $form_data = $template->form_data ?? [];
        $companyInfo = $template->company_information ?? [];
        $timeLine = $template->time_line ?? [];

        // Prefer dedicated columns if present; fallback to form_data for older rows
        $components = $template->components ?? ($form_data['components'] ?? []);
        $paymentTerms = $template->payment_terms ?? ($form_data['payment_terms'] ?? []);
        $environmentImpact = $template->environment_impact ?? ($form_data['environment_impact'] ?? []);
        $footer = $template->footer ?? ($form_data['footer'] ?? []);

        return view('pdfbuilder.edit', [
            'template' => $template,
            'companyInfo' => $companyInfo,
            'timeLine' => $timeLine,
            'components' => $components,
            'paymentTerms' => $paymentTerms,
            'environmentImpact' => $environmentImpact,
            'footer' => $footer,
            'before_blocks' => $form_data['before_blocks'] ?? [],
            'after_blocks' => $form_data['after_blocks'] ?? [],
            'edit_mode' => true
        ]);
    }

    public function update(Request $request, $id)
    {
        // dd($request->all());
        $template = PdfBuilderForm::findOrFail($id);

        $oldFormData = $template->form_data ?? [];
        $oldBeforeBlocks = $oldFormData['before_blocks'] ?? [];
        $oldAfterBlocks = $oldFormData['after_blocks'] ?? [];

        // BEFORE QUOTATION
        $before_titles = $request->input('before_title', []);
        $before_contents = $request->input('before_content', []);
        $before_ids = $request->input('before_id', []);
        $before_old_images = $request->input('before_image_old', []);

        $before_blocks = [];
        foreach ($before_titles as $i => $title) {
            $key = $before_ids[$i] ?? ('before_' . $i);

            // Match old block by ID/Key if possible for reordering safety
            $oldBlock = [];
            foreach ($oldBeforeBlocks as $ob) {
                if (isset($ob['id']) && $ob['id'] == $key) {
                    $oldBlock = $ob;
                    break;
                }
            }
            // Fallback to index if no ID match (for legacy data)
            if (empty($oldBlock)) {
                $oldBlock = $oldBeforeBlocks[$i] ?? [];
            }

            $imgUrl = $oldBlock['image'] ?? null;
            $absoluteImgPath = $oldBlock['absolute_image'] ?? null;

            [$uploadedUrl, $uploadedAbsolutePath] = $this->storeBlockImage($request, 'before', $key, $i);

            if ($uploadedUrl) {
                $imgUrl = $uploadedUrl;
                $absoluteImgPath = $uploadedAbsolutePath;
            } else {
                if (!empty($before_old_images[$key])) {
                    $imgUrl = $before_old_images[$key];
                    // Also ensure absolute path is kept if the URL matches the old one
                    if ($imgUrl === ($oldBlock['image'] ?? null)) {
                        $absoluteImgPath = $oldBlock['absolute_image'] ?? null;
                    }
                }
            }

            $before_blocks[] = [
                'id' => $key,
                'title' => !empty($title) ? $title : ($oldBlock['title'] ?? ''),
                'content' => !empty($before_contents[$i]) ? $before_contents[$i] : ($oldBlock['content'] ?? ''),
                'image' => $imgUrl,
                'absolute_image' => $absoluteImgPath,
            ];
        }

        // AFTER QUOTATION
        $after_titles = $request->input('after_title', []);
        $after_contents = $request->input('after_content', []);
        $after_ids = $request->input('after_id', []);
        $after_old_images = $request->input('after_image_old', []);

        $after_blocks = [];
        foreach ($after_titles as $i => $title) {
            $key = $after_ids[$i] ?? ('after_' . $i);

            // Match old block by ID/Key if possible
            $oldBlock = [];
            foreach ($oldAfterBlocks as $ob) {
                if (isset($ob['id']) && $ob['id'] == $key) {
                    $oldBlock = $ob;
                    break;
                }
            }
            if (empty($oldBlock)) {
                $oldBlock = $oldAfterBlocks[$i] ?? [];
            }

            $imgUrl = $oldBlock['image'] ?? null;
            $absoluteImgPath = $oldBlock['absolute_image'] ?? null;

            [$uploadedUrl, $uploadedAbsolutePath] = $this->storeBlockImage($request, 'after', $key, $i);

            if ($uploadedUrl) {
                $imgUrl = $uploadedUrl;
                $absoluteImgPath = $uploadedAbsolutePath;
            } else {
                if (!empty($after_old_images[$key])) {
                    $imgUrl = $after_old_images[$key];
                    if ($imgUrl === ($oldBlock['image'] ?? null)) {
                        $absoluteImgPath = $oldBlock['absolute_image'] ?? null;
                    }
                }
            }

            $after_blocks[] = [
                'id' => $key,
                'title' => !empty($title) ? $title : ($oldBlock['title'] ?? ''),
                'content' => !empty($after_contents[$i]) ? $after_contents[$i] : ($oldBlock['content'] ?? ''),
                'image' => $imgUrl,
                'absolute_image' => $absoluteImgPath,
            ];
        }

        // Header image
        $firstImgPath = $template->first_img;
        if ($request->hasFile('first_img')) {
            $firstImgPath = $this->storeUploadedImage($request, 'first_img', 'uploads/pdf_headers');
        } elseif ($request->input('delete_first_img') == '1') {
            $firstImgPath = null;
        }

        // Company Info
        $oldCompanyInfo = $template->company_information ?? [];
        $companyInfo = [
            'active' => (int) ($request->input('company_info_active', $oldCompanyInfo['active'] ?? 1)),
            'company_description' => $request->input('company_description', $oldCompanyInfo['company_description'] ?? ''),
            'company_capacity_installed' => $request->input('company_capacity_installed', $oldCompanyInfo['company_capacity_installed'] ?? ''),
            'happy_customers' => $request->input('happy_customers', $oldCompanyInfo['happy_customers'] ?? ''),
            'cities' => $request->input('cities', $oldCompanyInfo['cities'] ?? ''),
            'image1' => $this->storeUploadedImage($request, 'image1', 'uploads/pdf_company', $request->input('image1_old', $oldCompanyInfo['image1'] ?? null)),
            'image2' => $this->storeUploadedImage($request, 'image2', 'uploads/pdf_company', $request->input('image2_old', $oldCompanyInfo['image2'] ?? null)),
            'image3' => $this->storeUploadedImage($request, 'image3', 'uploads/pdf_company', $request->input('image3_old', $oldCompanyInfo['image3'] ?? null)),
        ];

        // Timeline
        $oldTimeLine = $template->time_line ?? [];
        $timeLine = [
            'active' => (int) ($request->input('timeline_active', $oldTimeLine['active'] ?? 1)),
            'main_title' => $request->input('main_title', $oldTimeLine['main_title'] ?? ''),
            'title' => $request->input('title', $oldTimeLine['title'] ?? ''),
            'image1' => $this->storeUploadedImage($request, 'timeline_image1', 'uploads/pdf_timeline', $request->input('timeline_image1_old', $oldTimeLine['image1'] ?? null)),
            'title2' => $request->input('title2', $oldTimeLine['title2'] ?? ''),
            'image2' => $this->storeUploadedImage($request, 'timeline_image2', 'uploads/pdf_timeline', $request->input('timeline_image2_old', $oldTimeLine['image2'] ?? null)),
            'note' => $request->input('timeline_note', $oldTimeLine['note'] ?? ''),
        ];

        // Components
        $oldComponents = $template->components ?? ($oldFormData['components'] ?? []);
        $components = [
            'active' => (int) ($request->input('components_active', $oldComponents['active'] ?? 1)),
            'title' => $request->input('components_title', $oldComponents['title'] ?? ''),
            'description' => $request->input('components_description', $oldComponents['description'] ?? ''),
        ];

        // Payment Terms
        $oldPaymentTerms = $template->payment_terms ?? ($oldFormData['payment_terms'] ?? []);
        $paymentTerms = [
            'active' => (int) ($request->input('payment_terms_active', $oldPaymentTerms['active'] ?? 1)),
            'scope' => $request->input('scope', $oldPaymentTerms['scope'] ?? ''),
            'note' => $request->input('payment_terms_note', $oldPaymentTerms['note'] ?? ''),
            'title' => $request->input('payment_terms_title', $oldPaymentTerms['title'] ?? ''),
            'image' => $this->storeUploadedImage($request, 'payment_terms_image', 'uploads/pdf_sections/payment_terms', $request->input('payment_terms_image_old', $oldPaymentTerms['image'] ?? null)),
        ];

        $servicesLeft = $request->input('services_left');
        $servicesRight = $request->input('services_right');
        if ($servicesLeft === null && $servicesRight === null) {
            $paymentTerms['services'] = $oldPaymentTerms['services'] ?? [];
        } else {
            $servicesLeft = $servicesLeft ?? [];
            $servicesRight = $servicesRight ?? [];
            $rows = max(count($servicesLeft), count($servicesRight));
            $services = [];
            for ($i = 0; $i < $rows; $i++) {
                $l = trim((string) ($servicesLeft[$i] ?? ''));
                $r = trim((string) ($servicesRight[$i] ?? ''));
                if ($l === '' && $r === '')
                    continue;
                $services[] = ['left' => $l, 'right' => $r];
            }
            $paymentTerms['services'] = $services;
        }

        // Environment Impact
        $oldEnvironmentImpact = $template->environment_impact ?? ($oldFormData['environment_impact'] ?? []);
        $environmentImpact = [
            'active' => (int) ($request->input('environment_impact_active', $oldEnvironmentImpact['active'] ?? 1)),
            'title' => $request->input('environment_impact_title', $oldEnvironmentImpact['title'] ?? ''),
            'content' => $request->input('environment_impact_content', $oldEnvironmentImpact['content'] ?? ''),
            'image' => $this->storeUploadedImage($request, 'environment_impact_image', 'uploads/pdf_sections/environment_impact', $request->input('environment_impact_image_old', $oldEnvironmentImpact['image'] ?? null)),
        ];

        // Footer
        $oldFooter = $template->footer ?? ($oldFormData['footer'] ?? []);
        $footer = [
            'active' => (int) ($request->input('footer_active', $oldFooter['active'] ?? 1)),
            'title' => $request->input('footer_title', $oldFooter['title'] ?? ''),
            'sub_title' => $request->input('footer_sub_title', $oldFooter['sub_title'] ?? ''),
            'image' => $this->storeUploadedImage($request, 'footer_image', 'uploads/pdf_sections/footer', $request->input('footer_image_old', $oldFooter['image'] ?? null)),
        ];

        // Generation & Ongrid ROI
        $generation = [
            'active' => 0,
            'title' => (string) ($request->input('generation_title', $oldFormData['generation']['title'] ?? '')),
            'sub_title' => (string) ($request->input('generation_sub_title', $oldFormData['generation']['sub_title'] ?? '')),
            'note' => (string) ($request->input('generation_note', $oldFormData['generation']['note'] ?? '')),
        ];

        $ongrid_roi = [
            'active' => (int) ($request->input('ongrid_roi_active', $oldFormData['ongrid_roi']['active'] ?? 0)),
            'title' => (string) ($request->input('ongrid_roi_title', $oldFormData['ongrid_roi']['title'] ?? '')),
            'sub_title' => (string) ($request->input('ongrid_roi_sub_title', $oldFormData['ongrid_roi']['sub_title'] ?? '')),
            'residential_starts_percent' => $request->input('residential_starts_percent', $oldFormData['ongrid_roi']['residential_starts_percent'] ?? ''),
            'note' => (string) ($request->input('ongrid_roi_note', $oldFormData['ongrid_roi']['note'] ?? '')),
        ];

        $estimateComment = [
            'active' => (int) ($request->input('estimate_comment_active', $oldFormData['estimate_comment']['active'] ?? 0)),
            'content' => (string) ($request->input('estimate_template_comment', $oldFormData['estimate_comment']['content'] ?? '')),
        ];

        $formData = [
            'before_blocks' => $before_blocks,
            'after_blocks' => $after_blocks,
            'generation' => $generation,
            'ongrid_roi' => $ongrid_roi,
            'estimate_comment' => $estimateComment,
        ];

        $updateData = [
            'form_title' => $request->input('form_title', $template->form_title),
            'template_name' => $request->input('template_name', $template->template_name),
            'form_data' => $formData,
            'company_information' => $companyInfo,
            'time_line' => $timeLine,
            'components' => $components,
            'payment_terms' => $paymentTerms,
            'environment_impact' => $environmentImpact,
            'footer' => $footer,
            'image_paths' => array_values(array_filter(array_merge(
                array_column($before_blocks, 'image'),
                array_column($after_blocks, 'image'),
                array_filter([
                    $companyInfo['image1'] ?? null,
                    $companyInfo['image2'] ?? null,
                    $companyInfo['image3'] ?? null,
                    $timeLine['image1'] ?? null,
                    $timeLine['image2'] ?? null,
                    $paymentTerms['image'] ?? null,
                    $environmentImpact['image'] ?? null,
                    $footer['image'] ?? null,
                ])
            ))),
            'first_img' => $firstImgPath,
            'updated_at' => now(),
        ];

        $template->update($updateData);

        // Generate PDF
        $estimateNo = $request->input('estimate_no') ?? $request->query('estimate_no') ?? '--';
        $estdata = $this->getEstimateDetails($estimateNo);

        $pdfData = array_merge($updateData, $this->pdfCompanyData(), [
            'template_id' => $template->id,
            'quotation_html' => $request->input('quotation_html', ''),
            'header_image' => $firstImgPath ? asset($firstImgPath) : 'https://solar-crm.fableadtech.com/public/assets/img/profile/1760436391_b4bc9a00389df8eac539.jpg',
            'footer_image' => asset('/assets/pdfFooter.png'),
            'generated_at' => now()->format('d M Y h:i A'),
            'generationSection' => $generation,
            'ongridRoiSection' => $ongrid_roi,
            'estimateCommentSection' => $estimateComment,
            'before_blocks' => $before_blocks,
            'after_blocks' => $after_blocks,
            'companyInfo' => $companyInfo,
            'timeLine' => $timeLine,
            'components' => $components,
            'paymentTerms' => $paymentTerms,
            'environmentImpact' => $environmentImpact,
            'footer' => $footer,
            'estimate_no' => $estimateNo,
            'estdata' => $estdata,
        ]);

        $pdf = Pdf::loadView('pdfbuilder.pdf', $pdfData);
        $pdf->setPaper('A4', 'portrait');
        $this->applyPdfDocumentTitle($pdf, (string) ($updateData['template_name'] ?? $template->template_name));

        $pdfDir = public_path('uploads/pdfs/');
        if (!File::isDirectory($pdfDir)) {
            File::makeDirectory($pdfDir, 0777, true);
        }

        $pdfFileName = 'pdf_' . $id . '_' . time() . '.pdf';
        $pdfFilePath = 'uploads/pdfs/' . $pdfFileName;

        if (!empty($template->pdf_file) && File::exists(public_path($template->pdf_file))) {
            File::delete(public_path($template->pdf_file));
        }

        $pdf->save(public_path($pdfFilePath));

        $template->update(['pdf_file' => $pdfFilePath]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => true,
                'message' => 'Template updated successfully',
                'redirect' => route('pdfbuilder.index'),
                'pdf_file' => asset($pdfFilePath),
            ]);
        }

        return redirect()->route('pdfbuilder.index')->with('success', 'Template updated successfully');
    }

    public function view($id)
    {
        $template = PdfBuilderForm::findOrFail($id);

        $streamUrl = route('pdfbuilder.stream', ['id' => $template->id]);
        $query = request()->getQueryString();
        if ($query) {
            $streamUrl .= '?' . $query;
        }

        return view('pdfbuilder.preview', [
            'templateName' => $template->template_name,
            'streamUrl' => $streamUrl,
        ]);
    }

    public function stream($id)
    {
        $template = PdfBuilderForm::findOrFail($id);

        $estimateNo = request()->query('estimate_no') ?? '--';
        $pdfData = $this->buildTemplateViewPdfData($template, $estimateNo);

        $pdf = Pdf::loadView($this->isBasicTemplate($template) ? 'pdfbuilder.basic-template-pdf' : 'pdfbuilder.pdf', $pdfData);
        $pdf->setPaper('A4', 'portrait');
        $this->applyPdfDocumentTitle($pdf, (string) $template->template_name);

        return $pdf->stream($this->pdfDownloadFilename($template));
    }

    public function generate(Request $request)
    {
        $request->validate([
            'template_name' => 'required|unique:pdf_builder_forms,template_name',
        ]);

        // Port logic similar to update() but for create
        // (Simplified for brevity, similar to update but without $oldBlock fallbacks)

        $before_titles = $request->input('before_title', []);
        $before_contents = $request->input('before_content', []);
        $before_ids = $request->input('before_id', []);
        $before_blocks = [];
        foreach ($before_titles as $i => $t) {
            $key = $before_ids[$i] ?? ('before_' . $i);
            $imgUrl = null;
            $absoluteImgPath = null;
            [$uploadedUrl, $uploadedAbsolutePath] = $this->storeBlockImage($request, 'before', $key, $i);

            if ($uploadedUrl) {
                $imgUrl = $uploadedUrl;
                $absoluteImgPath = $uploadedAbsolutePath;
            }
            $before_blocks[] = [
                'id' => $key,
                'title' => $t,
                'content' => $before_contents[$i] ?? '',
                'image' => $imgUrl,
                'absolute_image' => $absoluteImgPath,
            ];
        }

        $after_titles = $request->input('after_title', []);
        $after_contents = $request->input('after_content', []);
        $after_ids = $request->input('after_id', []);
        $after_blocks = [];
        foreach ($after_titles as $i => $t) {
            $key = $after_ids[$i] ?? ('after_' . $i);
            $imgUrl = null;
            $absoluteImgPath = null;
            [$uploadedUrl, $uploadedAbsolutePath] = $this->storeBlockImage($request, 'after', $key, $i);

            if ($uploadedUrl) {
                $imgUrl = $uploadedUrl;
                $absoluteImgPath = $uploadedAbsolutePath;
            }
            $after_blocks[] = [
                'id' => $key,
                'title' => $t,
                'content' => $after_contents[$i] ?? '',
                'image' => $imgUrl,
                'absolute_image' => $absoluteImgPath,
            ];
        }

        $companyInfo = [
            'active' => (int) ($request->input('company_info_active', 1)),
            'company_description' => $request->input('company_description', ''),
            'company_capacity_installed' => $request->input('company_capacity_installed', ''),
            'happy_customers' => $request->input('happy_customers', ''),
            'cities' => $request->input('cities', ''),
            'image1' => $this->storeUploadedImage($request, 'image1', 'uploads/pdf_company'),
            'image2' => $this->storeUploadedImage($request, 'image2', 'uploads/pdf_company'),
            'image3' => $this->storeUploadedImage($request, 'image3', 'uploads/pdf_company'),
        ];

        $timeLine = [
            'active' => (int) ($request->input('timeline_active', 1)),
            'main_title' => $request->input('main_title', ''),
            'title' => $request->input('title', ''),
            'image1' => $this->storeUploadedImage($request, 'timeline_image1', 'uploads/pdf_timeline'),
            'title2' => $request->input('title2', ''),
            'image2' => $this->storeUploadedImage($request, 'timeline_image2', 'uploads/pdf_timeline'),
            'note' => $request->input('timeline_note', ''),
        ];

        $components = [
            'active' => (int) ($request->input('components_active', 1)),
            'title' => $request->input('components_title', ''),
            'description' => $request->input('components_description', ''),
        ];

        $paymentTerms = [
            'active' => (int) ($request->input('payment_terms_active', 1)),
            'scope' => $request->input('scope', ''),
            'note' => $request->input('payment_terms_note', ''),
            'title' => $request->input('payment_terms_title', ''),
            'image' => $this->storeUploadedImage($request, 'payment_terms_image', 'uploads/pdf_sections/payment_terms'),
        ];

        $servicesLeft = $request->input('services_left', []);
        $servicesRight = $request->input('services_right', []);
        $services = [];
        $rows = max(count($servicesLeft), count($servicesRight));
        for ($i = 0; $i < $rows; $i++) {
            $l = trim((string) ($servicesLeft[$i] ?? ''));
            $r = trim((string) ($servicesRight[$i] ?? ''));
            if ($l === '' && $r === '')
                continue;
            $services[] = ['left' => $l, 'right' => $r];
        }
        $paymentTerms['services'] = $services;

        $environmentImpact = [
            'active' => (int) ($request->input('environment_impact_active', 1)),
            'title' => $request->input('environment_impact_title', ''),
            'content' => $request->input('environment_impact_content', ''),
            'image' => $this->storeUploadedImage($request, 'environment_impact_image', 'uploads/pdf_sections/environment_impact'),
        ];

        $footer = [
            'active' => (int) ($request->input('footer_active', 1)),
            'title' => $request->input('footer_title', ''),
            'sub_title' => $request->input('footer_sub_title', ''),
            'image' => $this->storeUploadedImage($request, 'footer_image', 'uploads/pdf_sections/footer'),
        ];

        $generation = [
            'active' => 0,
            'title' => (string) ($request->input('generation_title', '')),
            'sub_title' => (string) ($request->input('generation_sub_title', '')),
            'note' => (string) ($request->input('generation_note', '')),
        ];

        $ongrid_roi = [
            'active' => (int) ($request->input('ongrid_roi_active', 0)),
            'title' => (string) ($request->input('ongrid_roi_title', '')),
            'sub_title' => (string) ($request->input('ongrid_roi_sub_title', '')),
            'residential_starts_percent' => $request->input('residential_starts_percent', ''),
            'note' => (string) ($request->input('ongrid_roi_note', '')),
        ];

        $estimateComment = [
            'active' => (int) ($request->input('estimate_comment_active', 0)),
            'content' => (string) ($request->input('estimate_template_comment', '')),
        ];

        $formData = [
            'before_blocks' => $before_blocks,
            'after_blocks' => $after_blocks,
            'generation' => $generation,
            'ongrid_roi' => $ongrid_roi,
            'estimate_comment' => $estimateComment,
        ];

        $firstImgPath = null;
        if ($request->hasFile('first_img')) {
            $firstImgPath = $this->storeUploadedImage($request, 'first_img', 'uploads/pdf_headers');
        }

        $data = [
            'user_id' => Auth::id(),
            'form_title' => $request->input('form_title', 'Custom PDF'),
            'form_data' => $formData,
            'company_information' => $companyInfo,
            'time_line' => $timeLine,
            'components' => $components,
            'payment_terms' => $paymentTerms,
            'environment_impact' => $environmentImpact,
            'footer' => $footer,
            'image_paths' => array_values(array_filter(array_merge(
                array_column($before_blocks, 'image'),
                array_column($after_blocks, 'image'),
                array_filter([
                    $companyInfo['image1'] ?? null,
                    $companyInfo['image2'] ?? null,
                    $companyInfo['image3'] ?? null,
                    $timeLine['image1'] ?? null,
                    $timeLine['image2'] ?? null,
                    $paymentTerms['image'] ?? null,
                    $environmentImpact['image'] ?? null,
                    $footer['image'] ?? null,
                ])
            ))),
            'template_name' => $request->input('template_name'),
            'first_img' => $firstImgPath,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $template = PdfBuilderForm::create($data);

        // PDF generation logic (same as update)
        $estimateNo = $request->input('estimate_no') ?? $request->query('estimate_no') ?? '--';
        $estdata = $this->getEstimateDetails($estimateNo);

        $pdfData = array_merge($data, $this->pdfCompanyData(), [
            'template_id' => $template->id,
            'quotation_html' => '',
            'header_image' => $firstImgPath ? asset($firstImgPath) : 'https://solar-crm.fableadtech.com/public/assets/img/profile/1760436391_b4bc9a00389df8eac539.jpg',
            'footer_image' => asset('/assets/pdfFooter.png'),
            'generated_at' => now()->format('d M Y h:i A'),
            'generationSection' => $generation,
            'ongridRoiSection' => $ongrid_roi,
            'estimateCommentSection' => $estimateComment,
            'before_blocks' => $before_blocks,
            'after_blocks' => $after_blocks,
            'companyInfo' => $companyInfo,
            'timeLine' => $timeLine,
            'components' => $components,
            'paymentTerms' => $paymentTerms,
            'environmentImpact' => $environmentImpact,
            'footer' => $footer,
            'estimate_no' => $estimateNo,
            'estdata' => $estdata,
        ]);

        $pdf = Pdf::loadView('pdfbuilder.pdf', $pdfData);
        $this->applyPdfDocumentTitle($pdf, (string) ($data['template_name'] ?? $template->template_name));
        $pdfFileName = 'pdf_' . $template->id . '_' . time() . '.pdf';
        $pdfFilePath = 'uploads/pdfs/' . $pdfFileName;

        $pdfDir = public_path('uploads/pdfs/');
        if (!File::isDirectory($pdfDir)) {
            File::makeDirectory($pdfDir, 0777, true);
        }

        $pdf->save(public_path($pdfFilePath));

        $template->update(['pdf_file' => $pdfFilePath]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => true,
                'message' => 'Template created successfully!',
                'redirect' => route('pdfbuilder.index'),
                'template_id' => $template->id,
                'pdf_file' => asset($pdfFilePath),
            ]);
        }

        return redirect()->route('pdfbuilder.index')->with('success', 'Template created successfully!');
    }

    public function delete($id)
    {
        $template = PdfBuilderForm::findOrFail($id);

        if (!empty($template->pdf_file) && File::exists(public_path($template->pdf_file))) {
            File::delete(public_path($template->pdf_file));
        }

        $template->delete();

        return response()->json(['status' => true, 'message' => 'Template deleted successfully']);
    }

    /**
     * Special view for QT-000150 custom Blade template.
     * Redirects to the estimate preview route which renders qt-000150-pdf.blade.php.
     */
    public function viewQt000150()
    {
        return redirect()->route('estimates.preview.qt-000150');
    }
}
