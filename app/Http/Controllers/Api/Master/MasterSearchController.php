<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Master\MasterSearchRequest;
use App\Http\Resources\MasterResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MasterSearchController extends Controller
{
    public function index(MasterSearchRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();

        $query = User::query()
            ->where('role', 'master')
            ->whereHas('masterSettings', function ($q) {
                $q->whereNotNull('work_time_from')
                    ->whereNotNull('work_time_to')
                    ->whereNotNull('slot_duration_min')
                    ->whereRaw('json_array_length(work_days) > 0');
            })
            ->when(isset($filters['city_id']), fn ($q) => $q->where('city_id', $filters['city_id']))
            ->when(isset($filters['service_id']), function ($q) use ($filters) {
                $q->whereHas('services', function ($sq) use ($filters) {
                    $sq->where('services.id', $filters['service_id']);
                });
            })
            ->with([
                'city',
                'masterSettings',
                'services' => function ($sq) {
                    $sq->wherePivot('is_active', true)->orderBy('name');
                },
            ])
            ->orderBy('name');

        $masters = $query->get();

        return MasterResource::collection($masters);
    }
}
