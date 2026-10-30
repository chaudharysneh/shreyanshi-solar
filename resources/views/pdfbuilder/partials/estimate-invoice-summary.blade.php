<?php
$summaryProducts = ($estdata && !empty($estdata->product_name))
    ? (is_array($estdata->product_name) ? $estdata->product_name : (is_string($estdata->product_name) ? json_decode($estdata->product_name, true) : []))
    : [];
$summaryProductsTotal = 0.0;
if (is_array($summaryProducts)) {
    foreach ($summaryProducts as $summaryProduct) {
        $summaryProductsTotal += (float) ($summaryProduct['quantity'] ?? 0) * (float) ($summaryProduct['price'] ?? 0);
    }
}
$summaryBaseCost = ($estdata && isset($estdata->price)) ? (float) $estdata->price : 0;
$summaryBomTotal = $summaryProductsTotal;
$summarySubtotal = $summaryBaseCost + $summaryBomTotal;
$summaryGstRate = ($estdata && isset($estdata->gst)) ? (float) $estdata->gst : 0;
$summaryDiscount = ($estdata && isset($estdata->discount)) ? (float) $estdata->discount : 0;
$summarySubsidy = ($estdata && isset($estdata->subsidy_amount)) ? (float) $estdata->subsidy_amount : 0;
$summarySolarStructureCharges = ($estdata && isset($estdata->solar_structure_charges)) ? (float) $estdata->solar_structure_charges : 0;
$summaryIsQuotation = ($estdata && isset($estdata->is_quotation)) ? (int) $estdata->is_quotation : 0;
$summaryGstAmount = null;
$summaryGstBreakdown = [];

if ($estdata && isset($estdata->gst_amount) && $estdata->gst_amount !== null && $estdata->gst_amount !== '') {
    $summaryGstAmount = (float) $estdata->gst_amount;
}

if ($estdata && !empty($estdata->gst_breakdown)) {
    $decodedSummaryGst = is_array($estdata->gst_breakdown)
        ? $estdata->gst_breakdown
        : json_decode($estdata->gst_breakdown, true);
    if (is_array($decodedSummaryGst)) {
        $summaryGstBreakdown = $decodedSummaryGst;
        if ($summaryGstAmount === null && isset($decodedSummaryGst['gst_amount'])) {
            $summaryGstAmount = (float) $decodedSummaryGst['gst_amount'];
        }
    }
}

if ($summaryGstAmount === null && $estdata && !empty($estdata->product_name)) {
    $summaryItems = is_array($estdata->product_name) ? $estdata->product_name : json_decode($estdata->product_name, true);
    if (is_array($summaryItems)) {
        $summaryTaxSum = 0.0;
        foreach ($summaryItems as $item) {
            if (isset($item['tax_amount']) && is_numeric($item['tax_amount'])) {
                $summaryTaxSum += (float) $item['tax_amount'];
            }
        }
        if ($summaryTaxSum > 0) {
            $summaryGstAmount = $summaryTaxSum;
        }
    }
}

if ($summaryGstAmount === null) {
    $summaryGstAmount = $summaryBomTotal * ($summaryGstRate / 100);
}

