<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    protected $fillable = ['name', 'slug', 'is_active'];

    public function masters(): HasMany
    {
        return $this->hasMany(User::class, 'city_id');
    }
}
