<?php

namespace App\Support;

class CustomerDocumentFields
{
    public const REQUIRED = [
        'aadhar_card' => 'Aadhar card',
        'pan_card' => 'PAN card',
        'electricity_bill' => 'Electricity bill',
        'bank_document' => 'Bank passbook / cancelled cheque',
        'rooftop_images' => 'Rooftop images',
    ];

    public static function rules(): array
    {
        $rules = [
            'documents' => ['required', 'array'],
            'documents.*' => ['array:category,label,files'],
            'documents.*.category' => ['required', 'in:' . implode(',', array_keys(self::REQUIRED)) . ',extra'],
            'documents.*.label' => ['required', 'string', 'max:100'],
            'documents.*.files' => ['required', 'array', 'min:1'],
            'documents.*.files.*' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,webp,jfif'],
        ];

        return $rules;
    }

}
