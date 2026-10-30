<?php
// ✅ Start the session here
$session = session();
$userId = $session->get('id');

// Compatibility helper: `esc()` is used throughout this PDF template.
if (!function_exists('esc')) {
    function esc($value = null)
    {
        return e($value);
    }
}

if (!function_exists('pdf_has_real_value')) {
    function pdf_has_real_value($value): bool
    {
        $value = trim((string) $value);

        return $value !== '' && !in_array(strtolower($value), ['--', 'address', 'n/a', 'na'], true);
    }
}

if (!defined('FCPATH')) {
    define('FCPATH', public_path() . DIRECTORY_SEPARATOR);
}

if (!function_exists('normalize_pdf_image')) {
    function normalize_pdf_image($path)
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }

        // Helper closure to optimize and convert progressive images to Baseline using GD
        $optimizeImage = function ($candidate) {
            $ext = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
            if (empty($ext)) {
                $ext = 'png';
            } elseif ($ext === 'jpg') {
                $ext = 'jpeg';
            }

            if (extension_loaded('gd') && ($ext === 'jpg' || $ext === 'jpeg')) {
                try {
                    // Detect Progressive JPEG format using header inspection
                    $handle = @fopen($candidate, 'rb');
                    $isProgressive = false;
                    if ($handle) {
                        $header = @fread($handle, 131072); // Read initial segment to check for SOF2 markers
                        @fclose($handle);
                        if (strpos($header, "\xFF\xC2") !== false) {
                            $isProgressive = true;
                        }
                    }

                    // Run GD ONLY for broken Progressive JPEGs. Baseline JPEGs are left untouched!
                    if ($isProgressive) {
                        $srcImg = @imagecreatefromjpeg($candidate);
                        if ($srcImg) {
                            $width = imagesx($srcImg);
                            $height = imagesy($srcImg);

                            // Keep 100% of original dimensions to prevent tampering with document layout sizes!
                            $newW = $width;
                            $newH = $height;

                            $dstImg = imagecreatetruecolor($newW, $newH);
                            $white = imagecolorallocate($dstImg, 255, 255, 255);
                            imagefilledrectangle($dstImg, 0, 0, $newW, $newH, $white);

                            imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newW, $newH, $width, $height);

                            ob_start();
                            imageinterlace($dstImg, 0); // Enforce non-progressive baseline format
                            imagejpeg($dstImg, null, 90); // High quality 90 to preserve crispness
                            $binData = ob_get_clean();

                            imagedestroy($srcImg);
                            imagedestroy($dstImg);

                            if ($binData !== false && strlen($binData) > 0) {
                                return 'data:image/jpeg;base64,' . base64_encode($binData);
                            }
                        }
                    }
                } catch (\Throwable $t) {
                }
            }

            // Fast pathway for all other formats (Baseline JPEG & PNG) to preserve exact original bytes
            $imgData = @file_get_contents($candidate);
            if ($imgData !== false) {
                $mime = $ext === 'png' ? 'image/png' : 'image/jpeg';
                return 'data:' . $mime . ';base64,' . base64_encode($imgData);
            }
            return null;
        };

        // If it's already a base64 data URI, return as-is
        if (strpos($path, 'data:image') === 0) {
            return $path;
        }

        // Parse URL paths and filenames
        $cleanPath = $path;
        if (preg_match('/^https?:\/\//i', $path)) {
            $urlParts = parse_url($path);
            $cleanPath = isset($urlParts['path']) ? ltrim($urlParts['path'], '/\\') : $path;
        }

        // Clean prefix components (public, storage, public_html, etc)
        $cleanPath = preg_replace('#^(?:public|public_html|storage|app/public|storage/app/public)(?:/|\\\\)+#i', '', $cleanPath);
        $cleanPath = ltrim($cleanPath, '/\\');

        // Generate robust list of potential disk locations
        $candidates = [
            // 0. Raw path itself (may already be an absolute filesystem path)
            $path,

            // 1. Standard Storage paths
            storage_path('app/public/' . $cleanPath),
            storage_path('app/' . $cleanPath),

            // 2. Standard Public and Public/Storage paths
            public_path('storage/' . $cleanPath),
            public_path($cleanPath),

            // 3. Raw filesystem path mappings in standard web-serving folders
            base_path('public_html/storage/' . $cleanPath),
            base_path('public_html/' . $cleanPath),
            base_path('public/storage/' . $cleanPath),

            // 4. Deeply nested common project assets & uploads
            public_path('uploads/' . $cleanPath),
            public_path('uploads/products/' . $cleanPath),
            public_path('uploads/img/product/' . $cleanPath),
            public_path('assets/' . $cleanPath),
            public_path('assets/img/profile/' . $cleanPath),
        ];

        // Always try matching by filename for bom-products & products folders
        $filename = basename($cleanPath);
        if ($filename !== '') {
            $candidates[] = storage_path('app/public/bom-products/' . $filename);
            $candidates[] = storage_path('app/public/products/' . $filename);
            $candidates[] = public_path('storage/bom-products/' . $filename);
            $candidates[] = public_path('storage/products/' . $filename);
            $candidates[] = base_path('public_html/storage/bom-products/' . $filename);
            $candidates[] = base_path('public_html/storage/products/' . $filename);
            $candidates[] = public_path('assets/img/' . $filename);
            $candidates[] = base_path('public/assets/img/' . $filename);
            $candidates[] = base_path('public_html/assets/img/' . $filename);
            $candidates[] = public_path('uploads/pdf_sections/environment_impact/' . $filename);
            $candidates[] = public_path('uploads/pdf_sections/payment_terms/' . $filename);
        }

        foreach (array_unique($candidates) as $candidate) {
            if ($candidate && @file_exists($candidate) && @is_file($candidate)) {
                $result = $optimizeImage($candidate);
                if ($result) {
                    return $result;
                }
                // Fallback if optimize failed but file is accessible
                $imgData = @file_get_contents($candidate);
                if ($imgData !== false) {
                    $ext = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
                    $mime = $ext === 'png' ? 'image/png' : 'image/jpeg';
                    return 'data:' . $mime . ';base64,' . base64_encode($imgData);
                }
            }
        }

        // HTTP fallback: try to fetch the image via URL and convert to base64
        // (Dompdf often cannot fetch URLs from the same server due to loopback issues)
        $urlToTry = preg_match('/^https?:\/\//i', $path) ? $path : asset('storage/' . $cleanPath);
        try {
            $ctx = stream_context_create(['http' => ['timeout' => 5], 'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
            $imgData = @file_get_contents($urlToTry, false, $ctx);
            if ($imgData !== false && strlen($imgData) > 0) {
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->buffer($imgData) ?: 'image/jpeg';
                return 'data:' . $mime . ';base64,' . base64_encode($imgData);
            }
        } catch (\Throwable $e) {
        }

        // Last resort: return the URL directly (may not render in Dompdf)
        return $urlToTry;
    }
}

if (!function_exists('resolve_pdf_image_with_fallback')) {
    function resolve_pdf_image_with_fallback(?string $primaryPath, string $fallbackPath): string
    {
        $isValidDataUri = static function (?string $src): bool {
            return is_string($src) && strpos($src, 'data:image') === 0;
        };

        $primaryPath = trim((string) $primaryPath);
        if ($primaryPath !== '') {
            $resolved = normalize_pdf_image($primaryPath);
            if ($isValidDataUri($resolved)) {
                return $resolved;
            }
        }

        $fallback = normalize_pdf_image($fallbackPath);

        return $isValidDataUri($fallback) ? $fallback : '';
    }
}

if (!function_exists('sanitize_pdf_rich_html')) {
    function sanitize_pdf_rich_html($html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $allowed = '<p><br><br/><b><strong><i><em><u><h2><h3><h4><ul><ol><li>';
        $html = strip_tags($html, $allowed);
        $html = str_replace(['<br/>', '<br />'], '<br>', $html);

        return trim($html);
    }
}

if (!function_exists('pdf_rich_html_plain_length')) {
    function pdf_rich_html_plain_length($html): int
    {
        return mb_strlen(trim(strip_tags((string) $html)));
    }
}

if (!function_exists('split_pdf_rich_html_half')) {
    function split_pdf_rich_html_half(string $html): array
    {
        $html = trim($html);
        if ($html === '') {
            return ['', ''];
        }

        $chunks = preg_split('/(?<=<\/p>)/i', $html, -1, PREG_SPLIT_NO_EMPTY);
        if (count($chunks) <= 1) {
            $chunks = preg_split('/(?<=<\/li>)/i', $html, -1, PREG_SPLIT_NO_EMPTY);
        }
        if (count($chunks) <= 1) {
            $chunks = preg_split('/(?<=<\/h2>)/i', $html, -1, PREG_SPLIT_NO_EMPTY);
        }
        if (count($chunks) <= 1) {
            $chunks = preg_split('/(?<=<\/h3>)/i', $html, -1, PREG_SPLIT_NO_EMPTY);
        }
        if (count($chunks) <= 1) {
            return [$html, ''];
        }

        $mid = (int) ceil(count($chunks) / 2);

        return [implode('', array_slice($chunks, 0, $mid)), implode('', array_slice($chunks, $mid))];
    }
}

if (!function_exists('split_env_content_for_image')) {
    function split_env_content_for_image(string $html): array
    {
        $html = trim($html);
        if ($html === '') {
            return ['', ''];
        }

        if (preg_match('/^(.*?)(<(?:ul|ol|h3)\b.*)$/is', $html, $match)) {
            $top = trim($match[1]);
            $bottom = trim($match[2]);
            if ($top !== '' && $bottom !== '') {
                return [$top, $bottom];
            }
        }

        return split_pdf_rich_html_half($html);
    }
}

// Compatibility helper: CodeIgniter-style `base_url()` used in this PDF template.
if (!function_exists('base_url')) {
    function base_url($path = '')
    {
        return normalize_pdf_image($path);
    }
}

// ✅ Load the model here directly
$model = new \App\Models\User(); // Change to your actual model name
// $user = $model->where('id', $userId)->first();
// $user = $model->where('role', 3)->first();

$rupeeHtml = '<span style="font-family: DejaVu Sans; sans-serif;">&#8377;</span>';
$estimate_no = $estimate_no ?? '--';

// Normalize user data for this legacy PDF template.
if (!isset($user) && isset($profileUser)) {
    $user = $profileUser;
}

if (!isset($user) || (!is_array($user) && !($user instanceof \ArrayAccess))) {
    $user = [];
}

