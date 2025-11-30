<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class TrialWillEndNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Carbon $trialEndsAt, private readonly int $daysLeft) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $dateStr = $this->trialEndsAt->format('d.m.Y');

        return (new MailMessage)
            ->subject('Ваш триал скоро завершится')
            ->line('До окончания триал-периода осталось '.$this->daysLeft.' дн.')
            ->line('Дата окончания: '.$dateStr)
            ->action('Активировать подписку', url('/billing'));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Триал скоро завершится',
            'days_left' => $this->daysLeft,
            'trial_ends_at' => $this->trialEndsAt->toISOString(),
            'cta_url' => url('/billing'),
        ];
    }
}
