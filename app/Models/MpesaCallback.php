<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MpesaCallback extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'checkout_request_id',
        'merchant_request_id',
        'result_code',
        'result_description',
        'raw_payload',
        'processed_at',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'processed_at' => 'datetime',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
