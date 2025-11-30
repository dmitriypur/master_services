<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\TrialWillEndNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendTrialWarnings extends Command
{
    protected $signature = 'app:send-trial-warnings';

    protected $description = 'Send trial ending warnings to masters';

    public function handle(): int
    {
        $daysList = [7, 3, 1];
        foreach ($daysList as $days) {
            $targetDate = Carbon::now()->addDays($days)->toDateString();

            $users = User::query()
                ->where('role', 'master')
                ->where('subscription_status', 'trial')
                ->whereDate('trial_ends_at', $targetDate)
                ->get();

            foreach ($users as $user) {
                if ($user->trial_ends_at) {
                    $user->notify(new TrialWillEndNotification($user->trial_ends_at->copy(), $days));
                }
            }
        }

        return self::SUCCESS;
    }
}
