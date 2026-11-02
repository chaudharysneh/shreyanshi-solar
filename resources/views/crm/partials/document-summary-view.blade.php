@php
    extract($documentSummary ?? []);
@endphp

<style>
    .document-summary-view {
        font-family: 'Montserrat', Arial, sans-serif;
        color: #24372d;
    }

    .document-summary-view table {
        width: 98%;
        margin-left: auto;
        margin-right: auto;
        border-collapse: collapse;
    }

    .document-summary-view .summary-header-cell,
    .detail-view-card .document-summary-view .summary-header-cell,
    .detail-view-card .document-summary-view td.summary-header-cell {
        background-color: #327548;
        color: #fff !important;
        border: 1px solid #cbdcd0;
        padding: 5px 8px;
        font-size: 15px;
        font-weight: bold;
        line-height: 1.25;
    }

    .document-summary-view .summary-header-cell *,
    .detail-view-card .document-summary-view .summary-header-cell * {
        color: #fff !important;
    }

    .document-summary-view .summary-cell {
        border: 1px solid #cbdcd0;
        padding: 5px 8px;
        font-size: 15px;
        line-height: 1.25;
    }

    .document-summary-view .summary-cell-right {
        text-align: right;
    }

    .document-summary-view .summary-highlight,
    .detail-view-card .document-summary-view .summary-highlight,
    .detail-view-card .document-summary-view td.summary-highlight {
        background-color: #327548;
        color: #fff !important;
        font-weight: bold;
        text-align: right;
    }

    .document-summary-view .summary-highlight *,
    .detail-view-card .document-summary-view .summary-highlight * {
        color: #fff !important;
    }

    .document-summary-view .summary-footer-header,
    .detail-view-card .document-summary-view .summary-footer-header,
    .detail-view-card .document-summary-view th.summary-footer-header {
        background-color: #327548;
        color: #fff !important;
        border: 1px solid #cbdcd0;
        padding: 4px 6px;
        font-size: 13px;
        font-weight: bold;
        line-height: 1.2;
    }

    .document-summary-view .summary-footer-cell {
        border: 1px solid #cbdcd0;
        padding: 4px 6px;
        font-size: 13px;
        line-height: 1.2;
        vertical-align: top;
    }

    .document-summary-view .summary-meta {
        font-size: 16px;
        font-weight: bold;
    }

    .document-summary-view .summary-company-text {
        font-size: 13.5px;
        line-height: 1.45;
    }

    .document-summary-view .summary-bank-box {
        background-color: #f6fbf6;
        border: 1px solid #cfe5cf;
        width: 100%;
        border-collapse: collapse;
    }

    .document-summary-view .summary-bank-title,
    .detail-view-card .document-summary-view .summary-bank-title,
    .detail-view-card .document-summary-view td.summary-bank-title {
        background-color: #327548;
        color: #fff !important;
        padding: 4px 6px;
        font-size: 13px;
        font-weight: bold;
    }

    .document-summary-view .summary-qr-img {
        max-width: 58px;
        max-height: 58px;
    }

    @media (max-width: 767.98px) {
        .document-summary-view .summary-top-row,
        .document-summary-view .summary-meta-row {
            display: block;
        }

        .document-summary-view .summary-top-row td,
        .document-summary-view .summary-meta-row td {
            display: block;
            width: 100% !important;
            text-align: left !important;
            padding-bottom: 8px;
        }

        .document-summary-view .summary-customer-table td {
            display: block;
            width: 100% !important;
        }

        .document-summary-view .summary-footer-table thead {
            display: none;
        }

        .document-summary-view .summary-footer-table tr {
            display: block;
            margin-bottom: 12px;
        }

        .document-summary-view .summary-footer-table td {
            display: block;
            width: 100% !important;
        }
    }
</style>

