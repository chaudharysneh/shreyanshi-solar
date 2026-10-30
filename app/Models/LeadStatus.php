<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadStatus extends Model
{
    protected $fillable = ['name', 'code'];

    public const DEFAULTS = [
        'new' => 'New', 'qualified' => 'Qualified', 'working' => 'Working',
        'ready_to_close' => 'Ready to Close', 'won' => 'Closed Won', 'lost' => 'Closed Lost',
    ];

    public static function options(): array
    {
        return static::orderBy('id')->pluck('name', 'code')->all();
    }
}
