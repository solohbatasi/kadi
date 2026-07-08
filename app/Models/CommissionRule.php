<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'name',
        'type',
        'percentage',
        'flat_amount',
        'minimum_fee',
        'maximum_fee',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'flat_amount' => 'integer',
        'minimum_fee' => 'integer',
        'maximum_fee' => 'integer',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }
}