$summaryHeaderCellStyle = "background-color:#4b9349;color:#fff;border:1px solid #333;padding:5px 8px;font-size:15px;font-family:'Montserrat',sans-serif;font-weight:bold;line-height:1.25;";
$summaryCellStyle = "border:1px solid #333;padding:5px 8px;font-size:15px;font-family:'Montserrat',sans-serif;color:#000;line-height:1.25;";
$summaryFooterHeaderCellStyle = "background-color:#4b9349;color:#fff;border:1px solid #333;padding:4px 6px;font-size:13px;font-family:'Montserrat',sans-serif;font-weight:bold;line-height:1.2;";
$summaryFooterCellStyle = "border:1px solid #333;padding:4px 6px;font-size:13px;font-family:'DejaVu Sans',sans-serif;color:#000;line-height:1.2;vertical-align:top;";
$summaryRightCellStyle = $summaryCellStyle . 'text-align:right;';
$summaryHighlightCellStyle = $summaryRightCellStyle . 'background-color:#4b9349;color:#fff;font-weight:bold;';
$summaryHeaderTextStyle = "font-size:13.5px;font-family:'Montserrat',sans-serif;color:#000;line-height:1.45;";
$summaryEstimationHeaderTextStyle = "font-size:16px;font-family:'Montserrat',sans-serif;color:#000;line-height:1.45;";
$summaryEstimateNo = $estdata->estimate_no ?? ($estimate_no ?? '--');
$summaryDate = ($estdata && !empty($estdata->estimate_date)) ? date('Y-m-d', strtotime($estdata->estimate_date)) : date('Y-m-d');
$summaryCompanyName = $companySettings['company_name'] ?? $globalCompanyName ?? '--';
$summaryCompanyAddress = $companySettings['company_address'] ?? $user['address'] ?? '';
$summaryCompanyPhone = $companySettings['phone'] ?? $user['phone'] ?? $user['contact'] ?? '';
$summaryEstimateTypeLabel = $estdata && isset($estdata->type) ? ucfirst((string) $estdata->type) : '--';
$summarySolarMeterLabel = ($estdata && !empty($estdata->solar_meter_charges)) ? ucwords(str_replace('_', ' ', (string) $estdata->solar_meter_charges)) : '--';
$summaryEstimateComment = $estdata && isset($estdata->estimate_comment) ? $estdata->estimate_comment : ($estdata->comment ?? '--');
$summaryBankFields = array_values(array_filter([
    ['label' => 'A/C Name', 'value' => $companySettings['account_name'] ?? ''],
    ['label' => 'A/C No.', 'value' => $companySettings['account_number'] ?? ''],
    ['label' => 'IFSC Code', 'value' => $companySettings['ifsc_code'] ?? ''],
    ['label' => 'Branch', 'value' => $companySettings['branch_name'] ?? ''],
], fn ($field) => trim((string) ($field['value'] ?? '')) !== ''));
$summaryBankName = trim((string) ($companySettings['bank_name'] ?? ''));
$summaryQrImage = !empty($companyQrCodePath) ? normalize_pdf_image($companyQrCodePath) : '';
$summaryGstRateText = is_numeric($summaryGstRate) ? rtrim(rtrim(number_format((float) $summaryGstRate, 2, '.', ''), '0'), '.') : '';
$summaryShowGst = ((float) $summaryGstAmount > 0) || ((float) $summaryGstRate > 0);
$summaryBreakupLines = [];
$summaryUsesGlobalTax = false;
$summaryHasSavedTaxScope = false;

if (!empty($summaryGstBreakdown['groups']) && is_array($summaryGstBreakdown['groups'])) {
    foreach ($summaryGstBreakdown['groups'] as $group) {
        if ((string) ($group['tax_type'] ?? '') === 'global_tax') {
            $summaryUsesGlobalTax = true;
            $summaryHasSavedTaxScope = true;
        } elseif ((string) ($group['tax_type'] ?? '') === 'bom_selected_tax') {
            $summaryHasSavedTaxScope = true;
        }
        if ((string) ($group['tax_type'] ?? '') === 'gst_percent') {
            continue;
        }
        $lines = $group['lines'] ?? [];
        if (!is_array($lines)) {
            continue;
        }
        foreach ($lines as $line) {
            $label = trim((string) ($line['label'] ?? ''));
            if ($label === '' || strtoupper($label) === 'GST') {
                continue;
            }
            $summaryBreakupLines[] = [
                'label' => $label,
                'rate' => $line['rate'] ?? null,
                'amount' => (float) ($line['amount'] ?? 0),
            ];
        }
    }
}

if (!$summaryHasSavedTaxScope && $summaryBaseCost > 0 && $summaryBomTotal <= 0) {
    $summaryUsesGlobalTax = true;
}
if (in_array($estdata->price_mode ?? null, ['base', 'bom'], true)) {
    $summaryUsesGlobalTax = $estdata->price_mode === 'base';
}

