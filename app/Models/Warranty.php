<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warranty extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $table = 'warranty';

    protected $fillable = [
        'title',
        'description',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
}
