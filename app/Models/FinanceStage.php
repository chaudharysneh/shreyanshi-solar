<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'system_key',
        'finance_master_id',
        'stage_name',
        'stage_order',
        'is_active',
    ];

    public function financeMaster()
    {
        return $this->belongsTo(FinanceMaster::class);
    }
}
