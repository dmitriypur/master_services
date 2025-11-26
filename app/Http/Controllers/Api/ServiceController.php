<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $services = Service::query()->orderBy('name')->get();

        return ServiceResource::collection($services);
    }

    public function forMaster(User $master): AnonymousResourceCollection
    {
        $services = $master->services()->wherePivot('is_active', true)->orderBy('name')->get();

        return ServiceResource::collection($services);
    }
}
