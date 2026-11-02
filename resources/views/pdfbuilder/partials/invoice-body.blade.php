@php
    $invoiceRows = $summaryUsesGlobalTax ? [[
        'name' => $estdata->invoice_name ?? 'Solar installation', 'quantity' => 1,
        'unit' => 'Job', 'price' => $summaryBaseCost, 'tax_rate' => (float) ($estdata->gst ?? 0),
        'tax_label' => 'CGST + SGST',
    ]] : ($estdata->product_name ?? []);
    // Legacy documents can contain a base charge alongside their material rows.
    if (!$summaryUsesGlobalTax && $summaryBaseCost > 0) {
        array_unshift($invoiceRows, ['name' => 'Base cost', 'quantity' => 1, 'unit' => 'Job', 'price' => $summaryBaseCost]);
    }
    $invoiceTaxLabels = ['CGST', 'SGST'];
    foreach ($summaryBreakupLines as $line) {
        $label = strtoupper(trim($line['label'] ?? ''));
        if ($label !== '' && !in_array($label, $invoiceTaxLabels, true)) $invoiceTaxLabels[] = $label;
    }
    $invoiceTaxes = array_fill_keys($invoiceTaxLabels, 0.0);
    foreach ($summaryBreakupLines as $line) {
        $label = strtoupper(trim($line['label'] ?? ''));
        if (isset($invoiceTaxes[$label])) $invoiceTaxes[$label] += (float) $line['amount'];
    }
    $spell = function (int $number) use (&$spell): string {
        $ones = ['Zero','One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen'];
        $tens = ['', '', 'Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
        if ($number < 20) return $ones[$number];
        if ($number < 100) return $tens[intdiv($number, 10)] . ($number % 10 ? ' ' . $ones[$number % 10] : '');
        foreach ([10000000 => 'Crore', 100000 => 'Lakh', 1000 => 'Thousand', 100 => 'Hundred'] as $scale => $word) {
            if ($number >= $scale) return $spell(intdiv($number, $scale)) . ' ' . $word . ($number % $scale ? ' ' . $spell($number % $scale) : '');
        }
        return '';
    };
    $invoicePaise = (int) round(abs($summaryNetPayable) * 100);
    $invoiceWords = ($summaryNetPayable < 0 ? 'Minus ' : '') . 'Rupees ' . $spell(intdiv($invoicePaise, 100))
        . ($invoicePaise % 100 ? ' and ' . $spell($invoicePaise % 100) . ' Paise' : '') . ' Only';
    $savedDisplay = $estimate->table_display ?? $estdata->table_display ?? null;
    if (is_string($savedDisplay)) $savedDisplay = json_decode($savedDisplay, true);
    $tableDisplay = \App\Support\InvoiceTableDisplay::resolve(is_array($savedDisplay) ? $savedDisplay : null);
    $tableColumns = [];
    if ($tableDisplay['serial']) $tableColumns['serial'] = ['label' => 'No.', 'weight' => 5];
    $tableColumns['item'] = ['label' => $tableDisplay['description'] ? 'Item & Description' : 'Item', 'weight' => 32];
    foreach (['quantity' => ['Qty', 6], 'unit' => ['Unit', 7], 'rate' => ['Rate (₹)', 13], 'taxable' => ['Taxable (₹)', 13]] as $key => [$label, $weight]) {
        if ($tableDisplay[$key]) $tableColumns[$key] = compact('label', 'weight');
    }
    if ($tableDisplay['taxes']) {
        foreach ($invoiceTaxLabels as $label) $tableColumns['tax_' . $label] = ['label' => $label, 'weight' => 8];
    }
    if ($tableDisplay['amount']) $tableColumns['amount'] = ['label' => 'Amount (₹)', 'weight' => 14];
    $tableWeight = array_sum(array_column($tableColumns, 'weight'));
@endphp
<style>
    .reference-invoice { width:98%; margin:0 auto; color:#24372d; font-family:'DejaVu Sans',sans-serif; font-size:11px; line-height:1.4; }
    .reference-invoice table { width:100%; border-collapse:collapse; table-layout:fixed; }
    .reference-invoice .grid th, .reference-invoice .grid td { border:0.6px solid #cbdcd0; padding:4px 5px; vertical-align:top; overflow-wrap:break-word; }
    .reference-invoice .grid th { background:#eaf3ec; color:#245d38; text-align:left; font-weight:bold; }
    .reference-invoice .items thead th { background:#327548; color:#fff; border-color:#327548; padding-top:6px; padding-bottom:6px; }
    .reference-invoice .items tbody tr:nth-child(even) td { background:#f5f9f6; }
    .reference-invoice .items tbody td strong { color:#234d32; }
    .reference-invoice .items tbody td:last-child { font-weight:bold; }
    .reference-invoice .section-label { color:#28643d; }
    .reference-invoice .bank-details, .reference-invoice .amount-words { background:#f5f9f6; }
    .reference-invoice .grand-total td { background:#327548; color:#fff; border-color:#327548; padding-top:6px; padding-bottom:6px; }
    .reference-invoice .document-notes { font-size:10px; line-height:1.5; }
    .reference-invoice .document-notes strong { color:#28643d; }
    .reference-invoice .payable { color:#245d38; font-size:12px; }
    .reference-invoice .signature { color:#28643d; background:#f5f9f6; }
    .reference-invoice .number { text-align:right; white-space:nowrap; }
    .reference-invoice .items { margin-top:7px; font-size:10px; }
    .reference-invoice .items tr { page-break-inside:avoid; }
    .reference-invoice .items thead { display:table-header-group; }
    .reference-invoice .closing { page-break-inside:avoid; margin-top:7px; }
</style>
@include('pdfbuilder.partials.invoice-customer-header')
<div class="reference-invoice">
    <table class="grid items">
        <thead><tr>
            @foreach ($tableColumns as $column)
                <th style="width:{{ number_format($column['weight'] / $tableWeight * 100, 4, '.', '') }}%;">{{ $column['label'] }}</th>
            @endforeach
        </tr></thead>
        <tbody>
        @foreach ($invoiceRows as $row)
            @php
                $rowQty = (float) ($row['quantity'] ?? 0);
                $rowPrice = (float) ($row['price'] ?? 0);
                $rowTaxable = $rowQty * $rowPrice;
                $rowRate = (float) ($row['tax_rate'] ?? 0);
                $rowLabel = strtoupper($row['tax_label'] ?? '');
                $rowRates = array_fill_keys($invoiceTaxLabels, 0.0);
                if (str_contains($rowLabel, 'CGST') && str_contains($rowLabel, 'SGST')) {
                    $rowRates['CGST'] = $rowRates['SGST'] = $rowRate / 2;
                } elseif (isset($rowRates[$rowLabel])) {
                    $rowRates[$rowLabel] = $rowRate;
                }
                foreach ($invoiceTaxLabels as $label) {
                    if (isset($row[strtolower($label) . '_rate'])) $rowRates[$label] = (float) $row[strtolower($label) . '_rate'];
                }
                $rowTax = isset($row['tax_amount']) ? (float) $row['tax_amount'] : $rowTaxable * array_sum($rowRates) / 100;
            @endphp
            <tr>
                @if ($tableDisplay['serial'])<td>{{ $loop->iteration }}</td>@endif
                <td><strong>{{ $row['name'] ?? 'Solar installation' }}</strong>
                    @if ($tableDisplay['description'] && !empty($row['description']))<br>{!! nl2br(e($row['description'])) !!}@endif
                    @if ($tableDisplay['hsn'] && !empty($row['hsn_sac']))<br>HSN/SAC: {{ $row['hsn_sac'] }}@endif
                </td>
                @if ($tableDisplay['quantity'])<td class="number">{{ rtrim(rtrim(number_format($rowQty, 2, '.', ''), '0'), '.') }}</td>@endif
                @if ($tableDisplay['unit'])<td>{{ $row['unit'] ?? 'Nos.' }}</td>@endif
                @if ($tableDisplay['rate'])<td class="number">{{ number_format($rowPrice, 2) }}</td>@endif
                @if ($tableDisplay['taxable'])<td class="number">{{ number_format($rowTaxable, 2) }}</td>@endif
                @if ($tableDisplay['taxes'])
                    @foreach ($rowRates as $rate)<td class="number">{{ number_format($rate, 2) }}%</td>@endforeach
                @endif
                @if ($tableDisplay['amount'])<td class="number">{{ number_format($rowTaxable + $rowTax, 2) }}</td>@endif
            </tr>
        @endforeach
        </tbody>
    </table>
    <div class="closing">
        <table class="grid">
            <tr>
                <td class="bank-details" style="width:30%;">
                    @if ($summaryHasBank)
                        <strong class="section-label">Bank Details:</strong><br>Bank Name: {{ $summaryBankName }}
                        @foreach ($summaryBankFields as $field)<br>{{ $field['label'] }}: {{ $field['value'] }}@endforeach
                    @endif
                    @if ($summaryHasQr)<br><img src="{{ $summaryQrImage }}" alt="Payment QR Code" style="width:65px;height:65px;margin-top:5px;">@endif
                </td>
                <td class="amount-words" style="width:30%;"><strong class="section-label">Total Invoice Amount in Words:</strong><br>{{ $invoiceWords }}</td>
                <td style="width:40%;padding:0;border:0;">
                    <table class="grid">
                        <tr><td>Total Amount before Tax (₹)</td><td class="number" style="width:30%;">{{ number_format($summaryBaseCost + $summaryBomTotal, 2) }}</td></tr>
                        @foreach ($invoiceTaxes as $label => $amount)
                            @if ($amount > 0)<tr><td>Add {{ $label }} (₹)</td><td class="number">{{ number_format($amount, 2) }}</td></tr>@endif
                        @endforeach
                        @if ($summarySolarStructureCharges > 0)<tr><td>Structure Charges (₹)</td><td class="number">{{ number_format($summarySolarStructureCharges, 2) }}</td></tr>@endif
                        @if ($summaryDiscount > 0)<tr><td>Discount (₹)</td><td class="number">-{{ number_format($summaryDiscount, 2) }}</td></tr>@endif
                        @if ($summarySubsidy > 0)<tr><td>Subsidy (₹)</td><td class="number">-{{ number_format($summarySubsidy, 2) }}</td></tr>@endif
                        <tr class="grand-total"><td><strong>Grand Total (₹)</strong></td><td class="number"><strong>{{ number_format($summaryNetPayable, 2) }}</strong></td></tr>
                    </table>
                </td>
            </tr>
            @if (trim((string) ($estdata->terms_conditions ?? '')) !== '')
            <tr><td class="document-notes" colspan="3"><strong>Terms &amp; Conditions:</strong><br>{!! nl2br(e($estdata->terms_conditions)) !!}</td></tr>
            @endif
            @if ($summaryHasComment)
            <tr><td class="document-notes" colspan="3"><strong>Notes:</strong><br>{!! nl2br(e($summaryEstimateComment)) !!}</td></tr>
            @endif
            <tr>
                <td colspan="2" style="vertical-align:bottom;height:70px;">
                    <strong class="payable">Total Payable (₹): {{ number_format($summaryNetPayable, 2) }}</strong><br>
                    @if ($summarySubsidy > 0)Subsidy amount to be credited to the client's account.<br>@endif
                    This is a computer-generated invoice. E. &amp; O. E.
                </td>
                <td class="signature" style="text-align:center;">For, {{ $summaryCompanyName }}<br><br><br><br><strong>Authorised Signatory</strong></td>
            </tr>
        </table>
    </div>
</div>
