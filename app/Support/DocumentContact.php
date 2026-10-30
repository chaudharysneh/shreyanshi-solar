<?php

namespace App\Support;

class DocumentContact
{
    public static function lines(?string $phone, ?string $email, ?string $taxNumber): array
    {
        $contact = array_filter([trim($phone ?? ''), trim($email ?? '')], fn ($value) => $value !== '');
        $lines = $contact ? [implode(' | ', $contact)] : [];
        $taxNumber = trim($taxNumber ?? '');
        if ($taxNumber !== '') {
            $lines[] = 'GSTIN/UIN: ' . $taxNumber;
        }

        return $lines;
    }
}
