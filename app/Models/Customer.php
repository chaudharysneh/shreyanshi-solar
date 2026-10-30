<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Schema;

use App\Traits\HasCustomFields;

class Customer extends Model
{
    use HasFactory, HasCustomFields, SoftDeletes, Blameable;

    protected static ?array $assignableCustomerTables = null;

    protected $casts = ['customer_docs' => 'array'];

    protected $fillable = [
        'lead_source_id','source','referral_name','channel_partner_id',
        'name',
        'email',
        'phone',
        'whatsapp',
        'address',
        'dob',
        'anniversary_date',
        'company_name',
        'website',
        'tax_number',
        'image',
        'type',
        'country_id',
        'city_id',
        'finance_id',
        'installation_master_id',
        'pipeline_stage_id',
        'is_active',
        'created_by',
        'updated_by',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function leadSource() { return $this->belongsTo(LeadSource::class); }
    public function channelPartner() { return $this->belongsTo(ChannelPartner::class); }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function pipelineStage(): BelongsTo
    {
        return $this->belongsTo(Stage::class, 'pipeline_stage_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function meetings()
    {
        return $this->hasMany(Meeting::class)->orderByDesc('scheduled_at');
    }

    public function deals()
    {
        return $this->hasMany(Deal::class)->latest();
    }

    public function estimates()
    {
        return $this->hasMany(Estimate::class, 'customer_id')->latest('estimate_date');
    }

    public function followUps()
    {
        return $this->hasMany(FollowUp::class, 'customer_id')->latest('follow_up_at');
    }

    /**
     * ✅ CHANGED: scopeVisibleToUser now returns ALL customers for all users
     * Previously filtered customers by created_by or assigned_user_id
     * Now all customers are visible to all staff and admins
     * Permission logic (edit/delete) is handled in CustomerPolicy instead
     */
    public function scopeVisibleToUser(Builder $query, ?User $user = null): Builder
    {
        // Return all customers - no filtering
        // Permission checks (edit/delete) are handled in CustomerPolicy
        return $query;
    }

    public function scopeVisibleTo(Builder $query, ?User $user = null): Builder
    {
        return $query->visibleToUser($user);
    }

    protected static function discoverAssignableCustomerTables(string $customerTable): array
    {
        if (static::$assignableCustomerTables !== null) {
            return static::$assignableCustomerTables;
        }

        $tables = collect(Schema::getTableListing())
            ->filter(function (string $table) use ($customerTable) {
                if ($table === $customerTable) {
                    return false;
                }

                return Schema::hasColumn($table, 'customer_id')
                    && Schema::hasColumn($table, 'assigned_user_id');
            })
            ->values()
            ->all();

        return static::$assignableCustomerTables = $tables;
    }
}
