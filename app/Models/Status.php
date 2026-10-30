<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use HasFactory;

    public const DEAL_NAMES = ['Pending', 'In-Process', 'Paused', 'Lost', 'Won/Confirm'];

    public function scopeDealChoices($query)
    {
        return $query->where('type', 'deal')->where('is_active', true)->whereIn('name', self::DEAL_NAMES)
            ->orderByRaw("CASE name WHEN 'Pending' THEN 1 WHEN 'In-Process' THEN 2 WHEN 'Paused' THEN 3 WHEN 'Lost' THEN 4 ELSE 5 END");
    }

    protected $fillable = [
        'name',
        'type',
        'color',
        'is_active',
    ];
}
