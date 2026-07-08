<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'owner_name',
        'owner_phone',
        'owner_email',
        'document_type',
        'document_number',
        'kra_pin',
        'address',
        'notification_email_enabled',
        'notification_sms_enabled',
    ];

    protected $casts = [
        'document_number' => 'encrypted',
        'kra_pin' => 'encrypted',
        'notification_email_enabled' => 'boolean',
        'notification_sms_enabled' => 'boolean',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }
}
