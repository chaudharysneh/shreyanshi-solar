<?php
$blade = <<<'EOD'
@php
if (!function_exists('numberToWords')) {
    function numberToWords($num) {
        $num = (int) $num;
        $words = [];
        $list1 = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $list2 = ['', 'Ten', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
        if ($num == 0) return 'Zero';
        $crores = floor($num / 10000000); $num -= $crores * 10000000;
        $lakhs = floor($num / 100000); $num -= $lakhs * 100000;
        $thousands = floor($num / 1000); $num -= $thousands * 1000;
        $hundreds = floor($num / 100); $num -= $hundreds * 100;
        $tens = floor($num / 10); $ones = $num % 10;
        if ($crores > 0) $words[] = numberToWords($crores) . " Crore";
        if ($lakhs > 0) $words[] = numberToWords($lakhs) . " Lakh";
        if ($thousands > 0) $words[] = numberToWords($thousands) . " Thousand";
        if ($hundreds > 0) $words[] = numberToWords($hundreds) . " Hundred";
        if ($tens > 0 || $ones > 0) {
            if ($tens < 2) $words[] = $list1[$tens * 10 + $ones];
            else { $words[] = $list2[$tens]; if ($ones > 0) $words[] = $list1[$ones]; }
        }
        return implode(' ', $words);
    }
}
if (!function_exists('normalize_pdf_image')) {
    function normalize_pdf_image($path)
    {
        $path = trim((string) $path);
        if ($path === '') return '';
        if (strpos($path, 'data:image') === 0) return $path;
        $cleanPath = $path;
        if (preg_match('/^https?:\/\//i', $path)) {
            $urlParts = parse_url($path);
            $cleanPath = isset($urlParts['path']) ? ltrim($urlParts['path'], '/\\') : $path;
        }
        $cleanPath = preg_replace('#^(?:public|public_html|storage|app/public|storage/app/public)(?:/|\\\\)+#i', '', $cleanPath);
        $cleanPath = ltrim($cleanPath, '/\\');
        $candidates = [
            $path,
            storage_path('app/public/' . $cleanPath),
            storage_path('app/' . $cleanPath),
            public_path('storage/' . $cleanPath),
            public_path($cleanPath),
            base_path('public_html/storage/' . $cleanPath),
            base_path('public_html/' . $cleanPath),
            base_path('public/storage/' . $cleanPath),
        ];
        $filename = basename($cleanPath);
        if ($filename !== '') {
            $candidates[] = storage_path('app/public/bom-products/' . $filename);
            $candidates[] = public_path('storage/bom-products/' . $filename);
        }
        foreach (array_unique($candidates) as $candidate) {
            if ($candidate && @file_exists($candidate) && @is_file($candidate)) {
                $imgData = @file_get_contents($candidate);
                if ($imgData !== false) {
                    $ext = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
                    $mime = ($ext === 'png') ? 'image/png' : 'image/jpeg';
                    return 'data:' . $mime . ';base64,' . base64_encode($imgData);
                }
            }
        }
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
        return $urlToTry;
    }
}
@endphp
@php
    $doc = $estimate ?? $estdata ?? null;
    $customer = $doc->customer ?? null;
    $preparedUser = $doc->user ?? $doc->creator ?? $profileUser ?? $user ?? null;
    $clientName = trim((string) ($customer->name ?? $doc->name ?? ''));
    $clientAddress = trim((string) ($customer->address ?? $doc->address ?? ''));
    $companySettings = isset($companySettings) && is_iterable($companySettings) ? collect($companySettings) : collect($companySettings ?? []);
    $valueOr = static function ($value, string $fallback = '') {
        $value = trim((string) $value);
        return $value !== '' && !in_array(strtolower($value), ['--', 'address', 'client name', 'n/a', 'na'], true) ? $value : $fallback;
    };
    $money = static function ($value) {
        return '&#8377; ' . number_format((float) $value, 2);
    };
    $plainNumber = static function ($value, int $decimals = 0) {
        return rtrim(rtrim(number_format((float) $value, $decimals), '0'), '.');
    };
    $companyName = $valueOr($companySettings['company_name'] ?? data_get($preparedUser, 'company') ?? data_get($preparedUser, 'company_name'), 'Rising Green Energy');
    $clientName = $valueOr($clientName, 'Valued Customer');
    $clientAddress = $valueOr($clientAddress, 'the project site');
    $preparedName = $valueOr(data_get($preparedUser, 'name'), $companyName . ' Team');
    $preparedTitle = $valueOr(data_get($preparedUser, 'job_title'), 'Solar Consultant');
    $companyPhone = $valueOr($companySettings['phone'] ?? data_get($preparedUser, 'phone') ?? data_get($preparedUser, 'mobile'));
    $companyEmail = $valueOr($companySettings['email'] ?? data_get($preparedUser, 'email'));
    $companyWebsite = $valueOr($companySettings['website'] ?? data_get($preparedUser, 'website'));
    
    $capacityValue = (float) ($doc->quantity ?? 0);
    $capacity = $capacityValue > 0 ? $plainNumber($capacityValue, 1) . ' kWp' : 'as proposed';
    
    $dailyGenerationValue = $capacityValue > 0 ? $capacityValue * 4.3 : 0;
    $monthlyGenerationValue = $dailyGenerationValue * 30;
    $annualGenerationValue = $dailyGenerationValue * 365;
    $dailyGeneration = $dailyGenerationValue > 0 ? $plainNumber($dailyGenerationValue, 1) : 'As per system size';
    $monthlyGeneration = $monthlyGenerationValue > 0 ? $plainNumber($monthlyGenerationValue, 0) : 'As per system size';
    $annualGeneration = $annualGenerationValue > 0 ? $plainNumber($annualGenerationValue, 0) : 'As per system size';
    
    $co2OffsetValue = $capacityValue > 0 ? $capacityValue * 1.01 * 25 : 0;
    $coalSavedValue = $capacityValue > 0 ? $capacityValue * 1.259 : 0;
    $treesValue = $co2OffsetValue > 0 ? $co2OffsetValue * 45 : 0;
    $co2Offset = $co2OffsetValue > 0 ? $plainNumber($co2OffsetValue, 1) : 'As per system size';
    $coalSaved = $coalSavedValue > 0 ? $plainNumber($coalSavedValue, 1) : 'As per system size';
    $treesEquivalent = $treesValue > 0 ? $plainNumber($treesValue, 0) : 'As per system size';
    
    $baseSystemValue = (float) ($doc->price ?? $doc->total ?? 0);
    $gstValue = (float) ($doc->gst_amount ?? 0);
    $solarStructureValue = (float) ($doc->solar_structure_charges ?? 0);
    $grossValue = $baseSystemValue + $solarStructureValue + $gstValue + ($solarStructureValue * 0.18);
    $subsidyValue = (float) ($doc->subsidy_amount ?? 0);
    
    $gstRate = (float) ($doc->gst ?? 0);
    $qtyForCalc = $capacityValue > 0 ? $capacityValue : 3.3;
    $rate1 = $baseSystemValue / $qtyForCalc;
    $rate2 = $solarStructureValue / $qtyForCalc;
    $actualGstRate = $baseSystemValue > 0 ? round(($gstValue / $baseSystemValue) * 100, 1) : 0;
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Estimate {{ $doc->estimate_no ?? 'QT-000150' }}</title>
    <style>
        * { box-sizing: border-box; }
        @page { margin: 0; }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            font-size: 12px;
            margin: 0;
            padding: 0;
            line-height: 1.35;
        }
        .page {
            page-break-after: always;
            position: relative;
            min-height: 100vh;
        }
        .page:last-of-type {
            page-break-after: auto;
        }
        .quote-page {
            padding: 40px;
        }
        .proposal-page {
            padding: 0;
        }
        
        /* Proposal Header */
        .prop-header {
            background-color: #1b365d;
            color: #ffffff;
            padding: 30px 40px 20px 40px;
            border-bottom: 4px solid #f39c12;
        }
        .prop-header h1 {
            margin: 0;
            font-size: 26px;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .prop-header p {
            margin: 5px 0 0 0;
            font-size: 13px;
            font-style: italic;
            color: #e0e0e0;
        }
        
        /* Section Title */
        .section-title {
            color: #1b365d;
            font-size: 18px;
            font-weight: bold;
            margin: 15px 40px 10px 40px;
            padding-left: 10px;
            border-left: 4px solid #f39c12;
        }
        
        /* Content Body */
        .content {
            padding: 0 40px 10px 40px;
            text-align: justify;
        }
        .content p {
            margin-bottom: 8px;
        }
        
        /* Quote Table */
        .quote-table { 
            width: 100%; 
            border-collapse: collapse; 
            font-size: 11px; 
            margin-bottom: 10px; 
            table-layout: fixed;
            word-wrap: break-word;
        }
        .quote-table th { padding: 8px 5px; text-align: left; font-weight: normal; background-color: #333; color: #fff; border: 1px solid #333; }
        .quote-table td { padding: 10px 5px; text-align: left; border-bottom: 1px solid #ddd; vertical-align: top; }
        .quote-table .right { text-align: right; }
        
        .quote-table th:nth-child(1) { width: 5%; }
        .quote-table th:nth-child(2) { width: 35%; }
        .quote-table th:nth-child(3) { width: 10%; }
        .quote-table th:nth-child(4) { width: 8%; }
        .quote-table th:nth-child(5) { width: 10%; }
        .quote-table th:nth-child(6) { width: 10%; }
        .quote-table th:nth-child(7) { width: 10%; }
        .quote-table th:nth-child(8) { width: 12%; }
        
        /* Footer */
        .prop-footer {
            position: absolute;
            bottom: 20px;
            left: 40px;
            right: 40px;
            font-size: 10px;
            color: #888;
            border-top: 1px solid #eee;
            padding-top: 10px;
            display: table;
            width: calc(100% - 80px);
        }
        
        ul {
            padding-left: 20px;
            margin-top: 5px;
            margin-bottom: 5px;
        }
        li {
            margin-bottom: 5px;
        }
        
        /* Component Grid */
        .component-row {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .comp-img {
            display: table-cell;
            width: 30%;
            vertical-align: middle;
            text-align: center;
        }
        .comp-img img {
            max-width: 150px;
            max-height: 150px;
        }
        .comp-details {
            display: table-cell;
            width: 70%;
            vertical-align: middle;
            padding-left: 20px;
        }
        .comp-title {
            font-size: 16px;
            font-weight: bold;
            color: #1b365d;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .comp-table {
            width: 100%;
            font-size: 13px;
            border-collapse: collapse;
        }
        .comp-table td {
            padding: 3px 0;
            vertical-align: top;
        }
        .comp-table td:first-child {
            width: 35%;
            font-weight: bold;
        }
        
        .placeholder-box {
            border: 2px dashed #ccc;
            background-color: #f9f9f9;
            text-align: center;
            padding: 50px 20px;
            color: #888;
            font-style: italic;
            margin: 20px 0;
        }
        
        .full-width-image {
            width: 100%;
            max-width: 100%;
            height: auto;
            display: block;
            margin: 20px auto;
        }
    </style>
</head>
<body>

    <!-- Page 1: Quote -->
    <section class="page quote-page">
        <div style="width: 100%; margin-bottom: 20px; display: table;">
            <div style="display: table-cell; width: 60%; vertical-align: top;">
                <div style="font-size: 14px; font-weight: bold; margin-bottom: 5px;">{{ $companyName }}</div>
                <div style="font-size: 11px; line-height: 1.3;">
                    PM SURYAGHAR EMP NO. NPDG-164<br>
                    316 SUNTRADE CENTER RAMNAGAR Surat Gujarat 395005<br>
                    India<br>
                    Surat Gujarat 395005<br>
                    India<br>
                    GSTIN 24ABBFR1974L1ZY<br>
                    {{ $companyPhone }}<br>
                    {{ $companyEmail }}
                </div>
            </div>
            <div style="display: table-cell; width: 40%; vertical-align: top; text-align: right;">
                <div style="font-size: 32px; font-weight: normal; margin-bottom: 5px; color: #000;">Quote</div>
                <div style="font-size: 14px; font-weight: bold; color: #000;"># {{ $doc->estimate_no ?? 'QT-000150' }}</div>
            </div>
        </div>

        <div style="width: 100%; margin-bottom: 20px; display: table;">
            <div style="display: table-cell; width: 50%; vertical-align: top;">
                <div style="font-size: 12px; color: #555; margin-bottom: 3px;">Bill To</div>
                <div style="font-size: 13px; font-weight: bold;">{{ $clientName }}</div>
            </div>
            <div style="display: table-cell; width: 50%; vertical-align: top; text-align: right;">
                <div style="font-size: 12px;">Quote Date : &nbsp;&nbsp;&nbsp;&nbsp; {{ $doc->estimate_date ? \Carbon\Carbon::parse($doc->estimate_date)->format('d/m/Y') : date('d/m/Y') }}</div>
            </div>
        </div>
        
        <div style="font-size: 12px; margin-bottom: 20px;">
            Place Of Supply: Gujarat (24)
        </div>

        <table class="quote-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Item &amp; Description</th>
                    <th class="right">HSN/SAC</th>
                    <th class="right">Qty</th>
                    <th class="right">Rate</th>
                    <th class="right">CGST</th>
                    <th class="right">SGST</th>
                    <th class="right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>
                        SUPPLY AND INSTALLATION<br>
                        <span style="color:#555; font-size:10px;">{{ $capacity }} SOLAR ROOFTOP SYSTEM</span>
                    </td>
                    <td class="right">854140</td>
                    <td class="right">{{ number_format($qtyForCalc, 2) }}</td>
                    <td class="right">{{ number_format($rate1, 2) }}</td>
                    <td class="right">{{ number_format($gstValue/2, 2) }}<br><span style="font-size:9px;color:#555;">{{ $actualGstRate/2 }}%</span></td>
                    <td class="right">{{ number_format($gstValue/2, 2) }}<br><span style="font-size:9px;color:#555;">{{ $actualGstRate/2 }}%</span></td>
                    <td class="right">{{ number_format($baseSystemValue, 2) }}</td>
                </tr>
                @if($solarStructureValue > 0)
                <tr>
                    <td>2</td>
                    <td>
                        STRUCTURE FABRICATION WORK<br>
                        <span style="color:#555; font-size:10px;">{{ $capacity }} SOLAR ROOFTOP SYSTEM</span>
                    </td>
                    <td class="right">998873</td>
                    <td class="right">{{ number_format($qtyForCalc, 2) }}</td>
                    <td class="right">{{ number_format($rate2, 2) }}</td>
                    <td class="right">{{ number_format($solarStructureValue * 0.09, 2) }}<br><span style="font-size:9px;color:#555;">9%</span></td>
                    <td class="right">{{ number_format($solarStructureValue * 0.09, 2) }}<br><span style="font-size:9px;color:#555;">9%</span></td>
                    <td class="right">{{ number_format($solarStructureValue, 2) }}</td>
                </tr>
                @endif
            </tbody>
        </table>

        <div style="width: 100%; display: table;">
            <div style="display: table-cell; width: 50%;"></div>
            <div style="display: table-cell; width: 50%;">
                <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                    <tr>
                        <td style="padding: 5px; text-align: right; width: 70%;">Sub Total</td>
                        <td style="padding: 5px; text-align: right; width: 30%;">{{ number_format($baseSystemValue + $solarStructureValue, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px; text-align: right;">CGST{{ $actualGstRate/2 + 0 }} ({{ $actualGstRate/2 + 0 }}%)</td>
                        <td style="padding: 5px; text-align: right;">{{ number_format($gstValue/2, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px; text-align: right;">SGST{{ $actualGstRate/2 + 0 }} ({{ $actualGstRate/2 + 0 }}%)</td>
                        <td style="padding: 5px; text-align: right;">{{ number_format($gstValue/2, 2) }}</td>
                    </tr>
                    @if($solarStructureValue > 0)
                    <tr>
                        <td style="padding: 5px; text-align: right;">CGST9 (9%)</td>
                        <td style="padding: 5px; text-align: right;">{{ number_format($solarStructureValue * 0.09, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px; text-align: right;">SGST9 (9%)</td>
                        <td style="padding: 5px; text-align: right;">{{ number_format($solarStructureValue * 0.09, 2) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td style="padding: 10px 5px; text-align: right; background-color: #f2f2f2; font-weight: bold; font-size: 13px;">Total</td>
                        <td style="padding: 10px 5px; text-align: right; background-color: #f2f2f2; font-weight: bold; font-size: 13px;"><span style="font-family: 'DejaVu Sans', sans-serif;">&#8377;</span>{{ number_format($grossValue, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div style="font-size: 12px; margin-top: 15px;">
            Total In Words: <strong>Indian Rupee {{ numberToWords($grossValue) }} Only</strong>
        </div>

        <div style="margin-top: 30px; font-size: 11px;">
            <div style="font-size: 14px; color: #333; margin-bottom: 10px;">Notes</div>
            ALL BILL OF MATERIAL USED FOR INSTALLATION ARE AS PER PM SURYAGHAR GUIDLINE GOVERMENT CRITERIA AND AS PER ISI STANDER WITH AUTHENTIC CERTIFICATES<br><br>
            DOCUMENTS LIST:<br>
            ELECTRICITY BILL<br>
            VERA BILL<br>
            ADHARCARD<br>
            PANCARD<br>
            CANCELCHEQUE<br>
            CONCENT LETTER (FOR COMMON TERRACE)
        </div>

        <div style="margin-top: 30px; font-size: 11px;">
            <div style="font-size: 14px; color: #333; margin-bottom: 10px;">Terms &amp; Conditions</div>
            - ESTIMATE VALID FOR 10DAYS ONLY<br>
            - GEDA REGISTRATION FEES EXTRA AS PER ACTUAL (IF APPLICABLE)<br>
            - METER CHARGES EXTRA AS PER ACTUAL DGVCL/TORRENT (IF APPLICABLE)<br>
            - SUBSIDY IN CLIENT ACCOUNT {{ $plainNumber($subsidyValue, 0) }}/- <br>
            - AVG MONTHLY SAVING {{ $monthlyGeneration }} UNITS GENERATION WHICH SAVES UP TO {{ $plainNumber($monthlyGeneration * 8, 0) }}/-
        </div>
    </section>

    <!-- Page 2: Quote Signature -->
    <section class="page quote-page">
        <div style="margin-top: 50px; font-size: 12px;">
            Authorized Signature _____________________________
        </div>
    </section>

    <!-- Page 3: Introduction -->
    <section class="page proposal-page">
        <div class="prop-header" style="padding: 0; height: 15px;">
        </div>
        
        <div class="section-title">1. Introduction Page</div>
        
        <div class="content">
            <p>Dear <strong>{{ $clientName }}</strong>,</p>
            <p>Thank you for giving <strong>{{ $companyName }}</strong> the opportunity to present this customized solar energy proposal for your property located at <strong>{{ $clientAddress }}</strong>.</p>
            <p>With electricity tariffs rising consistently year after year, switching to solar is no longer just an environmental choice-it is one of the smartest and safest financial investments available today. At <strong>{{ $companyName }}</strong>, we combine premium Tier-1 components, precise engineering, and seamless multi-stage execution to ensure your transition to clean energy is entirely effortless and highly profitable.</p>
            <p>Enclosed, you will find a comprehensive breakdown of your custom tailored solar solution, expected annual generation metrics, long-term financial returns, and an end-to-end implementation roadmap.</p>
            <p>As energy costs continue to rise and environmental sustainability becomes a growing priority, rooftop solar photovoltaic (PV) systems offer an efficient, reliable, and cost-effective solution for meeting electricity demands. By harnessing clean and renewable solar energy, organizations and homeowners can significantly reduce electricity expenses, decrease dependence on conventional power sources, and contribute to a greener future.</p>
            <p>This proposal presents a comprehensive rooftop solar solution designed to maximize energy generation based on the available roof area and site conditions. The proposed system incorporates high-quality solar modules, advanced inverters, and industry-standard installation practices to ensure optimal performance, safety, and long-term reliability.</p>
            <p>In addition to reducing operational costs, the installation of a rooftop solar system supports environmental responsibility by lowering carbon emissions and promoting the use of renewable energy. With minimal maintenance requirements and a long service life, the proposed solar power system represents a sustainable investment that delivers both economic and environmental benefits for years to come.</p>
            <div style="position: absolute; bottom: 70px; left: 40px;">
                <p style="margin-bottom: 5px;">Best Regards,</p>
                <p><strong>{{ $preparedName }}</strong><br>
                {{ $companyName }} | {{ $companyPhone }} | {{ $companyEmail }}</p>
            </div>
        </div>
        
        <div class="prop-footer">
            <div style="float: left;"></div>
            <div style="float: right;">Page 1 of 8</div>
        </div>
    </section>

    <!-- Page 4: Diagram & How it works -->
    <section class="page proposal-page">
        <div class="section-title">2. Technical Line Diagram Layout</div>
        <div class="content">
            <p>The layout below illustrates the seamless logical electrical connection map from production to grid export.</p>
            <div style="text-align: center; font-style: italic; color: #555; border: 1px dashed #ccc; padding: 10px; margin-bottom: 20px;">
                Engineering single line diagram (SLD) layout: Solar PV Modules &rarr; DC Distribution Box (DCDB) &rarr; Smart Grid-Tied Inverter &rarr; AC Distribution Box (ACDB) &rarr; Bi-Directional Net Meter &rarr; Residential Load / Public Grid
            </div>
            
            <div style="text-align: center;">
                <img src="{{ normalize_pdf_image('images/template/residential _01.png') }}" alt="Solar Diagram" class="full-width-image">
            </div>
        </div>
        
        <div class="section-title">3. How the Solar System Works</div>
        <div class="content">
            <p>A grid-tied solar power system seamlessly integrates with your existing utility grid infrastructure to cleanly power your property:</p>
            <ul>
                <li><strong>Step 1: Solar Panels (Photovoltaic Modules)</strong> - Positioned optimally on your roof, these modules absorb sunlight ambient photon radiation and convert it directly into Direct Current (DC) electricity.</li>
                <li><strong>Step 2: The Solar Inverter</strong> - Acts as the intelligent brain of the system, converting the DC electricity into stable Alternating Current (AC), standardizing it for all home appliances.</li>
                <li><strong>Step 3: Home Consumption &amp; Net Metering</strong> - Power goes to your appliances first. Any excess surplus electricity generated is instantly directed back into the government utility grid via a specialized bidirectional meter.</li>
                <li><strong>Step 4: Utility Grid Backup</strong> - At night or during heavily overcast days, this system smoothly pulls electricity back from the utility grid, ensuring uninterrupted power.</li>
            </ul>
        </div>
        
        <div class="prop-footer">
            <div style="float: left;"></div>
            <div style="float: right;">Page 2 of 8</div>
        </div>
    </section>

    <!-- Page 5: Calculations -->
    <section class="page proposal-page">
        <div class="section-title">4. Generation Calculations</div>
        <div class="content">
            <p>Projected estimations are generated based on regional irradiance data for a premium proposed <strong>{{ $capacity }}</strong> system:</p>
            <ul>
                <li><strong>Daily Average Generation:</strong> ~4 to 4.5 kWh (Units) per kWp installed - {{ $dailyGeneration }} Units/day</li>
                <li><strong>Estimated Monthly Generation:</strong> {{ $monthlyGeneration }} Units/month</li>
                <li><strong>Projected Annual Generation:</strong> {{ $annualGeneration }} Units/year</li>
            </ul>
        </div>
        
        <div class="section-title">5. Advantages of Solar Energy</div>
        <div class="content">
            <ul>
                <li><strong>Massive Utility Bill Reductions:</strong> Drastically slash your monthly energy spend by up to <strong>80% - 90%</strong>.</li>
                <li><strong>High Return on Investment:</strong> Solar operates as a high-yielding financial asset that typical clears its payback period within <strong>3 - 4 years</strong>, yielding completely free power for the remainder of its 25+ year lifecycle.</li>
                <li><strong>Property Appreciation:</strong> Green-certified residential buildings equipped with fixed solar infrastructure command higher market resale values.</li>
                <li><strong>Extremely Low Maintenance:</strong> With zero moving parts, the entire system requires minimal operational upkeep-restricted primarily to routine automated or manual panel washings.</li>
                <li><strong>Environmental Stewardship:</strong> Directly mitigate carbon footprints and actively combat localized climate change.</li>
            </ul>
        </div>

        <div class="section-title">6. Carbon Emission Offset Calculation</div>
        <div class="content">
            <p>By shifting power generation source to solar PV, your property achieves highly significant environmental offset targets across its guaranteed 25-year operational lifecycle:</p>
            <ul>
                <li><strong>CO2 Emissions Prevented:</strong> {{ $co2Offset }} Metric Tons of pure Carbon Dioxide stopped from entering the atmosphere.</li>
                <li><strong>Fossil Fuel Preservation:</strong> Equivalent to preventing the burning of {{ $coalSaved }} Tons of standard coal.</li>
                <li><strong>Reforestation Equivalence:</strong> Equal to the ecological impact of planting {{ $treesEquivalent }} mature trees.</li>
            </ul>
            
            <div style="text-align: center;">
                <img src="{{ normalize_pdf_image('images/template/solorrooftop_02.png') }}" alt="Carbon Emission Infographic" class="full-width-image">
            </div>
        </div>
        
        <div class="prop-footer">
            <div style="float: left;"></div>
            <div style="float: right;">Page 3 of 8</div>
        </div>
    </section>

    <!-- Page 6: PM Surya Ghar -->
    <section class="page proposal-page">
        <div class="section-title">7. PM-Surya Ghar: Muft Bijli Yojana Process</div>
        <div class="content">
            <p>As a fully authorized and certified empaneled solar vendor, we manage the entire national subsidy workflow framework for your project end-to-end:</p>
            
            <div style="background-color: #f0f7fb; padding: 20px; border: 1px solid #d4e8f6; border-radius: 5px; margin-bottom: 30px;">
                <p><strong>1. Registration:</strong> We safely onboard your consumer credentials directly onto the central government's PM-Surya Ghar National Portal.</p>
                <p><strong>2. Technical Feasibility:</strong> The regional DISCOM (Electricity Board) reviews local grid capacity and issues a formal structural clearance approval.</p>
                <p><strong>3. Execution &amp; Installation:</strong> Our engineering wing carries out code-compliant installation adhering precisely to MNRE quality guidelines.</p>
                <p><strong>4. Net Metering Inspection:</strong> DISCOM engineers perform physical on-site verification, deploy the smart net meter, and commission the plant.</p>
                <p><strong>5. Subsidy Disbursal:</strong> Post-commissioning, the sanctioned central government subsidy is electronically credited to your linked bank account within 30 business days.</p>
            </div>
            
            <div style="text-align: center;">
                <img src="{{ normalize_pdf_image('images/template/free_electricity_03.png') }}" alt="6 Steps Infographic" class="full-width-image">
            </div>
        </div>
        
        <div class="prop-footer">
            <div style="float: left;"></div>
            <div style="float: right;">Page 4 of 8</div>
        </div>
    </section>

    <!-- Page 7: Terms -->
    <section class="page proposal-page">
        <div class="section-title">8. Return on Savings (ROI)</div>
        <div class="content">
            <p>A solar power system is a long-term investment designed to reduce dependence on conventional grid electricity and provide lasting value throughout its operating life.</p>
            <ul>
                <li><strong>Lower Electricity Dependence:</strong> On-site solar generation reduces the amount of electricity purchased from the utility provider.</li>
                <li><strong>Protection Against Tariff Changes:</strong> Producing clean energy on-site helps reduce exposure to future increases in grid electricity rates.</li>
                <li><strong>Long Operating Life:</strong> Quality solar systems are designed to generate reliable energy over many years with appropriate maintenance.</li>
                <li><strong>Low Operating Requirements:</strong> Solar PV systems require no fuel and contain few moving parts, keeping routine maintenance straightforward.</li>
                <li><strong>Long-Term Property Benefit:</strong> A professionally installed solar system can improve the property's energy efficiency and overall appeal.</li>
            </ul>
            <p>Actual savings and the investment recovery period depend on site conditions, electricity consumption, applicable tariffs, system performance, maintenance, and utility policies.</p>
        </div>
        
        <div class="section-title">9. Documents Required</div>
        <div class="content">
            <p>To initiate file processing for DISCOM permissions and central subsidy approvals, please provide:</p>
            <ul>
                <li>Latest Official Electricity Utility Bill (All pages included)</li>
                <li>Aadhaar Card of the property owner (Name alignment must match utility billing details)</li>
                <li>PAN Card Copy</li>
                <li>Bank Cancelled Cheque or clear Passbook photocopy (Required for direct electronic subsidy disbursement)</li>
                <li>Two recent passport-size color photographs</li>
            </ul>
        </div>
        
        <div class="section-title">10. Payment Terms</div>
        <div class="content">
            <ul>
                <li><strong>30% Mobilization Advance:</strong> Booked to initiate legal DISCOM file log, architectural layouts, and factory component ordering.</li>
                <li><strong>60% Component Delivery Milestone:</strong> Payable immediately upon the safe arrival of primary inventory (PV Modules &amp; Inverter) at the installation site.</li>
                <li><strong>10% Commissioning Milestone:</strong> Balance due upon successful grid connection synchronization and hand over of system logins.</li>
            </ul>
        </div>
        
        <div class="section-title">11. Disclaimer</div>
        <div class="content">
            <p>Solar generation metrics are derived parameters calculated utilizing historical long-term satellite climate records for your specific latitude/longitude. Actual real-time production yields may fluctuate in accordance with variations in seasonal weather cycles, structural micro-climate shading patterns (such as subsequent newly erected adjacent high-rises), and regular panel dust wash upkeep consistency.</p>
        </div>
        
        <div class="prop-footer">
            <div style="float: left;"></div>
            <div style="float: right;">Page 5 of 8</div>
        </div>
    </section>

    <!-- Page 8-10: Material Specs -->
    @php
        // Prepare chunks of components to split across pages
        // The PDF has 5 items on page 6, 4 items on page 7, 4 items on page 8
        $componentChunks = isset($components) && is_array($components) ? array_chunk($components, 5) : [];
        $pageOffset = 6;
        $globalCompIndex = 1;
    @endphp
    
    @forelse($componentChunks as $index => $chunk)
    <section class="page proposal-page">
        @if($index === 0)
        <div class="section-title">12. Material Make &amp; Specifications</div>
        @endif
        
        <div class="content" style="margin-top: {{ $index === 0 ? '0' : '50px' }};">
            @foreach($chunk as $comp)
            <div class="component-row">
                <div class="comp-img">
                    @php
                        $fallbackImgRelative = 'images/template/Material/Material_' . $globalCompIndex . '.png';
                        $fallbackImgSys = public_path($fallbackImgRelative);
                    @endphp
                    @if(!empty($comp['image_path']))
                        <img src="{{ normalize_pdf_image($comp['image_path']) }}" alt="{{ $comp['type'] ?? 'Component' }}" style="max-width:150px; max-height:150px;">
                    @elseif(file_exists($fallbackImgSys))
                        <img src="{{ normalize_pdf_image($fallbackImgRelative) }}" alt="{{ $comp['type'] ?? 'Component' }}" style="max-width:150px; max-height:150px;">
                    @else
                        <div style="width:100px; height:100px; border:1px solid #ddd; background:#f9f9f9; display:inline-block;"></div>
                    @endif
                </div>
                <div class="comp-details">
                    <div class="comp-title">{{ strtoupper($comp['type'] ?? 'COMPONENT') }}</div>
                    <table class="comp-table">
                        @if(!empty($comp['make']))
                        <tr><td>MAKE</td><td>{{ strtoupper($comp['make']) }}</td></tr>
                        @endif
                        @if(!empty($comp['capacity']))
                        <tr><td>CAPACITY</td><td>{{ strtoupper($comp['capacity']) }}</td></tr>
                        @endif
                        @if(!empty($comp['specification']))
                        <tr><td>SPECIFICATION</td><td>{{ strtoupper($comp['specification']) }}</td></tr>
                        @endif
                        @if(!empty($comp['warranty']))
                        <tr><td>WARRANTY</td><td>{{ strtoupper($comp['warranty']) }}</td></tr>
                        @endif
                        @if(!empty($comp['technology']))
                        <tr><td>TECHNOLOGY</td><td>{{ strtoupper($comp['technology']) }}</td></tr>
                        @endif
                    </table>
                </div>
            </div>
            @php $globalCompIndex++; @endphp
            @endforeach
        </div>
        
        <div class="prop-footer">
            <div style="float: left;"></div>
            <div style="float: right;">Page {{ $pageOffset + $index }} of 8</div>
        </div>
    </section>
    @empty
    <section class="page proposal-page">
        <div class="section-title">12. Material Make &amp; Specifications</div>
        <div class="content">
            <p>No components specified.</p>
        </div>
        <div class="prop-footer">
            <div style="float: left;"></div>
            <div style="float: right;">Page 6 of 8</div>
        </div>
    </section>
    @endforelse

</body>
</html>
EOD;

file_put_contents('resources/views/pdfbuilder/qt-000150-pdf.blade.php', $blade);
echo "Blade file created.\n";
