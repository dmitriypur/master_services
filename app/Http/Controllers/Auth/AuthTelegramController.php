<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\TelegramWebAppAuthRequest;
use App\Models\User;
use App\Services\Telegram\TelegramWebAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuthTelegramController extends Controller
{
    public function store(TelegramWebAppAuthRequest $request, TelegramWebAppService $service): JsonResponse
    {
        \Log::info('webapp-auth-start', [
            'ua' => $request->header('User-Agent'),
            'init_len' => strlen((string) $request->input('initData')),
        ]);
        $userData = $service->validateInitData($request->input('initData'));
        if (empty($userData['id'])) {
            \Log::warning('webapp-auth-invalid-init');

            return response()->json(['message' => 'Invalid initData'], 422);
        }

        $name = trim(($userData['first_name'] ?? '').' '.($userData['last_name'] ?? '')) ?: ($userData['username'] ?? ('tg_'.$userData['id']));
        $email = 'tg_'.$userData['id'].'@local';

        $user = User::query()->firstOrCreate(
            ['telegram_id' => $userData['id']],
            [
                'name' => $name,
                'email' => $email,
                'password' => Str::password(16),
                'role' => 'master',
            ]
        );

        Auth::login($user, true);
        \Log::info('webapp-auth-success', ['user_id' => $user->id]);

        $token = base64_encode(random_bytes(36));

        $user->load('masterSettings');
        $settings = $user->masterSettings;
        $hasSettings = $settings && is_array($settings->work_days) && count($settings->work_days) > 0
            && ! empty($settings->work_time_from)
            && ! empty($settings->work_time_to)
            && (int) ($settings->slot_duration_min ?? 0) > 0;
        $redirect = $hasSettings ? url('/master/calendar') : url('/master/settings');

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
            ],
            'redirect' => $redirect,
        ]);
    }
}
