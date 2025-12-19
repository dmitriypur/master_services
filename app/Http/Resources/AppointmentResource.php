<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'master_id' => $this->master_id,
            'client_id' => $this->client_id,
            'service_id' => $this->service_id,
            'starts_at' => $this->starts_at?->toISOString(),
            'ends_at' => $this->ends_at?->toISOString(),
            'status' => $this->status,
            'source' => $this->source,
            'client' => $this->when($this->relationLoaded('client') && $this->client !== null, function () {
                return [
                    'id' => $this->client->id,
                    'name' => $this->client->name,
                    'phone' => $this->client->phone,
                ];
            }),
            'service' => $this->when($this->relationLoaded('service') && $this->service !== null, function () {
                return [
                    'id' => $this->service->id,
                    'name' => $this->service->name,
                ];
            }),
            'private_notes' => $this->private_notes,
        ];
    }
}
