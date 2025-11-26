<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use Illuminate\Support\Arr;

class TelegramWebAppService
{
    private readonly string $token;

    public function __construct()
    {
        $this->token = (string) (config('services.telegram.bot_token') ?? env('TELEGRAM_BOT_TOKEN'));
    }

    public function validateInitData(string $initData): array
    {
        parse_str($initData, $data);

        $hash = (string) ($data['hash'] ?? '');
        unset($data['hash']);

        ksort($data);
        $pairs = [];
        foreach ($data as $k => $v) {
            $pairs[] = $k.'='.$v;
        }
        $checkString = implode("\n", $pairs);

        $secretKey = hash('sha256', $this->token, true);
        $calcHash = hash_hmac('sha256', $checkString, $secretKey);

        if (! hash_equals($hash, $calcHash)) {
            if (config('services.telegram.webapp_skip_signature')) {
                $user = $data['user'] ?? '';
                if (is_string($user)) {
                    $decoded = json_decode($user, true);
                    if (is_array($decoded)) {
                        return [
                            'id' => (int) Arr::get($decoded, 'id', 0),
                            'username' => (string) Arr::get($decoded, 'username', ''),
                            'first_name' => (string) Arr::get($decoded, 'first_name', ''),
                            'last_name' => (string) Arr::get($decoded, 'last_name', ''),
                        ];
                    }
                }
            }

            return [];
        }

        $user = $data['user'] ?? '';
        if (is_string($user)) {
            $decoded = json_decode($user, true);
            if (is_array($decoded)) {
                return [
                    'id' => (int) Arr::get($decoded, 'id', 0),
                    'username' => (string) Arr::get($decoded, 'username', ''),
                    'first_name' => (string) Arr::get($decoded, 'first_name', ''),
                    'last_name' => (string) Arr::get($decoded, 'last_name', ''),
                ];
            }
        }

        return [];
    }
}
