<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\MasterResource;
use App\Models\User;
use Inertia\Inertia;

class MasterCalendarController extends Controller
{
    public function show(User $master)
    {
        $master->load([
            'city',
            'masterSettings',
            'services' => function ($q) {
                $q->wherePivot('is_active', true)->orderBy('name');
            },
        ]);

        return Inertia::render('Client/MasterCalendar', [
            'master' => MasterResource::make($master),
        ]);
    }
}
