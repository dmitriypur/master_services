<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramBotService
{
    private readonly string $token;

    public function __construct()
    {
        $this->token = (string) (config('services.telegram.bot_token') ?? env('TELEGRAM_BOT_TOKEN'));
    }

    public function sendMessage(int $chatId, string $text, array $params = []): array
    {
        $url = "https://api.telegram.org/bot{$this->token}/sendMessage";

        $payload = array_merge([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ], $params);

        $response = Http::timeout(10)->post($url, $payload);

        if (! $response->successful()) {
            Log::warning('Telegram sendMessage failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }

        return $response->json() ?? [];
    }
}
