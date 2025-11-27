<?php

declare(strict_types=1);

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Http\Requests\TelegramWebhookRequest;
use App\Services\Telegram\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use App\Models\User;

class WebhookController extends Controller
{
    public function handle(TelegramWebhookRequest $request, TelegramBotService $bot): JsonResponse
    {
        $update = $request->validated();
        $message = $update['message'] ?? [];
        $text = (string) Arr::get($message, 'text', '');
        $chatId = (int) Arr::get($message, 'chat.id', 0);
        $contact = $message['contact'] ?? null;

        // Сохранение телефона из контакта
        if ($chatId !== 0 && is_array($contact)) {
            $phone = (string) Arr::get($contact, 'phone_number', '');
            $digits = preg_replace('/\D+/', '', $phone) ?? '';
            if ($digits !== '') {
                Cache::put('tg:contact:'.$chatId, $digits, now()->addDays(30));
                // Если уже есть мастер с таким telegram_id — обновим телефон
                User::query()
                    ->where('telegram_id', $chatId)
                    ->where('role', 'master')
                    ->update(['phone' => $digits]);
            }
        }

        if ($chatId !== 0 && $text === '/start') {
            $bot->sendMessage($chatId, 'Привет! Выберите раздел.');

            $base = rtrim((string) config('app.url'), '/');
            $masterUrl = $base.'/master/register?webview=1';
            $clientUrl = $base.'/book?webview=1';
            $bot->sendMessage($chatId, 'Открыть приложение', [
                'reply_markup' => [
                    'inline_keyboard' => [
                        [
                            [
                                'text' => 'Для мастера',
                                'web_app' => ['url' => $masterUrl],
                            ],
                            [
                                'text' => 'Для клиента',
                                'web_app' => ['url' => $clientUrl],
                            ],
                        ],
                    ],
                ],
            ]);

            // Кнопка запроса контакта для автоподстановки телефона
            $bot->sendMessage($chatId, 'Поделитесь контактом, чтобы мы сохранили ваш телефон', [
                'reply_markup' => [
                    'keyboard' => [
                        [
                            [
                                'text' => 'Отправить контакт',
                                'request_contact' => true,
                            ],
                        ],
                    ],
                    'resize_keyboard' => true,
                    'one_time_keyboard' => true,
                ],
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