if (empty($summaryBreakupLines) && $estdata && !empty($estdata->product_name)) {
    $summaryItems = is_array($estdata->product_name) ? $estdata->product_name : json_decode($estdata->product_name, true);
    if (is_array($summaryItems)) {
        $summaryTaxBuckets = [
            'CGST' => ['amount' => 0.0, 'rate' => null],
            'SGST' => ['amount' => 0.0, 'rate' => null],
            'IGST' => ['amount' => 0.0, 'rate' => null],
        ];
        foreach ($summaryItems as $item) {
            foreach (['cgst' => 'CGST', 'sgst' => 'SGST', 'igst' => 'IGST'] as $key => $label) {
                if (isset($item[$key . '_amount']) && is_numeric($item[$key . '_amount'])) {
                    $summaryTaxBuckets[$label]['amount'] += (float) $item[$key . '_amount'];
                    if ($summaryTaxBuckets[$label]['rate'] === null && isset($item[$key . '_rate']) && is_numeric($item[$key . '_rate'])) {
                        $summaryTaxBuckets[$label]['rate'] = (float) $item[$key . '_rate'];
                    }
                }
            }
        }
        foreach ($summaryTaxBuckets as $label => $bucket) {
            if ($bucket['amount'] > 0) {
                $summaryBreakupLines[] = ['label' => $label, 'rate' => $bucket['rate'], 'amount' => $bucket['amount']];
            }
        }
    }
}
if ($estdata && !empty($estdata->product_name)) {
    $summaryItems = is_array($estdata->product_name) ? $estdata->product_name : json_decode($estdata->product_name, true);
    if (is_array($summaryItems)) {
        $summaryProductTaxBreakupLines = [];
        foreach ($summaryItems as $item) {
            $itemRate = (float) ($item['tax_rate'] ?? 0);
            $itemLabel = strtoupper(trim((string) ($item['tax_label'] ?? '')));
            $itemTaxable = (float) ($item['quantity'] ?? 0) * (float) ($item['price'] ?? 0);
            if ($itemRate <= 0 || $itemTaxable <= 0) {
                continue;
            }
            if (str_contains($itemLabel, 'CGST') && str_contains($itemLabel, 'SGST')) {
                $halfRate = $itemRate / 2;
                foreach (['CGST', 'SGST'] as $splitLabel) {
                    $summaryProductTaxBreakupLines[] = [
                        'label' => $splitLabel,
                        'rate' => $halfRate,
                        'amount' => ($itemTaxable * $halfRate) / 100,
                    ];
                }
            } else {
                $summaryProductTaxBreakupLines[] = [
                    'label' => str_contains($itemLabel, 'IGST') ? 'IGST' : 'GST',
                    'rate' => $itemRate,
                    'amount' => ($itemTaxable * $itemRate) / 100,
                ];
            }
        }
        if (!empty($summaryProductTaxBreakupLines)) {
            $summaryAggregatedTaxLines = [];
            foreach ($summaryProductTaxBreakupLines as $taxLine) {
                $taxKey = ($taxLine['label'] ?? '') . '|' . number_format((float) ($taxLine['rate'] ?? 0), 4, '.', '');
                if (!isset($summaryAggregatedTaxLines[$taxKey])) {
                    $summaryAggregatedTaxLines[$taxKey] = [
                        'label' => $taxLine['label'] ?? '',
                        'rate' => $taxLine['rate'] ?? null,
                        'amount' => 0,
                    ];
                }
                $summaryAggregatedTaxLines[$taxKey]['amount'] += (float) ($taxLine['amount'] ?? 0);
            }
            $summaryBreakupLines = array_values($summaryAggregatedTaxLines);
        }
    }
}
if (!empty($summaryBreakupLines)) {
    $summaryBomTaxTotal = array_sum(array_map(fn ($line) => (float) ($line['amount'] ?? 0), $summaryBreakupLines));
    $summaryGstAmount = $summaryBomTaxTotal;
} else {
    $summaryBomTaxTotal = (float) $summaryGstAmount;
}
if (empty($summaryBreakupLines) && $summaryGstRate > 0 && $summaryBomTaxTotal > 0) {
    $summaryBreakupLines = [
        ['label' => 'CGST', 'rate' => $summaryGstRate / 2, 'amount' => $summaryBomTaxTotal / 2],
        ['label' => 'SGST', 'rate' => $summaryGstRate / 2, 'amount' => $summaryBomTaxTotal / 2],
    ];
}
$summaryShowBomTaxes = !empty($summaryBreakupLines) && $summaryBomTaxTotal > 0;
$summaryInvoiceSubtotal = $summaryBaseCost + $summaryBomTotal + $summaryBomTaxTotal + $summarySolarStructureCharges - $summaryDiscount;
$summaryNetPayable = $summaryInvoiceSubtotal - $summarySubsidy;
$summaryTotalPayable = $summaryInvoiceSubtotal;
$summaryLendingCost = $summaryNetPayable;
if (!empty($invoiceOnly)) {
    extract(\App\Support\DocumentSummaryPresenter::fromEstdata(
        $estdata,
        $companySettings instanceof \Illuminate\Support\Collection ? $companySettings->all() : (array) $companySettings,
        (string) $quantity,
        ['document_no' => $estimate_no ?? $estdata->invoice_no ?? '--']
    ));
}
$summaryShowBankDetails = (string) ($companySettings['show_bank_details'] ?? '1') !== '0';
$summaryShowCompanyQrCode = (string) ($companySettings['show_company_qr_code'] ?? '1') !== '0';
$summaryHasBank = $summaryShowBankDetails && (trim((string) $summaryBankName) !== '' || !empty($summaryBankFields));
$summaryHasQr = $summaryShowCompanyQrCode && !empty($summaryQrImage) && str_starts_with($summaryQrImage, 'data:image/');
$summaryHasComment = !in_array(trim(strip_tags((string) $summaryEstimateComment)), ['', '-', '--', '—'], true);
$summaryFooterWeight = ($summaryHasComment ? 35 : 0) + ($summaryHasBank ? 40 : 0) + ($summaryHasQr ? 25 : 0);
$summaryCommentWidth = $summaryHasComment ? 100 * 35 / $summaryFooterWeight : 0;
$summaryBankWidth = $summaryHasBank ? 100 * 40 / $summaryFooterWeight : 0;
$summaryQrWidth = $summaryHasQr ? 100 * 25 / $summaryFooterWeight : 0;
?>

