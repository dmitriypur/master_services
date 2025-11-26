<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Client extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'telegram_id',
        'whatsapp_phone',
        'preferred_channels',
    ];

    protected $casts = [
        'preferred_channels' => 'array',
        'telegram_id' => 'integer',
    ];

    public function master(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
