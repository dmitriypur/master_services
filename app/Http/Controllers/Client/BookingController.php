<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Service;
use Inertia\Inertia;

class BookingController extends Controller
{
    public function index()
    {
        $cities = City::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $services = Service::query()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Client/Booking', [
            'cities' => $cities,
            'services' => $services,
        ]);
    }
}
