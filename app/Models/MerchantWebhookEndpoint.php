<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantWebhookEndpoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'url',
        'secret',
        'is_enabled',
    ];

    protected $casts = [
        'secret' => 'encrypted',
        'is_enabled' => 'boolean',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }
}