<table width="98%" align="center" cellpadding="0" cellspacing="0" style="margin-top:0;margin-bottom:8px;border-collapse:collapse;">
    <tr>
        <td width="45%" valign="top" align="left" style="<?= $summaryHeaderTextStyle ?>padding-bottom:8px;">
            <?php if (!empty($logoBase64)): ?>
                <img src="<?= $logoBase64 ?>" alt="Company Logo" style="max-width:250px;max-height:100px;object-fit:contain;height:auto;">
            <?php else: ?>
                <span style="color:#666;">Company Logo</span>
            <?php endif; ?>
        </td>
        <td width="55%" valign="top" align="right" style="<?= $summaryHeaderTextStyle ?>padding-bottom:8px;">
            <strong style="font-size:16px;"><?= esc($summaryCompanyName) ?></strong><br>
            <?php foreach (\App\Support\DocumentAddress::lines($summaryCompanyAddress) as $addressLine): ?>
                <div style="white-space:normal;word-wrap:break-word;"><?= esc($addressLine) ?></div>
            <?php endforeach; ?>
            <?php foreach (\App\Support\DocumentContact::lines($summaryCompanyPhone, $companySettings['email'] ?? $user['email'] ?? '', $companySettings['company_tax_id'] ?? '') as $contactLine): ?>
                <div style="white-space:nowrap;"><?= esc($contactLine) ?></div>
            <?php endforeach; ?>
        </td>
    </tr>
    <tr>
        <td colspan="2" style="border-top:1px solid #e5e5e5;padding-top:10px;"></td>
    </tr>
</table>

