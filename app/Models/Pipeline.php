<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pipeline extends Model
{
    use HasFactory;

    protected $fillable = [
        'pipeline_name',
        'customer_id',
        'stage_id',
        'status',
        'description',
        'created_by',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function stage()
    {
        return $this->belongsTo(Stage::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusHistories()
    {
        return $this->morphMany(ModuleStatusHistory::class, 'historable')->latest();
    }
}
