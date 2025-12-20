<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMasterSettings extends Model
{
    use HasFactory;

    protected $table = 'user_master_settings';

    protected $fillable = [
        'user_id',
        'address',
        'timezone',
        'work_days',
        'work_time_from',
        'work_time_to',
        'slot_duration_min',
        'lat',
        'lon',
    ];

    protected function casts(): array
    {
        return [
            'work_days' => 'array',
            'slot_duration_min' => 'integer',
            'lat' => 'decimal:7',
            'lon' => 'decimal:7',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
