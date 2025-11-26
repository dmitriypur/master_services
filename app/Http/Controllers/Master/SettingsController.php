<?php

namespace App\Http\Controllers\Master;

use App\Actions\Master\UpdateSettingsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\UpdateSettingsRequest;
use App\Models\City;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        $user->load('masterSettings');
        $cities = City::query()->where('is_active', true)->get(['id', 'name']);

        return Inertia::render('Master/Settings', [
            'user' => $user,
            'cities' => $cities,
            'settings' => $user->masterSettings,
            'servicesOptions' => Service::query()->orderBy('name')->get(['id', 'name']),
            'selectedServiceIds' => $user->services()->wherePivot('is_active', true)->pluck('services.id'),
        ]);
    }

    public function update(UpdateSettingsRequest $request, UpdateSettingsAction $action)
    {
        $action->execute($request->user(), $request->validated());

        return redirect()->route('master.calendar.index');
    }
}
