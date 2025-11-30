<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;

class GenerateMasterTokenAction
{
    public function execute(string $telegramId, ?array $telegramData = null): string
    {
        $tid = (int) $telegramId;

        $user = User::query()
            ->where('telegram_id', $tid)
            ->where('role', 'master')
            ->first();

        if (! $user) {
            $name = '';
            if (is_array($telegramData)) {
                $first = trim((string) ($telegramData['first_name'] ?? ''));
                $last = trim((string) ($telegramData['last_name'] ?? ''));
                $username = trim((string) ($telegramData['username'] ?? ''));
                $name = trim($first.' '.$last);
                if ($name === '') {
                    $name = $username !== '' ? $username : ('tg_'.$tid);
                }
            }

            $user = User::query()->create([
                'name' => $name !== '' ? $name : ('tg_'.$tid),
                'email' => 'tg_'.$tid.'@local',
                'role' => 'master',
                'telegram_id' => $tid,
                'subscription_status' => 'trial',
            ]);
        } else {
            if (is_array($telegramData)) {
                $first = trim((string) ($telegramData['first_name'] ?? ''));
                $last = trim((string) ($telegramData['last_name'] ?? ''));
                $username = trim((string) ($telegramData['username'] ?? ''));
                $name = trim($first.' '.$last);
                if ($name === '') {
                    $name = $username !== '' ? $username : $user->name;
                }
                if ($name !== '' && $name !== $user->name) {
                    $user->name = $name;
                    $user->save();
                }
            }
        }

        return $user->createToken('universal-master-token')->plainTextToken;
    }
}

