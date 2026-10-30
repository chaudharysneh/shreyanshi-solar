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
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Invoice - {{ $invoice->invoice_no }}</title>
    <style>
        .quotation-box,
        .quotation-box * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            color: #333;
            line-height: 1.4;
        }

        .quotation-box {
            max-width: 900px;
            margin: 0 auto;
            padding: 30px;
            background-color: #fff;
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
            width: 50%;
            height: auto;
            display: block;
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
            font-size: 14px;
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
            background-color: #3B5BDB;
            color: #fff;
        }

        .quotation-table tfoot td {
            font-weight: bold;
            text-align: right;
            padding: 8px 12px;
        }

        .quotation-table tbody td {
            padding: 10px 12px;
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
            flex: 1;
        }

        .page-break {
            page-break-before: always;
            margin-top: 40px;
        }

        .bom-section h2 {
            text-align: center;
            color: #19547B;
            margin-bottom: 30px;
            text-decoration: underline;
            font-size: 20px;
        }

        .highlight-bg {
            background-color: #3B5BDB;
            color: #fff;
        }

        .note-text {
            font-size: 15px;
            margin-top: 2px;
            color: #555;
        }

        hr {
            border: none;
            border-top: 1px solid #ddd;
            margin: 15px 0;
        }

        .qr-code-img {
            max-width: 120px;
            max-height: 120px;
            object-fit: contain;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <div class="quotation-box">
        <!-- Header -->
        <div class="quotation-header">
            <table>
                <tr>
                    <td class="company-logo" style="width: 50%;">
                        @if (isset($settings['company_logo_path']))
                            <img src="{{ base_url('storage/' . $settings['company_logo_path']) }}" alt="Company Logo" style="max-width: 300px; width: 50%; height: auto;">
                        @elseif ($user && $user->company_logo)
                            <img src="{{ base_url('storage/' . $user->company_logo) }}" alt="Company Logo" style="max-width: 300px; width: 50%; height: auto;">
                        @else
                            <img src="{{ base_url('assets/img/logo.jpg') }}" alt="Company Logo" style="max-width: 300px; width: 50%; height: auto;">
                        @endif
                    </td>
                    <td class="quotation-title" style="width: 50%;">
                        <div style="line-height:22px;color:#000">
                            <strong
                                style="font-size:18px;color:#000">{{ $user->company_name ?? 'Company Name' }}</strong><br>
                            {{ $user->address ?? '--' }}<br>
                            {{ $user->country ?? '--' }}, {{ $user->state ?? '--' }}, {{ $user->city ?? '--' }}
                            {{ $user->pincode ?? '--' }}<br>
                            {{ $user->contact ?? '--' }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <hr>

        <!-- Invoice Info -->
        <div class="flex-between">
            <div style="font-weight:700; font-size:15px;">Invoice no.: #{{ $invoice->invoice_no }}</div>
            <div class="center-text" style="font-size:16px;">TAX INVOICE</div>
            <div style="font-weight:700; font-size:15px;">Date: {{ optional($invoice->invoice_date)->format('Y-m-d') }}
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
                    <td>{{ $invoice->customer->name ?? '--' }}</td>
                    <td><strong>Email</strong></td>
                    <td>{{ $invoice->customer->email ?? '--' }}</td>
                </tr>
                <tr>
                    <td><strong>Address</strong></td>
                    <td>{{ $invoice->customer->address ?? '--' }}</td>
                    <td><strong>Contact</strong></td>
                    <td>{{ $invoice->customer->contact ?? '--' }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Invoice Details Table -->
        <table class="quotation-table" style="border: 1px solid #333; border-collapse: collapse; width: 100%; font-family: sans-serif; margin-bottom: 20px;">
            <thead style="background-color: #52866A; color: #fff;">
                <tr>
                    <th style="padding: 10px 8px; font-weight: bold; font-size: 13px; border: 1px solid #333; text-align: left; background-color: #52866A !important; color: #ffffff !important;">Invoice Name</th>
                    <th style="padding: 10px 8px; font-weight: bold; font-size: 13px; border: 1px solid #333; text-align: left; background-color: #52866A !important; color: #ffffff !important;">Quantity (kW)</th>
                    <th style="padding: 10px 8px; font-weight: bold; font-size: 13px; border: 1px solid #333; text-align: left; background-color: #52866A !important; color: #ffffff !important;">Price</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 10px 8px; border: 1px solid #333; color: #333; font-family: sans-serif;">{{ $invoice->invoice_name ?? '--' }}</td>
                    <td style="padding: 10px 8px; border: 1px solid #333; color: #333; font-family: sans-serif;">{{ $invoice->quantity ?? '0' }}</td>
                    <td style="padding: 10px 8px; border: 1px solid #333; color: #333; font-family: sans-serif;">{{ number_format((float) ($invoice->price ?? 0), 2) }}</td>
                </tr>
            </tbody>
            <tfoot>
                @php
                    $subtotal = (float) ($invoice->price ?? 0);
                    $gstRate = (float) ($invoice->gst ?? 0);
                    $discount = (float) ($invoice->discount ?? 0);
                    $subsidy = (float) ($invoice->subsidy_amount ?? 0);
                    $solarStructureCharges = (float) ($invoice->solar_structure_charges ?? 0);
                    $gstBreakdown = is_array($invoice->gst_breakdown ?? null)
                        ? $invoice->gst_breakdown
                        : (json_decode((string) ($invoice->gst_breakdown ?? ''), true) ?: []);
                    $usesGlobalTax = collect($gstBreakdown['groups'] ?? [])->contains(fn ($group) => ($group['tax_type'] ?? '') === 'global_tax');
                    $gstAmount = isset($invoice->gst_amount)
                        ? (float) $invoice->gst_amount
                        : $subtotal * ($gstRate / 100);
                    $totalPayable = $subtotal + $solarStructureCharges + $gstAmount - $discount;
                    $lendingCost = $totalPayable - $subsidy;
                @endphp
                <tr>
                    <td style="border: 1px solid #333; background-color: #fff;"></td>
                    <td style="text-align: right; border: 1px solid #333; font-weight: normal; padding: 8px 12px; color: #333; font-family: sans-serif;">Base Price</td>
                    <td style="text-align: right; border: 1px solid #333; padding: 8px 12px; color: #333; font-family: sans-serif;">{{ number_format($subtotal, 2) }}</td>
                </tr>
                @if($solarStructureCharges > 0)
                <tr>
                    <td style="border: 1px solid #333; background-color: #fff;"></td>
                    <td style="text-align: right; border: 1px solid #333; font-weight: normal; padding: 8px 12px; color: #333; font-family: sans-serif;">Solar Structure Charges</td>
                    <td style="text-align: right; border: 1px solid #333; padding: 8px 12px; color: #333; font-family: sans-serif;">{{ number_format($solarStructureCharges, 2) }}</td>
                </tr>
                @endif
                @if($gstRate > 0)
                <tr>
                    <td style="border: 1px solid #333; background-color: #fff;"></td>
                    <td style="text-align: right; border: 1px solid #333; font-weight: normal; padding: 8px 12px; color: #333; font-family: sans-serif;">{{ $usesGlobalTax ? 'Global Tax' : 'BOM Tax' }} ({{ $gstRate }}%)</td>
                    <td style="text-align: right; border: 1px solid #333; padding: 8px 12px; color: #333; font-family: sans-serif;">{{ number_format($gstAmount, 2) }}</td>
                </tr>
                @endif
                @if($discount > 0)
                <tr>
                    <td style="border: 1px solid #333; background-color: #fff;"></td>
                    <td style="text-align: right; border: 1px solid #333; font-weight: normal; padding: 8px 12px; color: #333; font-family: sans-serif;">Discount</td>
                    <td style="text-align: right; border: 1px solid #333; padding: 8px 12px; color: #333; font-family: sans-serif;">-{{ number_format($discount, 2) }}</td>
                </tr>
                @endif
                <tr>
                    <td style="border: 1px solid #333; background-color: #fff;"></td>
                    <td style="text-align: right; border: 1px solid #333; font-weight: bold; padding: 8px 12px; color: #333; font-family: sans-serif;">Customer Payable Amount</td>
                    <td style="text-align: right; border: 1px solid #333; padding: 8px 12px; background-color: #52866A !important; color: #ffffff !important; font-weight: bold; font-family: sans-serif;">{{ number_format($totalPayable, 2) }}</td>
                </tr>
                @if($subsidy > 0)
                <tr>
                    <td style="border: 1px solid #333; background-color: #fff;"></td>
                    <td style="text-align: right; border: 1px solid #333; font-weight: normal; padding: 8px 12px; color: #333; font-family: sans-serif;">Subsidy</td>
                    <td style="text-align: right; border: 1px solid #333; padding: 8px 12px; color: #333; font-family: sans-serif;">-{{ number_format($subsidy, 2) }}</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #333; background-color: #fff;"></td>
                    <td style="text-align: right; border: 1px solid #333; font-weight: bold; padding: 8px 12px; color: #333; font-family: sans-serif;">Lending Cost Of Customer</td>
                    <td style="text-align: right; border: 1px solid #333; padding: 8px 12px; background-color: #52866A !important; color: #ffffff !important; font-weight: bold; font-family: sans-serif;">{{ number_format($lendingCost, 2) }}</td>
                </tr>
                @endif
            </tfoot>
        </table>

        @if($subsidy > 0)
            <p class="note-text"><strong>Note:</strong> Subsidy Amount to be credited in clients account.</p>
        @endif

        <!-- Extra Info -->
        <div class="extra-info">
            <table>
                <tr>
                    <th style="width: 40%;">System Capacity</th>
                    <td>{{ $invoice->quantity ?? '0' }} kW</td>
                </tr>
                <tr>
                    <th>Type</th>
                    <td>{{ ucfirst($invoice->type ?? '') }}</td>
                </tr>
                @if(!empty($invoice->solar_meter_charges))
                    <tr>
                        <th>Solar Meter Charges</th>
                        <td>{{ ucwords(str_replace('_', ' ', $invoice->solar_meter_charges)) }}</td>
                    </tr>
                @endif
            </table>
        </div>

        <!-- Comment + Bank Details Table -->
        <table class="info-table" style="margin-top:20px; border: 1px solid #eee;">
            <thead>
                <tr>
                    <th style="width: 35%; background-color: #f8f9fa; color: #333; border: 1px solid #ddd;">Comment</th>
                    <th style="width: 40%; background-color: #f8f9fa; color: #333; border: 1px solid #ddd;">Bank Details
                    </th>
                    <th style="width: 25%; background-color: #f8f9fa; color: #333; border: 1px solid #ddd;">QR Code</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="vertical-align: top; padding: 15px; background: #fff; border: 1px solid #eee;">
                        <div style="font-size: 13px; color: #555;">{!! nl2br(e($invoice->comment ?? '--')) !!}</div>
                    </td>
                    <td style="vertical-align: top; padding: 15px; background: #fff; border: 1px solid #eee;">
                        @if(!empty($settings['bank_name']) || !empty($settings['account_number']))
                            <div style="font-size: 13px; line-height: 1.6;">
                                <strong>Bank:</strong> {{ $settings['bank_name'] ?? '--' }}<br>
                                <strong>A/c Name:</strong> {{ $settings['account_name'] ?? '--' }}<br>
                                <strong>A/c No.:</strong> {{ $settings['account_number'] ?? '--' }}<br>
                                <strong>IFSC:</strong> {{ $settings['ifsc_code'] ?? '--' }}<br>
                                <strong>Branch:</strong> {{ $settings['branch_name'] ?? '--' }}
                            </div>
                        @else
                            <div style="color:#999; font-size: 12px;">No bank details available.</div>
                        @endif
                    </td>
                    <td
                        style="vertical-align: top; padding: 15px; background: #fff; border: 1px solid #eee; text-align:center;">
                        @php
                            $qrCodePath = \App\Models\Setting::where('key', 'company_qr_code_path')->value('value');
                        @endphp
                        @if($qrCodePath)
                            <img src="{{ base_url('storage/' . $qrCodePath) }}" alt="QR Code"
                                style="max-width: 100px; height: auto;">
                        @else
                            <div style="color:#999; font-size: 12px;">No QR code available.</div>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Page Break for BOM -->
        <div style="page-break-before: always;"></div>

        <!-- BOM Section -->
        <div style="margin-top: 20px;">
            <h2 style="text-align: center; color: #19547B; margin-bottom: 30px; text-decoration: underline;">
                BILL OF MATERIALS (BOM)
            </h2>
            <table class="quotation-table" style="border: 1px solid #333; border-collapse: collapse; width: 100%; font-family: sans-serif;">
                <thead style="background-color: #52866A; color: #fff;">
                    <tr>
                        <th style="padding: 10px 8px; font-weight: bold; font-size: 13px; border: 1px solid #333; text-align: left; background-color: #52866A !important; color: #ffffff !important; width: 35%;">Product Name</th>
                        <th style="padding: 10px 8px; font-weight: bold; font-size: 13px; border: 1px solid #333; text-align: left; background-color: #52866A !important; color: #ffffff !important; width: 65%;">Specifications</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $allproduct = is_array($invoice->product_name) ? $invoice->product_name : json_decode($invoice->product_name, true);
                    @endphp
                    @if(is_array($allproduct) && !empty($allproduct))
                        @foreach($allproduct as $item)
                            @php
                                $product_id = $item['product_id'] ?? null;
                                $product_name_display = $item['name'] ?? '';
                                $product_quantity = $item['quantity'] ?? 0;
                                $product_category_makes = $item['category_name'] ?? '';

                                // Find product details from master list
                                $full_product_details = null;
                                foreach ($product_data as $prod_detail) {
                                    if ($prod_detail['id'] == $product_id) {
                                        $full_product_details = $prod_detail;
                                        break;
                                    }
                                }

                                // Robust product name fallback
                                if (empty(trim($product_name_display)) && $full_product_details) {
                                    $product_name_display = $full_product_details['product_name'] ?? '';
                                }
                                if (empty(trim($product_name_display))) {
                                    $product_name_display = 'Product name not found';
                                }
                                $product_name_display = ucwords(strtolower($product_name_display));

                                // Robust Make (category) fallback
                                if (empty(trim($product_category_makes)) && $full_product_details && !empty($full_product_details['categories'])) {
                                    $firstCat = reset($full_product_details['categories']);
                                    $product_category_makes = $firstCat['name'] ?? '';
                                }

                                $specifications = [];
                                if (!empty($product_category_makes)) {
                                    $specifications[] = '<strong>Make: </strong>' . e($product_category_makes);
                                }
                                if (!empty($product_quantity)) {
                                    $specifications[] = '<strong>Quantity: </strong>' . e($product_quantity);
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
                                    $specifications[] = '<strong>Technology: </strong>' . e($techVal);
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
                                    $specifications[] = '<strong>Warranty: </strong>' . e($warVal);
                                }

                                if ($full_product_details && !empty($full_product_details['height'])) {
                                    $specifications[] = '<strong>Height: </strong>' . e($full_product_details['height']);
                                }
                                if ($full_product_details && !empty($full_product_details['fitting_material'])) {
                                    $specifications[] = '<strong>Fitting Material: </strong>' . e($full_product_details['fitting_material']);
                                }
                                if ($full_product_details && !empty($full_product_details['fitting_type'])) {
                                    $specifications[] = '<strong>Fitting Type: </strong>' . e($full_product_details['fitting_type']);
                                }
                                if ($full_product_details && !empty($full_product_details['thickness'])) {
                                    $specifications[] = '<strong>Thickness: </strong>' . e($full_product_details['thickness']);
                                }
                                if ($full_product_details && !empty($full_product_details['size_of_pipe'])) {
                                    $specifications[] = '<strong>Size of Pipe: </strong>' . e($full_product_details['size_of_pipe']);
                                }
                                if ($full_product_details && !empty($full_product_details['capacity'])) {
                                    $specifications[] = '<strong>Capacity: </strong>' . e($full_product_details['capacity']);
                                }

                                $specifications_html = implode('<br>', $specifications);
                            @endphp
                            <tr>
                                <td style="padding: 10px 8px; border: 1px solid #333; color: #333; font-weight: bold; font-size: 13px; font-family: sans-serif; vertical-align: middle;">{{ $product_name_display }}</td>
                                <td style="padding: 10px 8px; border: 1px solid #333; font-size: 13px; font-family: sans-serif; line-height: 1.5; vertical-align: middle;">{!! $specifications_html !!}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="2" style="text-align: center; color: #666; padding: 15px; border: 1px solid #333;">No products added to this invoice</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>
