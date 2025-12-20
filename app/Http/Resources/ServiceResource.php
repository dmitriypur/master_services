<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'pivot' => $this->when(
                $this->pivot !== null,
                fn () => [
                    'price' => $this->pivot->price,
                    'duration_minutes' => $this->pivot->duration_minutes,
                    'is_active' => (bool) $this->pivot->is_active,
                ]
            ),
        ];
    }
}
