@php
    $company = $settings['company_name'] ?? config('app.name');
    $customer = $deal->customer;
    $invoice = $deal->invoice;
    $currency = $invoice?->currency?->symbol ?: ($invoice?->currency?->code ?: '');
    $money = fn ($value) => $currency . ' ' . number_format((float) $value, 2);
    $clientName = $customer?->name ?: 'Valued Client';
    $clientCompany = $customer?->company_name ?: $clientName;
    $timeline = $deal->timeline_value ? $deal->timeline_value . ' ' . ($deal->timeline_unit ?: 'days') : 'As mutually agreed';
    $preparedBy = $deal->assignedUser ?: $deal->creator;
    $preparedName = $preparedBy?->name ?: $company . ' Team';
    $preparedTitle = $preparedBy?->job_title ?: 'Authorized Representative';
    $contact = implode(' | ', array_filter([$settings['phone'] ?? null, $settings['email'] ?? null]));
    $showSignature = (string) ($settings['show_company_signature'] ?? '1') !== '0';
    $signatureImage = $images['deal_signature_path'] ?? $images['company_signature_path'] ?? null;
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Deal Confirmation</title>
    <style>
        @page { margin: 28px 30px; }
        body { margin: 0; color: #263942; font-family: DejaVu Sans, Arial, sans-serif; font-size: 13px; line-height: 1.66; }
        .page-frame { position: fixed; top: 0; right: 0; bottom: 0; left: 0; border: 2px solid #4b9349; }
        .document { padding: 18px 22px; }
        table { width: 100%; border-collapse: collapse; }
        .brand { border-bottom: 2px solid #d9eadc; margin-bottom: 28px; }
        .brand td { padding-bottom: 15px; vertical-align: middle; }
        .company { color: #1e6138; font-size: 18px; font-weight: bold; letter-spacing: .5px; }
        .company-info { color: #62756a; font-size: 10px; line-height: 1.55; }
        .subject { margin: 0 0 20px; padding: 7px 0 8px 12px; border-left: 3px solid #2f7a46; color: #263942; line-height: 1.35; }
        .subject-label { display: block; margin-bottom: 2px; color: #5b8868; font-size: 8.5px; font-weight: bold; letter-spacing: 1px; }
        .subject-title { font-size: 13px; font-weight: bold; color: #195e35; }
        p { margin: 0 0 17px; }
        .details { margin: 18px 0; page-break-inside: avoid; border: 1px solid #d8e5db; border-radius: 3px; overflow: hidden; }
        .details-title { margin: 0; padding: 6px 10px; background: #f1f7f2; color: #2f6d45; font-size: 11px; font-weight: bold; letter-spacing: .1px; border-bottom: 1px solid #d8e5db; }
        .details td { padding: 6px 10px; border: 0; border-bottom: 1px solid #e6eee8; font-size: 11px; }
        .details tr:last-child td { border-bottom: 0; }
        .details tr:nth-child(odd) td { background: #f7fbf8; }
        .details .label { width: 32%; color: #65766b; font-weight: normal; background: #fbfdfb; }
        .details .value { color: #243a2e; font-weight: bold; }
        .signature { margin-top: 30px; padding: 14px 17px; border-left: 3px solid #91bd9b; background: #fafdfb; }
        .signature-name { color: #1d5e36; font-size: 13px; font-weight: bold; }
        .contact { color: #62756a; font-size: 10.5px; }
    </style>
</head>
<body>
    <div class="page-frame"></div>
    <div class="document">
        <table class="brand"><tr>
            <td style="width:42%;">
                @if (!empty($images['company_logo_path']))
                    <img src="{{ $images['company_logo_path'] }}" alt="{{ $company }}" style="max-width:220px; max-height:65px;">
                @else
                    <span class="company">{{ $company }}</span>
                @endif
            </td>
            <td style="text-align:right;">
                <div class="company">{{ mb_strtoupper($company) }}</div>
                @if (!empty($settings['company_address']))
                    @foreach (\App\Support\DocumentAddress::lines($settings['company_address']) as $addressLine)
                        <div class="company-info" style="white-space:normal;word-wrap:break-word;">{{ $addressLine }}</div>
                    @endforeach
                @endif
                @foreach (\App\Support\DocumentContact::lines($settings['phone'] ?? '', $settings['email'] ?? '', $settings['company_tax_id'] ?? '') as $contactLine)
                    <div class="company-info" style="white-space:nowrap;">{{ $contactLine }}</div>
                @endforeach
            </td>
        </tr></table>

        <div class="subject"><span class="subject-label">SUBJECT</span><span class="subject-title">Confirmation of Partnership / Deal Closure</span></div>

        <p>Dear {{ $clientName }},</p>
        <p>We are thrilled to confirm that, after thorough discussions and negotiations, we are pleased to finalize the partnership between <strong>{{ $company }}</strong> and <strong>{{ $clientCompany }}</strong>. We appreciate the trust you have placed in our team and company.</p>

        <div class="details">
            <div class="details-title">Deal Details</div>
            <table>
                <tr><td class="label">Invoice Name</td><td class="value">{{ $invoice?->invoice_name ?: 'No invoice linked' }}</td></tr>
                <tr><td class="label">Invoice Number</td><td class="value">{{ $invoice?->invoice_no ?: '--' }}</td></tr>
                <tr><td class="label">Invoice Amount</td><td class="value">{{ $invoice ? $money($invoice->amount) : '--' }}</td></tr>
                <tr><td class="label">Timeline</td><td class="value">{{ $timeline }}</td></tr>
            </table>
        </div>

        <p>We are grateful for the opportunity to work with you and assure you that our team will deliver exceptional service. Your trust in our company is paramount, and we will strive to meet and exceed your expectations.</p>
        <p>Please feel free to reach out if you have any questions or concerns. We look forward to a successful partnership.</p>
        <p>Thank you once again for choosing {{ $company }}.</p>

        <div class="signature">
            <p>Best regards,</p>
            @if ($showSignature && $signatureImage)
                <img src="{{ $signatureImage }}" alt="Signature" style="display:block; max-width:150px; max-height:55px; margin:0 0 6px; object-fit:contain; object-position:left center;">
            @endif
            <div class="signature-name">{{ $preparedName }}</div>
            <div>{{ $preparedTitle }}</div>
            <div>{{ $company }}</div>
            @if ($contact !== '')<div class="contact">{{ $contact }}</div>@endif
        </div>
    </div>
</body>
</html>
