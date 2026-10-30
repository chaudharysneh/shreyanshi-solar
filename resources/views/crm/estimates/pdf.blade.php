<?php
if (!function_exists('normalize_pdf_image')) {
    function normalize_pdf_image($path)
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }

        // Helper closure to optimize and convert progressive images to Baseline using GD
        $optimizeImage = function($candidate) {
            $ext = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
            if (empty($ext)) $ext = 'png';
            elseif ($ext === 'jpg') $ext = 'jpeg';
            
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
                } catch (\Throwable $t) {}
            }
            
            // Fast pathway for all other formats (Baseline JPEG & PNG) to preserve exact original bytes
            $imgData = @file_get_contents($candidate);
            if ($imgData !== false) {
                $mime = ($ext === 'png') ? 'image/png' : 'image/jpeg';
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
        }

        foreach (array_unique($candidates) as $candidate) {
            if ($candidate && @file_exists($candidate) && @is_file($candidate)) {
                $result = $optimizeImage($candidate);
                if ($result) return $result;
                // Fallback if optimize failed but file is accessible
                $imgData = @file_get_contents($candidate);
                if ($imgData !== false) {
                    $ext = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
                    $mime = ($ext === 'png') ? 'image/png' : 'image/jpeg';
                    return 'data:' . $mime . ';base64,' . base64_encode($imgData);
                }
            }
        }

        // HTTP fallback: try to fetch the image via URL and convert to base64
        // (Dompdf often cannot fetch URLs from the same server due to loopback issues)
        $urlToTry = (preg_match('/^https?:\/\//i', $path)) ? $path : asset('storage/' . $cleanPath);
        try {
            $ctx = stream_context_create(['http' => ['timeout' => 5], 'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
            $imgData = @file_get_contents($urlToTry, false, $ctx);
            if ($imgData !== false && strlen($imgData) > 0) {
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->buffer($imgData) ?: 'image/jpeg';
                return 'data:' . $mime . ';base64,' . base64_encode($imgData);
            }
        } catch (\Throwable $e) {}

        // Last resort: return the URL directly (may not render in Dompdf)
        return $urlToTry;
    }
}

