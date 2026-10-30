<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\OwnedByUser;

class Deal extends Model
{
    use HasFactory, OwnedByUser;

    protected static function booted(): void
    {
        static::saving(function ($deal) {
            static::syncOwnedUserFromAssignee($deal);
        });
    }

    protected $fillable = [
        'invoice_id',
        'customer_id',
        'estimate_id',
        'title',
        'amount',
        'timeline_value',
        'timeline_unit',
        'probability',
        'stage_id',
        'currency_id',
        'status_id',
        'expected_close_date',
        'assigned_user_id',
        'user_id',
        'created_by',
        'signature_path',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'timeline_value' => 'integer',
        'probability' => 'decimal:2',
        'expected_close_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function estimate()
    {
        return $this->belongsTo(Estimate::class, 'estimate_id', 'estimate_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function stage()
    {
        return $this->belongsTo(Stage::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
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
