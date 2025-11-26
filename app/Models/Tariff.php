<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tariff extends Model
{
    protected $fillable = [
        'name',
        'code',
        'price_month',
        'max_clients',
        'included_sms',
        'auto_sms_enabled',
    ];

    protected function casts(): array
    {
        return [
            'price_month' => 'decimal:2',
            'max_clients' => 'integer',
            'included_sms' => 'integer',
            'auto_sms_enabled' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}