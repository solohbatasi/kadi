<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'public_id',
        'type',
        'direction',
        'environment',
        'phone',
        'amount',
        'currency',
        'commission_amount',
        'provider_fee',
        'net_amount',
        'status',
        'reference',
        'description',
        'idempotency_key',
        'mpesa_checkout_request_id',
        'mpesa_merchant_request_id',
        'mpesa_receipt_number',
        'mpesa_result_code',
        'mpesa_result_description',
        'customer_message',
        'metadata',
        'paid_at',
        'failed_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'commission_amount' => 'integer',
        'provider_fee' => 'integer',
        'net_amount' => 'integer',
        'metadata' => 'array',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function walletEntries(): HasMany
    {
        return $this->hasMany(WalletLedgerEntry::class);
    }

    public function callbacks(): HasMany
    {
        return $this->hasMany(MpesaCallback::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }
}
