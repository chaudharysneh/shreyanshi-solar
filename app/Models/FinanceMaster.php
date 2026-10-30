<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceMaster extends Model
{
    use HasFactory;

    protected $table = 'finance_masters';

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    public function stages()
    {
        return $this->hasMany(FinanceStage::class)->orderBy('stage_order', 'asc');
    }

    public function customers()
    {
        return $this->hasMany(Customer::class, 'finance_id');
    }
}
