<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DocumentCurrency
{
    public static function defaultCode(): string
    {
        if (!Schema::hasTable('currencies')) return 'INR';
        $query = DB::table('currencies');
        if (Schema::hasColumn('currencies','is_active')) $query->where('is_active',true);
        if ((clone $query)->where('code','INR')->exists()) return 'INR';
        $default = Schema::hasColumn('currencies','is_default') ? (clone $query)->where('is_default',true)->value('code') : null;
        return strtoupper($default ?: (clone $query)->orderBy('id')->value('code') ?: 'INR');
    }

    public static function idForCode(string $code): ?int
    {
        if (!Schema::hasTable('currencies')) return null;
        $id = DB::table('currencies')->where('code',strtoupper(trim($code)))->value('id');
        return $id ? (int) $id : null;
    }
}