<div class="document-summary-view">
    <table class="summary-top-row" style="margin-bottom:8px;">
        <tr>
            <td width="45%" valign="top" align="left" class="summary-company-text" style="padding-bottom:8px;">
                @if (!empty($summaryLogoUrl))
                    <img src="{{ $summaryLogoUrl }}" alt="Company Logo" style="display:block;width:250px;max-width:100%;height:auto;max-height:90px;object-fit:contain;object-position:left center;">
                @endif
            </td>
            <td width="55%" valign="top" align="right" class="summary-company-text" style="padding-bottom:8px;">
                <strong style="font-size:16px;">{{ $summaryCompanyName ?? '--' }}</strong><br>
                @foreach (\App\Support\DocumentAddress::lines($summaryCompanyAddress) as $addressLine)
                    <span style="display:block;white-space:normal;overflow-wrap:anywhere;">{{ $addressLine }}</span>
                @endforeach
                @foreach (\App\Support\DocumentContact::lines($summaryCompanyPhone ?? '', $summaryCompanyEmail ?? '', $summaryCompanyTaxId ?? '') as $contactLine)
                    <div style="white-space:nowrap;">{{ $contactLine }}</div>
                @endforeach
            </td>
        </tr>
        <tr>
            <td colspan="2" style="border-top:1px solid #e5e5e5;padding-top:10px;"></td>
        </tr>
    </table>

    @php
        $viewCompanySettings = [
            'company_tax_id' => $summaryCompanyTaxId ?? '',
        ];
    @endphp
    @if (isset($invoice) && empty($summaryUsesGlobalTax))
        @include('pdfbuilder.partials.invoice-body', [
            'estimate' => $invoice,
            'companySettings' => $viewCompanySettings,
            'summaryHasBank' => ($summaryShowBankDetails ?? true) && (!empty($summaryBankName) || !empty($summaryBankFields)),
            'summaryHasQr' => ($summaryShowCompanyQrCode ?? true) && !empty($summaryQrUrl),
            'summaryQrImage' => $summaryQrUrl ?? '',
            'summaryHasComment' => !in_array(trim((string) ($summaryEstimateComment ?? '')), ['', '-', '--'], true),
        ])
    @else
        @include('pdfbuilder.partials.invoice-customer-header', [
            'estimate' => $invoice ?? $estimate ?? null,
            'companySettings' => $viewCompanySettings,
            'documentTitle' => isset($invoice) ? 'INVOICE' : 'ESTIMATION',
            'documentNumberLabel' => isset($invoice) ? 'Invoice No.' : 'Estimate No.',
            'showCustomerEmail' => !isset($invoice),
        ])
    <table style="margin-top:6px;margin-bottom:8px;">
        <tr>
            <td class="summary-header-cell summary-pricing-header" style="width:68%;">Description</td>
            <td class="summary-header-cell summary-cell-right summary-pricing-header" style="width:32%;">Amount (&#8377;)</td>
        </tr>
        @if (!empty($summaryUsesGlobalTax))
            <tr>
                <td class="summary-cell">Base cost</td>
                <td class="summary-cell summary-cell-right">{{ number_format($summaryBaseCost ?? 0, 2) }}</td>
            </tr>
        @else
        <tr>
            <td class="summary-cell">Bill of Materials (BOM)</td>
            <td class="summary-cell summary-cell-right">{{ !empty($summaryUsesGlobalTax) ? '--' : number_format($summaryBomTotal ?? 0, 2) }}</td>
        </tr>
        @endif
        @if (!empty($summaryShowBomTaxes))
            <tr>
                <td class="summary-cell"><strong>{{ !empty($summaryUsesGlobalTax) ? 'Global Tax on Base Price' : 'Taxes on Bill of Materials (BOM Only)' }}</strong></td>
                <td class="summary-cell summary-cell-right">&nbsp;</td>
            </tr>
            @foreach ($summaryBreakupLines ?? [] as $line)
                @php
                    $lineLabel = trim((string) ($line['label'] ?? ''));
                    $lineRate = $line['rate'] ?? null;
                    $lineAmount = (float) ($line['amount'] ?? 0);
                    $lineRateText = is_numeric($lineRate) ? rtrim(rtrim(number_format((float) $lineRate, 2, '.', ''), '0'), '.') : '';
                @endphp
                @if ($lineLabel !== '' && $lineAmount > 0)
                    <tr>
                        <td class="summary-cell">{{ $lineLabel }}{{ $lineRateText !== '' ? ' (' . $lineRateText . '%)' : '' }}</td>
                        <td class="summary-cell summary-cell-right">{{ number_format($lineAmount, 2) }}</td>
                    </tr>
                @endif
            @endforeach
            <tr>
                <td class="summary-cell"><strong>{{ !empty($summaryUsesGlobalTax) ? 'Total Global Tax' : 'Total Taxes on BOM' }}</strong></td>
                <td class="summary-cell summary-cell-right"><strong>{{ number_format($summaryBomTaxTotal ?? 0, 2) }}</strong></td>
            </tr>
        @elseif (!empty($summaryShowGst) && ($summaryBomTaxTotal ?? 0) > 0)
            <tr>
                <td class="summary-cell"><strong>{{ !empty($summaryUsesGlobalTax) ? 'Global Tax on Base Price' : 'Taxes on Bill of Materials (BOM Only)' }}</strong></td>
                <td class="summary-cell summary-cell-right">&nbsp;</td>
            </tr>
            <tr>
                <td class="summary-cell">GST{{ !empty($summaryGstRateText) ? ' (' . $summaryGstRateText . '%)' : '' }}</td>
                <td class="summary-cell summary-cell-right">{{ number_format((float) ($summaryBomTaxTotal ?? 0), 2) }}</td>
            </tr>
            <tr>
                <td class="summary-cell"><strong>{{ !empty($summaryUsesGlobalTax) ? 'Total Global Tax' : 'Total Taxes on BOM' }}</strong></td>
                <td class="summary-cell summary-cell-right"><strong>{{ number_format($summaryBomTaxTotal ?? 0, 2) }}</strong></td>
            </tr>
        @endif
        @if (($summarySolarStructureCharges ?? 0) > 0)
            <tr>
                <td class="summary-cell">Solar Structure Charges</td>
                <td class="summary-cell summary-cell-right">{{ number_format($summarySolarStructureCharges, 2) }}</td>
            </tr>
        @endif
        @if (($summaryDiscount ?? 0) > 0)
            <tr>
                <td class="summary-cell">Discount</td>
                <td class="summary-cell summary-cell-right">-{{ number_format($summaryDiscount, 2) }}</td>
            </tr>
        @endif
        @php
            $summarySubtotalFormula = !empty($summaryUsesGlobalTax)
                ? '(Base cost + Global Tax'
                : '(Base cost + BOM + BOM Taxes';
            if (($summarySolarStructureCharges ?? 0) > 0) {
                $summarySubtotalFormula .= ' + Solar Structure Charges';
            }
            if (($summaryDiscount ?? 0) > 0) {
                $summarySubtotalFormula .= ' - Discount';
            }
            $summarySubtotalFormula .= ')';
        @endphp
        <tr>
            <td class="summary-cell">
                <strong>Consumer Net Payable</strong>
                <span style="font-style:italic;font-weight:normal;">{{ $summarySubtotalFormula }}</span>
            </td>
            <td class="summary-cell summary-cell-right"><strong>{{ number_format($summaryInvoiceSubtotal ?? 0, 2) }}</strong></td>
        </tr>
        @if (($summarySubsidy ?? 0) > 0)
            <tr>
                <td class="summary-cell">Subsidy</td>
                <td class="summary-cell summary-cell-right">-{{ number_format($summarySubsidy, 2) }}</td>
            </tr>
        @endif
        <tr>
            <td class="summary-cell"><strong>Net Amount Payable</strong></td>
            <td class="summary-cell summary-highlight"><strong>{{ number_format($summaryNetPayable ?? 0, 2) }}</strong></td>
        </tr>
    </table>

    @if (($summarySubsidy ?? 0) > 0)
        <div style="width:98%;margin:4px auto 0;font-size:14px;line-height:1.3;">
            <strong>Note:</strong> Subsidy Amount to be credited in clients account.
        </div>
    @endif

    <table style="margin-top:8px;margin-bottom:8px;">
        <tr>
            <td class="summary-header-cell" style="width:38%;">System Capacity</td>
            <td class="summary-cell">{{ $quantity ?? '0' }} kW</td>
        </tr>
        <tr>
            <td class="summary-header-cell">{{ isset($invoice) ? 'Invoice Type' : 'Estimate Type' }}</td>
            <td class="summary-cell">{{ $summaryEstimateTypeLabel ?? '--' }}</td>
        </tr>
        <tr>
            <td class="summary-header-cell">Solar Meter Charges</td>
            <td class="summary-cell">{{ $summarySolarMeterLabel ?? '--' }}</td>
        </tr>
    </table>

    @php
        $summaryHasBank = ($summaryShowBankDetails ?? true) && (trim((string) ($summaryBankName ?? '')) !== '' || !empty($summaryBankFields));
        $summaryHasQr = ($summaryShowCompanyQrCode ?? true) && !empty($summaryQrUrl);
        $summaryCommentWidth = $summaryHasBank ? ($summaryHasQr ? 35 : 50) : ($summaryHasQr ? 75 : 100);
        $summaryBankWidth = $summaryHasQr ? 40 : 50;
    @endphp
    <table class="summary-footer-table" style="margin-top:8px;margin-bottom:8px;">
        <thead>
            <tr>
                <th class="summary-footer-header" style="width:{{ $summaryCommentWidth }}%;">Comment</th>
                @if ($summaryHasBank)
                <th class="summary-footer-header" style="width:{{ $summaryBankWidth }}%;">Bank Details</th>
                @endif
                @if ($summaryHasQr)
                <th class="summary-footer-header" style="width:25%;">QR Code</th>
                @endif
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="summary-footer-cell">{!! nl2br(e($summaryEstimateComment ?: '--')) !!}</td>
                @if ($summaryHasBank)
                <td class="summary-footer-cell">
                    @if (($summaryBankName ?? '') !== '' || !empty($summaryBankFields))
                        <table class="summary-bank-box">
                            @if (($summaryBankName ?? '') !== '')
                                <tr>
                                    <td colspan="2" class="summary-bank-title">{{ $summaryBankName }}</td>
                                </tr>
                            @endif
                            @foreach ($summaryBankFields ?? [] as $bankIndex => $bankField)
                                <tr>
                                    <td width="38%" style="padding:3px 6px;font-size:13px;color:#5a6b5a;font-weight:bold;vertical-align:top;{{ $bankIndex > 0 || ($summaryBankName ?? '') !== '' ? 'border-top:1px solid #e3efe3;' : '' }}">
                                        {{ $bankField['label'] }}
                                    </td>
                                    <td width="62%" style="padding:3px 6px;font-size:13px;color:#1a1a1a;vertical-align:top;{{ $bankIndex > 0 || ($summaryBankName ?? '') !== '' ? 'border-top:1px solid #e3efe3;' : '' }}">
                                        {{ $bankField['value'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    @else
                        <span style="font-size:13px;color:#888;font-style:italic;">No bank details available.</span>
                    @endif
                </td>
                @endif
                @if ($summaryHasQr)
                <td class="summary-footer-cell" style="text-align:center;">
                    @if (!empty($summaryQrUrl))
                        <img src="{{ $summaryQrUrl }}" alt="QR Code" class="summary-qr-img">
                    @else
                        No QR code available.
                    @endif
                </td>
                @endif
            </tr>
        </tbody>
    </table>
    @endif
</div>

<style>

    .detail-view-card .document-summary-view .summary-header-cell,
    .detail-view-card .document-summary-view .summary-header-cell *,
    .detail-view-card .document-summary-view .summary-footer-header,
    .detail-view-card .document-summary-view .summary-footer-header * {
        background: #eaf3ec; color: #245d38 !important;
    }
    .detail-view-card .document-summary-view .summary-pricing-header,
    .detail-view-card .document-summary-view .summary-pricing-header * {
        background: #327548; color: #fff !important;
    }
    .document-summary-view .summary-footer-cell { background: #f5f9f6; }
    .document-summary-view .reference-invoice,
    .document-summary-view .invoice-customer-header { font-size: 14px; }
    .document-summary-view .reference-invoice table,
    .document-summary-view .invoice-customer-header table { width: 100%; }
    .document-summary-view .reference-invoice .items { font-size: 13px; }
    .document-summary-view .reference-invoice .document-notes { font-size: 13px; }
    .document-summary-view .reference-invoice .payable { font-size: 15px; }
    .document-summary-view .reference-invoice .grid td,
    .document-summary-view .reference-invoice .grid th { padding: 9px 10px; }
</style>
