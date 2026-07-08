<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PaymentLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'public_id',
        'slug',
        'title',
        'description',
        'amount',
        'currency',
        'allow_custom_amount',
        'success_redirect_url',
        'status',
        'metadata',
    ];

    protected $casts = [
        'allow_custom_amount' => 'boolean',
        'metadata' => 'array',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'merchant_id', 'merchant_id')
            ->where('metadata->payment_link_public_id', $this->public_id);
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
