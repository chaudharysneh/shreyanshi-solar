<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subsidy extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id', 'aspl_id', 'status', 'assigned_to', 'remarks', 'previous_stage', 'subsidy_amount', 'application_number',
        'category', 'label', 'amount', 'is_active', 'current_stage_id'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Scope to get only active subsidies
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function currentStage()
    {
        return $this->belongsTo(WorkflowStage::class, 'current_stage_id');
    }
}

