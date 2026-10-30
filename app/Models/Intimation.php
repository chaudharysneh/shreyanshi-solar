<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Intimation extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 'aspl_id', 'status', 'assigned_to', 'remarks', 'previous_stage', 'current_stage_id',
        'self_certificate_status', 'self_certificate_file',
        'gps_photo_status', 'gps_photo_file',
        'dcr_certificate_status', 'dcr_certificate_file',
        'model_agreement_status', 'model_agreement_file'
    ];

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
        return $this->belongsTo(WorkflowStage::class, 'current_stage_id');
    }
}