<table width="98%" align="center" cellpadding="0" cellspacing="0" style="margin-bottom:8px;border-collapse:collapse;">
    <tr>
        <td width="33%" align="left" style="<?= $summaryEstimationHeaderTextStyle ?>font-weight:bold;"><?= !empty($isInvoice) ? 'Invoice' : 'Estimate' ?> no.: #<?= esc($summaryEstimateNo) ?></td>
        <td width="34%" align="center" style="<?= $summaryEstimationHeaderTextStyle ?>font-weight:bold;text-decoration:underline;"><?= !empty($isInvoice) ? 'INVOICE' : 'ESTIMATION' ?></td>
        <td width="33%" align="right" style="<?= $summaryEstimationHeaderTextStyle ?>font-weight:bold;">Date: <?= esc($summaryDate) ?></td>
    </tr>
</table>

<table width="98%" align="center" cellpadding="0" cellspacing="0" style="margin-top:6px;border-collapse:collapse;">
    <tr>
        <td colspan="4" style="<?= $summaryHeaderCellStyle ?>">Customer Details</td>
    </tr>
    <tr>
        <td style="<?= $summaryCellStyle ?>"><strong>Customer Name</strong></td>
        <td style="<?= $summaryCellStyle ?>"><?= esc($estdata->name ?? '--') ?></td>
        <td style="<?= $summaryCellStyle ?>"><strong>Email</strong></td>
        <td style="<?= $summaryCellStyle ?>"><?= esc($estdata->email ?? '--') ?></td>
    </tr>
    <tr>
        <td style="<?= $summaryCellStyle ?>"><strong>Address</strong></td>
        <td style="<?= $summaryCellStyle ?>"><?= esc($estdata->address ?? '--') ?></td>
        <td style="<?= $summaryCellStyle ?>"><strong>Contact</strong></td>
        <td style="<?= $summaryCellStyle ?>"><?= esc($estdata->phone ?? '--') ?></td>
    </tr>
</table>

