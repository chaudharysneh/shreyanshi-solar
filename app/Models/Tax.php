<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tax extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'name',
        'rate',
        'is_active'
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    /**
     * Scope to get only active taxes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get formatted rate with percentage
     */
    public function getFormattedRateAttribute()
    {
        return $this->rate . '%';
    }

    /**
     * Get display name with rate
     */
    public function getDisplayNameAttribute()
    {
        return $this->name . ' (' . $this->formatted_rate . ')';
    }
}
