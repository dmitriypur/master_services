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

        if ($chatId !== 0 && $text === '/start') {
            $bot->sendMessage($chatId, 'Привет! Добро пожаловать.');

            $url = rtrim((string) config('app.url'), '/').'/app?role=master&ngrok-skip-browser-warning=true&_ngrok_skip_browser_warning=1';
            $bot->sendMessage($chatId, 'Открыть приложение', [
                'reply_markup' => [
                    'inline_keyboard' => [
                        [
                            [
                                'text' => 'Открыть в Telegram',
                                'web_app' => ['url' => $url],
                            ],
                        ],
                    ],
                ],
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