if (!function_exists('base_url')) {
    function base_url($path = '')
    {
        if (empty($path)) {
            return rtrim(url('/'), '/') . '/';
        }
        return normalize_pdf_image($path);
    }
}
?>
<?php
if (!isset($estdata) && isset($estimate)) {
    $estdata = new \stdClass();

    $attrs = [];
    if ($estimate instanceof \Illuminate\Database\Eloquent\Model) {
        $attrs = $estimate->getAttributes();
    } elseif (is_array($estimate)) {
        $attrs = $estimate;
    } else {
        $attrs = (array) $estimate;
    }

    foreach ($attrs as $key => $val) {
        $estdata->$key = $val;
    }

    if (isset($estimate->customer)) {
        $estdata->name = $estimate->customer->name ?? '--';
        $estdata->email = $estimate->customer->email ?? '--';
        $estdata->address = $estimate->customer->address ?? '--';
        $estdata->phone = $estimate->customer->phone ?? '--';
    }
}
?>
<div class="quotation-block">
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
        }

        .quotation-box {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            background-color: #fff;
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
        }

        .quotation-header table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }

        .quotation-header td {
            vertical-align: top;
            padding: 5px 0;
        }

        .company-logo img {
            max-width: 300px;
            max-height: 120px;
            display: block;
            object-fit: contain;
        }

        .quotation-title {
            font-size: 16px;
            text-align: right;
            color: #686868;
        }

        .info-table,
        .quotation-table,
        .extra-info table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 16px;
        }

        .info-table th,
        .info-table td,
        .quotation-table th,
        .quotation-table td,
        .extra-info th,
        .extra-info td {
            border: 1px solid #333;
            padding: 4px 6px;
            text-align: left;
        }

        .info-table th,
        .quotation-table th,
        .extra-info th {
            background-color: #1b365d;
            color: #fff;
        }

        .quotation-table tfoot td {
            font-weight: bold;
            text-align: right;
        }

        .quotation-footer {
            margin-top: 20px;
            font-size: 14px;
            text-align: center;
            color: #555;
        }

        .flex-between {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-direction: row;
        }

        .center-text {
            text-align: center;
            font-weight: 700;
            text-decoration: underline;
        }
    </style>

    <main id="main" class="main">
        <div class="quotation-box">

            <!-- Header -->
            <div class="quotation-header">
                <table>
                    <tr>
                        <td class="company-logo">
                            <?php $pdfLogoPath = $settings['company_logo_path'] ?? ($user['company_logo'] ?? ''); ?>
                            <?php if ($pdfLogoPath): ?>
                            <img src="<?php echo normalize_pdf_image($pdfLogoPath); ?>" alt="Company Logo" style="max-width:150px; max-height:80px; object-fit:contain; background-color:#fff; padding:5px; border-radius:4px;">
                            <?php endif; ?>
                        </td>
                        <td class="quotation-title">
                            <div style="line-height:22px;color:#000">
                                <strong
                                    style="font-size:18px;color:#000"><?php echo htmlspecialchars($settings['company_name'] ?? ($user['company'] ?? 'Rising Green Energy')); ?></strong><br>
                                <div style="max-width: 250px; display: inline-block; text-align: right; white-space: normal;">
                                    <?php echo htmlspecialchars($settings['company_address'] ?? ($user['address'] ?? '--')); ?>
                                </div><br>
                            </div>
                        </td>
                    </tr>
                </table>
                <hr>
            </div>

            <!-- Quotation Info -->
            <div class="flex-between">
                <div style="font-weight:700; font-size:16px;">
                    Estimate no.: #<?php echo htmlspecialchars($estdata->estimate_no); ?>
                </div>
                <div class="center-text" style="font-size:18px;">ESTIMATION</div>
                <div style="font-weight:700; font-size:16px;">
                    Date: <?php echo htmlspecialchars($estdata->estimate_date); ?>
                </div>
            </div>

            <!-- Customer Info Table -->
            <table class="info-table">
                <thead>
                    <tr>
                        <th colspan="4">Customer Details</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Customer Name</strong></td>
                        <td><?php echo htmlspecialchars($estdata->name ?? '--'); ?></td>
                        <td><strong>Email</strong></td>
                        <td><?php echo htmlspecialchars($estdata->email ?? '--'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Address</strong></td>
                        <td><?php echo htmlspecialchars($estdata->address ?? '--'); ?></td>
                        <td><strong>Contact</strong></td>
                        <td><?php echo htmlspecialchars($estdata->phone ?? '--'); ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- Estimate Details Table (no comment column) -->
            <table class="quotation-table">
                <thead>
                    <tr>
                        <th>Estimate Name</th>
                        <th>Quantity (kW)</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo htmlspecialchars($estdata->estimate_name ?? '--'); ?></td>
                        <td><?php echo htmlspecialchars($estdata->quantity ?? '0'); ?></td>
                        <td><?php echo number_format((float) ($estdata->price ?? 0), 2); ?></td>
                    </tr>
                </tbody>
                <tfoot>
                    <?php
// Main estimate price plus saved custom BOM line totals.
$summaryProducts = is_array($estdata->product_name)
    ? $estdata->product_name
    : (is_string($estdata->product_name) ? json_decode($estdata->product_name, true) : []);
$summaryProductsTotal = 0.0;
if (is_array($summaryProducts)) {
    foreach ($summaryProducts as $summaryProduct) {
        $summaryProductsTotal += (float) ($summaryProduct['quantity'] ?? 0) * (float) ($summaryProduct['price'] ?? 0);
    }
}
$subtotal = (float) ($estdata->price ?? 0) + $summaryProductsTotal;
$gstRate = (float) ($estdata->gst ?? 0);
$discount = (float) ($estdata->discount ?? 0);
$subsidy = (float) ($estdata->subsidy_amount ?? 0);
$solarStructureCharges = (float) ($estdata->solar_structure_charges ?? 0);
$gstBreakupLines = [];
$usesGlobalTax = false;

// Calculate totals
$gstAmount = (isset($estdata->gst_amount) && $estdata->gst_amount !== null && $estdata->gst_amount !== '')
    ? (float) $estdata->gst_amount
    : null;
if (!empty($estdata->gst_breakdown)) {
    $decodedGstBreakdown = is_array($estdata->gst_breakdown)
        ? $estdata->gst_breakdown
        : json_decode($estdata->gst_breakdown, true);
    if (is_array($decodedGstBreakdown)) {
        if ($gstAmount === null && isset($decodedGstBreakdown['gst_amount'])) {
            $gstAmount = (float) $decodedGstBreakdown['gst_amount'];
        }
        foreach (($decodedGstBreakdown['groups'] ?? []) as $group) {
            if (($group['tax_type'] ?? '') === 'global_tax') {
                $usesGlobalTax = true;
            }
            foreach (($group['lines'] ?? []) as $line) {
                $lineLabel = trim((string) ($line['label'] ?? ''));
                $lineAmount = (float) ($line['amount'] ?? 0);
                if ($lineLabel !== '' && strtoupper($lineLabel) !== 'GST' && $lineAmount > 0) {
                    $gstBreakupLines[] = [
                        'label' => $lineLabel,
                        'rate' => $line['rate'] ?? null,
                        'amount' => $lineAmount,
                    ];
                }
            }
        }
    }
}
if (!empty($estdata->product_name)) {
    $items = is_array($estdata->product_name)
        ? $estdata->product_name
        : (is_string($estdata->product_name) ? json_decode($estdata->product_name, true) : []);
    if (is_array($items)) {
        $productTaxBreakupLines = [];
        foreach ($items as $item) {
            $itemRate = (float) ($item['tax_rate'] ?? 0);
            $itemLabel = strtoupper(trim((string) ($item['tax_label'] ?? '')));
            $itemTaxable = (float) ($item['quantity'] ?? 0) * (float) ($item['price'] ?? 0);
            if ($itemRate <= 0 || $itemTaxable <= 0) {
                continue;
            }
            if (str_contains($itemLabel, 'CGST') && str_contains($itemLabel, 'SGST')) {
                $halfRate = $itemRate / 2;
                foreach (['CGST', 'SGST'] as $splitLabel) {
                    $productTaxBreakupLines[] = [
                        'label' => $splitLabel,
                        'rate' => $halfRate,
                        'amount' => ($itemTaxable * $halfRate) / 100,
                    ];
                }
            } else {
                $productTaxBreakupLines[] = [
                    'label' => str_contains($itemLabel, 'IGST') ? 'IGST' : 'GST',
                    'rate' => $itemRate,
                    'amount' => ($itemTaxable * $itemRate) / 100,
                ];
            }
        }
        if (!empty($productTaxBreakupLines)) {
            $aggregatedTaxLines = [];
            foreach ($productTaxBreakupLines as $taxLine) {
                $taxKey = ($taxLine['label'] ?? '') . '|' . number_format((float) ($taxLine['rate'] ?? 0), 4, '.', '');
                if (!isset($aggregatedTaxLines[$taxKey])) {
                    $aggregatedTaxLines[$taxKey] = [
                        'label' => $taxLine['label'] ?? '',
                        'rate' => $taxLine['rate'] ?? null,
                        'amount' => 0,
                    ];
                }
                $aggregatedTaxLines[$taxKey]['amount'] += (float) ($taxLine['amount'] ?? 0);
            }
            $gstBreakupLines = array_values($aggregatedTaxLines);
        }
    }
}
if (!empty($gstBreakupLines)) {
    $gstAmount = array_sum(array_map(fn ($line) => (float) ($line['amount'] ?? 0), $gstBreakupLines));
}
if ($gstAmount === null) {
    $gstAmount = $subtotal * ($gstRate / 100);
}
if (empty($gstBreakupLines) && $gstRate > 0 && $gstAmount > 0) {
    $gstBreakupLines = [
        ['label' => 'CGST', 'rate' => $gstRate / 2, 'amount' => $gstAmount / 2],
        ['label' => 'SGST', 'rate' => $gstRate / 2, 'amount' => $gstAmount / 2],
    ];
}
$totalPayable = $subtotal + $solarStructureCharges + $gstAmount - $discount; // exclude subsidy
$lendingCost = $totalPayable - $subsidy;
// $totalBeforeDiscount = $subtotal + $gstAmount + $solarStructureCharges;
// $finalTotal = $totalBeforeDiscount - $discount - $subsidy;
                    ?>
                    <tr>
                        <td colspan="2">Base Price</td>
                        <td><?php echo number_format($subtotal, 2); ?></td>
                    </tr>
                    <?php if ($solarStructureCharges > 0): ?>
                    <tr>
                        <td colspan="2">Solar Structure Charges</td>
                        <td><?php    echo number_format($solarStructureCharges, 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($gstBreakupLines)): ?>
                        <tr>
                            <td colspan="3"><strong><?php echo $usesGlobalTax ? 'Global Tax on Base Price' : 'Taxes on Bill of Materials (BOM Only)'; ?></strong></td>
                        </tr>
                        <?php foreach ($gstBreakupLines as $gstLine): ?>
                            <?php $lineRate = is_numeric($gstLine['rate'] ?? null) ? rtrim(rtrim(number_format((float) $gstLine['rate'], 2, '.', ''), '0'), '.') : ''; ?>
                            <tr>
                                <td colspan="2"><?php echo htmlspecialchars($gstLine['label']); ?><?php echo $lineRate !== '' ? ' (' . htmlspecialchars($lineRate) . '%)' : ''; ?></td>
                                <td><?php echo number_format((float) $gstLine['amount'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php elseif ($gstRate > 0 || $gstAmount > 0): ?>
                    <tr>
                        <td colspan="2">GST (<?php    echo $gstRate; ?>%)</td>
                        <td><?php    echo number_format($gstAmount, 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($discount > 0): ?>
                    <tr>
                        <td colspan="2">Discount</td>
                        <td>-<?php    echo number_format($discount, 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr style="font-weight: bold; border-top: 2px solid #000;">
                        <td colspan="2">Customer Payable Amount</td>
                        <td style="background-color: #1b365d; color: #fff;">
                            <?php echo number_format($totalPayable, 2); ?>
                        </td>
                    </tr>
                    <?php if ($subsidy > 0): ?>
                    <tr>
                        <td colspan="2">Subsidy</td>
                        <td>-<?php    echo number_format($subsidy, 2); ?></td>
                    </tr>
                    <tr style="font-weight: bold;">
                        <td colspan="2">Lending Cost Of Customer</td>
                        <td style="background-color: #1b365d; color: #fff;">
                            <?php    echo number_format($lendingCost, 2); ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <!-- <tr style="font-weight: bold; border-top: 2px solid #000;">
                        <td colspan="2">Total Amount</td>
                        <td><? // echo number_format($finalTotal, 2); ?></td>
                    </tr> -->
                </tfoot>
            </table>
            <?php if ($subsidy > 0): ?>
            <p style="font-size: 16px; margin-top: 2px; color: #555;"><strong>Note:</strong> Subsidy Amount to be
                credited in clients account.</p>
            <?php endif; ?>
            <!-- Extra Info and Side-by-side Comment / Bank Details -->
            <div class="extra-info">
                <table>
                    <tr>
                        <th>System Capacity</th>
                        <td><?php echo htmlspecialchars($estdata->quantity ?? '0'); ?> kW</td>
                    </tr>
                    <tr>
                        <th>Estimate Type</th>
                        <td><?php echo htmlspecialchars(ucfirst($estdata->type ?? '')); ?></td>
                    </tr>
                    <?php if (!empty($estdata->solar_meter_charges)): ?>
                    <tr>
                        <th>Solar Meter Charges</th>
                        <td><?php    echo htmlspecialchars(ucwords(str_replace('_', ' ', $estdata->solar_meter_charges))); ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>

            <?php
// Fetch bank details for current user
try {
    $bankModel = new \App\Models\BankModel();
    $currentUserId = session()->get('id') ?? (auth()->id() ?? 1);
    $bank = $bankModel->where('user_id', $currentUserId)->orderBy('id', 'DESC')->first();
} catch (\Throwable $e) {
    $bank = null;
}
$showBankDetails = (string) ($settings['show_bank_details'] ?? '1') !== '0';
$showCompanyQrCode = (string) ($settings['show_company_qr_code'] ?? '1') !== '0';
            ?>

            <!-- Comment + Bank Details Table -->
            <table class="info-table" style="margin-top:15px;">
                <thead>
                    <tr>
                        <th style="width: {{ $showBankDetails ? ($showCompanyQrCode ? 35 : 50) : ($showCompanyQrCode ? 75 : 100) }}%;">Comment</th>
                        <?php if ($showBankDetails): ?>
                        <th style="width: {{ $showCompanyQrCode ? 40 : 50 }}%;">Bank Details</th>
                        <?php endif; ?>
                        <?php if ($showCompanyQrCode): ?>
                        <th style="width: 25%;">QR Code</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <!-- Comment Column -->
                        <td style="vertical-align: top; background: #fafafa;">
                            <?php echo nl2br(htmlspecialchars($estdata->estimate_comment ?? ($estdata->comment ?? '--'))); ?>
                        </td>

                        <?php if ($showBankDetails): ?>
                        <!-- Bank Details Column -->
                        <td style="vertical-align: top; background: #fafafa;">
                            <?php if ($bank): ?>
                            <div><strong>Bank:</strong>
                                <?php    echo htmlspecialchars($bank['bank_name'] ?? '--'); ?>
                            </div>
                            <div><strong>Account Name:</strong>
                                <?php    echo htmlspecialchars($bank['account_name'] ?? '--'); ?>
                            </div>
                            <div><strong>Account No.:</strong>
                                <?php    echo htmlspecialchars($bank['account_number'] ?? '--'); ?>
                            </div>
                            <div><strong>IFSC:</strong>
                                <?php    echo htmlspecialchars($bank['ifsc_code'] ?? '--'); ?>
                            </div>
                            <div><strong>Branch:</strong>
                                <?php    echo htmlspecialchars($bank['branch_name'] ?? '--'); ?>
                            </div>
                            <?php else: ?>
                            <div style="color:#666;">No bank details available.</div>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                        <?php if ($showCompanyQrCode): ?>
                        <td style="vertical-align: top; background: #fafafa; text-align:center;">
                            <?php if (!empty($user['qr_code'])): ?>
                            <img src="<?php    echo htmlspecialchars(base_url('public/assets/img/profile/' . $user['qr_code'])); ?>"
                                alt="QR Code"
                                style="max-width:120px; max-height:120px; object-fit:contain; border:1px solid #ddd; border-radius:4px;">
                            <?php else: ?>
                            <div style="color:#666;">No QR code available.</div>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                </tbody>
            </table>




            <!-- Page Break for BOM (Removed to fit on single page) -->





        </div>
        @include('crm.estimates.partials.terms-conditions-pdf')
    </main>
</div>
