<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Finance extends Model
{
    use HasFactory;

    protected $fillable = ['customer_id', 'aspl_id', 'status', 'assigned_to', 'remarks', 'previous_stage', 'amount', 'payment_type', 'current_stage_type', 'current_stage_id'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function currentStage()
    {
        return $this->belongsTo(FinanceStage::class, 'current_stage_id');
    }
}
