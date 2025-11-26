<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Service extends Model
{
    protected $fillable = [
        'name',
    ];

    public function masters(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'master_services', 'service_id', 'master_id')
            ->withPivot(['price', 'is_active'])
            ->withTimestamps();
    }
}
