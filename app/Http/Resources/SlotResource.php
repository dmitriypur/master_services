<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SlotResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'time' => (string) ($this->resource['time'] ?? ''),
            'starts_at' => (string) ($this->resource['starts_at'] ?? ''),
            'available' => (bool) ($this->resource['available'] ?? false),
            'break_id' => $this->resource['break_id'] ?? null,
            'is_past' => (bool) ($this->resource['is_past'] ?? false),
        ];
    }
}
