<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CalendarController extends Controller
{
    public function index()
    {
        if (request()->boolean('webview')) {
            \Log::info('calendar-webview', [
                'user_id' => Auth::id(),
            ]);

            return view('master_calendar_webview', [
                'user' => Auth::user(),
            ]);
        }

        return Inertia::render('Master/Calendar', [
            'user' => Auth::user(),
        ]);
    }
}
