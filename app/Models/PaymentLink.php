<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    protected $casts = [
        'allow_custom_amount' => 'boolean',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }
}
