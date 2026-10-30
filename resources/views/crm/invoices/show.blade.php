@extends('layouts.app')

@section('page_title', 'Invoice Details #' . $invoice->invoice_no)

@section('content')
    @include('crm.invoices.partials.finance-summary')
    <style>
        .quotation-block {
            font-family: Arial, sans-serif;
            color: #333;
        }

        .quotation-box {
            max-width: 1000px;
            margin: 20px auto;
            padding: 30px;
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
            padding: 6px 10px;
            text-align: left;
        }

        .info-table th,
        .quotation-table th,
        .extra-info th {
            background-color: #4b9349;
            color: #fff;
        }

        .quotation-table tfoot td {
            font-weight: bold;
            text-align: right;
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

        .highlight-bg {
            background-color: #4b9349;
            color: #fff !important;
        }

        .qr-code-img {
            max-width: 120px;
            max-height: 120px;
            object-fit: contain;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        @media (max-width: 768px) {
            .info-table {
                font-size: 12px;
            }

            .info-table thead {
                display: block;
                width: 100%;
            }

            .info-table tbody {
                display: block;
                width: 100%;
            }

            .info-table tr {
                display: grid;
                grid-template-columns: 1fr 1fr;
                width: 100%;
            }

            .info-table th {
                grid-column: 1 / -1;
                width: 100% !important;
            }

            .info-table td {
                padding: 8px;
                word-wrap: break-word;
                overflow-wrap: break-word;
            }

            .info-table-responsive tr {
                display: flex;
                flex-wrap: wrap;
            }

            .info-table-responsive td {
                flex: 0 0 50%;
                min-width: 0;
            }

            .info-table-responsive thead th {
                flex: 0 0 100%;
            }

            /* Comment Bank Details QR Code Table - Stack on mobile */
            .comment-bank-qr-table {
                border-collapse: separate !important;
                border-spacing: 0;
            }

            .comment-bank-qr-table thead {
                display: none !important;
            }

            .comment-bank-qr-table tbody {
                display: block !important;
                width: 100%;
            }

            .comment-bank-qr-table tr {
                display: flex !important;
                flex-direction: column;
                width: 100%;
                border: none;
                margin-bottom: 15px;
            }

            .comment-bank-qr-table td {
                display: block !important;
                border: 1px solid #ddd;
                padding: 15px !important;
                background: #fafafa !important;
                width: 100%;
                position: relative;
                padding-top: 40px !important;
                word-wrap: break-word;
                overflow-wrap: break-word;
            }

            .comment-bank-qr-table td:first-child {
                border-top: 3px solid #4b9349;
            }

            .comment-bank-qr-table td::before {
                content: attr(data-label);
                display: block;
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                background: #4b9349;
                color: #fff;
                padding: 10px 15px;
                font-weight: 600;
                font-size: 12px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                /* margin: -15px -15px 0 -15px; */
                border-bottom: 1px solid #4b9349;
            }

            .comment-bank-qr-table td div {
                margin-bottom: 8px;
            }

            .comment-bank-qr-table td div:last-child {
                margin-bottom: 0;
            }

            .comment-bank-qr-table td img {
                max-width: 100%;
                height: auto;
                margin: 0 auto;
                display: block;
            }
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
            }
            .quotation-header td.quotation-title {
                text-align: center;
            }
            .quotation-title > div, .quotation-title > div > div {
                text-align: center !important;
            }
            .table-responsive-wrapper {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                width: 100%;
            }
        }

        @media print {
            .no-print, .card-header, .btn {
                display: none !important;
            }

            .quotation-box {
                box-shadow: none;
                border: none;
                margin: 0;
                padding: 0;
            }

            .page-break {
                page-break-before: always;
            }
        }
    </style>

    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden detail-view-card">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold text-dark-blue">Invoice Details</h1>
                        <p class="text-muted small mb-0">Complete information about this invoice</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-md-end no-print">
                        @can('invoices.edit')
                            <a href="{{ route('invoices.edit', $invoice->id) }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </a>
                        @endcan
                        @can('invoices.view')
                            <a href="{{ route('invoices.pdf', $invoice->id) }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0" target="_blank">
                                <i class="fa-solid fa-file-pdf me-1"></i>PDF
                            </a>
                        @endcan
                        <a href="{{ route('invoices.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                            <i class="fa-solid fa-angle-left pe-1"></i>
                            <span>Back</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <div class="quotation-block">
                    <div class="quotation-box mx-auto">
                        @include('crm.partials.document-summary-view', ['documentSummary' => $documentSummary])

                        <!-- BOM Section -->
                        <div class="page-break"></div>
                        <div class="document-bom document-bom--invoices">
                            <div class="document-bom-heading"><span class="document-bom-icon"><i class="bi bi-boxes" aria-hidden="true"></i></span><div><h2>Bill of Materials (BOM)</h2><p>Materials, specifications and pricing breakdown</p></div></div>
                            <div class="table-responsive-wrapper">
                            <table class="quotation-table table">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Product Name</th>
                                        <th>Specifications</th>
                                        <th>Quantity</th>
                                        <th>Price</th>
                                        <th>Total(Excl. GST)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $items = is_array($invoice->product_name) ? $invoice->product_name : json_decode($invoice->product_name ?? '[]', true);
                                        $total_quantity = 0;
                                        $grand_total_excluding_gst = 0.0;
                                        $usesGlobalTax = !empty($documentSummary['summaryUsesGlobalTax']);

                                        // Dynamically fetch dependencies
                                        $product_data = \App\Models\BomProduct::all()->toArray();
                                        $technology_map = \App\Models\Technology::pluck('title', 'id')->toArray();
                                        $warranty_map = \App\Models\Warranty::pluck('title', 'id')->toArray();
                                    @endphp
                                    @if (is_array($items) && !empty($items))
                                        @foreach ($items as $item)
                                            @php
                                                $product_id = $item['product_id'] ?? null;
                                                $product_name_display = $item['name'] ?? 'Product name not found';
                                                $product_name_display = ucwords(strtolower($product_name_display));
                                                $product_quantity = (int)($item['quantity'] ?? 0);
                                                $product_category_makes = $item['category_name'] ?? '';

                                                $full_product_details = null;
                                                foreach ($product_data as $prod_detail) {
                                                    if ($prod_detail['id'] == $product_id) {
                                                        $full_product_details = $prod_detail;
                                                        break;
                                                    }
                                                }

                                                $specifications = [];
                                                $make_val = ltrim(trim($product_category_makes), ',');
                                                if (!empty($make_val)) {
                                                    $specifications[] = '<span style="color: #555; font-weight: bold;">Make:</span> ' . e($make_val);
                                                }
                                                if ($full_product_details && !empty($full_product_details['technology'])) {
                                                    $techArray = json_decode($full_product_details['technology'], true);
                                                    if (!is_array($techArray)) {
                                                        $techArray = [$full_product_details['technology']];
                                                    }
                                                    $techArray = array_filter($techArray, fn($v) => trim((string)$v) !== '');
                                                    if (!empty($techArray)) {
                                                        $techNames = array_map(fn($id) => $technology_map[$id] ?? $id, $techArray);
                                                        $specifications[] = '<span style="color: #555; font-weight: bold;">Technology:</span> ' . e(implode(', ', $techNames));
                                                    }
                                                }
                                                if ($full_product_details && !empty($full_product_details['warranty'])) {
                                                    $warArray = json_decode($full_product_details['warranty'], true);
                                                    if (!is_array($warArray)) {
                                                        $warArray = [$full_product_details['warranty']];
                                                    }
                                                    $warArray = array_filter($warArray, fn($v) => trim((string)$v) !== '');
                                                    if (!empty($warArray)) {
                                                        $warNames = array_map(fn($id) => $warranty_map[$id] ?? $id, $warArray);
                                                        $specifications[] = '<span style="color: #555; font-weight: bold;">Warranty:</span> ' . e(implode(', ', $warNames));
                                                    }
                                                }
                                                if ($full_product_details && !empty($full_product_details['capacity'])) {
                                                    $specifications[] = '<span style="color: #555; font-weight: bold;">Capacity:</span> ' . e($full_product_details['capacity']);
                                                }
                                                if ($full_product_details && !empty($full_product_details['tax_rate']) && (float)$full_product_details['tax_rate'] > 0) {
                                                    $tax_rate = (float)$full_product_details['tax_rate'];
                                                    $tax_type = $full_product_details['tax_type'] ?? '';
                                                    if (strcasecmp($tax_type, 'IGST') === 0 || strcasecmp($tax_type, 'GST') === 0) {
                                                        $specifications[] = '<span style="color: #555; font-weight: bold;">GST:</span> ' . e($tax_type) . ' ' . $tax_rate . '%';
                                                    } else {
                                                        $half_rate = $tax_rate / 2;
                                                        $specifications[] = '<span style="color: #555; font-weight: bold;">GST:</span> (CGST ' . $half_rate . '% + SGST ' . $half_rate . '%)';
                                                    }
                                                }
                                                if ($full_product_details && !empty($full_product_details['height'])) {
                                                    $specifications[] = '<span style="color: #555; font-weight: bold;">Height:</span> ' . e($full_product_details['height']);
                                                }
                                                if ($full_product_details && !empty($full_product_details['fitting_material'])) {
                                                    $specifications[] = '<span style="color: #555; font-weight: bold;">Fitting Material:</span> ' . e($full_product_details['fitting_material']);
                                                }
                                                if ($full_product_details && !empty($full_product_details['fitting_type'])) {
                                                    $specifications[] = '<span style="color: #555; font-weight: bold;">Fitting Type:</span> ' . e($full_product_details['fitting_type']);
                                                }
                                                if ($full_product_details && !empty($full_product_details['thickness'])) {
                                                    $specifications[] = '<span style="color: #555; font-weight: bold;">Thickness:</span> ' . e($full_product_details['thickness']);
                                                }
                                                if ($full_product_details && !empty($full_product_details['size_of_pipe'])) {
                                                    $specifications[] = '<span style="color: #555; font-weight: bold;">Size of Pipe:</span> ' . e($full_product_details['size_of_pipe']);
                                                }

                                                $specifications_html = implode('<br>', $specifications);

                                                $price_val = array_key_exists('price', $item)
                                                    ? (float) ($item['price'] ?? 0)
                                                    : ($full_product_details ? (float) ($full_product_details['price'] ?? 0) : 0.0);
                                                $row_total = $price_val * $product_quantity;

                                                $total_quantity += $product_quantity;
                                                $grand_total_excluding_gst += $row_total;

                                                $qty_unit = '';
                                                if ($full_product_details && !empty($full_product_details['nos'])) {
                                                    $qty_unit = '(nos)';
                                                } elseif ($full_product_details && !empty($full_product_details['meter'])) {
                                                    $qty_unit = '(mtr)';
                                                }

                                                $dummyBomImageUrl = url((config('frontend_assets.public_prefix') ? rtrim(config('frontend_assets.public_prefix'), '/') . '/' : '') . 'images/template/faveicone.png');
                                                $productImageUrl = ($full_product_details && !empty($full_product_details['image']) && $product_id)
                                                    ? route('bom-products.image', $product_id)
                                                    : $dummyBomImageUrl;
                                            @endphp
                                            <tr>
                                                <td>
                                                    <div style="border: 1px solid #ddd; border-radius: 4px; padding: 4px; background-color: #fff; display: inline-block;">
                                                        <img src="{{ $productImageUrl }}" alt="{{ $product_name_display }}" style="width: 80px; height: 80px; object-fit: contain;" onerror="this.onerror=null;this.src='{{ $dummyBomImageUrl }}';">
                                                    </div>
                                                </td>
                                                <td>{{ $product_name_display }}</td>
                                                <td>{!! $specifications_html !!}</td>
                                                <td>{{ $product_quantity }}{{ $qty_unit }}</td>
                                                <td>{{ $usesGlobalTax ? '--' : number_format($price_val, 2) }}</td>
                                                <td>{{ $usesGlobalTax ? '--' : number_format($row_total, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="6">No BOM items found.</td>
                                        </tr>
                                    @endif
                                </tbody>
                                @if (is_array($items) && !empty($items))
                                    <tfoot>
                                        <tr>
                                            <td></td>
                                            <td></td>
                                            <td>Total:</td>
                                            <td>{{ $total_quantity }}</td>
                                            <td>—</td>
                                            <td>{{ $usesGlobalTax ? '--' : number_format($grand_total_excluding_gst, 2) }}</td>
                                        </tr>
                                    </tfoot>
                                @endif
                            </table>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="text-center mt-5 no-print">
                            <p class="mb-0 text-muted small">Thank you for your business!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@if($invoice->estimate)
    @include('crm.estimates.partials.equipment-details',['estimate'=>$invoice->estimate,'equipmentContext'=>['invoice_id'=>$invoice->id]])
@else
    <div class="alert alert-light border mt-4">Equipment details are available when this invoice is linked to an estimate.</div>
@endif
@endsection

@push('styles')
<link rel="stylesheet" href="{{ crm_asset('css/document-bom.css') }}">
@endpush