<table width="98%" align="center" cellpadding="0" cellspacing="0" style="margin-top:6px;border-collapse:collapse;">
    <tr>
        <td style="<?= $summaryHeaderCellStyle ?>width:68%;">Description</td>
        <td style="<?= $summaryHeaderCellStyle ?>width:32%;text-align:right;">Amount (<span style="font-family: DejaVu Sans, sans-serif;">&#8377;</span>)</td>
    </tr>
    <?php if ($summaryUsesGlobalTax): ?>
    <tr>
        <td style="<?= $summaryCellStyle ?>">Base cost</td>
        <td style="<?= $summaryRightCellStyle ?>"><?= number_format($summaryBaseCost, 2) ?></td>
    </tr>
    <?php else: ?>
    <tr>
        <td style="<?= $summaryCellStyle ?>">Bill of Materials (BOM)</td>
        <td style="<?= $summaryRightCellStyle ?>"><?= $summaryUsesGlobalTax ? '--' : number_format($summaryBomTotal, 2) ?></td>
    </tr>
    <?php endif; ?>
    <?php if ($summaryShowBomTaxes): ?>
    <tr>
        <td style="<?= $summaryCellStyle ?>"><strong><?= $summaryUsesGlobalTax ? 'Global Tax on Base Price' : 'Taxes on Bill of Materials (BOM Only)' ?></strong></td>
        <td style="<?= $summaryRightCellStyle ?>">&nbsp;</td>
    </tr>
        <?php foreach ($summaryBreakupLines as $line): ?>
            <?php
            $lineLabel = trim((string) ($line['label'] ?? ''));
            $lineRate = $line['rate'] ?? null;
            $lineAmount = (float) ($line['amount'] ?? 0);
            if ($lineLabel === '' || $lineAmount <= 0) {
                continue;
            }
            $lineRateText = is_numeric($lineRate) ? rtrim(rtrim(number_format((float) $lineRate, 2, '.', ''), '0'), '.') : '';
            ?>
    <tr>
        <td style="<?= $summaryCellStyle ?>"><?= esc($lineLabel) ?><?= $lineRateText !== '' ? ' (' . esc($lineRateText) . '%)' : '' ?></td>
        <td style="<?= $summaryRightCellStyle ?>"><?= number_format($lineAmount, 2) ?></td>
    </tr>
        <?php endforeach; ?>
    <tr>
        <td style="<?= $summaryCellStyle ?>"><strong><?= $summaryUsesGlobalTax ? 'Total Global Tax' : 'Total Taxes on BOM' ?></strong></td>
        <td style="<?= $summaryRightCellStyle ?>"><strong><?= number_format($summaryBomTaxTotal, 2) ?></strong></td>
    </tr>
    <?php elseif ($summaryShowGst && $summaryBomTaxTotal > 0): ?>
    <tr>
        <td style="<?= $summaryCellStyle ?>"><strong><?= $summaryUsesGlobalTax ? 'Global Tax on Base Price' : 'Taxes on Bill of Materials (BOM Only)' ?></strong></td>
        <td style="<?= $summaryRightCellStyle ?>">&nbsp;</td>
    </tr>
    <tr>
        <td style="<?= $summaryCellStyle ?>">GST<?= $summaryGstRateText !== '' ? ' (' . esc($summaryGstRateText) . '%)' : '' ?></td>
        <td style="<?= $summaryRightCellStyle ?>"><?= number_format((float) $summaryBomTaxTotal, 2) ?></td>
    </tr>
    <tr>
        <td style="<?= $summaryCellStyle ?>"><strong><?= $summaryUsesGlobalTax ? 'Total Global Tax' : 'Total Taxes on BOM' ?></strong></td>
        <td style="<?= $summaryRightCellStyle ?>"><strong><?= number_format($summaryBomTaxTotal, 2) ?></strong></td>
    </tr>
    <?php endif; ?>
    <?php if ($summarySolarStructureCharges > 0): ?>
    <tr>
        <td style="<?= $summaryCellStyle ?>">Solar Structure Charges</td>
        <td style="<?= $summaryRightCellStyle ?>"><?= number_format($summarySolarStructureCharges, 2) ?></td>
    </tr>
    <?php endif; ?>
    <?php if ($summaryDiscount > 0): ?>
    <tr>
        <td style="<?= $summaryCellStyle ?>">Discount</td>
        <td style="<?= $summaryRightCellStyle ?>">-<?= number_format($summaryDiscount, 2) ?></td>
    </tr>
    <?php endif; ?>
    <tr>
        <td style="<?= $summaryCellStyle ?>"><strong>Consumer Net Payable</strong> <span style="font-style:italic;font-weight:normal;">(<?= $summaryUsesGlobalTax ? 'Base cost + Global Tax' : 'BOM + BOM Taxes' ?><?= $summarySolarStructureCharges > 0 ? ' + Solar Structure Charges' : '' ?><?= $summaryDiscount > 0 ? ' - Discount' : '' ?>)</span></td>
        <td style="<?= $summaryRightCellStyle ?>"><strong><?= number_format($summaryInvoiceSubtotal, 2) ?></strong></td>
    </tr>
    <?php if ($summarySubsidy > 0): ?>
    <tr>
        <td style="<?= $summaryCellStyle ?>">Subsidy</td>
        <td style="<?= $summaryRightCellStyle ?>">-<?= number_format($summarySubsidy, 2) ?></td>
    </tr>
    <?php endif; ?>
    <tr>
        <td style="<?= $summaryCellStyle ?>"><strong>Net Amount Payable</strong></td>
        <td style="<?= $summaryHighlightCellStyle ?>"><strong><?= number_format($summaryNetPayable, 2) ?></strong></td>
    </tr>
</table>

<?php if ($summarySubsidy > 0): ?>
<div style="width:98%;margin:4px auto 0;font-size:14px;font-family:'Montserrat',sans-serif;color:#000;line-height:1.3;">
    <strong>Note:</strong> Subsidy Amount to be credited in clients account.
</div>
<?php endif; ?>

<div style="page-break-inside:avoid;">
<table width="98%" align="center" cellpadding="0" cellspacing="0" style="margin-top:5px;border-collapse:collapse;page-break-inside:avoid;">
    <tr>
        <td style="<?= $summaryHeaderCellStyle ?>width:38%;">System Capacity</td>
        <td style="<?= $summaryCellStyle ?>"><?= esc($quantity) ?> kW</td>
    </tr>
    <tr>
        <td style="<?= $summaryHeaderCellStyle ?>"><?= !empty($isInvoice) ? 'Invoice Type' : 'Estimate Type' ?></td>
        <td style="<?= $summaryCellStyle ?>"><?= esc($summaryEstimateTypeLabel) ?></td>
    </tr>
    <tr>
        <td style="<?= $summaryHeaderCellStyle ?>">Solar Meter Charges</td>
        <td style="<?= $summaryCellStyle ?>"><?= esc($summarySolarMeterLabel) ?></td>
    </tr>
</table>

<?php if ($summaryFooterWeight > 0): ?>
<table width="98%" align="center" cellpadding="0" cellspacing="0" style="margin-top:5px;margin-bottom:8px;border-collapse:collapse;page-break-inside:avoid;table-layout:fixed;">
    <tr style="page-break-inside:avoid;">
        <?php if ($summaryHasComment): ?>
        <td style="<?= $summaryFooterHeaderCellStyle ?>width:<?= $summaryCommentWidth ?>%;">Comment</td>
        <?php endif; ?>
        <?php if ($summaryHasBank): ?>
        <td style="<?= $summaryFooterHeaderCellStyle ?>width:<?= $summaryBankWidth ?>%;">Bank Details</td>
        <?php endif; ?>
        <?php if ($summaryHasQr): ?>
        <td style="<?= $summaryFooterHeaderCellStyle ?>width:<?= $summaryQrWidth ?>%;">QR Code</td>
        <?php endif; ?>
    </tr>
    <tr style="page-break-inside:avoid;">
        <?php if ($summaryHasComment): ?>
        <td style="<?= $summaryFooterCellStyle ?>"><?= nl2br(esc($summaryEstimateComment)) ?></td>
        <?php endif; ?>
        <?php if ($summaryHasBank): ?>
        <td style="<?= $summaryFooterCellStyle ?>padding:4px 6px;">
            <?php if ($summaryBankName !== '' || !empty($summaryBankFields)): ?>
            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;background-color:#f6fbf6;border:1px solid #cfe5cf;font-family:'DejaVu Sans',sans-serif;">
                <?php if ($summaryBankName !== ''): ?>
                <tr>
                    <td colspan="2" style="background-color:#4b9349;color:#fff;padding:4px 6px;font-size:13px;font-family:'DejaVu Sans',sans-serif;font-weight:bold;letter-spacing:0.3px;">
                        <?= esc($summaryBankName) ?>
                    </td>
                </tr>
                <?php endif; ?>
                <?php foreach ($summaryBankFields as $bankIndex => $bankField): ?>
                <?php $bankRowBorder = ($bankIndex > 0 || $summaryBankName !== '') ? 'border-top:1px solid #e3efe3;' : ''; ?>
                <tr>
                    <td width="38%" style="padding:3px 6px;font-size:13px;font-family:'DejaVu Sans',sans-serif;color:#5a6b5a;font-weight:bold;vertical-align:top;line-height:1.2;<?= $bankRowBorder ?>">
                        <?= esc($bankField['label']) ?>
                    </td>
                    <td width="62%" style="padding:3px 6px;font-size:13px;font-family:'DejaVu Sans',sans-serif;color:#1a1a1a;font-weight:normal;vertical-align:top;line-height:1.2;<?= $bankRowBorder ?>">
                        <?= esc($bankField['value']) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php else: ?>
            <span style="font-size:13px;font-family:'DejaVu Sans',sans-serif;color:#888;font-style:italic;">No bank details available.</span>
            <?php endif; ?>
        </td>
        <?php endif; ?>
        <?php if ($summaryHasQr): ?>
        <td style="<?= $summaryFooterCellStyle ?>text-align:center;">
            <?php if (!empty($summaryQrImage)): ?>
                <img src="<?= $summaryQrImage ?>" alt="QR Code" style="max-width:58px;max-height:58px;">
            <?php else: ?>
                No QR code available.
            <?php endif; ?>
        </td>
        <?php endif; ?>
    </tr>
</table>
<?php endif; ?>
</div>
