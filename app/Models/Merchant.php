<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Merchant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'public_id',
        'business_name',
        'business_email',
        'business_phone',
        'business_type',
        'platform_url',
        'description',
        'status',
        'compliance_status',
        'live_enabled',
        'live_requested_at',
        'live_reviewed_at',
        'live_rejection_reason',
    ];

    protected $casts = [
        'live_enabled' => 'boolean',
        'live_requested_at' => 'datetime',
        'live_reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(MerchantProfile::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function paymentLinks(): HasMany
    {
        return $this->hasMany(PaymentLink::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payoutRecipients(): HasMany
    {
        return $this->hasMany(PayoutRecipient::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    public function webhookEndpoint(): HasOne
    {
        return $this->hasOne(MerchantWebhookEndpoint::class);
    }

    public function webhookDeliveries(): HasMany
    {
        return $this->hasMany(MerchantWebhookDelivery::class);
    }

    public function commissionRules(): HasMany
    {
        return $this->hasMany(CommissionRule::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
