<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\MasterRegisterRequest;
use App\Models\Service;
use App\Models\User;
use App\Services\Telegram\TelegramWebAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class MasterRegisterController extends Controller
{
    public function store(MasterRegisterRequest $request, TelegramWebAppService $service): JsonResponse
    {
        $payload = $request->validated();
        $userData = $service->validateInitData((string) $request->input('initData'));
        if (empty($userData['id'])) {
            return response()->json(['message' => 'Invalid initData'], 422);
        }

        $exists = User::query()
            ->where('telegram_id', $userData['id'])
            ->where('role', 'master')
            ->exists();
        if ($exists) {
            return response()->json(['message' => 'Already registered'], 409);
        }

        $email = 'tg_' . $userData['id'] . '@local';
        $phone = null;
        if (! empty($payload['phone']) && is_string($payload['phone'])) {
            $digits = preg_replace('/\\D+/', '', (string) $payload['phone']) ?? '';
            if ($digits !== '' && strlen($digits) >= 5 && strlen($digits) <= 11) {
                $phone = $digits;
            }
        }
        if ($phone === null) {
            $cached = (string) (Cache::get('tg:contact_'.$userData['id']) ?? Cache::get('tg:contact:'.$userData['id']) ?? '');
            $cachedDigits = preg_replace('/\\D+/', '', $cached) ?? '';
            if ($cachedDigits !== '' && strlen($cachedDigits) >= 5 && strlen($cachedDigits) <= 11) {
                $phone = $cachedDigits;
            }
        }
        $user = User::query()->create([
            'name' => (string) $payload['name'],
            'email' => $email,
            'password' => Str::password(16),
            'role' => 'master',
            'telegram_id' => (int) $userData['id'],
            'city_id' => (int) $payload['city_id'],
            'phone' => $phone,
        ]);

        $serviceIds = array_map('intval', (array) $payload['services']);
        $active = [];
        foreach ($serviceIds as $sid) {
            if (Service::query()->where('id', $sid)->exists()) {
                $active[$sid] = ['is_active' => true];
            }
        }
        if (! empty($active)) {
            $user->services()->syncWithoutDetaching($active);
        }

        Auth::login($user, true);

        return response()->json([
            'message' => 'Registered',
            'redirect' => url('/master/settings'),
        ]);
    }
}
