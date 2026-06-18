<?php

namespace App\Notifications\Concerns;

use App\Models\User;
use App\Services\NotificationRecipientResolver;
use Illuminate\Notifications\Messages\MailMessage;

trait DeliversMailToEmployee
{
    protected function channelsForEmployee($notifiable): array
    {
        $channels = ['database'];
        $userId = $this->employeeUserIdFromNotifiable($notifiable);

        if ($userId && NotificationRecipientResolver::resolveUserEmailAddress($userId, null, false)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    protected function employeeUserIdFromNotifiable($notifiable): ?int
    {
        if ($notifiable instanceof User) {
            return (int) $notifiable->id;
        }

        if (is_object($notifiable) && isset($notifiable->id)) {
            return (int) $notifiable->id;
        }

        return null;
    }

    protected function employeeMailFromPayload($notifiable, array $payload, string $subject): MailMessage
    {
        $name = '';

        if ($notifiable instanceof User) {
            $name = trim(($notifiable->first_name ?? '').' '.($notifiable->last_name ?? ''));
        }

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting(__('Hello').($name !== '' ? ' '.$name : '').',');

        if (! empty($payload['data'])) {
            $message->line((string) $payload['data']);
        }

        if (! empty($payload['link'])) {
            $message->action(__('View'), url($payload['link']));
        }

        return $message->line(__('Thank you'));
    }
}
