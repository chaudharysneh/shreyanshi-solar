<?php

namespace App\Support;

class InvoiceTableDisplay
{
    public const OPTIONS = [
        'serial' => 'Row number',
        'description' => 'Product description',
        'hsn' => 'HSN / SAC code',
        'quantity' => 'Quantity',
        'unit' => 'Unit',
        'rate' => 'Rate',
        'taxable' => 'Taxable amount',
        'taxes' => 'Tax columns (CGST / SGST / IGST / GST)',
        'amount' => 'Item amount',
    ];

    public static function resolve(?array $options): array
    {
        return array_replace(array_fill_keys(array_keys(self::OPTIONS), true), array_intersect_key($options ?? [], self::OPTIONS));
    }
}
