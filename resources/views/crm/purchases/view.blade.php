@extends('layouts.app')

@section('page_title', 'Material IN - View')

@section('content')
    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden detail-view-card">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Material IN Details</h1>
                        <p class="text-muted small mb-0">Complete information about this material IN</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                        <a href="{{ route('purchases.pdf', $purchase->invoice_id) }}" class="btn btn-outline-dark-blue flex-grow-1 flex-md-grow-0" target="_blank">
                            <i class="bi bi-file-pdf"></i> Generate PDF
                        </a>
                        @can('purchases.edit')
                            <a href="{{ route('purchases.edit', $purchase->invoice_id) }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </a>
                        @endcan
                        <a href="{{ route('purchases.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                            <i class="fa-solid fa-angle-left pe-1"></i>
                            <span>Back</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body p-3 p-md-4">
                <div id="pdfContent">
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
                            background-color: #5e72e4;
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
                        @media (max-width: 768px) {
                            .quotation-header table, .quotation-header tbody, .quotation-header tr, .quotation-header td {
                                display: block;
                                width: 100% !important;
                            }
                            .quotation-header td.company-logo {
                                text-align: center;
                                margin-bottom: 15px;
                            }
                            .company-logo img {
                                margin: 0 auto;
                                max-width: 100% !important;
                                width: 250px !important;
                                height: auto !important;
                            }
                            .quotation-header td.quotation-title {
                                text-align: center;
                            }
                            .quotation-title > div {
                                text-align: center !important;
                            }
                            .flex-between {
                                flex-direction: column;
                                gap: 10px;
                                text-align: center;
                            }
                            .table-responsive-wrapper {
                                width: 100%;
                                overflow-x: auto;
                                -webkit-overflow-scrolling: touch;
                                margin-bottom: 1rem;
                            }
                        }
                    </style>

                    @php
                        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
                        $companyName = $settings['company_name'] ?? 'Rising Green Energy';
                        $companyAddress = $settings['company_address'] ?? '215 MAHER NAGAR OPP BAPS HOSPITAL ADAJAN SURAT (395009)';
                        $companyPhone = $settings['phone'] ?? '';
                        $companyEmail = $settings['email'] ?? '';
                        $publicPrefix = config('frontend_assets.public_prefix') ? rtrim(config('frontend_assets.public_prefix'), '/') . '/' : '';
                        $companyLogo = $settings['company_logo_path'] ?? null;
                        $companyLogoUrl = null;
                        if ($companyLogo) {
                            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($companyLogo)) {
                                $companyLogoUrl = url($publicPrefix . 'storage/' . ltrim($companyLogo, '/'));
                            } else {
                                $companyLogoUrl = url($publicPrefix . ltrim($companyLogo, '/'));
                            }
                        }
                        if (!$companyLogoUrl) {
                            $companyLogoUrl = url($publicPrefix . 'images/template/company-logo-image (1).png');
                        }
                    @endphp
                    <div class="quotation-box">
                        <!-- Header -->
                        <div class="quotation-header">
                            <table>
                                <tr>
                                    <td class="company-logo">
                                        <img src="{{ $companyLogoUrl }}" alt="Company Logo" onerror="this.onerror=null;this.src='{{ url((config('frontend_assets.public_prefix') ? rtrim(config('frontend_assets.public_prefix'), '/') . '/' : '') . 'images/template/company-logo-image (1).png') }}';">
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
                        <div class="table-responsive-wrapper">
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
                                        <td>@if($purchase->customer?->phone)<a href="tel:{{ $purchase->customer->phone }}">{{ $purchase->customer->phone }}</a>@else -- @endif</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Products Table -->
                        <div class="table-responsive-wrapper">
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
                        </div>

                        <!-- Comments Section -->
                        @if($purchase->comment)
                        <div class="table-responsive-wrapper">
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
                        </div>
                        @endif

                        <!-- Footer -->
                        <div class="quotation-footer">Thank you for your business!</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        document.getElementById('generatePdfBtn').addEventListener('click', function() {
            const element = document.getElementById('pdfContent');
            const opt = {
                margin: 5,
                filename: 'Material-IN-{{ $purchase->invoice_no }}.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { orientation: 'portrait', unit: 'mm', format: 'a4' }
            };
            html2pdf().set(opt).from(element).save();
        });
    </script>
@endpush
