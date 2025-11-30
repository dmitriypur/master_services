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

    protected function normalizePhone(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';
        if ($digits === '') {
            return null;
        }
        if (strlen($digits) === 11 && str_starts_with($digits, '8')) {
            $digits = '7'.substr($digits, 1);
        }
        if (strlen($digits) === 10) {
            $digits = '7'.$digits;
        }
        if (strlen($digits) !== 11) {
            return null;
        }
        if (! str_starts_with($digits, '7')) {
            return null;
        }

        return $digits;
    }

    public function setPhoneAttribute($value): void
    {
        $this->attributes['phone'] = $this->normalizePhone($value);
    }

    public function setWhatsappPhoneAttribute($value): void
    {
        $this->attributes['whatsapp_phone'] = $this->normalizePhone($value);
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
