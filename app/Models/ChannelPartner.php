<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelPartner extends Model
{
    protected $fillable = [
        'name', 'phone', 'address', 'whatsapp', 'email', 'company_name', 'status', 'assigned_user_id',
        'joining_date', 'notes', 'commission_type', 'commission_value', 'commission_basis', 'payable_on', 'commission_currency',
        'pan_number', 'gst_number', 'bank_name', 'account_holder', 'account_number', 'ifsc_code',
        'agreement_path', 'agreement_name',
    ];

    protected $casts = ['joining_date' => 'date', 'commission_value' => 'decimal:2'];

    protected static function booted(): void
    {
        static::created(function ($partner) {
            // The database ID makes codes unique even for simultaneous and quick-modal additions.
            if (\Illuminate\Support\Facades\Schema::hasColumn('channel_partners', 'partner_code')) {
                $partner->forceFill([
                    'partner_code' => 'CP-'.str_pad($partner->id, 6, '0', STR_PAD_LEFT),
                    'joining_date' => $partner->joining_date ?? now()->timezone('Asia/Kolkata')->toDateString(),
                ])->saveQuietly();
            }
        });
    }

    public function assignedUser() { return $this->belongsTo(User::class, 'assigned_user_id'); }
    public function leads() { return $this->hasMany(Lead::class); }
    public function payments() { return $this->hasMany(ChannelPartnerPayment::class); }
}
