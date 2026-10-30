<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'system_key',
        'workflow_type',
        'name',
        'order',
        'is_active',
    ];
}
