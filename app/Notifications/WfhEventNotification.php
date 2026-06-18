<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WfhEventNotification extends Notification
{
    use Queueable;

    private array $mailData;

    public function __construct(array $mailData)
    {
        $this->mailData = $mailData;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $recipientName = trim(($notifiable->first_name ?? '').' '.($notifiable->last_name ?? ''));

        return (new MailMessage)
            ->subject($this->mailData['subject'])
            ->view('emails.leave_event', array_merge($this->mailData, [
                'recipientName' => $recipientName !== '' ? $recipientName : __('Team Member'),
            ]));
    }

    public function toArray($notifiable)
    {
        return [
            'data' => $this->mailData['headline'] ?? '',
            'link' => $this->mailData['actionUrl'] ?? '',
        ];
    }
}
