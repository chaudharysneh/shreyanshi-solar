<?php

namespace App\Support;

class DocumentAddress
{
    public static function lines(?string $address): array
    {
        $address = trim(preg_replace('/\s+/u', ' ', $address ?? ''));
        $words = $address === '' ? ['--'] : explode(' ', $address);
        if (count($words) < 2) {
            return $words;
        }

        $split = 1;
        $difference = PHP_INT_MAX;
        for ($index = 1; $index < count($words); $index++) {
            $left = implode(' ', array_slice($words, 0, $index));
            $right = implode(' ', array_slice($words, $index));
            $distance = abs(mb_strlen($left) - mb_strlen($right));
            if ($distance < $difference) {
                $split = $index;
                $difference = $distance;
            }
        }

        return [implode(' ', array_slice($words, 0, $split)), implode(' ', array_slice($words, $split))];
    }
}
