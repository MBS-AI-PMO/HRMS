<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TeamManagementNotification extends Notification
{
    use Queueable;

    private string $bodyText;

    private string $link;

    public function __construct(string $bodyText, string $link)
    {
        $this->bodyText = $bodyText;
        $this->link = $link;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'data' => $this->bodyText,
            'link' => $this->link,
        ];
    }
}
