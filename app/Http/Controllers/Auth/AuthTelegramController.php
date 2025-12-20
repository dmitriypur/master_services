<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\GenerateMasterTokenAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\TelegramLoginWidgetRequest;
use App\Http\Requests\Auth\TelegramWebAppAuthRequest;
use App\Models\User;
use App\Services\Telegram\TelegramWebAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthTelegramController extends Controller
{
    public function store(TelegramWebAppAuthRequest $request, TelegramWebAppService $service, GenerateMasterTokenAction $action): JsonResponse
    {
        Log::info('webapp-auth-start', [
            'ua' => $request->header('User-Agent'),
            'init_len' => strlen((string) $request->input('initData')),
        ]);
        $userData = $service->validateInitData($request->input('initData'));
        if (empty($userData['id'])) {
            Log::warning('webapp-auth-invalid-init');

            return response()->json(['message' => 'Неверные данные WebApp'], 422);
        }

        $name = trim(($userData['first_name'] ?? '').' '.($userData['last_name'] ?? '')) ?: ($userData['username'] ?? ('tg_'.$userData['id']));
        $email = 'tg_'.$userData['id'].'@local';

        $token = $action->execute((string) $userData['id'], $userData);

        $user = User::query()
            ->where('telegram_id', (int) $userData['id'])
            ->where('role', 'master')
            ->first();

        if (! $user) {
            // Если пользователя нет, возвращаем 404, чтобы фронт перекинул на регистрацию
            return response()->json(['message' => 'User not found'], 404);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        $redirect = url('/master/calendar');

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

    public function widgetLogin(TelegramLoginWidgetRequest $request, TelegramWebAppService $service): JsonResponse
    {
        $userData = $service->validateLoginWidget($request->validated());

        if (empty($userData['id'])) {
            return response()->json(['message' => 'Неверные данные Telegram'], 422);
        }

        // Ищем или создаем пользователя
        $user = User::query()
            ->where('telegram_id', $userData['id'])
            ->where('role', 'master')
            ->first();

        if (! $user) {
            // Создаем нового, если нет
            $name = trim(($userData['first_name'] ?? '').' '.($userData['last_name'] ?? ''));
            if ($name === '') {
                $name = $userData['username'] ?? ('tg_'.$userData['id']);
            }

            $user = User::query()->create([
                'name' => $name,
                'email' => 'tg_'.$userData['id'].'@local',
                'password' => \Illuminate\Support\Str::password(32),
                'role' => 'master',
                'telegram_id' => $userData['id'],
                'subscription_status' => 'trial',
            ]);
        } else {
            // Обновляем имя если поменялось
            $name = trim(($userData['first_name'] ?? '').' '.($userData['last_name'] ?? ''));
            if ($name !== '' && $name !== $user->name) {
                $user->name = $name;
                $user->save();
            }
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        // Если профиль не заполнен, отправляем на настройки
        $redirect = $user->is_active ? url('/master/calendar') : url('/master/settings');

        $token = $user->createToken('master-token')->plainTextToken;

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
