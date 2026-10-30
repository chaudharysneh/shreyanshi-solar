<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class DocumentSummaryPresenter
{
    public static function fromRecord(Model $record, ?Model $customer, array $companySettings, array $options = []): array
    {
        $estdata = self::buildEstdata($record, $customer);

        $documentNo = (string) ($options['document_no'] ?? $estdata->estimate_no ?? $estdata->invoice_no ?? '--');
        $documentDate = (string) ($options['document_date'] ?? self::resolveDate($estdata));
        $quantity = (string) ($options['quantity'] ?? $estdata->quantity ?? '0');

        return self::calculate($estdata, $companySettings, $documentNo, $documentDate, $quantity);
    }

    public static function forView(Model $record, ?Model $customer, array $companySettings, $user, array $options = []): array
    {
        $summary = self::fromRecord($record, $customer, $companySettings, $options);
        $summary['summaryLogoUrl'] = self::companyLogoUrl($companySettings, $user);
        $summary['summaryQrUrl'] = self::companyQrUrl($companySettings, $user);

        return $summary;
    }

    public static function companyLogoUrl(array $settings, $user, string $fallback = 'assets/img/logo.jpg'): string
    {
        $companyLogoPath = $settings['company_logo_path'] ?? null;
        if ($companyLogoPath && Storage::disk('public')->exists($companyLogoPath)) {
            return route('profile.company_logo.image') . '?v=' . Storage::disk('public')->lastModified($companyLogoPath);
        }

        if ($user && !empty($user->company_logo) && Storage::disk('public')->exists($user->company_logo)) {
            return asset('storage/' . $user->company_logo);
        }

        return asset($fallback);
    }

    public static function companyQrUrl(array $settings, $user): ?string
    {
        $companyQrCodePath = $settings['company_qr_code_path'] ?? null;
        if ($companyQrCodePath && Storage::disk('public')->exists($companyQrCodePath)) {
            return route('profile.company_qr_code.image') . '?v=' . Storage::disk('public')->lastModified($companyQrCodePath);
        }

        if ($user && !empty($user->qr_code) && Storage::disk('public')->exists($user->qr_code)) {
            return asset('storage/' . $user->qr_code);
        }

        return null;
    }

    public static function fromEstdata(object $estdata, array $companySettings, string $quantity, array $options = []): array
    {
        $documentNo = (string) ($options['document_no'] ?? $estdata->estimate_no ?? $estdata->invoice_no ?? ($options['estimate_no'] ?? '--'));
        $documentDate = (string) ($options['document_date'] ?? self::resolveDate($estdata));

        return self::calculate($estdata, $companySettings, $documentNo, $documentDate, $quantity);
    }

    private static function buildEstdata(Model $record, ?Model $customer): object
    {
        $estdata = new \stdClass();

        foreach ($record->getAttributes() as $key => $value) {
            $estdata->{$key} = $value;
        }

        if (!empty($record->estimate_date)) {
            $estdata->estimate_date = $record->estimate_date instanceof \Carbon\Carbon
                ? $record->estimate_date->format('Y-m-d')
                : $record->estimate_date;
        } elseif (!empty($record->invoice_date)) {
            $estdata->estimate_date = $record->invoice_date instanceof \Carbon\Carbon
                ? $record->invoice_date->format('Y-m-d')
                : $record->invoice_date;
        }

        $estdata->name = $customer->name ?? '--';
        $estdata->email = $customer->email ?? '--';
        $estdata->address = $customer->address ?? '--';
        $estdata->phone = $customer->phone ?? '--';

        return $estdata;
    }

    private static function resolveDate(object $estdata): string
    {
        if (!empty($estdata->estimate_date)) {
            return date('Y-m-d', strtotime((string) $estdata->estimate_date));
        }

        if (!empty($estdata->invoice_date)) {
            return date('Y-m-d', strtotime((string) $estdata->invoice_date));
        }

        return date('Y-m-d');
    }

    private static function calculate(
        object $estdata,
        array $companySettings,
        string $documentNo,
        string $documentDate,
        string $quantity
    ): array {
        // Older invoices contain JSON strings encoded again by their array casts.
        // Normalize both storage formats before using the shared estimate calculations.
        $estdata = clone $estdata;
        foreach (['product_name', 'gst_breakdown'] as $field) {
            $value = $estdata->{$field} ?? [];
            for ($depth = 0; $depth < 2 && is_string($value); $depth++) {
                $value = json_decode($value, true);
            }
            $estdata->{$field} = is_array($value) ? $value : [];
        }

        $summaryProducts = !empty($estdata->product_name)
            ? (is_array($estdata->product_name) ? $estdata->product_name : (is_string($estdata->product_name) ? json_decode($estdata->product_name, true) : []))
            : [];
        $summaryProductsTotal = 0.0;

        if (is_array($summaryProducts)) {
            foreach ($summaryProducts as $summaryProduct) {
                $summaryProductsTotal += (float) ($summaryProduct['quantity'] ?? 0) * (float) ($summaryProduct['price'] ?? 0);
            }
        }

        $summaryBaseCost = isset($estdata->price) ? (float) $estdata->price : 0;
        $summaryBomTotal = $summaryProductsTotal;
        $summaryGstRate = isset($estdata->gst) ? (float) $estdata->gst : 0;
        $summaryDiscount = isset($estdata->discount) ? (float) $estdata->discount : 0;
        $summarySubsidy = isset($estdata->subsidy_amount) ? (float) $estdata->subsidy_amount : 0;
        $summarySolarStructureCharges = isset($estdata->solar_structure_charges) ? (float) $estdata->solar_structure_charges : 0;
        $summaryGstAmount = null;
        $summaryGstBreakdown = [];
        $summaryBreakupLines = [];
        $summaryUsesGlobalTax = false;
        $summaryHasSavedTaxScope = false;

        if (isset($estdata->gst_amount) && $estdata->gst_amount !== null && $estdata->gst_amount !== '') {
            $summaryGstAmount = (float) $estdata->gst_amount;
        }

        if (!empty($estdata->gst_breakdown)) {
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

        if ($summaryGstAmount === null && !empty($estdata->product_name)) {
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

        if (empty($summaryBreakupLines) && !empty($estdata->product_name)) {
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

        if (!$summaryHasSavedTaxScope && $summaryBaseCost > 0 && $summaryBomTotal <= 0) {
            $summaryUsesGlobalTax = true;
        }
        if (in_array($estdata->price_mode ?? null, ['base', 'bom'], true)) {
            $summaryUsesGlobalTax = $estdata->price_mode === 'base';
        }

        if (!empty($estdata->product_name)) {
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

        $summaryGstRateText = is_numeric($summaryGstRate) ? rtrim(rtrim(number_format((float) $summaryGstRate, 2, '.', ''), '0'), '.') : '';
        $summaryShowGst = ((float) $summaryGstAmount > 0) || ((float) $summaryGstRate > 0);
        $summaryShowBomTaxes = !empty($summaryBreakupLines) && $summaryBomTaxTotal > 0;
        $summaryInvoiceSubtotal = $summaryBaseCost + $summaryBomTotal + $summaryBomTaxTotal + $summarySolarStructureCharges - $summaryDiscount;
        $summaryNetPayable = $summaryInvoiceSubtotal - $summarySubsidy;

        $summaryBankFields = array_values(array_filter([
            ['label' => 'A/C Name', 'value' => $companySettings['account_name'] ?? ''],
            ['label' => 'A/C No.', 'value' => $companySettings['account_number'] ?? ''],
            ['label' => 'IFSC Code', 'value' => $companySettings['ifsc_code'] ?? ''],
            ['label' => 'Branch', 'value' => $companySettings['branch_name'] ?? ''],
        ], fn ($field) => trim((string) ($field['value'] ?? '')) !== ''));

        // Missing settings are treated as enabled so existing documents retain their current layout.
        $summaryShowBankDetails = (string) ($companySettings['show_bank_details'] ?? '1') !== '0';
        $summaryShowCompanyQrCode = (string) ($companySettings['show_company_qr_code'] ?? '1') !== '0';

        return [
            'estdata' => $estdata,
            'quantity' => $quantity,
            'summaryEstimateNo' => $documentNo,
            'summaryDate' => $documentDate,
            'summaryCompanyName' => $companySettings['company_name'] ?? '--',
            'summaryCompanyAddress' => $companySettings['company_address'] ?? '',
            'summaryCompanyPhone' => $companySettings['phone'] ?? '',
            'summaryEstimateTypeLabel' => isset($estdata->type) ? ucfirst((string) $estdata->type) : '--',
            'summarySolarMeterLabel' => !empty($estdata->solar_meter_charges) ? ucwords(str_replace('_', ' ', (string) $estdata->solar_meter_charges)) : '--',
            'summaryEstimateComment' => $estdata->estimate_comment ?? ($estdata->comment ?? '--'),
            'summaryBankName' => trim((string) ($companySettings['bank_name'] ?? '')),
            'summaryBankFields' => $summaryBankFields,
            'summaryShowBankDetails' => $summaryShowBankDetails,
            'summaryShowCompanyQrCode' => $summaryShowCompanyQrCode,
            'summaryBaseCost' => $summaryBaseCost,
            'summaryBomTotal' => $summaryBomTotal,
            'summaryBreakupLines' => $summaryBreakupLines,
            'summaryShowBomTaxes' => $summaryShowBomTaxes,
            'summaryUsesGlobalTax' => $summaryUsesGlobalTax,
            'summaryShowGst' => $summaryShowGst,
            'summaryGstRateText' => $summaryGstRateText,
            'summaryBomTaxTotal' => $summaryBomTaxTotal,
            'summarySolarStructureCharges' => $summarySolarStructureCharges,
            'summaryDiscount' => $summaryDiscount,
            'summaryInvoiceSubtotal' => $summaryInvoiceSubtotal,
            'summarySubsidy' => $summarySubsidy,
            'summaryNetPayable' => $summaryNetPayable,
        ];
    }
}
