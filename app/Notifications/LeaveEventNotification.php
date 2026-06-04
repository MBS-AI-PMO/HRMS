<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveEventNotification extends Notification
{
    use Queueable;

    private string $subjectText;
    private string $bodyText;
    private string $link;

    public function __construct(string $subjectText, string $bodyText, string $link)
    {
        $this->subjectText = $subjectText;
        $this->bodyText = $bodyText;
        $this->link = $link;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subjectText)
            ->line($this->bodyText)
            ->action(__('Open Request'), $this->link)
            ->line(__('Thank you'));
    }

    public function toArray($notifiable): array
    {
        return [
            'data' => $this->bodyText,
            'link' => $this->link,
        ];
    }
}
