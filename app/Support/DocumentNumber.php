<?php

namespace App\Support;

use App\Models\{Estimate, Invoice, Setting};

class DocumentNumber
{
    public static function next(string $kind): string
    {
        $model = $kind === 'invoice' ? new Invoice() : new Estimate();
        $numbers = $model->newQuery()->withTrashed()->lockForUpdate()->pluck($kind . '_no');
        $used = [];
        foreach ($numbers as $number) {
            $pattern = $kind === 'invoice' ? '/^#?INV-(\d+)$/i' : '/^(?:#?EST-)?(\d+)$/i';
            if (preg_match($pattern, (string) $number, $matches)) $used[(int) $matches[1]] = true;
        }
        $start = Setting::where('key', $kind . '_number_start')->value('value');
        $next = $start !== null && $start !== '' ? max(1, (int) $start) : (empty($used) ? 1 : max(array_keys($used)) + 1);
        while (isset($used[$next])) $next++;
        return $kind === 'invoice' ? 'INV-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT)
            : str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
