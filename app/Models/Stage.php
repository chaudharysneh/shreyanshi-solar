<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stage extends Model
{
    use HasFactory;

    protected $table = 'stages';

    protected $fillable = [
        'name',
        'status',
        'sort_order',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('active', fn ($query) => $query->where('stages.is_active', true));
    }

    public function getStatusAttribute($value): string
    {
        return $this->is_active ? 'active' : 'inactive';
    }

    public function leads()
    {
        return $this->hasMany(Lead::class, 'lead_stage_id');
    }

    public function deals()
    {
        return $this->hasMany(Deal::class);
    }
}
