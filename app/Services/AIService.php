<?php

declare(strict_types=1);

namespace App\Services;

class AIService
{
    public function parseBookingData(string $text): array
    {
        $clientName = null;
        $time = null;
        $serviceName = null;
        $phone = null;
        $comment = null;

        $t = mb_strtolower($text);

        if (str_contains($text, 'Иван')) {
            $clientName = 'Иван';
        }

        if (preg_match('/\b(\+?\d[\d\s\-]{7,}\d)\b/u', $text, $m)) {
            $phone = trim($m[1]);
        }

        if (str_contains($text, '15:30')) {
            $time = '15:30';
        } elseif (preg_match('/\b([01]?\d|2[0-3]):([0-5]\d)\b/u', $text, $m)) {
            $time = $m[0];
        }

        if (str_contains($t, 'маникюр')) {
            $serviceName = 'Маникюр';
        } elseif (str_contains($t, 'педикюр')) {
            $serviceName = 'Педикюр';
        } elseif (str_contains($t, 'стриж')) {
            $serviceName = 'Стрижка';
        }

        return [
            'client_name' => $clientName ?? '',
            'time' => $time ?? '',
            'service_name' => $serviceName ?? '',
            'phone' => $phone ?? '',
            'comment' => $comment ?? '',
        ];
    }
}
