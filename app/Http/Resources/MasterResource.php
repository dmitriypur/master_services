<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MasterResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'city' => $this->when($this->relationLoaded('city') && $this->city !== null, function () {
                return [
                    'id' => $this->city->id,
                    'name' => $this->city->name,
                ];
            }),
            'address' => $this->when($this->relationLoaded('masterSettings') && $this->masterSettings !== null, function () {
                return $this->masterSettings->address;
            }),
            'services' => $this->when($this->relationLoaded('services'), function () {
                return ServiceResource::collection($this->services);
            }),
        ];
    }
}
