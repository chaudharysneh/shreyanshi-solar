@php
    $invoiceCustomer = $estimate->customer ?? null;
    $customerDetail = static function ($value): string {
        $value = trim((string) $value);
        return in_array($value, ['-', '--'], true) ? '' : $value;
    };
    $invoicePhone = $customerDetail($invoiceCustomer->phone ?? $estdata->phone ?? '');
    $invoiceBilling = $customerDetail($invoiceCustomer->address ?? $estdata->address ?? '');
    $invoiceShipping = $customerDetail($invoiceCustomer->shipping_address ?? '') ?: $invoiceBilling;
@endphp
<style>
    .invoice-customer-header { width:98%; margin:0 auto; color:#24372d; font-family:'DejaVu Sans',sans-serif; font-size:11px; line-height:1.4; }
    .invoice-customer-header table { width:100%; border-collapse:collapse; table-layout:fixed; }
    .invoice-customer-header .grid th, .invoice-customer-header .grid td { border:0.6px solid #cbdcd0; padding:4px 5px; vertical-align:top; overflow-wrap:break-word; }
    .invoice-customer-header .grid th { background:#eaf3ec; color:#245d38; text-align:left; font-weight:bold; }
</style>
<div class="invoice-customer-header">
    <h2 style="text-align:center;color:#28643d;font-size:17px;letter-spacing:1.5px;line-height:1.15;margin:3px 0 {{ !empty($invoiceOnly) || empty($documentTitle) ? '2px' : '0' }};padding:0;">{{ $documentTitle ?? 'INVOICE' }}</h2>
    <table style="margin-bottom:7px;">
        <tr>
            <td style="vertical-align:bottom;"><strong>GSTIN:</strong> {{ $companySettings['company_tax_id'] ?? '--' }}</td>
            <td style="text-align:right;"><strong>{{ $documentNumberLabel ?? 'Invoice No.' }}: {{ $summaryEstimateNo }}</strong><br>Date: {{ date('d-M-Y', strtotime($summaryDate)) }}</td>
        </tr>
    </table>
    <table class="grid">
        <tr><th>Billing Address</th><th>Shipping Address</th></tr>
        <tr>
            <td><strong>{{ $estdata->name ?? '--' }}</strong>
                @if ($invoicePhone !== '')<br>Phone: {{ $invoicePhone }}@endif
                @if ($invoiceBilling !== '')<br>{!! nl2br(e($invoiceBilling)) !!}@endif
                @if (!empty($showCustomerEmail) && $customerDetail($estdata->email ?? '') !== '')<br>Email: {{ $estdata->email }}@endif
                @if (!empty($invoiceCustomer->tax_number))<br>GSTIN: {{ $invoiceCustomer->tax_number }}@endif
            </td>
            <td><strong>{{ $estdata->name ?? '--' }}</strong>
                @if ($invoicePhone !== '')<br>Phone: {{ $invoicePhone }}@endif
                @if ($invoiceShipping !== '')<br>{!! nl2br(e($invoiceShipping)) !!}@endif
            </td>
        </tr>
    </table>
</div>
