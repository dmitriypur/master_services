<?php

namespace App\Http\Controllers\Master;

use App\Actions\Master\UpdateSettingsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\UpdateSettingsRequest;
use App\Models\City;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(401);
        }
        $user->load('masterSettings');
        $cities = City::query()->where('is_active', true)->get(['id', 'name']);

        $selectedServices = $user->services()
            ->wherePivot('is_active', true)
            ->orderBy('name')
            ->get(['services.id', 'services.name'])
            ->map(fn (Service $service) => [
                'id' => $service->id,
                'name' => $service->name,
                'price' => $service->pivot?->price,
                'duration' => $service->pivot?->duration_minutes ?? 60,
            ])
            ->values();

        return Inertia::render('Master/Settings', [
            'user' => $user,
            'cities' => $cities,
            'settings' => $user->masterSettings,
            'servicesOptions' => Service::query()->orderBy('name')->get(['id', 'name']),
            'selectedServiceIds' => $selectedServices->pluck('id'),
            'selectedServices' => $selectedServices,
        ]);
    }

    public function update(UpdateSettingsRequest $request, UpdateSettingsAction $action)
    {
        $action->execute($request->user(), $request->validated());

        return back(); // Остаемся на странице настроек
    }
}
