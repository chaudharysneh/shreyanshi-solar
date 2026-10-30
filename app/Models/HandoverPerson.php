<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HandoverPerson extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $table = 'handover_persons';

    protected $fillable = [
        'name',
        'user_id',
        'phone',
        'address',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
