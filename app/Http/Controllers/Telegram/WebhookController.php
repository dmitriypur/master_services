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
            $base = rtrim((string) config('app.url'), '/');
            $masterUrl = $base.'/app?webview=1';
            
            $bot->sendMessage($chatId, 'Добро пожаловать! Нажмите кнопку ниже, чтобы войти.', [
                'reply_markup' => [
                    'inline_keyboard' => [
                        [
                            [
                                'text' => 'Открыть приложение',
                                'web_app' => ['url' => $masterUrl],
                            ],
                        ],
                    ],
                ],
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