// Resolve company logo from settings table (company_logo_path)
$logoBase64 = null;
if (!empty($companyLogoPath) && file_exists($companyLogoPath)) {
    $logoData = file_get_contents($companyLogoPath);
    $logoBase64 = 'data:image/' . pathinfo($companyLogoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode($logoData);
} elseif (!empty($companySettings['company_logo_path'])) {
    $diskPath = storage_path('app/public/' . $companySettings['company_logo_path']);
    if (file_exists($diskPath)) {
        $logoData = file_get_contents($diskPath);
        $logoBase64 = 'data:image/' . pathinfo($diskPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode($logoData);
    }
}

// Fallback to legacy $user['company_logo'] if settings logo is not found
if (!$logoBase64 && !empty($user['company_logo'])) {
    $legacyPath = public_path('assets/img/profile/' . $user['company_logo']);
    if (file_exists($legacyPath)) {
        $logoData = file_get_contents($legacyPath);
        $logoBase64 = 'data:image/' . pathinfo($legacyPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode($logoData);
    } else {
        $logoBase64 = normalize_pdf_image('public/assets/img/profile/' . $user['company_logo']);
    }
}

// Use the profile's small company logo for the compact cover footer.
$smallCompanyLogo = $logoBase64;
if (!empty($companySettings['sidebar_icon_path'])) {
    $smallLogoDisk = \Illuminate\Support\Facades\Storage::disk('public');
    if ($smallLogoDisk->exists($companySettings['sidebar_icon_path'])) {
        $smallCompanyLogo = normalize_pdf_image($smallLogoDisk->path($companySettings['sidebar_icon_path']));
    }
}

// Resolve company name from settings table (company_name)
$globalCompanyName = !empty($companySettings['company_name']) ? $companySettings['company_name'] : (!empty($user['company_name']) ? $user['company_name'] : '--');

// Section active helper (if "active" is missing, treat as active)
$_isActive = static function ($section): bool {
    return !is_array($section) || !array_key_exists('active', $section) || (int) $section['active'] === 1;
};

// ---------------- Page-break control ----------------
// Dompdf will add an extra blank page if the LAST rendered page has `page-break-after: always`.
// Since pages are conditionally rendered, compute which pages are active and only apply
// the `page-break` class to pages BEFORE the last active page.
if (!isset($paymentTerms) || !is_array($paymentTerms)) {
    $paymentTerms = isset($payment_terms) && is_array($payment_terms) ? $payment_terms : [];
}
if (!isset($environmentImpact) || !is_array($environmentImpact)) {
    $environmentImpact = isset($environment_impact) && is_array($environment_impact) ? $environment_impact : [];
}

$pdfTemplateName = trim((string) ($template_name ?? ''));
$pdfDocumentTitle = $pdfTemplateName;
if ($pdfDocumentTitle === '' && !empty($estimate_no) && $estimate_no !== '--') {
    $pdfDocumentTitle = 'Estimate ' . $estimate_no;
}
if ($pdfDocumentTitle === '') {
    $pdfDocumentTitle = 'Solar Proposal';
}
$__isResidentialTemplate = $pdfTemplateName !== '' && stripos($pdfTemplateName, 'resident') !== false;

$__companyInfoSection = isset($companyInfo) && is_array($companyInfo) ? $companyInfo : [];
$__generationSection0 = isset($generationSection) && is_array($generationSection) ? $generationSection : [];
$__ongridRoiSection0 = isset($ongridRoiSection) && is_array($ongridRoiSection) ? $ongridRoiSection : [];
$__timeLineSection = isset($timeLine) && is_array($timeLine) ? $timeLine : [];
$__componentsSection = isset($components) && is_array($components) ? $components : [];
$__environmentImpactSection = isset($environmentImpact) && is_array($environmentImpact) ? $environmentImpact : [];
$__footerSection = isset($footer) && is_array($footer) ? $footer : [];

$__companyInfoActive0 = $_isActive($__companyInfoSection);
$__generationActive0 = false;
$__ongridRoiActive0 = $_isActive($__ongridRoiSection0);
$__timeLineActive0 = $_isActive($__timeLineSection);
$__paymentTermsSection0 = isset($paymentTerms) && is_array($paymentTerms) ? $paymentTerms : [];
$__paymentTermsActive0 = $_isActive($__paymentTermsSection0);
$__componentsActive0 = $_isActive($__componentsSection);
$__environmentImpactActive0 = $_isActive($__environmentImpactSection);
$__footerActive0 = $_isActive($__footerSection);

// Page 5 (Offer & Terms content) uses timeline_active; Page 7 (Payment Terms) uses payment_terms active toggle.

$__activePages = ['p1']; // first page always renders
if ($__companyInfoActive0) {
    $__activePages[] = 'p2';
}
if ($__generationActive0) {
    $__activePages[] = 'p3';
}
if ($__ongridRoiActive0) {
    $__activePages[] = 'p4';
}
if ($__timeLineActive0) {
    $__activePages[] = 'p5';
}
if ($__componentsActive0) {
    $__activePages[] = 'p6';
}
if ($__paymentTermsActive0) {
    $__activePages[] = 'p7';
}
if ($__environmentImpactActive0) {
    $__activePages[] = 'p8';
}
if ($__isResidentialTemplate) {
    $__activePages[] = 'p8b';
}
if ($__footerActive0) {
    $__activePages[] = 'p9';
}

$__lastPageKey = $__activePages[count($__activePages) - 1];
$_pageClass = static function (string $key) use ($__lastPageKey): string {
    return $key === $__lastPageKey ? 'page' : 'page page-break';
};

$__pdfPageCounter = 0;
$_pdfPageNumber = static function () use (&$__pdfPageCounter): int {
    return ++$__pdfPageCounter;
};

// Load estimate data if estimate_no is available
$estdata = null;
$passedEstimate = isset($estimate) ? $estimate : null;

if (!$passedEstimate && !empty($estimate_no) && $estimate_no !== '--') {
    try {
        $passedEstimate = \App\Models\Estimate::where('estimate_no', $estimate_no)->with('customer')->first();
    } catch (\Throwable $e) {
        $passedEstimate = null;
    }
}

if ($passedEstimate) {
    $estdata = new \stdClass();
    $attrs = $passedEstimate instanceof \Illuminate\Database\Eloquent\Model ? $passedEstimate->getAttributes() : (array) $passedEstimate;

    foreach ($attrs as $key => $val) {
        $estdata->$key = $val;
    }
    if (!empty($invoiceOnly)) {
        foreach (['product_name', 'gst_breakdown'] as $field) {
            $value = $estdata->{$field} ?? [];
            for ($depth = 0; $depth < 2 && is_string($value); $depth++) {
                $value = json_decode($value, true);
            }
            $estdata->{$field} = is_array($value) ? $value : [];
        }
    }

    // Add aliases needed by this template for Customer details
    if (isset($passedEstimate->customer)) {
        $estdata->name = $passedEstimate->customer->name ?? '--';
        $estdata->address = $passedEstimate->customer->address ?? '';
    }
}

// Determine model type to dynamically switch labels (Invoice vs Estimate)
$isInvoice = $passedEstimate instanceof \App\Models\Invoice;
$pdfTypeLabelCap = $isInvoice ? 'SOLAR INVOICE' : 'SOLAR PROPOSAL';
$pdfTypeLabelMixed = $isInvoice ? 'Invoice' : 'Proposal';
$pdfTypeLabelMixed2 = $isInvoice ? 'invoice' : 'proposal';

// Get prepared by name (user who created/owns the estimate)
$preparedByName = $user['name'] ?? ($user['company_name'] ?? '--');
$preparedForName = $estdata && isset($estdata->name) ? $estdata->name : '--';
$clientAddress = $estdata && isset($estdata->address) ? trim((string) $estdata->address) : '';
$hasClientAddress = pdf_has_real_value($clientAddress);
// Get quantity from estimate data
// Get quantity from estimate data - ensure it displays correctly
$quantity = '0';
if ($estdata) {
    if (isset($estdata->quantity) && !empty($estdata->quantity)) {
        $qtyValue = (float) $estdata->quantity;
        if ($qtyValue > 0) {
            // Format quantity - remove trailing zeros if decimal
            $quantity = rtrim(rtrim(number_format($qtyValue, 2, '.', ''), '0'), '.');
        }
    }

    // If quantity is still 0, try to calculate from product_name JSON
    if ($quantity == '0' && !empty($estdata->product_name)) {
        $products = is_array($estdata->product_name) ? $estdata->product_name : json_decode($estdata->product_name, true);
        if (is_array($products) && !empty($products)) {
            $totalQty = 0;
            foreach ($products as $product) {
                if (isset($product['quantity']) && isset($product['capacity'])) {
                    $totalQty += (float) $product['quantity'] * (float) $product['capacity'];
                } elseif (isset($product['quantity'])) {
                    $totalQty += (float) $product['quantity'];
                }
            }
            if ($totalQty > 0) {
                $quantity = rtrim(rtrim(number_format($totalQty, 2, '.', ''), '0'), '.');
            }
        }
    }

    // If quantity is still 0, auto-calculate kW from bill/rate (proposal-wise sizing)
    // Uses the same style of assumptions as the generation chart:
    // monthly_units ≈ monthly_bill / unit_rate
    // required_kW ≈ monthly_units / (avg_units_per_kw_per_day * 30 * (pr/100))
    if ($quantity == '0' && !empty($estdata->generation_data)) {
        $gd = json_decode($estdata->generation_data, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($gd)) {
            $monthlyBill = isset($gd['monthly_electricity_bill']) && is_numeric($gd['monthly_electricity_bill']) ? (float) $gd['monthly_electricity_bill'] : 0.0;
            $unitRateTmp = isset($gd['unit_rate']) && is_numeric($gd['unit_rate']) ? (float) $gd['unit_rate'] : 0.0;

            // Optional override keys if you later store them in generation_data
            $avgUnitsPerKwTmp = isset($gd['avg_units_per_kw']) && is_numeric($gd['avg_units_per_kw']) ? (float) $gd['avg_units_per_kw'] : 4.31;
            $prTmp = isset($gd['pr']) && is_numeric($gd['pr']) ? (float) $gd['pr'] : 80.1;

            $unitRateTmp = $unitRateTmp > 0 ? $unitRateTmp : 8.0;
            $avgUnitsPerKwTmp = $avgUnitsPerKwTmp > 0 ? $avgUnitsPerKwTmp : 4.32;
            $prFactorTmp = max(0.0, min(1.0, $prTmp / 100.0));

            if ($monthlyBill > 0 && $unitRateTmp > 0 && $avgUnitsPerKwTmp > 0 && $prFactorTmp > 0) {
                $monthlyUnits = $monthlyBill / $unitRateTmp;
                $assumedDays = 30.0;
                $requiredKw = $monthlyUnits / ($avgUnitsPerKwTmp * $assumedDays * $prFactorTmp);
                if ($requiredKw > 0) {
                    $quantity = rtrim(rtrim(number_format($requiredKw, 2, '.', ''), '0'), '.');
                }
            }
        }
    }
}
$estimateDate = $estdata && isset($estdata->estimate_date) ? date('j, F Y', strtotime($estdata->estimate_date)) : date('j, F Y');
$generatedDateTime = date('j, F Y | g:iA');

// ================= ROI (Page 4) - Fixed assumptions (proposal-safe) =================
// Inputs:
// - Solar system size (kW): from estimate quantity (no default)
// - Unit rate (₹/unit): generation_data.unit_rate (default 8)
// - System cost (₹): use "Lending Cost Of Customer" = Customer Payable Amount - Subsidy
//
// Fixed assumptions:
// - Average generation: 3.6 units per kW per day
// - Days per year: 365
// - Solar lifetime: 25 years
// - Electricity tariff escalation: 5% per year
// - Self-consumption: 100%

$lifetimeYears = 25;
$daysPerYear = 365;
$avgUnitsPerKwPerDay_Roi = 3.6;
$tariffEscalation = 0.05;
$panelDegradation = 0.025; // 0.7% per year

// Use estimate quantity (NO default 10kW; if missing it stays 0 unless auto-calculated earlier)
$systemCapacity = (float) $quantity;
if ($systemCapacity <= 0) {
    $systemCapacity = 0.0;
}

// Unit rate (₹/unit) from generation_data, default ₹8
$unitRate = 8.0;
if ($estdata && !empty($estdata->generation_data)) {
    $gd = json_decode($estdata->generation_data, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($gd)) {
        if (isset($gd['unit_rate']) && is_numeric($gd['unit_rate'])) {
            $unitRate = (float) $gd['unit_rate'];
        }
    }
}
if ($unitRate <= 0) {
    $unitRate = 8.0;
}

// System cost = Customer Payable Amount - Subsidy (your "Lending Cost Of Customer")
$subtotalForCost = $estdata && isset($estdata->price) ? (float) $estdata->price : 0.0;
$solarStructureChargesForCost = $estdata && isset($estdata->solar_structure_charges) ? (float) $estdata->solar_structure_charges : 0.0;
$discountForCost = $estdata && isset($estdata->discount) ? (float) $estdata->discount : 0.0;
$subsidyForCost = $estdata && isset($estdata->subsidy_amount) ? (float) $estdata->subsidy_amount : 0.0;
$gstRateForCost = $estdata && isset($estdata->gst) ? (float) $estdata->gst : 0.0;

$gstAmountForCost = null;
if ($estdata && isset($estdata->gst_amount) && $estdata->gst_amount !== null && $estdata->gst_amount !== '') {
    $gstAmountForCost = (float) $estdata->gst_amount;
}
if ($gstAmountForCost === null && $estdata && !empty($estdata->gst_breakdown)) {
    $decoded = is_array($estdata->gst_breakdown) ? $estdata->gst_breakdown : json_decode($estdata->gst_breakdown, true);
    if (is_array($decoded) && isset($decoded['gst_amount'])) {
        $gstAmountForCost = (float) $decoded['gst_amount'];
    }
}
if ($gstAmountForCost === null && $estdata && !empty($estdata->product_name)) {
    $items = is_array($estdata->product_name) ? $estdata->product_name : json_decode($estdata->product_name, true);
    if (is_array($items)) {
        $sum = 0.0;
        foreach ($items as $it) {
            if (isset($it['tax_amount']) && is_numeric($it['tax_amount'])) {
                $sum += (float) $it['tax_amount'];
            }
        }
        if ($sum > 0) {
            $gstAmountForCost = $sum;
        }
    }
}
if ($gstAmountForCost === null) {
    $gstAmountForCost = ($subtotalForCost + $solarStructureChargesForCost) * ($gstRateForCost / 100.0);
}

$customerPayableForCost = $subtotalForCost + $solarStructureChargesForCost + (float) $gstAmountForCost - $discountForCost;
$lendingCostForCost = $customerPayableForCost - $subsidyForCost;

// Keep consistent naming with COST section later (PAGE 6)
// Customer Payable Amount (incl. taxes) and Lending Cost Of Customer (after subsidy)
$totalPayable = $customerPayableForCost;
$lendingCost = $lendingCostForCost;

// ROI investment: prefer Lending Cost Of Customer when available
$systemCost = $lendingCost > 0 ? $lendingCost : ($totalPayable > 0 ? $totalPayable : $subtotalForCost);

// Base generation & savings (Year 1)
$yearlyUnits = $systemCapacity * $avgUnitsPerKwPerDay_Roi * $daysPerYear;
$year1Savings = $yearlyUnits * $unitRate;

// ROI outputs
$roiData = [];
$yearlySavings = $year1Savings;
$paybackPeriod = 0.0;
$totalLifetimeSavings = 0.0;
$netLifetimeProfit = 0.0;

// Payback (as requested): Investment ÷ Year-1 savings (rounded to nearest whole year)
$paybackExact = $year1Savings > 0 ? $systemCost / $year1Savings : 0.0;
$paybackRoundedYears = $paybackExact > 0 ? (int) round($paybackExact) : 0;

// Build Year 1..25 cumulative savings with:
// - 5% tariff escalation per year
// - 0.7% panel degradation per year
$cumulative = 0.0;
for ($y = 1; $y <= $lifetimeYears; $y++) {
    $yearSaving = $year1Savings * pow(1.0 + $tariffEscalation, $y - 1) * pow(1.0 - $panelDegradation, $y - 1);
    $cumulative += $yearSaving;

    $roiData[] = [
        'year' => $y,
        'cumulative' => $cumulative,
        'hasData' => true,
    ];
}

$totalLifetimeSavings = $cumulative;
$netLifetimeProfit = $totalLifetimeSavings - $systemCost;

// Payback display value (whole years)
$paybackPeriod = $paybackExact;
// If payback can't be computed (0/empty), show "1" as requested (display-only)
$paybackPeriodDisplay = (string) max(1, (int) $paybackRoundedYears);

// This template now always uses the fixed-assumptions ROI (Year 1–25).
// Keep the flag for compatibility with existing chart rendering code below.
$useSimpleRoi = true;

// For axis fallback in chart code later (kept for backward compatibility)
$estimateAmountMin = null;
$estimateAmountMax = null;

// Format outputs (₹)
$yearlySavingsFormatted = number_format($yearlySavings, 0);
$paybackPeriodFormatted = (string) $paybackRoundedYears;
$totalLifetimeSavingsFormatted = number_format($totalLifetimeSavings, 0);
$netLifetimeProfitFormatted = number_format($netLifetimeProfit, 0);
// Lakhs display (like 35.0L)
$totalLifetimeSavingsLakhs = $totalLifetimeSavings / 100000;
$totalLifetimeSavingsLakhsFormatted = number_format($totalLifetimeSavingsLakhs, 1) . 'L';
$netLifetimeProfitLakhs = $netLifetimeProfit / 100000;
$netLifetimeProfitLakhsFormatted = number_format($netLifetimeProfitLakhs, 1) . 'L';

// Chart scaling helpers
$allCumulativeValues = !empty($roiData) ? array_column($roiData, 'cumulative') : [0];
$maxRoiValue = max($allCumulativeValues);
$minRoiValue = min($allCumulativeValues);
$absMax = max(abs($maxRoiValue), abs($minRoiValue));
$maxChartRoi = ceil($absMax / 500000) * 500000;
if ($maxChartRoi < 500000) {
    $maxChartRoi = 3000000;
}

// Fetch generation data from all current year estimates
$monthlyData = [];
// Default values - will be calculated dynamically from estimates table
$maxChartValue = 2000;
$pr = 80;
$monsoonDip = 13.8;
$avgUnitsPerKw = 4.31;

// Get current year
$currentYear = date('Y');
$currentYearStart = $currentYear . '-01-01';
$currentYearEnd = $currentYear . '-12-31';

// Initialize monthly data arrays for aggregation
$monthlyPrimary = array_fill(0, 12, 0);
$monthlySecondary = array_fill(0, 12, 0);
$totalQuantity = 0;
$estimateCount = 0;
$hasGenerationData = false;
try {
    $estimates = \App\Models\Estimate::whereYear('created_at', $currentYear)->get()->toArray();

    // Initialize month-wise quantity aggregation
    $monthlyQuantity = array_fill(0, 12, 0);
    $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    // Base generation per kW per month (varies by season) - units per kW
    $baseGenerationPerKw = [
        110,
        110,
        140,
        150,
        150,
        120,
        90,
        80,
        100,
        110,
        110,
        100, // Jan-Dec
    ];

    // Aggregate estimates by month based on created_at
    foreach ($estimates as $estimate) {
        $estimateCount++;
        $estQuantity = isset($estimate['quantity']) ? (float) $estimate['quantity'] : 0;
        if ($estQuantity > 0) {
            $totalQuantity += $estQuantity;

            // Get month from created_at
            if (isset($estimate['created_at']) && !empty($estimate['created_at'])) {
                $createdDate = $estimate['created_at'];
                $monthNum = (int) date('n', strtotime($createdDate)); // 1-12
                $monthIndex = $monthNum - 1; // 0-11

                if ($monthIndex >= 0 && $monthIndex < 12) {
                    // Add quantity to the corresponding month
                    $monthlyQuantity[$monthIndex] += $estQuantity;
                }
            }
        }

        // Check if generation_data field exists and has data
        $generationDataJson = null;
        if (isset($estimate['generation_data']) && !empty($estimate['generation_data'])) {
            $generationDataJson = $estimate['generation_data'];
        } elseif (isset($estimate['comment']) && !empty($estimate['comment'])) {
            // Try parsing comment field as JSON (fallback)
            $decoded = json_decode($estimate['comment'], true);
            if (json_last_error() === JSON_ERROR_NONE && isset($decoded['generation_data'])) {
                $generationDataJson = json_encode($decoded['generation_data']);
            }
        }

        // Parse generation data if available
        if ($generationDataJson) {
            $genData = json_decode($generationDataJson, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($genData)) {
                $hasGenerationData = true;
                $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                foreach ($months as $index => $month) {
                    $monthKey = strtolower($month);
                    $monthNum = $index + 1;

                    // Try multiple key formats
                    $primary = 0;
                    $secondary = 0;

                    if (isset($genData[$monthKey])) {
                        $monthData = $genData[$monthKey];
                        $primary = isset($monthData['primary']) ? (float) $monthData['primary'] : (isset($monthData[0]) ? (float) $monthData[0] : 0);
                        $secondary = isset($monthData['secondary']) ? (float) $monthData['secondary'] : (isset($monthData[1]) ? (float) $monthData[1] : $primary * 0.9);
                    } elseif (isset($genData[$monthNum])) {
                        $monthData = $genData[$monthNum];
                        $primary = isset($monthData['primary']) ? (float) $monthData['primary'] : (isset($monthData[0]) ? (float) $monthData[0] : 0);
                        $secondary = isset($monthData['secondary']) ? (float) $monthData['secondary'] : (isset($monthData[1]) ? (float) $monthData[1] : $primary * 0.9);
                    } elseif (isset($genData[$index])) {
                        $monthData = $genData[$index];
                        $primary = isset($monthData['primary']) ? (float) $monthData['primary'] : (isset($monthData[0]) ? (float) $monthData[0] : 0);
                        $secondary = isset($monthData['secondary']) ? (float) $monthData['secondary'] : (isset($monthData[1]) ? (float) $monthData[1] : $primary * 0.9);
                    }

                    // Aggregate data
                    $monthlyPrimary[$index] += $primary;
                    $monthlySecondary[$index] += $secondary;
                }

                // Get statistics from estimate data if available (will be overridden by calculated values later)
                if (isset($genData['pr']) && $genData['pr'] > 0) {
                    $pr = (float) $genData['pr'];
                }
                if (isset($genData['monsoon_dip']) && $genData['monsoon_dip'] > 0) {
                    $monsoonDip = (float) $genData['monsoon_dip'];
                }
                if (isset($genData['avg_units_per_kw']) && $genData['avg_units_per_kw'] > 0) {
                    $avgUnitsPerKw = (float) $genData['avg_units_per_kw'];
                }
            }
        }
    }

    // Calculate generation data based on month-wise quantity aggregation
    // For each month, calculate generation based on quantity created in that month
    foreach ($monthNames as $index => $month) {
        $monthQty = $monthlyQuantity[$index];

        if ($monthQty > 0) {
            // Calculate generation for this month based on quantity created
            // Use base generation scaled by quantity
            $baseGen = $baseGenerationPerKw[$index];
            $primaryGen = round($baseGen * $monthQty);
            $secondaryGen = round($primaryGen * 0.9);

            $monthlyPrimary[$index] += $primaryGen;
            $monthlySecondary[$index] += $secondaryGen;
        }
    }
} catch (\Throwable $e) {
    // If error, fall back to default
    $estimateCount = 0;
}

// Build monthly data array
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
foreach ($months as $index => $month) {
    $monthlyData[] = [
        'month' => $month,
        'primary' => round($monthlyPrimary[$index]),
        'secondary' => round($monthlySecondary[$index]),
    ];
}

// If no data found, use default calculated values based on current estimate quantity
if (empty($monthlyData) || array_sum($monthlyPrimary) == 0) {
    $baseMonthlyData = [['month' => 'Jan', 'primary' => 1100, 'secondary' => 1000], ['month' => 'Feb', 'primary' => 1100, 'secondary' => 950], ['month' => 'Mar', 'primary' => 1400, 'secondary' => 1200], ['month' => 'Apr', 'primary' => 1500, 'secondary' => 1300], ['month' => 'May', 'primary' => 1500, 'secondary' => 1350], ['month' => 'Jun', 'primary' => 1200, 'secondary' => 1000], ['month' => 'Jul', 'primary' => 900, 'secondary' => 750], ['month' => 'Aug', 'primary' => 800, 'secondary' => 650], ['month' => 'Sep', 'primary' => 1000, 'secondary' => 850], ['month' => 'Oct', 'primary' => 1100, 'secondary' => 950], ['month' => 'Nov', 'primary' => 1100, 'secondary' => 950], ['month' => 'Dec', 'primary' => 1000, 'secondary' => 850]];

    // Scale data based on quantity (baseMonthlyData is defined for 10kW, so scale only when quantity is provided)
    $scaleFactor = (float) $quantity > 0 ? (float) $quantity / 10.0 : 0.0;

    $monthlyData = [];
    foreach ($baseMonthlyData as $data) {
        $monthlyData[] = [
            'month' => $data['month'],
            'primary' => round($data['primary'] * $scaleFactor),
            'secondary' => round($data['secondary'] * $scaleFactor),
        ];
    }
}

// Calculate dynamic statistics from actual estimates data
if (!empty($monthlyData)) {
    $totalPrimary = array_sum(array_column($monthlyData, 'primary'));
    $totalSecondary = array_sum(array_column($monthlyData, 'secondary'));

    // Calculate Performance Ratio (PR) dynamically from estimates data
    $ratios = [];
    foreach ($monthlyData as $data) {
        if ($data['primary'] > 0) {
            $ratios[] = ($data['secondary'] / $data['primary']) * 100;
        }
    }
    if (!empty($ratios) && count($ratios) > 0) {
        $pr = round(array_sum($ratios) / count($ratios), 1);
    }

    // Calculate Monsoon Dip dynamically from estimates data
    $peakMonths = ['Mar', 'Apr', 'May'];
    $monsoonMonths = ['Jul', 'Aug'];
    $peakAvg = 0;
    $monsoonAvg = 0;
    $peakCount = 0;
    $monsoonCount = 0;

    foreach ($monthlyData as $data) {
        if (in_array($data['month'], $peakMonths)) {
            $peakAvg += $data['primary'];
            $peakCount++;
        }
        if (in_array($data['month'], $monsoonMonths)) {
            $monsoonAvg += $data['primary'];
            $monsoonCount++;
        }
    }

    if ($peakCount > 0 && $monsoonCount > 0 && $peakAvg > 0) {
        $peakAvg = $peakAvg / $peakCount;
        $monsoonAvg = $monsoonAvg / $monsoonCount;
        if ($peakAvg > 0) {
            $monsoonDip = round((($peakAvg - $monsoonAvg) / $peakAvg) * 100, 1);
        }
    }

    // Calculate Average units per kW per day dynamically from estimates data
    $calcQuantity = $totalQuantity > 0 ? $totalQuantity : (float) $quantity;
    if ($calcQuantity > 0 && $totalPrimary > 0) {
        $totalUnits = $totalPrimary;
        $daysInYear = 365;
        if ($daysInYear > 0) {
            $avgUnitsPerKw = round($totalUnits / $calcQuantity / $daysInYear, 2);
        }
    }

    // Calculate maxChartValue dynamically from estimates data
    $maxPrimary = max(array_column($monthlyData, 'primary'));
    if ($maxPrimary > 0) {
        // Round up to nearest 200
        $maxChartValue = ceil($maxPrimary / 200) * 200;
        if ($maxChartValue < 1000) {
            $maxChartValue = 1000;
        } elseif ($maxChartValue > 2000) {
            $maxChartValue = 2000;
        }
    }
} else {
    // If no monthly data, try to get defaults from estimates table statistics
    try {
        $userId = $estdata ? $estdata->user_id ?? null : null;
        $query = \App\Models\Estimate::whereYear('created_at', $currentYear);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $estimates = $query->get()->toArray();

        // Calculate average quantity from estimates
        $quantities = [];
        foreach ($estimates as $est) {
            if (isset($est['quantity']) && (float) $est['quantity'] > 0) {
                $quantities[] = (float) $est['quantity'];
            }
        }

        if (!empty($quantities)) {
            $avgQuantity = array_sum($quantities) / count($quantities);
            // Use average quantity to calculate default avgUnitsPerKw
            if ($avgQuantity > 0) {
                $avgUnitsPerKw = round(4.31 * ($avgQuantity / 10), 2);
            }
        }
    } catch (\Throwable $e) {
        // Keep default values
    }
}

// --- START PLACEHOLDER LOGIC ---
$placeholders = [
    '{{client_name}}' => $preparedForName ?? '--',
    '{{client_address}}' => $hasClientAddress ? $clientAddress : '',
    '{{company_name}}' => $globalCompanyName ?? '--',
    '{{company_contact}}' => $companySettings['phone'] ?? ($user['mobile'] ?? '--'),
    '{{company_website}}' => $companySettings['website'] ?? ($user['website'] ?? '--'),
    '{{sales_person_name}}' => $preparedByName ?? '--',
    '{{sales_person_designation}}' => $user['designation'] ?? '--',
    '{{system_capacity}}' => $quantity ?? '--',
    '{{daily_generation}}' => isset($quantity) && isset($avgUnitsPerKwPerDay_Roi) ? round((float) $quantity * (float) $avgUnitsPerKwPerDay_Roi, 1) : '--',
    '{{monthly_generation}}' => isset($yearlyUnits) ? round($yearlyUnits / 12) : '--',
    '{{annual_generation}}' => $yearlyUnits ?? '--',
    '{{tariff_rate}}' => $unitRate ?? '--',
    '{{monthly_savings}}' => isset($year1Savings) ? round($year1Savings / 12) : '--',
    '{{annual_savings}}' => $yearlySavingsFormatted ?? '--',
    '{{lifetime_savings}}' => $totalLifetimeSavingsFormatted ?? '--',
    '{{payback_period}}' => $paybackPeriodFormatted ?? '--',
    '{{co2_offset}}' => isset($yearlyUnits) ? number_format(($yearlyUnits * 25 * 0.82) / 1000, 1) : '--',
    '{{coal_equivalent}}' => isset($yearlyUnits) ? number_format(($yearlyUnits * 25 * 0.4) / 1000, 1) : '--',
    '{{tree_equivalent}}' => isset($yearlyUnits) ? number_format($yearlyUnits * 25 * 0.0117, 0) : '--',
    '{{base_cost}}' => isset($subtotalForCost) ? number_format($subtotalForCost, 0) : '--',
    '{{gst_amount}}' => isset($gstAmountForCost) ? number_format($gstAmountForCost, 0) : '--',
    '{{gross_value}}' => isset($customerPayableForCost) ? number_format($customerPayableForCost, 0) : '--',
    '{{subsidy_amount}}' => isset($subsidyForCost) ? number_format($subsidyForCost, 0) : '--',
    '{{net_investment}}' => isset($lendingCost) ? number_format($lendingCost, 0) : '--',
    '{{estimate_date}}' => $estimateDate ?? date('j, F Y'),
];

$applyPlaceholders = function ($text) use ($placeholders) {
    if (empty($text) || !is_string($text)) {
        return $text;
    }
    $values = [];
    foreach ($placeholders as $placeholder => $value) {
        $value = trim((string) $value);
        $values[$placeholder] = $value === '--' ? '' : $value;
        if ($values[$placeholder] === '') {
            // Remove a separator belonging to an absent field, not document text.
            $token = preg_quote($placeholder, '/');
            $text = preg_replace('/\s*\|\s*' . $token . '/', $placeholder, $text);
            $text = preg_replace('/' . $token . '\s*\|\s*/', $placeholder, $text);
        }
    }
    $text = str_replace(array_keys($values), array_values($values), $text);
    // Drop empty lines left by optional designation/contact fields.
    do {
        $previous = $text;
        $text = preg_replace('/<(p|strong|b|em|i|span)\b[^>]*>(?:\s|&nbsp;|<br\s*\/?\s*>)*<\/\1>/i', '', $text);
    } while ($text !== $previous);

    return $text;
};

if (isset($quotation_html)) {
    $quotation_html = $applyPlaceholders($quotation_html);
}

// Process before_blocks
if (isset($before_blocks) && is_array($before_blocks)) {
    foreach ($before_blocks as $idx => $block) {
        if (isset($block['description'])) {
            $before_blocks[$idx]['description'] = $applyPlaceholders($block['description']);
        }
    }
}

// Process after_blocks
if (isset($after_blocks) && is_array($after_blocks)) {
    foreach ($after_blocks as $idx => $block) {
        if (isset($block['description'])) {
            $after_blocks[$idx]['description'] = $applyPlaceholders($block['description']);
        }
    }
}
// --- END PLACEHOLDER LOGIC ---
?>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?= esc($pdfDocumentTitle) ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap');

        * {
            margin: 0;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            font-family: 'Montserrat', sans-serif;
            height: 100%;
            width: 100%;
        }

        .header,
        .footer {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            object-fit: cover;
        }

        .brand-logo {
            display: block;
            width: 120px;
            margin: 0px 0 10px 10px;
        }

        .page {
            height: 100%;
            width: 100%;
        }

        /* ✅ apply break only where needed */
        .page-break {
            page-break-after: always;
        }

        .pdf-rich-content {
            font-family: 'Montserrat', sans-serif;
            font-size: 13px;
            line-height: 1.55;
            color: #333;
        }

        .pdf-rich-content h2 {
            font-size: 17px;
            font-weight: bold;
            color: #4b9349;
            margin: 0 0 10px 0;
            padding: 0;
            line-height: 1.3;
        }

        .pdf-rich-content h3 {
            font-size: 14px;
            font-weight: bold;
            color: #2f5f2f;
            margin: 14px 0 8px 0;
            padding: 0;
            line-height: 1.3;
        }

        .pdf-rich-content h4 {
            font-size: 13px;
            font-weight: bold;
            color: #333;
            margin: 10px 0 6px 0;
        }

        .pdf-rich-content p {
            margin: 0 0 10px 0;
            text-align: justify;
            line-height: 1.55;
        }

        .pdf-rich-content ul,
        .pdf-rich-content ol {
            margin: 6px 0 12px 0;
            padding-left: 24px;
        }

        .pdf-rich-content ul {
            list-style-type: disc;
        }

        .pdf-rich-content ol {
            list-style-type: decimal;
        }

        .pdf-rich-content li {
            margin: 0 0 7px 0;
            line-height: 1.45;
            padding-left: 2px;
            display: list-item;
        }

        .pdf-rich-content strong,
        .pdf-rich-content b {
            font-weight: bold;
            color: #1a1a1a;
        }

        .pdf-rich-content-spacious {
            font-size: 15px;
            line-height: 1.65;
        }

        .pdf-rich-content-spacious h2 {
            font-size: 21px;
            margin: 0 0 14px 0;
            line-height: 1.35;
        }

        .pdf-rich-content-spacious h3 {
            font-size: 17px;
            margin: 18px 0 12px 0;
            line-height: 1.35;
        }

        .pdf-rich-content-spacious p {
            font-size: 15px;
            margin: 0 0 14px 0;
            line-height: 1.65;
        }

        .pdf-rich-content-spacious ul,
        .pdf-rich-content-spacious ol {
            margin: 10px 0 18px 0;
            padding-left: 30px;
        }

        .pdf-rich-content-spacious li {
            font-size: 15px;
            margin: 0 0 11px 0;
            line-height: 1.55;
            padding-left: 4px;
        }

        .pdf-company-page-about {
            font-size: 16px;
            line-height: 1.68;
        }

        .pdf-company-page-about h2 {
            font-size: 22px;
            color: #4b9349;
            margin: 0 0 16px 0;
        }

        .pdf-company-page-about h3 {
            font-size: 18px;
            color: #2f5f2f;
            margin: 20px 0 14px 0;
        }

        .pdf-env-impact-content h2,
        .pdf-env-impact-content h3 {
            font-weight: bold;
            padding-left: 14px;
            line-height: 1.25;
        }

        .pdf-env-impact-content h2 {
            font-size: 22px;
            color: #111;
            margin: 0 0 12px 0;
        }

        .pdf-env-impact-content h3 {
            font-size: 18px;
            color: #111;
            margin: 0 0 10px 0;
        }

        .pdf-env-impact-content p {
            font-size: 15px;
            margin: 0 0 12px 0;
            line-height: 1.52;
        }

        .pdf-env-impact-content ul,
        .pdf-env-impact-content ol {
            margin: 8px 0 12px 0;
            padding-left: 24px;
        }

        .pdf-env-impact-content li {
            font-size: 15px;
            margin: 0 0 8px 0;
            line-height: 1.48;
        }

        .pdf-env-impact-content strong,
        .pdf-env-impact-content em {
            font-size: 15px;
        }

        .pdf-company-page-about p {
            font-size: 16px;
            margin: 0 0 16px 0;
            line-height: 1.68;
        }

        .pdf-company-page-about ul,
        .pdf-company-page-about ol {
            margin: 12px 0 20px 0;
            padding-left: 32px;
        }

        .pdf-company-page-about li {
            font-size: 16px;
            margin: 0 0 12px 0;
            line-height: 1.58;
        }

        .with-logo {
            padding-top: 10px;
        }

        .image-container {
            height: 60%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .image-container img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
        }

        .content-container {
            flex: 1;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            text-align: center;
        }

        .content-container h4 {
            margin: 0;
            font-size: 24px !important;
            font-weight: bold;
        }

        .content-container div {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            letter-spacing: 1px;
        }

        .content-container div h2 {
            color: #52866A;
        }

        .content-container div p {
            color: #000;
            font-size: 15px
        }

        .content-container div ul li,
        .content-container div ol li {
            color: #000;
            font-size: 15px
        }

        /* ✅ Quotation styles (NO page-break) */
        .quotation-page {
            padding: 0px !important;
        }

        .quotation-container {
            width: 100%;
            font-size: 14px;
            line-height: 1.6;
            text-align: left;
        }

        /* Footer repeated on every page */
        .pdf-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 35px;
            font-size: 15px;
            color: #333;
            background: white;
            border-top: 1px solid #ccc;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 5px 20px;
            z-index: 999;
        }

        .footer-left {
            font-weight: bold;
        }

        .footer-center {
            text-align: center;
            flex: 1;
        }


        .footer-right a {
            margin-left: 8px;
            text-decoration: none;
            color: #333;
            /* margin-top: -15px; */
        }

        /* Header Row */
        /* Header Row - only for first page */
        .header-container {
            display: flex;
            align-items: center;
            /* keep both aligned vertically */
            padding: 0;
            /* remove big padding */
            height: 120px;
            /* give more height */
        }

        .header-left {
            width: 50%;
            height: 100%;
        }

        .header-left img {
            width: 410px;
            /* stretch across left side */
            height: 1075px;
            /* fill container height */
            object-fit: cover;
            /* make it look like a banner */
        }

        .header-right {
            width: 100%;
            text-align: right;
            font-size: 29px;
            line-height: 1.4;
            padding-right: 0px;
            /* add spacing from edge */
        }


        .header-right img {
            width: 160px;
            margin-top: -120px;
        }

        .header-right .company-name {
            font-weight: bold;
            font-size: 30px;
            margin-top: 0px;
            font-family: 'Montserrat', sans-serif;
        }

        .header-right .company-address {
            font-weight: 600;
            font-size: 15px;
            margin-top: 100px;
            font-family: 'Montserrat', sans-serif;
        }
    </style>
    <?php
    $img3 = !empty($companyInfo['image3']) ? normalize_pdf_image($companyInfo['image3']) : normalize_pdf_image('public/assets/img/secondpage_3.png');
    $normalized_header = !empty($header_image) ? normalize_pdf_image($header_image) : '';
    $header_image = strpos($normalized_header, 'data:image') === 0 ? $normalized_header : normalize_pdf_image('public/assets/img/header_Image.jpg');
    ?>
</head>

<body>
    @if (empty($invoiceOnly))

    <!-- ✅ FIRST PAGE -->
    <div class="<?= $_pageClass('p1') ?>" style="position: relative; height: 100%; min-height: 842px; overflow: hidden;">
        <!-- Top Half: Header Image -->
        <div style="height: 62%; width: 100%; overflow: hidden; position: relative;">
            <img src="<?= $header_image ?>" alt="Header Image"
                style="width: 100%; height: 100%; object-fit: cover; display: block;">
        </div>

        <!-- Bottom Half: Content Section with Red Border -->
        <table width="100%" cellpadding="0" cellspacing="0"
            style="background:#fff; border-collapse:collapse; font-family:'Montserrat', sans-serif; margin-top:20px; padding-left: 20px; padding-right:15px;">
            <tr>
                <!-- LEFT SECTION -->
                <td width="40%" valign="top" style="padding:10px 15px; border-right:2px solid #4b9349;">

                    <!-- Logo + Company Info -->
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <?php if (!empty($logoBase64)): ?>
                            <td width="40%" valign="top">
                                <img src="<?= $logoBase64 ?>" style="max-width:200px; height:auto; object-fit:contain;">
                            </td>
                            <?php endif; ?>
                        </tr>
                    </table>

                    <!-- Company Full Name -->
                    <div
                        style="font-size:20px; color:#000; margin:25px 0; font-weight:400; font-family: 'Montserrat', sans-serif;">
                        <?php
                        $fullCompanyName = esc($globalCompanyName);
                        if (stripos($fullCompanyName, 'technologies') === false && stripos($fullCompanyName, 'pvt') === false) {
                            $fullCompanyName .= ' Technologies Pvt. Ltd.';
                        }
                        echo $fullCompanyName;
                        ?>
                    </div>

                    <!-- Proposal No -->
                    <div style="font-size:20px; color:#000; font-weight:400; font-family: 'Montserrat', sans-serif;">
                        <span style="font-weight:400;"><?= $pdfTypeLabelMixed ?> no : </span>
                        #<?= esc($estimate_no) ?? '--' ?>
                    </div>

                </td>

                <!-- RIGHT SECTION -->
                <td width="60%" valign="top" style="padding:10px 10px; padding-top: 25px;">

                    <!-- Title -->
                    <div
                        style="margin-top: -25px; font-size:35px; font-weight:700; margin-bottom:10px; font-family: 'Montserrat', sans-serif; color:#000;">
                        <?= ($estdata->type ?? '') === 'residential' ? 'SOLAR PROPOSAL' : $pdfTypeLabelCap ?>
                    </div>
                    <?php if (($estdata->type ?? '') === 'residential'): ?>
                    <div
                        style="font-size:16px; font-style:italic; margin-bottom:15px; font-family: 'Montserrat', sans-serif; color:#666;">
                        Clean Energy. Guaranteed Savings. Sustainable Future.
                    </div>
                    <?php endif; ?>

                    <!-- ONGRID + Date -->
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:10px;">
                        <tr>
                            <td
                                style="font-size:22px; font-weight:400; font-family: 'Montserrat', sans-serif; color:#000;">
                                ONGRID <?= $quantity ?>KW
                            </td>
                            <td align="right"
                                style="font-size:22px; font-weight:400; font-family: 'Montserrat', sans-serif;">
                                <?= $estimateDate ?>
                            </td>
                        </tr>
                    </table>

                    <!-- Client Name -->
                    <div
                        style="font-size:20px; color:#000; margin-bottom:10px; font-weight:400; font-family: 'Montserrat', sans-serif;">
                        <span style="font-size:20px;">Client name :</span> <?= esc($preparedForName) ?>
                    </div>

                    <?php if ($hasClientAddress): ?>
                    <!-- Client Address -->
                    <div style="font-size:22px; font-weight:400; font-family: 'Montserrat', sans-serif;">
                        <?= esc($clientAddress) ?>
                    </div>
                    <?php endif; ?>

                    <!-- Red Line -->
                    <!-- <div style="border-top:1px solid #ff0000; margin-top:15px;"></div> -->

                </td>
            </tr>
        </table>


        <!-- Footer -->
        <div style="position:fixed; bottom:0; left:0; right:0; background:#4b9349; height:60px; padding:15px; ">
            <table width="100%" cellpadding="0" cellspacing="0"
                style="height:60px; color:#fff; font-size:13px; font-family:'Montserrat', sans-serif;">
                <tr>

                    <!-- Prepared By -->
                    <td width="20%" valign="middle" style="padding-left:30px;">
                        <div
                            style="opacity:0.8; font-size:18px; font-weight:400; font-family: 'Montserrat', sans-serif; color:#fff;">
                            Prepared by:</div>
                        <div
                            style="margin-top:0px; font-size:20px; font-weight:400; font-family: 'Montserrat', sans-serif; color:#fff;">
                            <?= esc($preparedByName) ?>
                        </div>
                    </td>

                    <!-- Prepared For -->
                    <td width="20%" valign="middle">
                        <div
                            style="opacity:0.8; font-size:18px; font-weight:400; font-family: 'Montserrat', sans-serif; color:#fff;">
                            Prepared for:</div>
                        <div
                            style="margin-top:0px; font-size:20px; font-weight:400; font-family: 'Montserrat', sans-serif; color:#fff;">
                            <?= esc($preparedForName) ?>
                        </div>
                    </td>

                    <!-- Logo + Divider -->
                    <td width="10%" valign="middle" align="right" style="border-right:3px solid #fff;">
                        <?php if (!empty($smallCompanyLogo)): ?>
                        <img src="<?= $smallCompanyLogo ?>" alt="Company logo"
                            style="height:32px; width:32px; object-fit:contain;
                                        border-radius:6px; opacity:0.9; margin-right:5px;">
                        <?php endif; ?>
                    </td>

                    <!-- Generated On -->
                    <td width="30%" valign="middle" style="padding-left:20px;">
                        <div
                            style="opacity:0.8; font-size:18px; font-weight:400; font-family: 'Montserrat', sans-serif; color:#fff;">
                            Generated on</div>
                        <div
                            style="margin-top:0px; white-space:nowrap; font-size:18px; font-weight:400; font-family: 'Montserrat', sans-serif; color:#fff;">
                            <?= $generatedDateTime ?>
                        </div>
                        <div style="margin-top:4px;font-size:15px;font-weight:600;color:#fff;">
                            PAGE <?= $_pdfPageNumber() ?>
                        </div>
                    </td>

                </tr>
            </table>
        </div>

    </div>

    <!-- ✅ SECOND PAGE: Company Information & Gallery -->
    <?php
    $__companyInfo = isset($companyInfo) && is_array($companyInfo) ? $companyInfo : [];
    $__companyInfoActive = $_isActive($__companyInfo);
    ?>
    <?php if ($__companyInfoActive): ?>
    <?php
    $companyInfo = isset($companyInfo) && is_array($companyInfo) ? $companyInfo : [];
    $companyDescriptionRaw = (string) $applyPlaceholders($companyInfo['company_description'] ?? '');
    $companyDescription = sanitize_pdf_rich_html($companyDescriptionRaw);
    $cap = trim((string) ($companyInfo['company_capacity_installed'] ?? ''));
    $happy = trim((string) ($companyInfo['happy_customers'] ?? ''));
    $cities = trim((string) ($companyInfo['cities'] ?? ''));
    
    $capDisplay = $cap !== '' ? esc($cap) . '+' : '100+';
    $happyDisplay = $happy !== '' ? esc($happy) . '+' : '30+';
    $citiesDisplay = $cities !== '' ? esc($cities) . '+' : '20+';
    
    $img1 = !empty($companyInfo['image1']) ? normalize_pdf_image($companyInfo['image1']) : normalize_pdf_image('public/assets/img/seconpage_1.png');
    $img2 = !empty($companyInfo['image2']) ? normalize_pdf_image($companyInfo['image2']) : normalize_pdf_image('public/assets/img/secondpage_2.png');
    $img3 = !empty($companyInfo['image3']) ? normalize_pdf_image($companyInfo['image3']) : normalize_pdf_image('public/assets/img/secondpage_3.png');
    
    $isResidentialIntro = ($estdata->type ?? '') === 'residential';
    $companyAboutLength = $isResidentialIntro ? 1200 : pdf_rich_html_plain_length($companyDescriptionRaw);
    $companyInfoSinglePage = !$isResidentialIntro && $companyAboutLength <= 500;
    
    $galleryGap = 10;
    $galleryRightHeight = $companyInfoSinglePage ? 150 : 275;
    $galleryLeftHeight = $galleryRightHeight * 2 + $galleryGap;
    $companyInfoPageClass = $_pageClass('p2');
    ?>

    <?php if (!$companyInfoSinglePage): ?>
    <!-- Company about (continues on next page with stats + gallery) -->
    <div class="page page-break" style="position: relative; background: white;">
        <div style="padding: 36px 40px 44px;">
            @include('pdfbuilder.partials.pdf-page-header')
            <div
                style="font-size: 40px; font-weight: bold; margin-bottom: 16px; line-height: 1.12; font-family: 'Montserrat', sans-serif; color: #000; border-left: 8px solid #4b9349; padding-left: 18px;">
                <?= esc($globalCompanyName) ?>
            </div>
            @include('pdfbuilder.partials.company-info-about')
        </div>
        <table width="100%" cellpadding="0" cellspacing="0"
            style="position:fixed; bottom:10; left:0; right:0;
                    background:#fff; color:#4b9349; height:40px; border-top: 1px solid #4b9349;">
            <tr>
                <td width="33.33%" style="padding:10px; font-family: 'Montserrat', sans-serif;">
                    <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                </td>
                <td width="33.33%" align="center" style="padding:10px; font-family: 'Montserrat', sans-serif;">PAGE
                    <?= $_pdfPageNumber() ?></td>
                <td width="33.33%" align="right"
                    style="padding:10px; font-family: 'Montserrat', sans-serif; white-space:nowrap;">
                    Generated by <?= esc($globalCompanyName) ?>
                </td>
            </tr>
        </table>
    </div>
    <?php endif; ?>

    <!-- Stats + gallery (same page; full company section when intro is short) -->
    <div class="<?= $companyInfoSinglePage ? $companyInfoPageClass : 'page page-break' ?>"
        style="position: relative; background: white;">
        <div style="padding: 36px 40px 44px;">
            @include('pdfbuilder.partials.pdf-page-header')

            <?php if ($companyInfoSinglePage): ?>
            <div
                style="font-size: 40px; font-weight: bold; margin-bottom: 16px; line-height: 1.12; font-family: 'Montserrat', sans-serif; color: #000; border-left: 8px solid #4b9349; padding-left: 18px;">
                <?= esc($globalCompanyName) ?>
            </div>
            @include('pdfbuilder.partials.company-info-about')
            <?php endif; ?>

            @include('pdfbuilder.partials.company-info-stats')
            @include('pdfbuilder.partials.company-gallery')
        </div>

        <table width="100%" cellpadding="0" cellspacing="0"
            style="position:fixed; bottom:10; left:0; right:0;
                    background:#fff; color:#4b9349; height:40px; border-top: 1px solid #4b9349;">
            <tr>
                <td width="33.33%" style="padding:10px; font-family: 'Montserrat', sans-serif;">
                    <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                </td>
                <td width="33.33%" align="center" style="padding:10px; font-family: 'Montserrat', sans-serif;">PAGE
                    <?= $_pdfPageNumber() ?></td>
                <td width="33.33%" align="right"
                    style="padding:10px; font-family: 'Montserrat', sans-serif; white-space:nowrap;">
                    Generated by <?= esc($globalCompanyName) ?>
                </td>
            </tr>
        </table>
    </div>
    <?php endif; ?>

    <?php if (($estdata->type ?? '') === 'residential'): ?>
    <!-- ================= PAGE 2b : HOW SOLAR WORKS & ADVANTAGES ================= -->
    <div class="page page-break"
        style="position: relative; min-height: 842px; background: white; font-family:'Montserrat', sans-serif;">
        <!-- Header -->
        <div style="padding: 40px;">
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 30px;">
                <tr>
                    <td width="50%" align="left" valign="top">
                        <div style="font-size: 18px; color: #333;">
                            <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                        </div>
                    </td>
                    <td width="50%" align="right" valign="top">
                        <?php if (!empty($logoBase64)): ?>
                        <img src="<?= $logoBase64 ?>" alt="Company Logo" style="max-width: 160px; height: auto;">
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <!-- Section 2: How the Solar System Works -->
            <div style="font-size: 26px; font-weight: bold; margin-bottom: 12px; padding-left:14px;">
                2. How the Solar System Works
            </div>
            <p style="font-size: 13.5px; margin-bottom: 15px; line-height: 1.5; text-align: justify;">
                A grid-tied solar power system seamlessly integrates with your existing utility grid infrastructure to
                cleanly power your property:
            </p>
            <ul style="padding-left: 20px; font-size: 13.5px; line-height: 1.5; margin-bottom: 25px;">
                <li style="margin-bottom: 8px;"><strong>Step 1: Solar Panels (Photovoltaic Modules) –</strong>
                    Positioned optimally on your roof, these modules absorb sunlight ambient photon radiation and
                    convert it directly into Direct Current (DC) electricity.</li>
                <li style="margin-bottom: 8px;"><strong>Step 2: The Solar Inverter –</strong> Acts as the intelligent
                    brain of the system, converting the DC electricity into stable Alternating Current (AC),
                    standardizing it for all home appliances.</li>
                <li style="margin-bottom: 8px;"><strong>Step 3: Home Consumption & Net Metering –</strong> Power goes
                    to your appliances first. Any excess surplus electricity generated is instantly directed back to the
                    government utility grid via a specialized bidirectional net meter.</li>
                <li style="margin-bottom: 8px;"><strong>Step 4: Utility Grid Backup –</strong> At night or during
                    heavily overcast days, the system smoothly pulls electricity back from the utility grid, ensuring
                    uninterrupted power.</li>
            </ul>

            <!-- Section 3: Advantages of Solar Energy -->
            <div style="font-size: 26px; font-weight: bold; margin-bottom: 12px; padding-left:14px; margin-top: 20px;">
                3. Advantages of Solar Energy
            </div>
            <ul style="padding-left: 20px; font-size: 13.5px; line-height: 1.5; margin-bottom: 20px;">
                <li style="margin-bottom: 8px;"><strong>Massive Utility Bill Reductions:</strong> Drastically slash
                    your monthly energy spend by up to <strong>80% – 90%</strong>.</li>
                <li style="margin-bottom: 8px;"><strong>High Return on Investment:</strong> Solar operates as a
                    high-yielding financial asset that typical clears its payback period within <strong>3 – 4</strong>
                    years, yielding completely free power for the remainder of its 25+ year lifecycle.</li>
                <li style="margin-bottom: 8px;"><strong>Property Appreciation:</strong> Green-certified residential
                    buildings equipped with fixed solar infrastructure command higher market resale values.</li>
                <li style="margin-bottom: 8px;"><strong>Extremely Low Maintenance:</strong> With zero moving parts, the
                    entire system requires minimal operational upkeep—restricted primarily to routine automated or
                    manual panel washings.</li>
                <li style="margin-bottom: 8px;"><strong>Environmental Stewardship:</strong> Directly mitigate carbon
                    footprints and actively combat localized climate change.</li>
            </ul>
        </div>

        <!-- Footer -->
        <table width="100%" cellpadding="0" cellspacing="0"
            style="position:fixed; bottom:10; left:0; right:0; background:#fff; color:#4b9349; height:40px; border-top: 1px solid #4b9349;">
            <tr>
                <td width="33.33%" style="padding:10px; font-family: 'Montserrat', sans-serif;">
                    <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                </td>
                <td width="33.33%" align="center" style="padding:10px; font-family: 'Montserrat', sans-serif;">
                    PAGE <?= $_pdfPageNumber() ?>
                </td>
                <td width="33.33%" align="right"
                    style="padding:10px; font-family: 'Montserrat', sans-serif; white-space:nowrap;">
                    Generated by <?= esc($globalCompanyName) ?>
                </td>
            </tr>
        </table>
    </div>

    <!-- ================= PAGE 2c : DIAGRAM & PM-SURYA GHAR ================= -->
    <div class="page page-break"
        style="position: relative; min-height: 842px; background: white; font-family:'Montserrat', sans-serif;">
        <!-- Header -->
        <div style="padding: 40px;">
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 30px;">
                <tr>
                    <td width="50%" align="left" valign="top">
                        <div style="font-size: 18px; color: #333;">
                            <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                        </div>
                    </td>
                    <td width="50%" align="right" valign="top">
                        <?php if (!empty($logoBase64)): ?>
                        <img src="<?= $logoBase64 ?>" alt="Company Logo" style="max-width: 160px; height: auto;">
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <!-- Section 4: Technical Line Diagram Layout -->
            <div style="font-size: 26px; font-weight: bold; margin-bottom: 12px; padding-left:14px;">
                4. Technical Line Diagram Layout
            </div>
            <p style="font-size: 13.5px; margin-bottom: 15px; line-height: 1.5;">The layout below illustrates the
                seamless logical electrical connection map from production to grid export.</p>
            <div
                style="border: 2px dashed #4b9349; padding: 15px; text-align: center; color: #333; background-color: #f9f9f9; border-radius: 6px; margin-bottom: 30px; font-size: 13px; font-style: italic; font-weight: 500; line-height: 1.4;">
                [ Engineering single line diagram (SLD) layout: Solar PV Modules &rarr; DC Distribution Box (DCDB)
                &rarr; Smart Grid-Tied Inverter &rarr; AC Distribution Box (ACDB) &rarr; Bi-Directional Net Meter &rarr;
                Residential Load / Public Grid ]
            </div>

            <!-- Section 5: PM-Surya Ghar Process -->
            <div style="font-size: 26px; font-weight: bold; margin-bottom: 12px; padding-left:14px; margin-top: 20px;">
                5. PM-Surya Ghar: Muft Bijli Yojana Process
            </div>
            <p style="font-size: 13.5px; margin-bottom: 15px; line-height: 1.5;">As a fully authorized and certified
                empaneled solar vendor, we manage the entire national subsidy workflow framework for your project
                end-to-end:</p>
            <div
                style="border: 1.5px solid #bbf7d0; padding: 15px; border-radius: 6px; font-size: 13px; line-height: 1.5; color: #1e3f20;">
                <p style="margin-bottom: 8px;"><strong>1. Registration:</strong> We safely onboard your consumer
                    credentials directly onto the central government's PM-Surya Ghar National Portal.</p>
                <p style="margin-bottom: 8px;"><strong>2. Technical Feasibility:</strong> The regional DISCOM
                    (Electricity Board) reviews local grid capacity and issues a formal structural clearance approval.
                </p>
                <p style="margin-bottom: 8px;"><strong>3. Execution & Installation:</strong> Our engineering wing
                    carries out code-compliant installation adhering precisely to MNRE quality guidelines.</p>
                <p style="margin-bottom: 8px;"><strong>4. Net Metering Inspection:</strong> DISCOM engineers perform
                    physical on-site verification, deploy the smart net meter, and commission the plant.</p>
                <p style="margin-bottom: 0;"><strong>5. Subsidy Disbursal:</strong> Post-commissioning, the sanctioned
                    central government subsidy is electronically credited to your linked bank account within 30 business
                    days.</p>
            </div>
        </div>

        <!-- Footer -->
        <table width="100%" cellpadding="0" cellspacing="0"
            style="position:fixed; bottom:10; left:0; right:0; background:#fff; color:#4b9349; height:40px; border-top: 1px solid #4b9349;">
            <tr>
                <td width="33.33%" style="padding:10px; font-family: 'Montserrat', sans-serif;">
                    <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                </td>
                <td width="33.33%" align="center" style="padding:10px; font-family: 'Montserrat', sans-serif;">
                    PAGE <?= $_pdfPageNumber() ?>
                </td>
                <td width="33.33%" align="right"
                    style="padding:10px; font-family: 'Montserrat', sans-serif; white-space:nowrap;">
                    Generated by <?= esc($globalCompanyName) ?>
                </td>
            </tr>
        </table>
    </div>
    <?php endif; ?>

    <?php
    $__generationSection = isset($generationSection) && is_array($generationSection) ? $generationSection : [];
    $__generationActive = false;
    
    $genTitle = trim((string) ($__generationSection['title'] ?? ''));
    $genTitle = $genTitle !== '' ? $genTitle : 'GENERATION';
    
    $genSubTitle = trim((string) ($__generationSection['sub_title'] ?? ''));
    $genSubTitle = $genSubTitle !== '' ? $genSubTitle : 'ROUND THE YEAR GENERATION';
    
    $genNote = trim((string) ($__generationSection['note'] ?? ''));
    $genNote = $genNote !== '' ? $genNote : 'Generation figures are indicative and may vary with site conditions and weather patterns.';
    ?>
    <?php if ($__generationActive): ?>
    <!-- ================= PAGE 3 : GENERATION ================= -->
    <div class="<?= $_pageClass('p3') ?>"
        style="position:relative; min-height:842px; background:#fff;
                   
                    font-family:'Montserrat', sans-serif;">
        <!-- Slightly tighter padding so chart + summary fit on one Dompdf page -->
        <div style="padding: 35px 45px 45px 45px;">
            <!-- ================= HEADER ================= -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
                <tr>
                    <td align="left" valign="top">
                        <div style="font-size:18px; font-family: 'Montserrat', sans-serif;">
                            <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                        </div>
                    </td>
                    <td align="right" valign="top">
                        <?php    if (!empty($logoBase64)): ?>
                        <img src="<?= $logoBase64 ?>" style="max-width: 160px; height: auto;">
                        <?php    endif; ?>

                    </td>
                </tr>
            </table>

            <!-- ================= TITLE ================= -->
            <div
                style="font-size:45px; font-weight:700; margin-bottom:4px; font-family: 'Montserrat', sans-serif; padding-left:16px; line-height:1.1;">
                <?= esc($genTitle) ?>
            </div>
            <div style="font-size:22px; margin-bottom:2px; font-family: 'Montserrat', sans-serif;">
                <?= date('F Y') ?>
            </div>
            <div style="font-size:20px; margin-bottom:12px; font-family: 'Montserrat', sans-serif;">
                <?= esc($genSubTitle) ?>
            </div>

            <!-- ================= BAR CHART ================= -->
            <?php
            // --------- Generation (Jan–Dec) from inputs ---------
            
            $systemCapacity = max(0.0, (float) $quantity);
            
            // Initialize
            $avgUnitsPerKw = 0.0; // kWh / kW / day
            $pr = 0.0; // %
            $monsoonDip = 0.0; // %
            
            $monthlyBill = 0.0;
            $unitRate = 0.0;
            
            if (!empty($estdata->generation_data)) {
                $gd = json_decode($estdata->generation_data, true);
            
                if (json_last_error() === JSON_ERROR_NONE && is_array($gd)) {
                    // Raw inputs
                    if (isset($gd['monthly_electricity_bill']) && is_numeric($gd['monthly_electricity_bill'])) {
                        $monthlyBill = (float) $gd['monthly_electricity_bill'];
                    }
            
                    if (isset($gd['unit_rate']) && is_numeric($gd['unit_rate'])) {
                        $unitRate = (float) $gd['unit_rate'];
                    }
            
                    if (isset($gd['avg_units_per_kw']) && is_numeric($gd['avg_units_per_kw'])) {
                        $avgUnitsPerKw = (float) $gd['avg_units_per_kw'];
                    }
            
                    if (isset($gd['pr']) && is_numeric($gd['pr'])) {
                        $pr = (float) $gd['pr'];
                    }
            
                    if (isset($gd['monsoon_dip']) && is_numeric($gd['monsoon_dip'])) {
                        $monsoonDip = (float) $gd['monsoon_dip'];
                    }
            
                    // ---------- Derivation Logic ----------
                    // monthly_units = monthly_bill / unit_rate
                    $monthlyUnits = $monthlyBill > 0 && $unitRate > 0 ? $monthlyBill / $unitRate : 0.0;
            
                    $days = 30.0;
            
                    if ($systemCapacity > 0 && $monthlyUnits > 0) {
                        // Case 1: avg_units_per_kw present, PR missing
                        if ($avgUnitsPerKw > 0 && $pr <= 0) {
                            $pr = ($monthlyUnits / ($systemCapacity * $avgUnitsPerKw * $days)) * 100.0;
                        }
            
                        // Case 2: PR present, avg_units_per_kw missing
                        elseif ($pr > 0 && $avgUnitsPerKw <= 0) {
                            $avgUnitsPerKw = $monthlyUnits / ($systemCapacity * $days * ($pr / 100.0));
                        }
            
                        // Case 3: Both missing → assume PR = 100 ONLY for derivation
                        elseif ($pr <= 0 && $avgUnitsPerKw <= 0) {
                            $pr = 100.0; // derivation-only
                            $avgUnitsPerKw = $monthlyUnits / ($systemCapacity * $days);
                        }
                    }
                }
            }
            
            // ---------- Final normalization ----------
            $avgUnitsPerKw = max(0.0, $avgUnitsPerKw);
            $pr = max(0.0, min(100.0, $pr));
            $monsoonDip = max(0.0, min(100.0, $monsoonDip));
            
            $prDisp = rtrim(rtrim(number_format($pr, 1, '.', ''), '0'), '.'); // ex: 80 / 80.5
            $monsoonDipDisp = rtrim(rtrim(number_format($monsoonDip, 1, '.', ''), '0'), '.'); // ex: 13.8
            $avgUnitsPerKwDisp = rtrim(rtrim(number_format($avgUnitsPerKw, 2, '.', ''), '0'), '.'); // ex: 4.31
            
            // Seasonal adjustments:
            // Feb–May: +12%
            // Jun–Aug: -monsoonDip%
            // Nov–Jan: -6%
            // Sep–Oct: 0%
            $peakBoost = 0.12;
            $winterDip = 0.06;
            $monsoonFactor = max(0, (float) $monsoonDip) / 100.0;
            
            $daysByMonth = [
                'Jan' => 31,
                'Feb' => 28,
                'Mar' => 31,
                'Apr' => 30,
                'May' => 31,
                'Jun' => 30,
                'Jul' => 31,
                'Aug' => 31,
                'Sep' => 30,
                'Oct' => 31,
                'Nov' => 30,
                'Dec' => 31,
            ];
            $seasonFactor = [
                'Jan' => -$winterDip,
                'Feb' => $peakBoost,
                'Mar' => $peakBoost,
                'Apr' => $peakBoost,
                'May' => $peakBoost,
                'Jun' => -$monsoonFactor,
                'Jul' => -$monsoonFactor,
                'Aug' => -$monsoonFactor,
                'Sep' => 0.0,
                'Oct' => 0.0,
                'Nov' => -$winterDip,
                'Dec' => -$winterDip,
            ];
            
            // Monthly generation (kWh):
            // - primary: after seasonal adjustment (before PR)
            // - secondary: after PR (final usable output)
            $monthlyData = [];
            foreach ($daysByMonth as $m => $days) {
                $dailyGen = $systemCapacity * (float) $avgUnitsPerKw;
                $baseMonth = $dailyGen * (int) $days;
                $adj = $baseMonth * (1.0 + (float) ($seasonFactor[$m] ?? 0.0));
                $finalKwh = $adj * ((float) $pr / 100.0);
                $monthlyData[] = [
                    'month' => $m,
                    'primary' => round(max(0, $adj)),
                    'secondary' => round(max(0, $finalKwh)),
                ];
            }
            
            // Dompdf is sensitive to total vertical height; increase chart height a bit to reduce blank space,
            // while still fitting chart + summary on a single page.
            $chartHeight = 650;
            // Use the dynamically calculated maxChartValue, or calculate from data if available
            $maxPrimaryFromData = !empty($monthlyData) ? max(array_column($monthlyData, 'primary')) : 0;
            $maxSecondaryFromData = !empty($monthlyData) ? max(array_column($monthlyData, 'secondary')) : 0;
            $maxChartValue = (float) max($maxPrimaryFromData, $maxSecondaryFromData);
            // Dynamic Y-axis labels (no hardcoded 2000/1600/...)
            // Build a "nice" scale with 6 ticks (max..0) based on actual data.
            $ticks = 6; // number of labels on Y-axis
            
            $maxFromData = max((float) $maxPrimaryFromData, (float) $maxSecondaryFromData, 0);
            
            // Choose a "nice" step (1/2/5 × 10^n)
            $rawMax = max(1, $maxFromData);
            $rawStep = $rawMax / ($ticks - 1);
            $pow10 = pow(10, floor(log10($rawStep)));
            $norm = $rawStep / $pow10;
            if ($norm <= 1) {
                $niceNorm = 1;
            } elseif ($norm <= 2) {
                $niceNorm = 2;
            } elseif ($norm <= 5) {
                $niceNorm = 5;
            } else {
                $niceNorm = 10;
            }
            $step = $niceNorm * $pow10;
            $niceMax = $step * ($ticks - 1);
            
            // If everything is 0, keep a small but readable axis
            if ($maxFromData <= 0) {
                $step = 1;
                $niceMax = 5;
            }
            
            $yAxisLabels = [];
            for ($i = 0; $i < $ticks; $i++) {
                $yAxisLabels[] = (int) round($niceMax - $step * $i);
            }
            
            // Use the axis max for scaling bars
            $maxChartValue = (float) ($yAxisLabels[0] ?? $niceMax);
            
            // Fix: row height must add up to chartHeight (previously it exceeded and pushed content to next page)
            $rowHeight = $chartHeight / count($yAxisLabels);
            ?>

            <table width="100%" cellpadding="0" cellspacing="0"
                style="margin-bottom:18px; border-bottom:1px solid #ccc; page-break-inside: avoid;">
                <tr>

                    <!-- LEFT Y AXIS -->
                    <td width="45" valign="top">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <?php    foreach ($yAxisLabels as $label): ?>
                            <tr>
                                <td
                                    style="height:<?= $rowHeight ?>px;
                                            font-size:10px; color:#666;
                                            text-align:right; padding-right:6px;
                                            border-bottom:1px solid #eee;
                                            font-family: 'Montserrat', sans-serif;">
                                    <?= $label ?>
                                </td>
                            </tr>
                            <?php    endforeach; ?>
                        </table>
                    </td>

                    <!-- BARS -->
                    <td valign="bottom">
                        <table width="100%" height="<?= $chartHeight ?>" cellpadding="0" cellspacing="0">
                            <tr>
                                <?php 
                                // Ensure all 12 months are displayed
    $allMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $monthlyDataMap = [];
    foreach ($monthlyData as $data) {
        $monthlyDataMap[$data['month']] = $data;
    }

    // Build complete monthly data array with all 12 months
    $completeMonthlyData = [];
    foreach ($allMonths as $month) {
        if (isset($monthlyDataMap[$month])) {
            $completeMonthlyData[] = $monthlyDataMap[$month];
        } else {
            // Add month with zero values if missing
            $completeMonthlyData[] = [
                'month' => $month,
                'primary' => 0,
                'secondary' => 0
            ];
        }
    }

    foreach ($completeMonthlyData as $data):
        // Prevent division by zero
        $maxValue = $maxChartValue > 0 ? $maxChartValue : 1;
        $primaryValue = max(0, (float) ($data['primary'] ?? 0));
        $secondaryValue = max(0, (float) ($data['secondary'] ?? 0));

        $primaryHeight = ($primaryValue / $maxValue) * $chartHeight;
        $secondaryHeight = ($secondaryValue / $maxValue) * $chartHeight;

        if ($primaryHeight < 1 && $primaryValue > 0)
            $primaryHeight = 1;
        if ($secondaryHeight < 1 && $secondaryValue > 0)
            $secondaryHeight = 1;

        $mLabel = (string) ($data['month'] ?? '');
        $mLabel = strtoupper(substr($mLabel, 0, 3));
                                ?>
                                <td width="8.33%" valign="bottom" align="center">
                                    <table width="100%" height="<?= $chartHeight ?>" cellpadding="0"
                                        cellspacing="0">
                                        <tr>
                                            <td valign="bottom">
                                                <table width="100%" cellpadding="0" cellspacing="0">
                                                    <tr>
                                                        <!-- Green (after PR) -->
                                                        <td width="40%" valign="bottom">
                                                            <div
                                                                style="height:<?= $secondaryHeight ?>px;
                                                                        background:#00c389;
                                                                        border-radius:8px 8px 0 0;">
                                                            </div>
                                                        </td>
                                                        <td width="20%"></td>
                                                        <!-- Black (before PR) -->
                                                        <td width="40%" valign="bottom">
                                                            <div
                                                                style="height:<?= $primaryHeight ?>px;
                                                                        background:#000;
                                                                        border-radius:8px 8px 0 0;">
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                    <div style="margin-top:6px; height:26px; font-family:'Montserrat', sans-serif;">
                                        <span
                                            style="display:inline-block; font-size:7px; line-height:1; color:#000;
                                                     transform: rotate(-90deg); transform-origin:center;">
                                            <?= $mLabel ?>
                                        </span>
                                    </div>
                                </td>
                                <?php    endforeach; ?>
                            </tr>
                        </table>
                    </td>

                    <!-- RIGHT LABEL -->
                    <td width="60" valign="middle" align="center"
                        style="font-size:11px; color:#666;   font-family: 'Montserrat', sans-serif;">
                        units produced per month
                    </td>

                </tr>
            </table>

            <!-- ================= SUMMARY ================= -->
            <table width="100%" cellpadding="0" cellspacing="0"
                style="margin-bottom:18px; page-break-inside: avoid;">
                <tr>
                    <td width="33.33%" align="center">
                        <div style="font-size:22px; font-weight:600; font-family: 'Montserrat', sans-serif;">80%</div>
                        <div style="font-size:18px; font-family: 'Montserrat', sans-serif;">PR: Performance Ratio</div>
                    </td>
                    <td width="33.33%" align="center">
                        <div style="font-size:22px; font-weight:600; font-family: 'Montserrat', sans-serif;">13.8%
                        </div>
                        <div style="font-size:18px; font-family: 'Montserrat', sans-serif;">Monsoon Dip</div>
                    </td>
                    <td width="33.33%" align="center">
                        <div style="font-size:22px; font-weight:600; font-family: 'Montserrat', sans-serif;">4.31</div>
                        <div style="font-size:18px; font-family: 'Montserrat', sans-serif;">Average units per<br>kW/Day
                        </div>
                    </td>
                </tr>
            </table>

            <!-- ================= DISCLAIMER ================= -->
            <div style="font-size:15px; text-align:center; font-family: 'Montserrat', sans-serif;">
                <?= esc($genNote) ?>
            </div>
            <?php if (($estdata->type ?? '') === 'residential'): ?>
            <?php
            $systemKwp = $quantity;
            $dailyUnits = $quantity * 4.2;
            $monthlyUnits = $dailyUnits * 30;
            $annualUnits = $dailyUnits * 365;
            
            $dailyUnitsFormatted = number_format($dailyUnits, 1);
            $monthlyUnitsFormatted = number_format($monthlyUnits, 0);
            $annualUnitsFormatted = number_format($annualUnits, 0);
            ?>
            <!-- Section 6: Generation Calculations -->
            <div
                style="font-size: 20px; font-weight: bold; margin-top: 25px; margin-bottom: 10px; padding-left:10px; text-align: left;">
                6. Generation Calculations
            </div>
            <p style="font-size: 13.5px; margin-bottom: 15px; line-height: 1.5; text-align: left;">Projected
                estimations are generated based on regional irradiance data for a premium proposed
                <strong><?= $systemKwp ?> kWp</strong> system:</p>
            <ul
                style="padding-left: 20px; font-size: 13.5px; line-height: 1.5; margin-bottom: 15px; text-align: left;">
                <li style="margin-bottom: 8px;"><strong>Daily Average Generation:</strong> ~4 to 4.5 kWh (Units) per
                    kWp installed &rarr; <strong><?= $dailyUnitsFormatted ?> Units/day</strong></li>
                <li style="margin-bottom: 8px;"><strong>Estimated Monthly Generation:</strong>
                    <strong><?= $monthlyUnitsFormatted ?> Units/month</strong></li>
                <li style="margin-bottom: 8px;"><strong>Projected Annual Generation:</strong>
                    <strong><?= $annualUnitsFormatted ?> Units/year</strong></li>
            </ul>
            <?php endif; ?>
        </div>
        <!-- ================= FOOTER ================= -->
        <table width="100%" cellpadding="0" cellspacing="0"
            style="position:fixed; bottom:10; left:0; right:0;
                        background:#fff; color:#4b9349; height:40px; border-top: 1px solid #4b9349;">
            <tr>
                <td width="33.33%" style="padding:10px;">
                    <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                </td>

                <td width="33.33%" align="center" style="padding:10px;">
                    PAGE <?= $_pdfPageNumber() ?>
                </td>

                <td width="33.33%" align="right" style="padding:10px; white-space:nowrap;">
                    <?php
                    $companyName = esc($globalCompanyName);
                    $companyParts = explode(' ', $companyName);
                    $mainName = $companyParts[0] ?? $companyName;
                    ?>
                    Generated by <?= esc($companyName) ?>
                </td>
            </tr>
        </table>

    </div>
    <!-- ================= END PAGE 3 ================= -->
    <?php endif; ?>

    <?php
    $__ongridRoiSection = isset($ongridRoiSection) && is_array($ongridRoiSection) ? $ongridRoiSection : [];
    $__ongridRoiActive = $_isActive($__ongridRoiSection);
    
    $roiTitle = trim((string) ($__ongridRoiSection['title'] ?? ''));
    $roiTitle = $roiTitle !== '' ? $roiTitle : 'ROI';
    
    $roiSubTitle = trim((string) ($__ongridRoiSection['sub_title'] ?? ''));
    $roiSubTitle = $roiSubTitle !== '' ? $roiSubTitle : 'Ongrid ROI';
    
    $roiStarts = $__ongridRoiSection['residential_starts_percent'] ?? '';
    $roiStarts = is_numeric($roiStarts) && (float) $roiStarts > 0 ? rtrim(rtrim(number_format((float) $roiStarts, 2, '.', ''), '0'), '.') : '';
    
    $roiNote = trim((string) ($__ongridRoiSection['note'] ?? ''));
    $roiNote = $roiNote !== '' ? $roiNote : 'SOLAR IS ONE OF THE BEST INVESTMENT YOU WILL EVER MAKE';
    ?>
    <?php if ($__ongridRoiActive): ?>
    <!-- ================= PAGE 4 : ROI ================= -->
    <div class="<?= $_pageClass('p4') ?>"
        style="position:relative; min-height:842px;
                background:#4b9349 !important;  /* image-like green */
                font-family:'Montserrat', sans-serif;">
        <div style="padding: 50px;">
            <!-- ================= HEADER ================= -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:30px;">
                <tr>
                    <td align="left" valign="top">
                        <div
                            style="font-size:18px; color:#e8f6f4; margin-bottom:14px; font-family: 'Montserrat', sans-serif;">
                            <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                        </div>
                        <div
                            style="font-size:45px; font-weight:700; color:#fff; margin-bottom:8px; font-family: 'Montserrat', sans-serif; border-left:7px solid #fff; padding-left:16px; line-height:1.1;">
                            <?= $quantity ?>KW <?= esc($roiTitle) ?>
                        </div>
                        <div style="font-size:18px; color:#e0f2ef; font-family: 'Montserrat', sans-serif;">
                            <?= esc($roiSubTitle) ?>
                        </div>

                    </td>
                </tr>
            </table>

            <!-- ================= ROI BAR CHART ================= -->
            <?php
            // ROI chart:
            // - If simple ROI inputs are present -> show Year 1..25
            // - Else -> keep legacy "last 10 calendar years" behavior
            $roiYears = $useSimpleRoi ? 25 : 10;
            
            // Chart height (you changed this): keep as-is, but ensure it’s numeric
            // $chartHeight = (int) ($chartHeight ?? 360);
            $chartHeight = 550;
            if ($chartHeight <= 0) {
                $chartHeight = 360;
            }
            
            $roiDataSafe = is_array($roiData ?? null) ? $roiData : [];
            $completeRoiData = [];
            
            if ($useSimpleRoi) {
                // Already prepared as Year 1..25 in the calculation block above
                $completeRoiData = $roiDataSafe;
            } else {
                $roiMap = [];
                foreach ($roiDataSafe as $d) {
                    $y = (int) ($d['year'] ?? 0); // calendar year
                    if ($y > 0) {
                        $roiMap[$y] = $d;
                    }
                }
                $currentYearRoi = (int) date('Y');
                $startYearRoi = $currentYearRoi - ($roiYears - 1);
                for ($y = $startYearRoi; $y <= $currentYearRoi; $y++) {
                    if (isset($roiMap[$y])) {
                        $completeRoiData[] = $roiMap[$y];
                    } else {
                        $completeRoiData[] = [
                            'year' => $y,
                            'cumulative' => 0,
                        ];
                    }
                }
            }
            
            // Dynamic Y-axis (Lakhs) + dynamic scaling
            // Build a "nice" scale with 7 ticks (max..0) based on actual ROI data.
            $ticks = 7;
            
            $roiMaxFromData = 0;
            foreach ($completeRoiData as $d) {
                $roiMaxFromData = max($roiMaxFromData, (float) ($d['cumulative'] ?? 0));
            }
            
            $maxFromData = max($roiMaxFromData, (float) ($maxChartRoi ?? 0), 0);
            
            // Choose a "nice" step (1/2/5 × 10^n)
            $rawMax = max(1, $maxFromData);
            $rawStep = $rawMax / ($ticks - 1);
            $pow10 = pow(10, floor(log10($rawStep)));
            $norm = $rawStep / $pow10;
            if ($norm <= 1) {
                $niceNorm = 1;
            } elseif ($norm <= 2) {
                $niceNorm = 2;
            } elseif ($norm <= 5) {
                $niceNorm = 5;
            } else {
                $niceNorm = 10;
            }
            $step = $niceNorm * $pow10;
            $niceMax = $step * ($ticks - 1);
            
            // If everything is 0, fall back to estimates-table min/max instead of fixed 30L.
            // (If estimates are also empty, then use the old 30L default.)
            
            if ($maxFromData <= 0) {
                $fallbackMax = (float) ($estimateAmountMax ?? 0);
                if ($fallbackMax > 0) {
                    $rawMax = max(1, $fallbackMax);
                    $rawStep = $rawMax / ($ticks - 1);
                    $pow10 = pow(10, floor(log10($rawStep)));
                    $norm = $rawStep / $pow10;
                    if ($norm <= 1) {
                        $niceNorm = 1;
                    } elseif ($norm <= 2) {
                        $niceNorm = 2;
                    } elseif ($norm <= 5) {
                        $niceNorm = 5;
                    } else {
                        $niceNorm = 10;
                    }
                    $step = $niceNorm * $pow10;
                    $niceMax = $step * ($ticks - 1);
                } else {
                    $step = 500000; // 5L
                    $niceMax = 3000000; // 30L
                }
            }
            
            $yAxis = [];
            for ($i = 0; $i < $ticks; $i++) {
                $val = $niceMax - $step * $i;
                if ($val <= 0) {
                    $yAxis[] = $rupeeHtml . '0';
                    continue;
                }
                $yAxis[] = $rupeeHtml . number_format($val / 100000, 1) . 'L';
            }
            
            // Use axis max for bar scaling
            $maxValue = (float) $niceMax;
            
            // Keep axis labels within chart height (Dompdf can overflow otherwise)
            $rowHeight = $chartHeight / count($yAxis);
            ?>

            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:40px;">
                <tr>

                    <!-- CHART -->
                    <td valign="bottom">
                        <table width="100%" height="<?= $chartHeight ?>" cellpadding="0" cellspacing="0">
                            <tr>
                                <?php    foreach ($completeRoiData as $data):
        $cumulative = (float) ($data['cumulative'] ?? 0);
        if ($cumulative < 0) {
            $cumulative = 0;
        }
        $height = 0;
        if ($maxValue > 0 && $cumulative > 0) {
            $height = ($cumulative / $maxValue) * $chartHeight;
            // Minimum height only when there is real data
            if ($height > 0 && $height < 6) {
                $height = 6;
            }
        }
                            ?>
                                <td width="<?= round(100 / max(count($completeRoiData), 1), 2) ?>%" align="center"
                                    valign="bottom">
                                    <div
                                        style="height:<?= $height ?>px;
                                            width:20px;
                                            margin:0 auto;
                                            background:#ffffff;
                                            border-radius:10px 10px 0 0;">
                                    </div>
                                    <!-- Dompdf doesn't reliably support writing-mode; keep labels horizontal & compact -->
                                    <div
                                        style="font-size:7px; color:#e8f6f4; margin-top:4px; line-height:1; white-space:nowrap; font-family: 'Montserrat', sans-serif;">
                                        <?php        if ($useSimpleRoi): ?>
                                        Y<br><?= (int) ($data['year'] ?? 0) ?>
                                        <?php        else: ?>
                                        <?= (int) ($data['year'] ?? 0) ?>
                                        <?php        endif; ?>
                                    </div>
                                </td>
                                <?php    endforeach; ?>
                            </tr>
                        </table>
                    </td>

                    <!-- RIGHT Y AXIS -->
                    <td width="65" valign="top">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <?php    foreach ($yAxis as $label): ?>
                            <tr>
                                <td
                                    style="height:<?= $rowHeight ?>px;
                                        font-size:10px;
                                        color:#e8f6f4;
                                        text-align:right;
                                        padding-right:6px;
                                        border-bottom:1px solid rgba(255,255,255,0.15);
                                        font-family: 'Montserrat', sans-serif;">
                                    <?= $label ?>
                                </td>
                            </tr>
                            <?php    endforeach; ?>
                        </table>
                    </td>

                </tr>
            </table>

            <!-- ================= SUMMARY BOXES (like screenshot) ================= -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:22px;">
                <tr>
                    <td width="33.33%" align="center">
                        <table width="92%" cellpadding="0" cellspacing="0"
                            style="border:1px solid rgba(255,255,255,0.6);">
                            <tr>
                                <td align="center" style="padding:16px 10px;">
                                    <div
                                        style="font-size:20px; font-weight:700; color:#fff; font-family: 'Montserrat', sans-serif;">
                                        <?= $rupeeHtml . $yearlySavingsFormatted ?>
                                    </div>
                                    <div
                                        style="font-size:11px; font-weight:600; color:#e8f6f4; margin-top:6px; letter-spacing:0.5px; font-family: 'Montserrat', sans-serif;">
                                        YEARLY SAVINGS
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td width="33.33%" align="center">
                        <table width="92%" cellpadding="0" cellspacing="0"
                            style="border:1px solid rgba(255,255,255,0.6);">
                            <tr>
                                <td align="center" style="padding:16px 10px;">
                                    <div
                                        style="font-size:20px; font-weight:700; color:#fff; font-family: 'Montserrat', sans-serif;">
                                        <?= esc($paybackPeriodDisplay) ?> Years
                                    </div>
                                    <div
                                        style="font-size:11px; font-weight:600; color:#e8f6f4; margin-top:6px; letter-spacing:0.5px; font-family: 'Montserrat', sans-serif;">
                                        PAYBACK PERIOD
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td width="33.33%" align="center">
                        <table width="92%" cellpadding="0" cellspacing="0"
                            style="border:1px solid rgba(255,255,255,0.6);">
                            <tr>
                                <td align="center" style="padding:16px 10px;">
                                    <div
                                        style="font-size:20px; font-weight:700; color:#fff; font-family: 'Montserrat', sans-serif;">
                                        <?= $rupeeHtml . $totalLifetimeSavingsLakhsFormatted ?>
                                    </div>
                                    <div
                                        style="font-size:11px; font-weight:600; color:#e8f6f4; margin-top:6px; letter-spacing:0.5px; font-family: 'Montserrat', sans-serif;">
                                        TOTAL LIFETIME SAVING
                                    </div>
                                    <?php //if ($useSimpleRoi):
                                    ?>
                                    <!-- <div style="font-size:9px; color:#e8f6f4; margin-top:6px; font-family:'Montserrat', sans-serif;">
                                        Net Profit: <?= $rupeeHtml . $netLifetimeProfitLakhsFormatted ?>
                                    </div> -->
                                    <?php //endif;
                                    ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <!-- ================= FINANCING ================= -->
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td align="center">
                        <div
                            style="font-size:16px; color:#e8f6f4; margin-bottom:10px; font-family: 'Montserrat', sans-serif;">
                            <?= esc($roiNote) ?>
                        </div>
                        <?php if ($roiStarts !== ''): ?>
                        <div
                            style="background:#fff; color:#4b9349;
                                padding:10px 30px;
                                display:inline-block;                                font-size:13px;
                                font-size:18px;
                                font-family: 'Montserrat', sans-serif;">
                            Residential starts at <?= esc($roiStarts) ?> % only
                        </div>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
        <?php if (($estdata->type ?? '') === 'residential'): ?>
        <?php
        $monthlySavingsFormatted = number_format($yearlySavings / 12, 0);
        $totalCo2Offset = $systemCapacity * 1.01 * 25;
        $coalAvoided = $systemCapacity * 1.259 * 25;
        $treesPlanted = (int) round($totalCo2Offset * 2.0);
        $co2OffsetFormatted = number_format($totalCo2Offset, 1);
        $coalAvoidedFormatted = number_format($coalAvoided, 1);
        ?>
        <div
            style="position:fixed; bottom:10; left:0; right:0;
                        background:#4b9349; color:#fff; height:40px; border-top: 1px solid #fff;">
            <table width="100%" height="36" cellpadding="0" cellspacing="0"
                style="font-size:11px; color:#fff;">
                <tr>
                    <td width="33.33%" style="padding:10px; font-family: 'Montserrat', sans-serif; font-size:16px;">
                        <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                    </td>

                    <td width="33.33%" align="center"
                        style="padding:10px; font-family: 'Montserrat', sans-serif; font-size:16px;">
                        PAGE <?= $_pdfPageNumber() ?>
                    </td>

                    <td width="33.33%" align="right"
                        style="padding:10px; font-family: 'Montserrat', sans-serif; font-size:16px; white-space:nowrap;">
                        Generated by <?= esc($globalCompanyName) ?>
                    </td>
                </tr>
            </table>
        </div>
    </div> <!-- Close Page 4 green page div -->

    <!-- ================= PAGE 4b : ROI & CARBON OFFSET ================= -->
    <div class="page page-break"
        style="position: relative; min-height: 842px; background: white; font-family:'Montserrat', sans-serif;">
        <div style="padding: 40px;">
            <!-- Header -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 30px;">
                <tr>
                    <td width="50%" align="left" valign="top">
                        <div style="font-size: 18px; color: #333;">
                            <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                        </div>
                    </td>
                    <td width="50%" align="right" valign="top">
                        <?php if (!empty($logoBase64)): ?>
                        <img src="<?= $logoBase64 ?>" alt="Company Logo" style="max-width: 160px; height: auto;">
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <!-- Section 7: Return on Savings (ROI) -->
            <div style="font-size: 26px; font-weight: bold; margin-bottom: 12px; padding-left:14px;">
                7. Return on Savings (ROI)
            </div>
            <p style="font-size: 13.5px; margin-bottom: 15px; line-height: 1.5;">Financial projections calculated based
                on a baseline localized utility utility tariff of <strong><?= $rupeeHtml . number_format($unitRate, 2) ?></strong> per unit:</p>
            <table class="info-table"
                style="width: 100%; border-collapse: collapse; margin-bottom: 25px; font-size: 13px;">
                <thead>
                    <tr style="background-color: #4b9349; color: #fff;">
                        <th style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Financial Parameter</th>
                        <th style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Projected Value</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 8px; border: 1px solid #ddd;">Estimated Monthly Savings Target</td>
                        <td style="padding: 8px; border: 1px solid #ddd;"><?= $rupeeHtml ?>
                            <?= $monthlySavingsFormatted ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border: 1px solid #ddd;">Projected First-Year Annual Savings</td>
                        <td style="padding: 8px; border: 1px solid #ddd;"><?= $rupeeHtml ?>
                            <?= $yearlySavingsFormatted ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border: 1px solid #ddd;">Cumulative 25-Year System Lifecycle Savings
                        </td>
                        <td style="padding: 8px; border: 1px solid #ddd;"><?= $rupeeHtml ?>
                            <?= $totalLifetimeSavingsFormatted ?></td>
                    </tr>
                    <tr style="background-color: #f9f9f9; font-weight: bold;">
                        <td style="padding: 8px; border: 1px solid #ddd; color: #1e3f20;">Calculated System Payback
                            Period (Break-Even)</td>
                        <td style="padding: 8px; border: 1px solid #ddd; color: #1e3f20;"><?= $paybackPeriodDisplay ?>
                            Years</td>
                    </tr>
                </tbody>
            </table>

            <!-- Section 8: Carbon Emission Offset Calculation -->
            <div style="font-size: 26px; font-weight: bold; margin-bottom: 12px; padding-left:14px; margin-top: 20px;">
                8. Carbon Emission Offset Calculation
            </div>
            <p style="font-size: 13.5px; margin-bottom: 15px; line-height: 1.5;">By shifting power generation source to
                solar PV, your property achieves highly significant environmental offset targets across its guaranteed
                25-year operational lifecycle:</p>
            <ul style="padding-left: 20px; font-size: 13.5px; line-height: 1.5; margin-bottom: 25px;">
                <li style="margin-bottom: 8px;"><strong>CO₂ Emissions Prevented:</strong>
                    <strong><?= $co2OffsetFormatted ?> Metric Tons</strong> of pure Carbon Dioxide stopped from entering
                    the atmosphere.</li>
                <li style="margin-bottom: 8px;"><strong>Fossil Fuel Preservation:</strong> Equivalent to preventing the
                    burning of <strong><?= $coalAvoidedFormatted ?> Tons</strong> of standard coal.</li>
                <li style="margin-bottom: 8px;"><strong>Reforestation Equivalence:</strong> Equal to the ecological
                    impact of planting <strong><?= $treesPlanted ?> mature trees</strong>.</li>
            </ul>

            <!-- Section 9: Documents Required -->
            <div style="font-size: 26px; font-weight: bold; margin-bottom: 12px; padding-left:14px; margin-top: 20px;">
                9. Documents Required
            </div>
            <p style="font-size: 13px; margin-bottom: 12px; line-height: 1.4;">To initiate file processing for DISCOM
                permissions and central subsidy approvals, please provide:</p>
            <ul style="padding-left: 20px; font-size: 12.5px; line-height: 1.4; margin-bottom: 15px;">
                <li style="margin-bottom: 6px;">Latest Official Electricity Utility Bill (All pages included)</li>
                <li style="margin-bottom: 6px;">Aadhaar Card of the property owner (Name alignment must match utility
                    billing details)</li>
                <li style="margin-bottom: 6px;">PAN Card Copy</li>
                <li style="margin-bottom: 6px;">Bank Cancelled Cheque or clear Passbook photocopy (Required for direct
                    electronic subsidy disbursement)</li>
                <li style="margin-bottom: 6px;">Two recent passport-size color photographs</li>
            </ul>
        </div>

        <!-- Footer -->
        <table width="100%" cellpadding="0" cellspacing="0"
            style="position:fixed; bottom:10; left:0; right:0; background:#fff; color:#4b9349; height:40px; border-top: 1px solid #4b9349;">
            <tr>
                <td width="33.33%" style="padding:10px; font-family: 'Montserrat', sans-serif;">
                    <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                </td>
                <td width="33.33%" align="center" style="padding:10px; font-family: 'Montserrat', sans-serif;">
                    PAGE <?= $_pdfPageNumber() ?>
                </td>
                <td width="33.33%" align="right"
                    style="padding:10px; font-family: 'Montserrat', sans-serif; white-space:nowrap;">
                    Generated by <?= esc($globalCompanyName) ?>
                </td>
            </tr>
        </table>
    </div>
    <?php else: ?>
    <div
        style="position:fixed; bottom:10; left:0; right:0;
                        background:#4b9349; color:#fff; height:40px; border-top: 1px solid #fff;">
        <table width="100%" height="36" cellpadding="0" cellspacing="0" style="font-size:11px; color:#fff;">
            <tr>
                <td width="33.33%" style="padding:10px; font-family: 'Montserrat', sans-serif; font-size:16px;">
                    <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                </td>

                <td width="33.33%" align="center"
                    style="padding:10px; font-family: 'Montserrat', sans-serif; font-size:16px;">
                    PAGE <?= $_pdfPageNumber() ?>
                </td>

                <td width="33.33%" align="right"
                    style="padding:10px; font-family: 'Montserrat', sans-serif; font-size:16px; white-space:nowrap;">
                    Generated by <?= esc($globalCompanyName) ?>
                </td>
            </tr>
        </table>
    </div>
    </div> <!-- Close Page 4 -->
    <?php endif; ?>
    </div>
    <!-- ================= END PAGE 4 ================= -->
    <?php endif; ?>
    <!-- ================= PAGE 5 : TIMELINE & SYSTEM SPECIFICATION ================= -->
    <?php
    $__timeLine = isset($timeLine) && is_array($timeLine) ? $timeLine : [];
    $__timeLineActive = $_isActive($__timeLine);
    ?>
    <?php if ($__timeLineActive): ?>
    <div class="<?= $_pageClass('p5') ?>"
        style="position: relative;
            min-height: 842px;
            background: #ffffff;
            font-family: 'Montserrat', sans-serif;">
        <div style="padding: 38px 42px 30px;">
            <!-- ================= HEADER ================= -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 14px;">
                <tr>
                    <td width="50%" align="left" valign="top">
                        <div style="font-size:14px;font-family: 'Montserrat', sans-serif;">
                            <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                        </div>
                    </td>

                    <td width="50%" align="right" valign="top">
                        <?php    if (!empty($logoBase64)): ?>
                        <img src="<?= $logoBase64 ?>" style="max-width:160px; margin-bottom:5px;">
                        <?php    endif; ?>
                    </td>
                </tr>
            </table>

            <!-- Page main heading -->
            <div
                style="font-size:32px; font-weight:bold; font-family:'Montserrat',sans-serif; border-left:7px solid #4b9349; padding-left:16px; line-height:1.1; margin-bottom:14px;">
                OFFER &amp; TERMS
            </div>

            <?php
            // Template-specific timeline (saved in pdfbuilder_forms.time_line JSON)
            $timeLine = isset($timeLine) && is_array($timeLine) ? $timeLine : [];
            $timelineMainTitle = trim((string) ($timeLine['main_title'] ?? ''));
            $timelineTitle = trim((string) ($timeLine['title'] ?? ''));
            $timelineTitle2 = trim((string) ($timeLine['title2'] ?? ''));
            $timelineNote = trim((string) ($timeLine['note'] ?? ''));
            
            $timelineImg1 = !empty($timeLine['image1']) ? normalize_pdf_image($timeLine['image1']) : null;
            $timelineImg2 = !empty($timeLine['image2']) ? normalize_pdf_image($timeLine['image2']) : null;
            
            $timelineImg1 = is_string($timelineImg1) && strpos($timelineImg1, 'data:image') === 0 ? $timelineImg1 : normalize_pdf_image('public/assets/img/page-5-1.png');
            $timelineImg2 = is_string($timelineImg2) && strpos($timelineImg2, 'data:image') === 0 ? $timelineImg2 : normalize_pdf_image('public/assets/img/page-5-2.png');
            ?>
            <!-- ================= OFFER & TERMS (timeline) ================= -->
            <div>
                <div
                    style="font-size:30px; font-weight:bold; color:#000; margin-bottom:6px; font-family: 'Montserrat', sans-serif; color: #4b9349; padding-left:16px; line-height:1.1;">
                    <?= $timelineMainTitle !== '' ? esc($timelineMainTitle) : 'TIMELINE' ?>
                </div>
                <div
                    style="font-size:17px; font-weight:bold; margin-bottom:12px; font-family: 'Montserrat', sans-serif; padding-left:16px; line-height:1.2;">
                    <span
                        style="display:inline-block; width:10px; height:10px; background-color:#4b9349; border-radius:50%; margin-right:10px;"></span><?= $timelineTitle !== '' ? esc($timelineTitle) : 'TIMELINE AND MILESTONES' ?>
                </div>

                <?php if (!empty($timelineImg1)): ?>
                <img src="<?= $timelineImg1 ?>"
                    style="width:100%; max-width:590px; max-height:370px; display:block; margin:0 auto 12px;">
                <?php else: ?>
                <table width="100%" cellpadding="0" cellspacing="0"
                    style="margin:0 auto 12px; border-collapse:collapse;">
                    <tr>
                        <td width="25%" align="center" style="padding:10px;">
                            <div
                                style="border:3px solid #4b9349; color:#4b9349; border-radius:50%; width:55px; height:55px; line-height:55px; font-size:24px; font-weight:bold; margin:0 auto 8px;">
                                1</div>
                            <div style="font-size:15px; font-weight:bold;">10%</div>
                            <div style="font-size:14px;">Advance</div>
                        </td>
                        <td width="25%" align="center" style="padding:10px;">
                            <div
                                style="border:3px solid #000; color:#000; border-radius:50%; width:55px; height:55px; line-height:55px; font-size:24px; font-weight:bold; margin:0 auto 8px;">
                                2</div>
                            <div style="font-size:15px; font-weight:bold;">60%</div>
                            <div style="font-size:14px;">Procurement</div>
                        </td>
                        <td width="25%" align="center" style="padding:10px;">
                            <div
                                style="border:3px solid #4b9349; color:#4b9349; border-radius:50%; width:55px; height:55px; line-height:55px; font-size:24px; font-weight:bold; margin:0 auto 8px;">
                                3</div>
                            <div style="font-size:15px; font-weight:bold;">20%</div>
                            <div style="font-size:14px;">Installation</div>
                        </td>
                        <td width="25%" align="center" style="padding:10px;">
                            <div
                                style="border:3px solid #000; color:#000; border-radius:50%; width:55px; height:55px; line-height:55px; font-size:24px; font-weight:bold; margin:0 auto 8px;">
                                4</div>
                            <div style="font-size:15px; font-weight:bold;">10%</div>
                            <div style="font-size:14px;">Net metering</div>
                        </td>
                    </tr>
                </table>
                <?php endif; ?>

                <!-- ================= SYSTEM SPECIFICATION ================= -->
                <div
                    style="font-size:17px; font-weight:bold; margin-bottom:10px; font-family: 'Montserrat', sans-serif; padding-left:16px; line-height:1.2;">
                    <span
                        style="display:inline-block; width:10px; height:10px; background-color:#4b9349; border-radius:50%; margin-right:10px;"></span><?= $timelineTitle2 !== '' ? esc($timelineTitle2) : 'SYSTEM SPECIFICATION' ?>
                </div>

                <?php if (!empty($timelineImg2)): ?>
                <img src="<?= $timelineImg2 ?>"
                    style="width:100%; max-width:590px; max-height:370px; display:block; margin:0 auto 10px;">
                <?php else: ?>
                <table width="100%" cellpadding="0" cellspacing="0"
                    style="border-collapse:collapse; margin:0 auto 10px;">
                    <tr>
                        <td
                            style="background:#4b9349; color:#fff; padding:9px; border:1px solid #333; font-weight:bold;">
                            System Specification</td>
                        <td
                            style="background:#4b9349; color:#fff; padding:9px; border:1px solid #333; font-weight:bold;">
                            Details</td>
                    </tr>
                    <tr>
                        <td style="padding:9px; border:1px solid #333; font-weight:bold;">System Capacity</td>
                        <td style="padding:9px; border:1px solid #333;"><?= esc($quantity) ?> kW</td>
                    </tr>
                    <tr>
                        <td style="padding:9px; border:1px solid #333; font-weight:bold;">System Type</td>
                        <td style="padding:9px; border:1px solid #333;">Ongrid</td>
                    </tr>
                    <tr>
                        <td style="padding:9px; border:1px solid #333; font-weight:bold;">Application</td>
                        <td style="padding:9px; border:1px solid #333;">
                            <?= esc(ucfirst((string) ($estdata->type ?? 'Residential'))) ?></td>
                    </tr>
                </table>
                <?php endif; ?>

                <!-- ================= NOTE ================= -->
                <div
                    style="text-align:center; font-size:13px; line-height:1.45; font-family: 'Montserrat', sans-serif; margin-top:6px;">
                    <?= $timelineNote !== '' ? esc($timelineNote) : "Net metering is entirely dependent on DISCOM, we don't control that process" ?>
                </div>
            </div>

        </div>
        <!-- ================= FOOTER ================= -->
        <table width="100%" cellpadding="0" cellspacing="0"
            style="position:fixed; bottom:10; left:0; right:0;
                        background:#fff; color:#4b9349; height:40px; border-top: 1px solid #4b9349;">
            <tr>
                <td width="33.33%" style="padding:10px;">
                    <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                </td>

                <td width="33.33%" align="center" style="padding:10px;">
                    PAGE <?= $_pdfPageNumber() ?>
                </td>

                <td width="33.33%" align="right" style="padding:10px; white-space:nowrap;">
                    <?php
                    $companyName = esc($globalCompanyName);
                    $companyParts = explode(' ', $companyName);
                    $mainName = $companyParts[0] ?? $companyName;
                    ?>
                    Generated by <?= esc($companyName) ?>
                </td>
            </tr>
        </table>

        <!-- </div> -->
    </div>
    <?php endif; ?>
    <!-- ================= FIXED FOOTER (DOMPDF SAFE) ================= -->



    <!-- ================= END PAGE 5 ================= -->
    @endif

    <?php
    $__components = isset($components) && is_array($components) ? $components : [];
    $__componentsActive = empty($invoiceOnly) && $_isActive($__components);
    ?>
    <?php if ($__componentsActive): ?>
    <?php
    $components = isset($components) && is_array($components) ? $components : [];
    $componentsActive = (int) ($components['active'] ?? 1);
    $componentsTitle = trim((string) ($components['title'] ?? ''));
    $componentsDocumentType = strtolower(trim((string) ($estdata->type ?? '')));
    if (in_array($componentsDocumentType, ['residential', 'commercial', 'industrial'], true)) {
        $componentsTitle = ucfirst($componentsDocumentType) . ' Solar System Components';
    }
    $componentsDescRaw = (string) ($components['description'] ?? '');
    $componentsDesc = sanitize_pdf_rich_html($componentsDescRaw);
    
    // Load product data, technology map, warranty map, and categories
    $product_data = [];
    $technology_map = [];
    $warranty_map = [];
    $categories_data = [];
    $category_image_map = []; // Map category name to image
    
    try {
        $product_data = \App\Models\BomProduct::with('categories')->get()->toArray();
    
        $technologyList = \App\Models\Technology::all();
        foreach ($technologyList as $tech) {
            $technology_map[$tech->id] = $tech->title;
        }
    
        $warrantyList = \App\Models\Warranty::all();
        foreach ($warrantyList as $war) {
            $warranty_map[$war->id] = $war->title;
        }
    
        // Load categories to get brand images
        $categories_data = \App\Models\Category::all();
        foreach ($categories_data as $cat) {
            if (!empty($cat->name) && !empty($cat->image)) {
                $category_image_map[$cat->name] = $cat->image;
            }
        }
    } catch (\Throwable $e) {
        // If models don't exist, continue with empty arrays
    }
    
    // Get product data from estimate
    $componentsData = [];
    if ($estdata && !empty($estdata->product_name)) {
        $allproduct = $estdata->product_name;
        while (!is_array($allproduct) && !empty($allproduct) && is_string($allproduct)) {
            $decoded = json_decode($allproduct, true);
            if (json_last_error() !== JSON_ERROR_NONE || $decoded === null) {
                break;
            }
            $allproduct = $decoded;
        }
    
        if (is_array($allproduct) && !empty($allproduct)) {
            foreach ($allproduct as $item) {
                $product_id = $item['product_id'] ?? null;
                $product_name_display = $item['name'] ?? '';
                $product_category_makes = $item['category_name'] ?? '';
                $product_description = $item['description'] ?? '';
    
                // Find product details from master list
                $full_product_details = null;
                if ($product_id) {
                    foreach ($product_data as $prod_detail) {
                        $prod_detail_arr = (array) $prod_detail;
                        if (isset($prod_detail_arr['id']) && $prod_detail_arr['id'] == $product_id) {
                            $full_product_details = $prod_detail_arr;
                            break;
                        }
                    }
                }
    
                // Robust product name fallback
                if (empty(trim($product_name_display)) && $full_product_details) {
                    $product_name_display = $full_product_details['product_name'] ?? '';
                }
                if (empty(trim($product_name_display))) {
                    $product_name_display = 'Product name not found';
                }
                $product_name_display = ucfirst(strtolower($product_name_display));
    
                // Robust Make (category) fallback
                if (empty(trim($product_category_makes)) && $full_product_details && !empty($full_product_details['categories'])) {
                    $firstCat = reset($full_product_details['categories']);
                    $product_category_makes = $firstCat['name'] ?? '';
                }
    
                // Build specifications rows as: [Label, Value]
                $specifications = [];
    
                // Capacity
                if ($full_product_details && !empty($full_product_details['capacity'])) {
                    $specifications[] = ['Capacity', htmlspecialchars((string) $full_product_details['capacity'])];
                }
    
                // Watt Peak
                if ($full_product_details && !empty($full_product_details['watt_peak'])) {
                    $specifications[] = ['Watt Peak', htmlspecialchars((string) $full_product_details['watt_peak'])];
                }
    
                // Technology with fallback to legacy JSON and ID lookups
                $techVal = null;
                if ($full_product_details && !empty($full_product_details['technology_id'])) {
                    $techVal = $technology_map[$full_product_details['technology_id']] ?? null;
                } elseif ($full_product_details && !empty($full_product_details['technology'])) {
                    $techArray = json_decode($full_product_details['technology'], true);
                    if (!is_array($techArray)) {
                        $techArray = [$full_product_details['technology']];
                    }
                    $techArray = array_filter($techArray, fn($v) => trim((string) $v) !== '');
                    if (!empty($techArray)) {
                        $techNames = array_map(fn($id) => $technology_map[$id] ?? $id, $techArray);
                        $techVal = implode(', ', $techNames);
                    }
                }
                if ($techVal) {
                    $specifications[] = ['Type', htmlspecialchars($techVal)];
                }
    
                // Warranty with fallback to legacy JSON and ID lookups
                $warVal = null;
                if ($full_product_details && !empty($full_product_details['warranty_id'])) {
                    $warVal = $warranty_map[$full_product_details['warranty_id']] ?? null;
                } elseif ($full_product_details && !empty($full_product_details['warranty'])) {
                    $warArray = json_decode($full_product_details['warranty'], true);
                    if (!is_array($warArray)) {
                        $warArray = [$full_product_details['warranty']];
                    }
                    $warArray = array_filter($warArray, fn($v) => trim((string) $v) !== '');
                    if (!empty($warArray)) {
                        $warNames = array_map(fn($id) => $warranty_map[$id] ?? $id, $warArray);
                        $warVal = implode(', ', $warNames);
                    }
                }
                if ($warVal) {
                    $specifications[] = ['Warranty', htmlspecialchars($warVal)];
                }
    
                // Height
                if ($full_product_details && !empty($full_product_details['height'])) {
                    $specifications[] = ['Height', htmlspecialchars((string) $full_product_details['height'])];
                }
    
                // Thickness
                if ($full_product_details && !empty($full_product_details['thickness'])) {
                    $specifications[] = ['Thickness', htmlspecialchars((string) $full_product_details['thickness'])];
                }
    
                // Fitting Material
                if ($full_product_details && !empty($full_product_details['fitting_material'])) {
                    $specifications[] = ['Fitting Material', htmlspecialchars((string) $full_product_details['fitting_material'])];
                }
    
                // Fitting Type
                if ($full_product_details && !empty($full_product_details['fitting_type'])) {
                    $specifications[] = ['Fitting Type', htmlspecialchars((string) $full_product_details['fitting_type'])];
                }
    
                // Size of Pipe
                if ($full_product_details && !empty($full_product_details['size_of_pipe'])) {
                    $specifications[] = ['Size of Pipe', htmlspecialchars((string) $full_product_details['size_of_pipe'])];
                }
    
                // Get product image
                $product_image = $full_product_details['image'] ?? ($full_product_details['product_image'] ?? ($full_product_details['photo'] ?? ($item['image'] ?? $item['image_path'] ?? null)));
    
                // Get category/brand image
                $category_image = null;
                if (!empty($product_category_makes) && isset($category_image_map[$product_category_makes])) {
                    $category_image = $category_image_map[$product_category_makes];
                }
    
                // Get description from product table
                $product_table_description = '';
                if ($full_product_details) {
                    if (isset($full_product_details['description'])) {
                        $desc_value = $full_product_details['description'];
                        if ($desc_value !== null && $desc_value !== '' && trim($desc_value) !== '') {
                            $product_table_description = trim($desc_value);
                        }
                    }
                }
    
                // Use product table description first, then fallback to estimate item description
                $final_description = '';
                if (!empty($product_table_description) && trim($product_table_description) !== '') {
                    $final_description = $product_table_description;
                } elseif (!empty($product_description) && trim($product_description) !== '') {
                    $final_description = trim($product_description);
                }
    
                // Store ALL products
                $uniqueKey = $product_id . '_' . $product_name_display;
                $componentsData[$uniqueKey] = [
                    'name' => $product_name_display,
                    'category' => $product_category_makes, // Make = Brand
                    'category_image' => $category_image,
                    'description' => $final_description,
                    'specifications' => $specifications,
                    'image' => $product_image,
                    'quantity' => $item['quantity'] ?? 0,
                ];
            }
        }
    }
    
    // Default components if no data
    if (empty($componentsData)) {
        $componentsData = [
            'Panel' => [
                'name' => 'Panel',
                'category' => 'Waaree Group',
                'description' => 'Bifacial High Wattage solar panels, with 25 years of warranty.',
                'specifications' => '<strong>Watt Peak:</strong> 585-615<br><strong>Type:</strong> Bifacial<br><strong>Warranty:</strong> 10 years (product), 25 years (performance)',
                'image' => null,
                'category_image' => null,
            ],
            'Inverter' => [
                'name' => 'Inverter',
                'category' => 'Sungrow',
                'description' => 'Sungrow 15kw inverter, with all protection features, and anti-islanding support.',
                'specifications' => '<strong>Capacity:</strong> 15 KW design<br><strong>Type:</strong> On-Grid<br><strong>Warranty:</strong> 10 years',
                'image' => null,
                'category_image' => null,
            ],
            'Cable' => [
                'name' => 'Cable',
                'category' => 'Polycab',
                'description' => 'Polycab U/V resistant, long lasting cables.',
                'specifications' => '<strong>Type:</strong> DC/AC Cables<br>double insulated cables<br><strong>Warranty:</strong> 30 years',
                'image' => null,
                'category_image' => null,
            ],
            'Electrical Component' => [
                'name' => 'Electrical Component',
                'category' => 'True Power',
                'description' => 'TruePower electrical system.',
                'specifications' => 'MC4 Connectors<br>HDGI Earthing',
                'image' => null,
                'category_image' => null,
            ],
        ];
    }
    
    $componentsPageClass = $_pageClass('p6');
    $componentsList = array_values($componentsData);
    // Let the PDF renderer fill each page according to actual row heights.
    // A fixed row count leaves large gaps for products with short specifications.
    $componentsPages = [['layout' => 'combined', 'rows' => $componentsList]];

    if (!empty($invoiceOnly)) {
        $componentsPages = [['layout' => 'table', 'rows' => $componentsList]];
        $componentsPageClass = 'page';
    }
    $componentsPageTotal = count($componentsPages);
    ?>
    <?php endif; ?>

    <!-- PAGE 6A: ESTIMATION / INVOICE -->
    <div class="page {{ empty($invoiceOnly) ? 'page-break' : '' }}" style="position: relative; background: white;">
        <div style="padding: 32px 40px 36px;">
            @if (empty($invoiceOnly))
            @include('pdfbuilder.partials.pdf-page-header')
            @endif
            @include('pdfbuilder.partials.estimate-invoice-summary')
        </div>
        <table width="100%" cellpadding="0" cellspacing="0"
            style="position:fixed; bottom:{{ !empty($invoiceOnly) ? '0' : '10px' }}; left:0; right:0;
                    background:#fff; color:#4b9349; height:40px; border-top: 1px solid #4b9349;">
            <tr>
                <td width="33.33%" style="{{ !empty($invoiceOnly) ? 'height:40px;vertical-align:middle;padding:0 10px;' : 'padding:10px;' }} font-family: 'Montserrat', sans-serif;">
                    <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                </td>
                <td width="33.33%" align="center" style="{{ !empty($invoiceOnly) ? 'height:40px;vertical-align:middle;padding:0 10px;' : 'padding:10px;' }} font-family: 'Montserrat', sans-serif;">
                    @if (empty($invoiceOnly))
                    PAGE <?= $_pdfPageNumber() ?>
                    @endif
                </td>
                <td width="33.33%" align="right"
                    style="{{ !empty($invoiceOnly) ? 'height:40px;vertical-align:middle;padding:0 10px;' : 'padding:10px;' }} font-family: 'Montserrat', sans-serif; white-space:nowrap;">
                    Generated by <?= esc($globalCompanyName) ?>
                </td>
            </tr>
        </table>
    </div>

    <?php if ($__componentsActive && isset($componentsPages)): ?>
    <?php foreach ($componentsPages as $componentsPageIndex => $componentsPage):
        $isLastComponentsPage = ($componentsPageIndex === $componentsPageTotal - 1);
        $componentsChunkPageClass = $isLastComponentsPage ? $componentsPageClass : 'page page-break';
        $componentsPageLayout = $componentsPage['layout'];
    ?>
    <div class="<?= $componentsChunkPageClass ?>" style="position: relative; background: white;">
        <div style="padding: 40px; padding-bottom: 56px;">
            @include('pdfbuilder.partials.pdf-page-header')
            <?php if ($componentsPageLayout === 'intro_block'): ?>
            @include('pdfbuilder.partials.company-components-intro', ['componentsIntroExpanded' => false])
            <?php elseif ($componentsPageLayout === 'combined'): ?>
            @include('pdfbuilder.partials.company-components-intro', ['componentsIntroExpanded' => false])
            @include('pdfbuilder.partials.company-components-table', [
                'componentsTableRows' => $componentsPage['rows'] ?? [],
            ])
            <?php else: ?>
            @include('pdfbuilder.partials.company-components-title')
            @include('pdfbuilder.partials.company-components-table', [
                'componentsTableRows' => $componentsPage['rows'] ?? [],
            ])
            <?php endif; ?>
        </div>
        <table width="100%" cellpadding="0" cellspacing="0"
            style="position:fixed; bottom:10; left:0; right:0;
                    background:#fff; color:#4b9349; height:40px; border-top: 1px solid #4b9349;">
            <tr>
                <td width="33.33%" style="padding:10px; font-family: 'Montserrat', sans-serif;">
                    <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                </td>
                <td width="33.33%" align="center" style="padding:10px; font-family: 'Montserrat', sans-serif;">
                    PAGE <?= $_pdfPageNumber() ?>
                </td>
                <td width="33.33%" align="right"
                    style="padding:10px; font-family: 'Montserrat', sans-serif; white-space:nowrap;">
                    Generated by <?= esc($globalCompanyName) ?>
                </td>
            </tr>
        </table>
    </div>
    <?php endforeach; ?>
    <!-- ================= END PAGE 6 ================= -->
    <?php endif; ?>

    @if (empty($invoiceOnly))
    <?php if ($__paymentTermsActive0): ?>
    <!-- ================= PAGE 7 : PAYMENT TERMS ================= -->
    <?php
    $paymentTerms = isset($paymentTerms) && is_array($paymentTerms) ? $paymentTerms : [];
    $paymentTermsTitle = trim((string) ($paymentTerms['title'] ?? ''));
    $paymentTermsScope = trim((string) ($paymentTerms['scope'] ?? ''));
    $paymentTermsNote = trim((string) ($paymentTerms['note'] ?? ''));
    $paymentTermsImg = resolve_pdf_image_with_fallback($paymentTerms['image'] ?? '', 'public/assets/img/page_7.png');
    $services = $paymentTerms['services'] ?? [];
    if (!is_array($services)) {
        $services = [];
    }
    $paymentRows = [];
    foreach ($services as $s) {
        if (is_array($s)) {
            $paymentRows[] = [
                'left' => trim((string) ($s['left'] ?? '')),
                'right' => trim((string) ($s['right'] ?? '')),
            ];
        } else {
            $paymentRows[] = ['left' => trim((string) $s), 'right' => ''];
        }
    }
    $paymentRows = array_values(array_filter($paymentRows, static fn($r) => ($r['left'] ?? '') !== '' || ($r['right'] ?? '') !== ''));
    $paymentTermsHeading = ($estdata->type ?? '') === 'residential' ? '12. Payment Terms' : ($paymentTermsTitle !== '' ? $paymentTermsTitle : 'PAYMENT TERMS');
    ?>
    <div class="<?= $_pageClass('p7') ?>" style="position: relative; min-height: 842px; background: white;">
        <div style="padding: 38px 42px 30px;">
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:14px;">
                <tr>
                    <td width="50%" align="left" valign="top">
                        <div style="font-size:18px; font-family:'Montserrat',sans-serif;">
                            <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                        </div>
                    </td>
                    <td width="50%" align="right" valign="top">
                        <?php if (!empty($logoBase64)): ?>
                        <div style="display:inline-block;text-align:right;">
                            <img src="<?= $logoBase64 ?>" alt="Company Logo"
                                style="max-width:160px;height:auto;margin-bottom:5px;">
                        </div>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <div
                style="font-size:32px;font-weight:bold;font-family:'Montserrat',sans-serif;border-left:7px solid #4b9349;padding-left:16px;line-height:1.1;margin-bottom:18px;">
                <?= esc($paymentTermsHeading) ?>
            </div>

            <div style="page-break-inside:avoid;">
                <table width="100%" cellpadding="0" cellspacing="0"
                    style="border-collapse:collapse;margin-bottom:14px;">
                    <tr>
                        <td align="center" valign="middle">
                            <img src="<?= $paymentTermsImg ?>" alt="Payment Terms Timeline"
                                style="max-width:66%;max-height:150px;height:auto;display:block;margin:0 auto;">
                        </td>
                    </tr>
                </table>

                <?php if (($estdata->type ?? '') === 'residential'): ?>
                <ul
                    style="padding-left:20px;font-size:13px;line-height:1.5;margin-top:8px;margin-bottom:12px;font-family:'Montserrat',sans-serif;">
                    <li style="margin-bottom:6px;"><strong>30% Mobilization Advance:</strong> Booked to initiate legal
                        DISCOM file log, architectural layouts, and factory component ordering.</li>
                    <li style="margin-bottom:6px;"><strong>60% Component Delivery Milestone:</strong> Payable
                        immediately upon the safe arrival of primary inventory (PV Modules &amp; Inverter) at the
                        installation site.</li>
                    <li style="margin-bottom:6px;"><strong>10% Commissioning Milestone:</strong> Balance due upon
                        successful grid connection synchronization and hand over of system logins.</li>
                </ul>
                <?php endif; ?>

                <?php if ($paymentTermsScope !== ''): ?>
                <div
                    style="font-size:17px;font-weight:bold;margin:12px 0 10px;font-family:'Montserrat',sans-serif;border-left:7px solid #4b9349;padding-left:16px;line-height:1.2;">
                    <?= esc($paymentTermsScope) ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($paymentRows)): ?>
                <?php
                $ptHeaderStyle = 'color:#000;padding:11px 14px;font-size:14px;font-weight:bold;font-family:Montserrat,sans-serif;border:1px solid #3d7a3b;';
                $ptLeftStyle = 'padding:11px 14px;font-size:13px;font-weight:bold;color:#000000;font-family:Montserrat,sans-serif;border:1px solid #d8e8d8;vertical-align:top;width:30%;line-height:1.4;background-color:#ffffff;';
                $ptRightStyle = 'padding:11px 14px;font-size:13px;color:#333;font-family:Montserrat,sans-serif;border:1px solid #d8e8d8;vertical-align:top;width:70%;line-height:1.55;';
                $ptRowEvenStyle = '';
                ?>
                <table width="100%" cellpadding="0" cellspacing="0"
                    style="border-collapse:collapse;margin:14px 0 16px;border:1px solid #4b9349;">
                    <tr>
                        <td colspan="2" style="<?= $ptHeaderStyle ?>letter-spacing:0.4px;">
                            What we cover under our services
                        </td>
                    </tr>
                    <?php foreach ($paymentRows as $ptIdx => $r): ?>
                    <?php $ptRowBg = $ptIdx % 2 === 1 ? $ptRowEvenStyle : ''; ?>
                    <tr style="<?= $ptRowBg ?>">
                        <td style="<?= $ptLeftStyle ?>">
                            <?= esc($r['left'] ?? '') ?>
                        </td>
                        <td style="<?= $ptRightStyle ?>">
                            <?= esc($r['right'] ?? '') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php endif; ?>

                <?php if ($paymentTermsNote !== ''): ?>
                <div
                    style="font-size:13px;font-style:italic;margin-top:8px;font-family:'Montserrat',sans-serif;line-height:1.45;">
                    <?= esc($paymentTermsNote) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- Footer -->
        <table width="100%" cellpadding="0" cellspacing="0"
            style="position:fixed; bottom:10; left:0; right:0;
                    background:#fff; color:#4b9349; height:40px; border-top: 1px solid #4b9349;">
            <tr>
                <td width="33.33%" style="padding:10px;">
                    <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                </td>

                <td width="33.33%" align="center" style="padding:10px;">
                    PAGE <?= $_pdfPageNumber() ?>
                </td>

                <td width="33.33%" align="right" style="padding:10px; white-space:nowrap;">
                    <?php
                    $companyName = esc($globalCompanyName);
                    $companyParts = explode(' ', $companyName);
                    $mainName = $companyParts[0] ?? $companyName;
                    ?>
                    Generated by <?= esc($companyName) ?>
                </td>
            </tr>
        </table>
    </div>
    <!-- ================= END PAGE 7 ================= -->
    <?php endif; ?>

    <!-- ================= PAGE 8 : ENVIRONMENT IMPACT ================= -->
    <?php
    $__environmentImpact = isset($environmentImpact) && is_array($environmentImpact) ? $environmentImpact : [];
    $__environmentImpactActive = $_isActive($__environmentImpact);
    ?>
    <?php if ($__environmentImpactActive): ?>
    <div class="<?= $_pageClass('p8') ?>" style="position:relative;min-height:842px;background:#fff;">
        <div style="padding:38px 42px 34px;">
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:14px;">
                <tr>
                    <td width="50%" align="left" valign="top">
                        <div style="font-size:14px;color:#000;font-family:'Montserrat',sans-serif;">
                            <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                        </div>
                    </td>
                    <td width="50%" align="right" valign="top">
                        <?php if (!empty($logoBase64)): ?>
                        <div style="display:inline-block;text-align:right;">
                            <img src="<?= $logoBase64 ?>" alt="Company Logo"
                                style="max-width:160px;height:auto;margin-bottom:5px;">
                        </div>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <?php
            $systemCapacity = max(0.0, (float) $quantity);
            $envLifetimeYears = 25;
            $co2PerKwPerYear = 1.01;
            $acresPerKwPerYear = 1.187;
            $coalPerKwPerYear = 1.259;
            
            $totalCo2Offset = $systemCapacity * $co2PerKwPerYear * $envLifetimeYears;
            $equivalentAcres = $systemCapacity * $acresPerKwPerYear;
            $coalAvoided = $systemCapacity * $coalPerKwPerYear;
            
            $co2OffsetDisplay = number_format($totalCo2Offset, 1);
            $equivalentAcresDisplay = number_format($equivalentAcres, 2);
            $coalAvoidedDisplay = number_format($coalAvoided, 2);
            
            $environmentImpact = isset($environmentImpact) && is_array($environmentImpact) ? $environmentImpact : [];
            $envTitle = trim((string) ($environmentImpact['title'] ?? ''));
            $envContentRaw = trim((string) ($environmentImpact['content'] ?? ''));
            $envContent = $envContentRaw !== '' ? sanitize_pdf_rich_html($envContentRaw) : "You are contributing to solve earth's biggest problem- <strong>Climate Change.</strong>";
            
            [$envContentTop, $envContentBottom] = split_env_content_for_image($envContent);
            if (pdf_rich_html_plain_length($envContent) < 120) {
                $envContentTop = $envContent;
                $envContentBottom = '';
            }
            
            $envImg = resolve_pdf_image_with_fallback($environmentImpact['image'] ?? '', 'public/assets/img/page_8.png');
            
            $envMetrics = [['label' => 'CARBON DIOXIDE OFFSET', 'value' => $co2OffsetDisplay . ' Metric Tons'], ['label' => 'EQUIVALENT ACRES OF FOREST', 'value' => $equivalentAcresDisplay . ' Acres/Year'], ['label' => 'COAL BURN AVOIDED', 'value' => $coalAvoidedDisplay . ' Metric Tons']];
            ?>

            <div style="page-break-inside:avoid;">
                <?php if ($envTitle !== ''): ?>
                <div
                    style="font-size:28px;font-weight:bold;margin-bottom:12px;letter-spacing:1px;font-family:'Montserrat',sans-serif;border-left:7px solid #4b9349;padding-left:16px;line-height:1.1;">
                    <?= esc($envTitle) ?>
                </div>
                <?php elseif ($envContentRaw === ''): ?>
                <div
                    style="font-size:28px;font-weight:bold;margin-bottom:12px;letter-spacing:1px;font-family:'Montserrat',sans-serif;border-left:7px solid #4b9349;padding-left:16px;line-height:1.1;">
                    ENVIRONMENT IMPACT
                </div>
                <?php endif; ?>

                <?php if ($envContentTop !== ''): ?>
                <style>
                    .pdf-env-impact-content-top h1, .pdf-env-impact-content-top h2, .pdf-env-impact-content-top h3, .pdf-env-impact-content-top h4, .pdf-env-impact-content-top h5, .pdf-env-impact-content-top h6 {
                        color: #4b9349 !important;
                    }
                </style>
                <div class="pdf-env-impact-content pdf-env-impact-content-top" style="margin-bottom:10px;">
                    <?= $envContentTop ?>
                </div>
                <?php endif; ?>

                <?php if ($envImg !== ''): ?>
                <table width="100%" cellpadding="0" cellspacing="0"
                    style="margin:10px 0 12px;border-collapse:collapse;">
                    <tr>
                        <td align="center" valign="middle" style="padding:15px 0;">
                            <img src="<?= $envImg ?>" alt="Environment Impact"
                                style="max-width:90%;max-height:320px;width:auto;height:auto;display:block;margin:0 auto;">
                        </td>
                    </tr>
                </table>
                <?php endif; ?>

                <?php if ($envContentBottom !== ''): ?>
                <div class="pdf-env-impact-content" style="margin-bottom:8px;">
                    <?= $envContentBottom ?>
                </div>
                <?php endif; ?>

                <table width="100%" cellpadding="0" cellspacing="0"
                    style="margin-top:8px;border-collapse:collapse;table-layout:fixed;">
                    <tr>
                        <?php foreach ($envMetrics as $metric): ?>
                        <td width="33.33%" valign="middle"
                            style="padding:15px 12px 15px 0;min-height:70px;font-family:'Montserrat',sans-serif;">
                            <div
                                style="font-size:11px;font-weight:bold;letter-spacing:0.2px;color:#111;padding-left:10px;line-height:1.35;min-height:28px;">
                                <?= esc($metric['label']) ?>
                            </div>
                            <div
                                style="font-size:15px;font-weight:bold;color:#4b9349;margin-top:8px;padding-left:14px;line-height:1.3;min-height:22px;">
                                <?= esc($metric['value']) ?>
                            </div>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                </table>
            </div>
        </div>
        <!-- Footer -->
        <table width="100%" cellpadding="0" cellspacing="0"
            style="position:fixed; bottom:10; left:0; right:0;
                    background:#fff; color:#4b9349; height:40px; border-top: 1px solid #4b9349;">
            <tr>
                <td width="33.33%" style="padding:10px;">
                    <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                </td>

                <td width="33.33%" align="center" style="padding:10px;">
                    PAGE <?= $_pdfPageNumber() ?>
                </td>

                <td width="33.33%" align="right" style="padding:10px; white-space:nowrap;">
                    <?php
                    $companyName = esc($globalCompanyName);
                    $companyParts = explode(' ', $companyName);
                    $mainName = $companyParts[0] ?? $companyName;
                    ?>
                    Generated by <?= esc($companyName) ?>
                </td>
            </tr>
        </table>
    </div>
    <?php endif; ?>
    <!-- ================= END PAGE 8 ================= -->

    <?php if ($__isResidentialTemplate): ?>
    <!-- ================= PAGE 8b : TERMS, DISCLAIMER & TESTIMONIALS (Residential templates) ================= -->
    <div class="<?= $_pageClass('p8b') ?>"
        style="position:relative;min-height:842px;background:#fff;font-family:'Montserrat',sans-serif;">
        <div style="padding:38px 42px 34px;">
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:18px;">
                <tr>
                    <td width="50%" align="left" valign="top">
                        <div style="font-size:14px;color:#111;">
                            <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                        </div>
                    </td>
                    <td width="50%" align="right" valign="top">
                        <?php if (!empty($logoBase64)): ?>
                        <img src="<?= $logoBase64 ?>" alt="Company Logo" style="max-width:160px;height:auto;">
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <div style="page-break-inside:avoid;">
                <!-- Terms & Conditions -->
                <div
                    style="font-size:22px;font-weight:bold;margin-bottom:12px;padding-left:14px;line-height:1.2;color:#111;">
                    Terms &amp; Conditions
                </div>
                <table width="100%" cellpadding="0" cellspacing="0"
                    style="margin-bottom:22px;border-collapse:collapse;">
                    <tr>
                        <td
                            style="padding:10px 12px 10px 14px;font-size:13.5px;line-height:1.55;color:#333;">
                            <strong>Turnaround Timeline:</strong> Project completion spans 3 to 4 weeks conditional upon
                            localized utility board structural approval speed.
                        </td>
                    </tr>
                    <tr>
                        <td style="height:8px;font-size:0;line-height:0;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td
                            style="padding:10px 12px 10px 14px;font-size:13.5px;line-height:1.55;color:#333;">
                            <strong>Site Handover Readiness:</strong> The client is required to grant clear rooftop
                            clearance, secure storage space for physical components, and a continuous water line
                            connection for maintenance panels cleaning.
                        </td>
                    </tr>
                    <tr>
                        <td style="height:8px;font-size:0;line-height:0;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td
                            style="padding:10px 12px 10px 14px;font-size:13.5px;line-height:1.55;color:#333;">
                            <strong>Civil Variations:</strong> Baseline quotes assume mounting configurations directly
                            onto structurally sound RCC flat roofs. High-raise custom structures or unique modifications
                            will be billed extra as per agreed metrics.
                        </td>
                    </tr>
                </table>

                <!-- Disclaimer -->
                <div
                    style="font-size:22px;font-weight:bold;margin-bottom:12px;padding-left:14px;line-height:1.2;color:#111;">
                    Disclaimer
                </div>
                <div
                    style="padding:14px 16px 14px 18px;font-size:13px;line-height:1.58;color:#444;margin-bottom:22px;text-align:justify;">
                    Solar generation metrics are derived parameters calculated utilizing historical long-term satellite
                    climate records for your specific latitude/longitude. Actual real-time production yields may
                    fluctuate in accordance with variations in seasonal weather cycles, structural micro-climate shading
                    patterns (such as subsequent newly erected adjacent high-rises), and regular panel dust wash upkeep
                    consistency.
                </div>

                <!-- Client Testimonials -->
                <div
                    style="font-size:22px;font-weight:bold;margin-bottom:14px;padding-left:14px;line-height:1.2;color:#111;">
                    Client Testimonials
                </div>

                <table width="100%" cellpadding="0" cellspacing="0"
                    style="margin-bottom:12px;border-collapse:collapse;border:1px solid #d8e8d8;">
                    <tr>
                        <td
                            style="padding:14px 16px;font-size:13px;line-height:1.55;color:#444;font-style:italic;">
                            &ldquo;The complete migration process to solar with this team was entirely fluid. Our
                            typical monthly operational electric bills fell right down from near <?= $rupeeHtml ?>8,000
                            to basic minimal meter standing charges under <?= $rupeeHtml ?>500! Clean installation and
                            outstanding customer portal communication.&rdquo;
                            <div
                                style="font-weight:bold;font-style:normal;margin-top:10px;font-size:12.5px;color:#1e3f20;">
                                &mdash; Rajesh K., Verified Residential Client
                            </div>
                        </td>
                    </tr>
                </table>

                <table width="100%" cellpadding="0" cellspacing="0"
                    style="border-collapse:collapse;border:1px solid #d8e8d8;">
                    <tr>
                        <td
                            style="padding:14px 16px;font-size:13px;line-height:1.55;color:#444;font-style:italic;">
                            &ldquo;Outstanding expertise handling the central PM-Surya Ghar portal compliance
                            parameters. The full subsidy allocation arrived securely into my bank profile within exactly
                            25 working days post meter calibration.&rdquo;
                            <div
                                style="font-weight:bold;font-style:normal;margin-top:10px;font-size:12.5px;color:#1e3f20;">
                                &mdash; Sunita Sharma, Verified Residential Client
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <table width="100%" cellpadding="0" cellspacing="0"
            style="position:fixed;bottom:10;left:0;right:0;background:#fff;color:#4b9349;height:40px;border-top:1px solid #4b9349;">
            <tr>
                <td width="33.33%" style="padding:10px;font-family:'Montserrat',sans-serif;">
                    <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                </td>
                <td width="33.33%" align="center" style="padding:10px;font-family:'Montserrat',sans-serif;">
                    PAGE <?= $_pdfPageNumber() ?>
                </td>
                <td width="33.33%" align="right"
                    style="padding:10px;font-family:'Montserrat',sans-serif;white-space:nowrap;">
                    Generated by <?= esc($globalCompanyName) ?>
                </td>
            </tr>
        </table>
    </div>
    <?php endif; ?>
    <!-- ================= END PAGE 8b ================= -->

    <!-- ================= PAGE 9 : THANK YOU ================= -->
    <?php
    $__footer = isset($footer) && is_array($footer) ? $footer : [];
    $__footerActive = $_isActive($__footer);
    ?>
    <?php if ($__footerActive): ?>
    <div class="page" style="position: relative; min-height: 842px; background: white;">
        <!-- Header -->
        <div style="padding: 50px;">
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 30px;">
                <tr>
                    <td width="50%" align="left" valign="top">
                        <div style="font-size: 18px;">
                            <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                        </div>
                    </td>
                    <td width="50%" align="right" valign="top">
                        <?php    if (!empty($logoBase64)): ?>
                        <div style="display: inline-block; text-align: right;">
                            <img src="<?= $logoBase64 ?>" alt="Company Logo"
                                style="max-width: 160px; height: auto; margin-bottom: 5px;">
                        </div>
                        <?php    endif; ?>
                    </td>
                </tr>
            </table>



            <!-- Solar Panel Image -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 10px;">
                <tr>
                    <td align="center">
                        <?php
                        // Template-specific footer (saved in DB as JSON)
                        $footer = isset($footer) && is_array($footer) ? $footer : [];
                        $footerActive = (int) ($footer['active'] ?? 1);
                        $footerTitle = trim((string) ($footer['title'] ?? ''));
                        $footerSubTitle = trim((string) ($footer['sub_title'] ?? ''));
                        $footerImg = !empty($footer['image']) ? normalize_pdf_image($footer['image']) : normalize_pdf_image('public/assets/img/footer.png');
                        ?>
                        <img src="<?= $footerImg ?>" alt="Solar Panels"
                            style="max-width: 100%; height: auto; display: block; margin: 0 auto;">
                    </td>
                </tr>
            </table>

            <!-- Thank You Section -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 20px;">
                <tr>
                    <td align="left" style="padding-bottom: 12px;">
                        <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
                            <tr>
                                <td align="center" valign="top"
                                    style="font-size: 80px; color: #000; letter-spacing: 3px; font-family: 'Montserrat', sans-serif; line-height: 1.05; text-align: center;">
                                    <?= $footerTitle !== '' ? esc($footerTitle) : 'THANK YOU' ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding-left: 26px;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <!-- Text -->
                                <td align="left" style="font-size: 20px; font-family: 'Montserrat', sans-serif;">
                                    <?php
                                    $defaultSub = 'Looking forward to work and add value';
                                    echo esc($footerActive === 1 && $footerSubTitle !== '' ? $footerSubTitle : $defaultSub);
                                    ?>
                                </td>

                                <!-- Image -->
                                <td align="right">
                                    <img src="<?= normalize_pdf_image('public/assets/img/footer_arrow.png') ?>"
                                        alt="Arrow" width="80" height="55">
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <!-- Contact Information Footer (Black Box) -->
            <?php
            $companySettings = $companySettings ?? [];
            $userPhone = $companySettings['phone'] ?? ($user['phone'] ?? ($user['whatsapp'] ?? ($user['contact'] ?? ($user['whatsapp_no'] ?? ($user['contact_phone'] ?? '')))));
            $userEmail = $companySettings['email'] ?? ($user['email'] ?? ($user['contact_email'] ?? ''));
            $userWebsite = $companySettings['company_name'] ?? ($user['website'] ?? '');
            $userAddress = $companySettings['company_address'] ?? ($user['address'] ?? '');
            $company_name = $companySettings['company_name'] ?? ($user['company_name'] ?? '');
            ?>
            <table width="100%" cellpadding="0" cellspacing="0"
                style="background-color: #4b9349; color: #fff; padding: 20px; margin-top: 20px;">
                <tr>
                    <td width="50%" valign="top" style="padding-right: 20px;">
                        <div style="margin-bottom: 25px;">
                            <div
                                style="font-size: 20px; font-weight: bold; color: #fff; margin-bottom: 8px; font-family: 'Montserrat', sans-serif; line-height: 1.4;">
                                Phone:</div>
                            <div
                                style="font-size: 16px; color: #fff; font-family: 'Montserrat', sans-serif; line-height: 1.4;">
                                <?= !empty($userPhone) ? esc($userPhone) : '--' ?>
                            </div>
                        </div>
                        <div>
                            <div
                                style="font-size: 20px; font-weight: bold; color: #fff; margin-bottom: 8px; font-family: 'Montserrat', sans-serif; line-height: 1.4;">
                                Email:</div>
                            <div
                                style="font-size: 16px; color: #fff; font-family: 'Montserrat', sans-serif; line-height: 1.4;">
                                <?= !empty($userEmail) ? esc($userEmail) : '--' ?>
                            </div>
                        </div>
                    </td>
                    <td width="50%" valign="top" align="right" style="padding-left: 20px; text-align: right;">
                        <div style="margin-bottom: 25px;">
                            <div
                                style="font-size: 20px; font-weight: bold; color: #fff; margin-bottom: 8px; font-family: 'Montserrat', sans-serif; line-height: 1.4;">
                                Website:
                            </div>
                            <div
                                style="font-size: 16px; color: #fff; font-family: 'Montserrat', sans-serif; line-height: 1.4;">
                                <a href="<?= base_url() ?>"
                                    style="color: #fff; text-decoration: none; font-family: 'Montserrat', sans-serif;"
                                    target="_blank">
                                    <?= !empty($company_name) ? esc($company_name) : '--' ?>
                                </a>
                            </div>
                        </div>

                        <div>
                            <div
                                style="font-size: 20px; font-weight: bold; color: #fff; margin-bottom: 8px; font-family: 'Montserrat', sans-serif; line-height: 1.4;">
                                Address:
                            </div>
                            <div
                                style="font-size: 16px; color: #fff; font-family: 'Montserrat', sans-serif; line-height: 1.4;">
                                <?= !empty($userAddress) ? esc($userAddress) : '--' ?>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        <!-- Footer -->
        <table width="100%" cellpadding="0" cellspacing="0"
            style="position:fixed; bottom:10; left:0; right:0;
                    background:#fff; color:#4b9349; height:40px; border-top: 1px solid #4b9349;">
            <tr>
                <td width="33.33%" style="padding:10px;">
                    <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                </td>

                <td width="33.33%" align="center" style="padding:10px;">
                    PAGE <?= $_pdfPageNumber() ?>
                </td>

                <td width="33.33%" align="right" style="padding:10px; white-space:nowrap;">
                    <?php
                    $companyName = esc($globalCompanyName);
                    $companyParts = explode(' ', $companyName);
                    $mainName = $companyParts[0] ?? $companyName;
                    ?>
                    Generated by <?= esc($companyName) ?>
                </td>
            </tr>
        </table>

    </div>
    <!-- ================= END PAGE 9 ================= -->
    <?php endif; ?>

    @endif
    @include('crm.estimates.partials.terms-conditions-pdf')
</body>

</html>
