<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payout extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'payout_recipient_id',
        'transaction_id',
        'public_id',
        'amount',
        'currency',
        'fee',
        'net_amount',
        'phone',
        'status',
        'provider',
        'provider_conversation_id',
        'provider_originator_conversation_id',
        'provider_result_code',
        'provider_result_description',
        'failure_reason',
        'metadata',
        'requested_at',
        'processed_at',
        'paid_at',
        'failed_at',
        'reversed_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'fee' => 'integer',
        'net_amount' => 'integer',
        'metadata' => 'array',
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(PayoutRecipient::class, 'payout_recipient_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
