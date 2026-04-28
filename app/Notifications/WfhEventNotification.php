<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WfhEventNotification extends Notification
{
    use Queueable;

    private $subjectText;
    private $bodyText;
    private $link;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(string $subjectText, string $bodyText, string $link)
    {
        $this->subjectText = $subjectText;
        $this->bodyText = $bodyText;
        $this->link = $link;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject($this->subjectText)
            ->line($this->bodyText)
            ->action('Open Request', $this->link)
            ->line('Thank you');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'data' => $this->bodyText,
            'link' => $this->link,
        ];
    }
}
