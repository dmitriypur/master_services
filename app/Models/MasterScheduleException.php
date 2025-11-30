<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterScheduleException extends Model
{
    protected $fillable = [
        'master_id',
        'date',
        'type',
        'start_time',
        'end_time',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(User::class, 'master_id');
    }
}
