<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'name',
        'environment',
        'publishable_key',
        'secret_key_hash',
        'secret_key_prefix',
        'secret_key_last_four',
        'status',
        'last_used_at',
        'revoked_at',
    ];

    protected $hidden = [
        'secret_key_hash',
        'secret_key_prefix',
        'secret_key_last_four',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
