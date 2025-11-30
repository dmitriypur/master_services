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

class MasterRegisterController extends Controller
{
    public function store(MasterRegisterRequest $request, TelegramWebAppService $service): JsonResponse
    {
        $payload = $request->validated();
        $initData = (string) ($request->input('initData') ?? '');
        $telegramUser = $request->input('telegram_user'); // Данные от виджета
        
        $userData = [];
        $isWidgetAuth = false;

        if ($initData !== '') {
             // Авторизация через WebApp (внутри Telegram)
             $userData = $service->validateInitData($initData);
        } elseif (!empty($telegramUser) && is_array($telegramUser)) {
             // Авторизация через Виджет (на сайте)
             $userData = $service->validateLoginWidget($telegramUser);
             $isWidgetAuth = true;
        }
        
        // Если пытались через ТГ, но валидация не прошла
        if (($initData !== '' || $isWidgetAuth) && empty($userData['id'])) {
            return response()->json(['message' => 'Неверные данные Telegram'], 422);
        }

        // Если регистрация БЕЗ Telegram (чисто сайт, без виджета), требуем пароль
        if (empty($userData['id']) && empty($payload['password'])) {
            return response()->json(['message' => 'Пароль обязателен для регистрации через сайт'], 422);
        }

        // Проверка на существование (по telegram_id)
        if (! empty($userData['id'])) {
             $existing = User::query()
                ->where('telegram_id', $userData['id'])
                ->where('role', 'master')
                ->first();
             
             if ($existing) {
                Auth::login($existing, true);
                return response()->json([
                    'message' => 'Уже зарегистрированы',
                    'redirect' => url('/master/settings'),
                ]);
             }
        }
        
        // Обработка телефона
        $phone = null;
        if (! empty($payload['phone']) && is_string($payload['phone'])) {
            $digits = preg_replace('/\\D+/', '', (string) $payload['phone']) ?? '';
            if ($digits !== '' && strlen($digits) >= 5 && strlen($digits) <= 11) {
                $phone = $digits;
            }
        }
        
        if ($phone && User::query()->where('phone', $phone)->exists()) {
             return response()->json(['message' => 'Пользователь с таким телефоном уже существует'], 422);
        }

        $email = ! empty($userData['id']) ? 'tg_'.$userData['id'].'@local' : 'phone_'.$phone.'@local';
        $password = ! empty($payload['password']) ? $payload['password'] : Str::password(16);
        $telegramId = ! empty($userData['id']) ? (int) $userData['id'] : null;

        $user = User::query()->create([
            'name' => (string) $payload['name'],
            'email' => $email,
            'password' => $password, // Будет хешироваться автоматически через casts
            'role' => 'master',
            'telegram_id' => $telegramId,
            'city_id' => (int) $payload['city_id'],
            'phone' => $phone,
            'subscription_status' => 'trial',
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
            'message' => 'Успешная регистрация',
            'redirect' => url('/master/settings'),
        ]);
    }
}
