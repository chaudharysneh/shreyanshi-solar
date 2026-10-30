@php
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
    $contactParts = array_values(array_filter([$companyPhone, $companyEmail]));
    $contactInfo = !empty($contactParts) ? implode(' | ', $contactParts) : 'Contact details on record';
    $websiteInfo = $companyWebsite !== '' ? $companyWebsite : 'Website on record';
    $capacityValue = (float) ($doc->quantity ?? 0);
    $capacity = $capacityValue > 0 ? $plainNumber($capacityValue, 1) . ' kWp' : 'as proposed';
    $dailyGenerationValue = $capacityValue > 0 ? $capacityValue * 4.3 : 0;
    $monthlyGenerationValue = $dailyGenerationValue * 30;
    $annualGenerationValue = $dailyGenerationValue * 365;
    $dailyGeneration = $dailyGenerationValue > 0 ? $plainNumber($dailyGenerationValue, 1) : 'As per system size';
    $monthlyGeneration = $monthlyGenerationValue > 0 ? $plainNumber($monthlyGenerationValue, 0) : 'As per system size';
    $annualGeneration = $annualGenerationValue > 0 ? $plainNumber($annualGenerationValue, 0) : 'As per system size';
    $netInvestmentValue = (float) ($doc->amount ?? 0);
    $co2OffsetValue = $capacityValue > 0 ? $capacityValue * 1.01 * 25 : 0;
    $coalSavedValue = $capacityValue > 0 ? $capacityValue * 1.259 : 0;
    $treesValue = $co2OffsetValue > 0 ? $co2OffsetValue * 45 : 0;
    $co2Offset = $co2OffsetValue > 0 ? $plainNumber($co2OffsetValue, 1) : 'As per system size';
    $coalSaved = $coalSavedValue > 0 ? $plainNumber($coalSavedValue, 1) : 'As per system size';
    $treesEquivalent = $treesValue > 0 ? $plainNumber($treesValue, 0) : 'As per system size';
    $baseSystemValue = (float) ($doc->total ?? $doc->price ?? 0);
    $gstValue = (float) ($doc->gst_amount ?? 0);
    $grossValue = $baseSystemValue + $gstValue;
    $subsidyValue = (float) ($doc->subsidy_amount ?? 0);
    $netInvestment = $netInvestmentValue > 0 ? $netInvestmentValue : max(0, $grossValue - $subsidyValue);
    $productsRaw = $doc->product_name ?? [];
    $products = is_array($productsRaw) ? $productsRaw : (json_decode((string) $productsRaw, true) ?: []);
    $bomValue = 0.0;
    foreach ($products as $product) {
        if (is_array($product)) {
            $bomValue += (float) ($product['quantity'] ?? 0) * (float) ($product['price'] ?? 0);
        }
    }
    $baseSystemValue = (float) ($doc->price ?? 0);
    $gstRate = (float) ($doc->gst ?? 0);
    $gstBreakdown = is_array($doc->gst_breakdown ?? null)
        ? $doc->gst_breakdown
        : (json_decode((string) ($doc->gst_breakdown ?? ''), true) ?: []);
    $taxLines = [];
    $usesGlobalTax = false;
    if (!empty($gstBreakdown['groups']) && is_array($gstBreakdown['groups'])) {
        foreach ($gstBreakdown['groups'] as $group) {
            if ((string) ($group['tax_type'] ?? '') === 'global_tax') {
                $usesGlobalTax = true;
            }
            if ((string) ($group['tax_type'] ?? '') === 'gst_percent') {
                continue;
            }
            foreach (($group['lines'] ?? []) as $line) {
                $label = trim((string) ($line['label'] ?? ''));
                $amount = (float) ($line['amount'] ?? 0);
                if ($label !== '' && strtoupper($label) !== 'GST' && $amount > 0) {
                    $taxLines[] = ['label' => $label, 'rate' => $line['rate'] ?? null, 'amount' => $amount];
                }
            }
        }
    }
    if (empty($taxLines)) {
        $taxBuckets = [];
        foreach ($products as $product) {
            if (!is_array($product)) {
                continue;
            }
            $taxable = (float) ($product['quantity'] ?? 0) * (float) ($product['price'] ?? 0);
            $rate = (float) ($product['tax_rate'] ?? 0);
            $label = strtoupper(trim((string) ($product['tax_label'] ?? '')));
            if ($taxable <= 0 || $rate <= 0) {
                continue;
            }
            $parts = str_contains($label, 'CGST') && str_contains($label, 'SGST')
                ? [['CGST', $rate / 2], ['SGST', $rate / 2]]
                : [[str_contains($label, 'IGST') ? 'IGST' : 'GST', $rate]];
            foreach ($parts as [$taxLabel, $taxRate]) {
                $key = $taxLabel . '|' . number_format($taxRate, 4, '.', '');
                if (!isset($taxBuckets[$key])) {
                    $taxBuckets[$key] = ['label' => $taxLabel, 'rate' => $taxRate, 'amount' => 0.0];
                }
                $taxBuckets[$key]['amount'] += ($taxable * $taxRate) / 100;
            }
        }
        $taxLines = array_values($taxBuckets);
    }
    $gstValue = !empty($taxLines)
        ? array_sum(array_map(static fn ($line) => (float) ($line['amount'] ?? 0), $taxLines))
        : (float) ($doc->gst_amount ?? ($bomValue * ($gstRate / 100)));
    if (empty($taxLines) && $gstValue > 0) {
        $taxLines = $gstRate > 0
            ? [
                ['label' => 'CGST', 'rate' => $gstRate / 2, 'amount' => $gstValue / 2],
                ['label' => 'SGST', 'rate' => $gstRate / 2, 'amount' => $gstValue / 2],
            ]
            : [['label' => 'GST', 'rate' => null, 'amount' => $gstValue]];
    }
    $solarStructureValue = (float) ($doc->solar_structure_charges ?? 0);
    $discountValue = (float) ($doc->discount ?? 0);
    $grossValue = $baseSystemValue + $bomValue + $gstValue + $solarStructureValue - $discountValue;
    $netInvestment = max(0, $grossValue - $subsidyValue);
    $estimateType = $valueOr(ucfirst((string) ($doc->type ?? '')), '--');
    $solarMeterCharges = $valueOr(ucwords(str_replace('_', ' ', (string) ($doc->solar_meter_charges ?? ''))), '--');
    $componentRows = [];
    foreach (array_slice($products, 0, 5) as $product) {
        if (!is_array($product)) {
            continue;
        }
        $productImage = $product['image'] ?? $product['product_image'] ?? $product['photo'] ?? null;
        if (empty($productImage) && !empty($product['product_id'])) {
            $bomProduct = \App\Models\BomProduct::find($product['product_id']);
            if ($bomProduct && !empty($bomProduct->image)) {
                $productImage = $bomProduct->image;
            }
        }
        
        $productImagePath = null;
        if (!empty($productImage)) {
            $productImage = trim((string) $productImage);
            if ($productImage !== '') {
                $resolved = normalize_pdf_image($productImage);
                if ($resolved && strpos($resolved, 'data:image') === 0) {
                    $productImagePath = $resolved;
                }
            }
        }

        $componentRows[] = [
            'type' => $valueOr($product['name'] ?? '', 'Selected BOM Component'),
            'make' => $valueOr($product['category_name'] ?? '', 'Approved Make'),
            'spec' => $valueOr($product['description'] ?? '', 'As per selected technical BOQ'),
            'warranty' => 'Standard OEM Warranty',
            'image_path' => $productImagePath,
        ];
    }
    if (empty($componentRows)) {
        $componentRows = [
            ['type' => 'Solar PV Panels', 'make' => 'Tier-1 Approved Brand', 'spec' => 'Mono PERC / high-efficiency solar module', 'warranty' => '10 Yr Product / 25 Yr Performance'],
            ['type' => 'Grid-Tied Inverter', 'make' => 'Approved Inverter Make', 'spec' => 'High efficiency string inverter with app monitoring', 'warranty' => '5 Years Base Warranty'],
            ['type' => 'Mounting Structure', 'make' => 'Custom Engineered', 'spec' => 'Hot-dip galvanized structural steel / aluminium', 'warranty' => '5 Years Structural'],
            ['type' => 'AC / DC Cabling', 'make' => 'Approved Cable Make', 'spec' => 'Multi-strand copper, FRLS, XLPE solar grade', 'warranty' => 'Standard OEM Warranty'],
            ['type' => 'Switchgear / Safety', 'make' => 'Approved Switchgear Make', 'spec' => 'IP65 enclosed ACDB & DCDB with Type-II SPD & fuses', 'warranty' => '1 Year Comprehensive'],
        ];
    }
    $proposalLabel = 'System Capacity: ' . ($capacityValue > 0 ? $plainNumber($capacityValue, 1) . ' kW' : 'To be finalized');
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Basic Template</title>
    <style>
        @page { margin: 14mm 15mm 12mm; size: A4 portrait; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: #1e2f44;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11.7px;
            line-height: 1.47;
            background: #fff;
        }
        .page {
            position: relative;
            width: 100%;
            min-height: 260mm;
            padding-bottom: 11mm;
            page-break-after: always;
            background: #fff;
            overflow: visible;
        }
        .page:last-child { page-break-after: auto; }
        .cover-header {
            margin: -14mm -15mm 9mm;
            padding: 13mm 15mm 10mm;
            background: #183d66;
            color: #fff;
            border-bottom: 4px solid #f2a51c;
        }
        .cover-title {
            font-size: 25px;
            line-height: 1;
            font-weight: 700;
            letter-spacing: .2px;
            text-transform: uppercase;
            margin-bottom: 7px;
        }
        .cover-subtitle {
            font-size: 10.5px;
            font-style: italic;
            color: #eef5ff;
        }
        .section {
            margin: 0 0 14px;
        }
        .section-title {
            color: #14395f;
            font-size: 15.3px;
            line-height: 1.2;
            font-weight: 700;
            border-left: 4px solid #f2a51c;
            padding-left: 8px;
            margin: 0 0 9px;
        }
        p { margin: 0 0 7px; }
        ul { margin: 0 0 9px 15px; padding: 0; }
        li { margin: 0 0 4px; }
        .diagram-box {
            margin: 9px 0 15px;
            padding: 14px 16px;
            border: 1px dashed #b9c8d7;
            background: #f8fbfe;
            color: #58708a;
            font-style: italic;
            text-align: center;
            overflow-wrap: break-word;
            word-wrap: break-word;
        }
        .process-box {
            margin: 9px 0 15px;
            padding: 12px 15px;
            border: 1px solid #9fd5f3;
            border-radius: 4px;
            background: #eef9ff;
            overflow-wrap: break-word;
            word-wrap: break-word;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 7px 0 15px;
        }
        .data-table th {
            background: #2d73b6;
            color: #fff;
            font-size: 11px;
            text-align: left;
            padding: 7px 8px;
            border: 1px solid #2d73b6;
        }
        .data-table td {
            padding: 7px 8px;
            border: 1px solid #d5e0eb;
            vertical-align: top;
        }
        .data-table tbody tr:nth-child(even) td { background: #f4f8fb; }
        .data-table .total-row td {
            background: #fff1d8 !important;
            border-color: #f2a51c;
            color: #c44d00;
            font-weight: 700;
        }
        .quote-box {
            border-left: 3px solid #c5d1de;
            background: #f7f9fb;
            padding: 11px 13px;
            margin-bottom: 10px;
            font-style: italic;
        }
        .quote-author {
            display: block;
            margin-top: 6px;
            font-style: normal;
            font-weight: 700;
            color: #1e2f44;
        }
        .footer {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            color: #7c8ca0;
            font-size: 10px;
        }
        .footer .page-no { float: right; }
        strong { color: #19314c; }
    </style>
</head>
<body>
    <section class="page">
        <div class="cover-header">
            <div class="cover-title">Rising Green Energy Proposal</div>
            <div class="cover-subtitle">Clean Energy. Guaranteed Savings. Sustainable Future.</div>
        </div>

        <div class="section">
            <h2 class="section-title">1. Introduction Page</h2>
            <p>Dear <strong>{{ $clientName }}</strong>,</p>
            <p>Thank you for giving <strong>{{ $companyName }}</strong> the opportunity to present this customized solar energy proposal for your property located at <strong>{{ $clientAddress }}</strong>.</p>
            <p>With electricity tariffs rising consistently year after year, switching to solar is no longer just an environmental choice-it is one of the smartest and safest financial investments available today. At <strong>{{ $companyName }}</strong>, we combine premium Tier-1 components, precise engineering, and seamless multi-stage execution to ensure your transition to clean energy is entirely effortless and highly profitable.</p>
            <p>Enclosed, you will find a comprehensive breakdown of your custom tailored solar solution, expected annual generation metrics, long-term financial returns, and an end-to-end implementation roadmap.</p>
            <p>Best Regards,</p>
            <p><strong>{{ $preparedName }} / {{ $preparedTitle }}</strong><br>{{ $companyName }} | {{ $contactInfo }} | {{ $websiteInfo }}</p>
        </div>

        <div class="section">
            <h2 class="section-title">2. How the Solar System Works</h2>
            <p>A grid-tied solar power system seamlessly integrates with your existing utility grid infrastructure to cleanly power your property:</p>
            <ul>
                <li><strong>Step 1: Solar Panels (Photovoltaic Modules)</strong> - Positioned optimally on your roof, these modules absorb sunlight ambient photon radiation and convert it directly into Direct Current (DC) electricity.</li>
                <li><strong>Step 2: The Solar Inverter</strong> - Acts as the intelligent brain of the system, converting the DC electricity into stable Alternating Current (AC), standardizing it for all home appliances.</li>
                <li><strong>Step 3: Home Consumption &amp; Net Metering</strong> - Power goes to your appliances first. Any excess surplus electricity generated is instantly directed back into the government utility grid via a specialized bidirectional meter.</li>
                <li><strong>Step 4: Utility Grid Backup</strong> - At night or during heavily overcast days, this system smoothly pulls electricity back from the utility grid, ensuring uninterrupted power.</li>
            </ul>
        </div>

        <div class="section">
            <h2 class="section-title">3. Advantages of Solar Energy</h2>
            <ul>
                <li><strong>Massive Utility Bill Reductions:</strong> Drastically slash your monthly energy spend by up to <strong>80% - 90%</strong>.</li>
                <li><strong>High Return on Investment:</strong> Solar operates as a high-yielding financial asset that typical clears its payback period within <strong>3 - 4 years</strong>, yielding completely free power for the remainder of its 25+ year lifecycle.</li>
                <li><strong>Property Appreciation:</strong> Green-certified residential buildings equipped with fixed solar infrastructure command higher market resale values.</li>
            </ul>
        </div>
        <div class="footer">{{ $proposalLabel }}<span class="page-no">Page 1 of 5</span></div>
    </section>

    <section class="page">
        <ul>
            <li><strong>Extremely Low Maintenance:</strong> With zero moving parts, the entire system requires minimal operational upkeep-restricted primarily to routine automated or manual panel washings.</li>
            <li><strong>Environmental Stewardship:</strong> Directly mitigate carbon footprints and actively combat localized climate change.</li>
        </ul>

        <div class="section">
            <h2 class="section-title">4. Technical Line Diagram Layout</h2>
            <p>The layout below illustrates the seamless logical electrical connection map from production to grid export.</p>
            <div class="diagram-box">Engineering single line diagram (SLD) layout: Solar PV Modules &rarr; DC Distribution Box (DCDB) &rarr; Smart Grid-Tied Inverter &rarr; AC Distribution Box (ACDB) &rarr; Bi-Directional Net Meter &rarr; Residential Load / Public Grid</div>
        </div>

        <div class="section">
            <h2 class="section-title">5. PM-Surya Ghar: Muft Bijli Yojana Process</h2>
            <p>As a fully authorized and certified empaneled solar vendor, we manage the entire national subsidy workflow framework for your project end-to-end:</p>
            <div class="process-box">
                <p><strong>1. Registration:</strong> We safely onboard your consumer credentials directly onto the central government's PM-Surya Ghar National Portal.</p>
                <p><strong>2. Technical Feasibility:</strong> The regional DISCOM (Electricity Board) reviews local grid capacity and issues a formal structural clearance approval.</p>
                <p><strong>3. Execution &amp; Installation:</strong> Our engineering wing carries out code-compliant installation adhering precisely to MNRE quality guidelines.</p>
                <p><strong>4. Net Metering Inspection:</strong> DISCOM engineers perform physical on-site verification, deploy the smart net meter, and commission the plant.</p>
                <p><strong>5. Subsidy Disbursal:</strong> Post-commissioning, the sanctioned central government subsidy is electronically credited to your linked bank account within 30 business days.</p>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">6. Generation Calculations</h2>
            <p>Projected estimations are generated based on regional irradiance data for a premium proposed <strong>{{ $capacity }}</strong> system:</p>
            <ul>
                <li><strong>Daily Average Generation:</strong> ~4 to 4.5 kWh (Units) per kWp installed - <strong>{{ $dailyGeneration }} Units/day</strong></li>
                <li><strong>Estimated Monthly Generation:</strong> <strong>{{ $monthlyGeneration }} Units/month</strong></li>
                <li><strong>Projected Annual Generation:</strong> <strong>{{ $annualGeneration }} Units/year</strong></li>
            </ul>
        </div>
        <div class="footer">{{ $proposalLabel }}<span class="page-no">Page 2 of 5</span></div>
    </section>

    <section class="page">
        <div class="section">
            <h2 class="section-title">7. Return on Savings (ROI)</h2>
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

        <div class="section">
            <h2 class="section-title">8. Carbon Emission Offset Calculation</h2>
            <p>By shifting power generation source to solar PV, your property achieves highly significant environmental offset targets across its guaranteed 25-year operational lifecycle:</p>
            <ul>
                <li><strong>CO2 Emissions Prevented:</strong> <strong>{{ $co2Offset }} Metric Tons</strong> of pure Carbon Dioxide stopped from entering the atmosphere.</li>
                <li><strong>Fossil Fuel Preservation:</strong> Equivalent to preventing the burning of <strong>{{ $coalSaved }} Tons</strong> of standard coal.</li>
                <li><strong>Reforestation Equivalence:</strong> Equal to the ecological impact of planting <strong>{{ $treesEquivalent }} mature trees</strong>.</li>
            </ul>
        </div>

        <div class="section">
            <h2 class="section-title">9. Documents Required</h2>
            <p>To initiate file processing for DISCOM permissions and central subsidy approvals, please provide:</p>
            <ul>
                <li>Latest Official Electricity Utility Bill (All pages included)</li>
                <li>Aadhaar Card of the property owner (Name alignment must match utility billing details)</li>
                <li>PAN Card Copy</li>
                <li>Bank Cancelled Cheque or clear Passbook photocopy (Required for direct electronic subsidy disbursement)</li>
                <li>Two recent passport-size color photographs</li>
            </ul>
        </div>
        <div class="footer">{{ $proposalLabel }}<span class="page-no">Page 3 of 5</span></div>
    </section>

    <section class="page">
        <div class="section">
            <h2 class="section-title">10. Material Make &amp; Specifications</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Component Type</th><th>Approved Brand / Make</th><th>Technical Specification</th><th>Warranty Terms</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($componentRows as $component)
                        <tr>
                            <td style="vertical-align: middle;">
                                @if (!empty($component['image_path']))
                                    <table width="100%" cellpadding="0" cellspacing="0" style="border:none; margin:0; padding:0; background:transparent;">
                                        <tr>
                                            <td style="border:none; padding:0; width:55px; vertical-align:middle; background:transparent;">
                                                <img src="{{ $component['image_path'] }}" alt="{{ $component['type'] }}" style="width:45px; height:45px; object-fit:contain; border:1px solid #d5e0eb; padding:2px; background:#fff; display: block;">
                                            </td>
                                            <td style="border:none; padding:0 0 0 8px; vertical-align:middle; background:transparent;">
                                                <strong>{{ $component['type'] }}</strong>
                                            </td>
                                        </tr>
                                    </table>
                                @else
                                    <strong>{{ $component['type'] }}</strong>
                                @endif
                            </td>
                            <td>{{ $component['make'] }}</td>
                            <td>{{ $component['spec'] }}</td>
                            <td>{{ $component['warranty'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            
            <table class="data-table" style="margin-top: 20px;">
                <tbody>
                    <tr><td style="width: 50%;"><strong>System Capacity</strong></td><td>{{ $capacityValue > 0 ? $plainNumber($capacityValue, 1) . ' kW' : '--' }}</td></tr>
                    <tr><td><strong>Estimate Type</strong></td><td>{{ $estimateType }}</td></tr>
                    <tr><td><strong>Solar Meter Charges</strong></td><td>{{ $solarMeterCharges }}</td></tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2 class="section-title">11. Price Quote &amp; Commercials</h2>
            <p>Commercial quote schedule valid for precisely 15 calendar days from document date of issue:</p>
            <table class="data-table">
                <thead><tr><th>Line Item Description</th><th>Amount (&#8377;)</th></tr></thead>
                <tbody>
                    <tr><td>Base cost</td><td>{!! $money($baseSystemValue) !!}</td></tr>
                    <tr><td>Bill of Materials (BOM)</td><td>{!! $usesGlobalTax ? '--' : $money($bomValue) !!}</td></tr>
                    @if ($gstValue > 0)
                        <tr><td><strong>{{ $usesGlobalTax ? 'Global Tax on Base Price' : 'Taxes on Bill of Materials (BOM Only)' }}</strong></td><td></td></tr>
                        @foreach ($taxLines as $taxLine)
                            @php
                                $taxRateText = is_numeric($taxLine['rate'] ?? null)
                                    ? rtrim(rtrim(number_format((float) $taxLine['rate'], 2, '.', ''), '0'), '.')
                                    : '';
                            @endphp
                            <tr>
                                <td>{{ $taxLine['label'] }}{{ $taxRateText !== '' ? ' (' . $taxRateText . '%)' : '' }}</td>
                                <td>{!! $money($taxLine['amount']) !!}</td>
                            </tr>
                        @endforeach
                        <tr><td><strong>{{ $usesGlobalTax ? 'Total Global Tax' : 'Total Taxes on BOM' }}</strong></td><td><strong>{!! $money($gstValue) !!}</strong></td></tr>
                    @endif
                    @if ($solarStructureValue > 0)
                        <tr><td>Solar Structure Charges</td><td>{!! $money($solarStructureValue) !!}</td></tr>
                    @endif
                    @if ($discountValue > 0)
                        <tr><td>Discount</td><td>- {!! $money($discountValue) !!}</td></tr>
                    @endif
                    <tr><td><strong>Consumer Net Payable</strong></td><td><strong>{!! $money($grossValue) !!}</strong></td></tr>
                    @if ($subsidyValue > 0)
                        <tr><td>Subsidy</td><td>- {!! $money($subsidyValue) !!}</td></tr>
                    @endif
                    <tr class="total-row"><td>Net Amount Payable</td><td>{!! $money($netInvestment) !!}</td></tr>
                </tbody>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2 class="section-title">12. Payment Terms</h2>
            <ul>
                <li><strong>30% Mobilization Advance:</strong> Booked to initiate legal DISCOM file log, architectural layouts, and factory component ordering.</li>
                <li><strong>60% Component Delivery Milestone:</strong> Payable immediately upon the safe arrival of primary inventory (PV Modules &amp; Inverter) at the installation site.</li>
                <li><strong>10% Commissioning Milestone:</strong> Balance due upon successful grid connection synchronization and hand over of system logins.</li>
            </ul>
        </div>
        <div class="footer">{{ $proposalLabel }}<span class="page-no">Page 4 of 5</span></div>
    </section>

    <section class="page">
        <div class="section">
            <h2 class="section-title">13. Terms &amp; Conditions</h2>
            <ul>
                <li><strong>Turnaround Timeline:</strong> Project completion spans 3 to 4 weeks conditional upon localized utility board structural approval speed.</li>
                <li><strong>Site Handover Readiness:</strong> The client is required to grant clear rooftop clearance, secure storage space for physical components, and a continuous water line connection for maintenance panels cleaning.</li>
                <li><strong>Civil Variations:</strong> Baseline quotes assume mounting configurations directly onto structurally sound RCC flat roofs. High-raise custom structures or unique modifications will be billed extra as per agreed metrics.</li>
            </ul>
        </div>

        <div class="section">
            <h2 class="section-title">14. Disclaimer</h2>
            <p>Solar generation metrics are derived parameters calculated utilizing historical long-term satellite climate records for your specific latitude/longitude. Actual real-time production yields may fluctuate in accordance with variations in seasonal weather cycles, structural micro-climate shading patterns (such as subsequent newly erected adjacent high-rises), and regular panel dust wash upkeep consistency.</p>
        </div>

        <div class="section">
            <h2 class="section-title">15. Client Testimonials</h2>
            <div class="quote-box">
                "The complete migration process to solar with this team was entirely fluid. Our typical monthly operational electric bills fell right down from near &#8377;8,000 to basic minimal meter standing charges under &#8377;500! Clean installation and outstanding customer portal communication."
                <span class="quote-author">- Rajesh K., Verified Residential Client</span>
            </div>
            <div class="quote-box">
                "Outstanding expertise handling the central PM-Surya Ghar portal compliance parameters. The full subsidy allocation arrived securely into my bank profile within exactly 25 working days post meter calibration."
                <span class="quote-author">- Sunita Sharma, Verified Residential Client</span>
            </div>
        </div>
        <div class="footer">{{ $proposalLabel }}<span class="page-no">Page 5 of 5</span></div>
    </section>
</body>
</html>
