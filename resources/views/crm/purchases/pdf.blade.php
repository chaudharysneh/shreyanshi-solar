<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Material IN - {{ $purchase->invoice_no }}</title>
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
            max-width: 256px;
            display: block;
        }
        .quotation-title {
            font-size: 16px;
            text-align: right;
            color: #686868;
        }
        .info-table, .quotation-table, .extra-info table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .info-table th, .info-table td, .quotation-table th, .quotation-table td, .extra-info th, .extra-info td {
            border: 1px solid #333;
            padding: 4px 6px;
            text-align: left;
        }
        .info-table th, .quotation-table th, .extra-info th {
            background-color: #3B5BDB;
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
        hr {
            border: none;
            border-top: 1px solid #333;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    @php
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
        $companyName = $settings['company_name'] ?? 'Rising Green Energy';
        $companyAddress = $settings['company_address'] ?? '215 MAHER NAGAR OPP BAPS HOSPITAL ADAJAN SURAT (395009)';
        $companyPhone = $settings['phone'] ?? '';
        $companyEmail = $settings['email'] ?? '';
        $companyLogo = $settings['company_logo_path'] ?? null;
        
        $logoPath = null;
        if ($companyLogo) {
            $storageLogoPath = storage_path('app/public/' . $companyLogo);
            if (file_exists($storageLogoPath)) {
                $logoPath = $storageLogoPath;
            }
        }
        if (!$logoPath) {
            $publicLogoPath = public_path('assets/img/logo.jpg');
            if (file_exists($publicLogoPath)) {
                $logoPath = $publicLogoPath;
            }
        }
    @endphp
    <div class="quotation-box">
        <!-- Header -->
        <div class="quotation-header">
            <table>
                <tr>
                    <td class="company-logo">
                        @if($logoPath)
                            <img src="{{ $logoPath }}" alt="Company Logo" style="max-width: 200px; height: auto;">
                        @endif
                    </td>
                    <td class="quotation-title">
                        <div style="line-height:22px;">
                            <strong style="font-size:18px;">{{ $companyName }}</strong><br>
                            {{ $companyAddress }}<br>
                            @if(!empty($companyPhone) || !empty($companyEmail))
                                {{ implode(' | ', array_filter([$companyEmail, $companyPhone])) }}<br>
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
            <hr>
        </div>

        <!-- Invoice Info -->
        <div class="flex-between">
            <div style="font-weight:700; font-size:15px;">IN No.: #{{ $purchase->invoice_no }}</div>
            <div class="center-text" style="font-size:16px;">Material IN</div>
            <div style="font-weight:700; font-size:15px;">IN Date: {{ optional($purchase->invoice_date)?->format('Y-m-d') ?? '-' }}</div>
        </div>

        <!-- Vendor Info -->
        <table class="info-table">
            <thead>
                <tr>
                    <th colspan="2">Vendor Details</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="width:30%;"><strong>Vendor Name</strong></td>
                    <td>{{ $purchase->customer?->name ?? '--' }}</td>
                </tr>
                <tr>
                    <td><strong>Address</strong></td>
                    <td>{{ $purchase->customer?->address ?? '--' }}</td>
                </tr>
                <tr>
                    <td><strong>Contact</strong></td>
                    <td>{{ $purchase->customer?->phone ?? '--' }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Products Table -->
        <table class="quotation-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                </tr>
            </thead>
            <tbody>
                @if($purchase->product)
                <tr>
                    <td>{{ $purchase->product?->name ?? 'Unknown' }}</td>
                    <td>{{ $purchase->quantity ?? '-' }}</td>
                </tr>
                @else
                <tr>
                    <td colspan="2" style="text-align:center;">No Products</td>
                </tr>
                @endif
            </tbody>
        </table>

        <!-- Comments Section -->
        @if($purchase->comment)
        <table class="extra-info">
            <thead>
                <tr>
                    <th>Additional Comments</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $purchase->comment }}</td>
                </tr>
            </tbody>
        </table>
        @endif

        <!-- Footer -->
        <div class="quotation-footer">Thank you for your business!</div>
    </div>
</body>
</html>
