<?php

use App\Http\Controllers\Auth\AuthTelegramController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Client\BookingController;
use App\Http\Controllers\Client\MasterCalendarController;
use App\Http\Controllers\Auth\MasterRegisterController;
use App\Http\Controllers\Master\CalendarController;
use App\Http\Controllers\Master\SettingsController;
use App\Http\Controllers\Telegram\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Inertia::render('Home');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('/telegram/webhook', [WebhookController::class, 'handle'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

Route::get('/app', function () {
    if (auth()->check()) {
        $u = auth()->user();
        $u->load('masterSettings');
        $s = $u->masterSettings;
        $has = $s && is_array($s->work_days) && count($s->work_days) > 0 && ! empty($s->work_time_from) && ! empty($s->work_time_to) && (int) ($s->slot_duration_min ?? 0) > 0;

        return redirect($has ? '/master/calendar' : '/master/settings');
    }

    return Inertia::render('Auth/TelegramWebApp');
});

Route::post('/auth/telegram/webapp', [AuthTelegramController::class, 'store'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

Route::get('/master/register', function () {
    $cities = \App\Models\City::query()->where('is_active', true)->get(['id', 'name']);
    $services = \App\Models\Service::query()->orderBy('name')->get(['id', 'name']);
    return Inertia::render('Master/Register', [
        'cities' => $cities,
        'services' => $services,
    ]);
})->name('master.register');

Route::post('/auth/telegram/master/register', [MasterRegisterController::class, 'store'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

Route::post('/debug/webapp-event', function (Request $request) {
    Log::info('webapp-event', [
        'stage' => $request->input('stage'),
        'ua' => $request->header('User-Agent'),
        'time' => now()->toISOString(),
    ]);

    return response()->json(['ok' => true]);
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

Route::middleware('auth')->group(function () {
    Route::get('/master/clients', function () {
        return Inertia::render('Master/Clients');
    })->name('master.clients.index');
    Route::get('/master/settings', [SettingsController::class, 'edit'])->name('master.settings.edit');
    Route::put('/master/settings', [SettingsController::class, 'update'])->name('master.settings.update');
    Route::get('/master/calendar', [CalendarController::class, 'index'])->name('master.calendar.index');
});

Route::get('/book', [BookingController::class, 'index'])->name('client.booking.index');
Route::get('/book/master/{master}', [MasterCalendarController::class, 'show'])->whereNumber('master')->name('client.master.calendar.show');

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/admin/logout', \Filament\Http\Controllers\Auth\LogoutController::class)->name('filament.admin.auth.logout.get');
    Route::get('/logout', [LogoutController::class, 'destroy'])->name('logout.get');
    Route::post('/logout', [LogoutController::class, 'destroy'])->name('logout');
});
