<?php

declare(strict_types=1);

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Http\Requests\TelegramWebhookRequest;
use App\Services\Telegram\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;

class WebhookController extends Controller
{
    public function handle(TelegramWebhookRequest $request, TelegramBotService $bot): JsonResponse
    {
        $update = $request->validated();
        $message = $update['message'] ?? [];
        $text = (string) Arr::get($message, 'text', '');
        $chatId = (int) Arr::get($message, 'chat.id', 0);
        // Контакт/телефон больше не обрабатываем — телефон вводится вручную

        if ($chatId !== 0 && $text === '/start') {
            $bot->sendMessage($chatId, 'Привет! Выберите раздел.');

            $base = rtrim((string) config('app.url'), '/');
            $masterUrl = $base.'/app?webview=1';
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
        }

        return response()->json(['ok' => true]);
    }
}
