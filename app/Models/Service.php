<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use SolutionForest\FilamentTree\Concern\ModelTree;

class Service extends Model
{
    use ModelTree;

    protected $fillable = [
        'name',
        'parent_id',
        'order',
    ];

    protected $casts = [
        'parent_id' => 'integer',
    ];

    public function determineTitleColumnName(): string
    {
        return 'name';
    }

    public function masters(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'master_services', 'service_id', 'master_id')
            ->withPivot(['price', 'is_active'])
            ->withTimestamps();
    }
}
