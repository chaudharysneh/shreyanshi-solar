<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Technology extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $table = 'technology';

    protected $fillable = [
        'title',
        'description',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
}
