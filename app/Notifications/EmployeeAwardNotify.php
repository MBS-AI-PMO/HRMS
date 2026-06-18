<?php

namespace App\Notifications;

use App\Notifications\Concerns\DeliversMailToEmployee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmployeeAwardNotify extends Notification
{
    use DeliversMailToEmployee, Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return $this->channelsForEmployee($notifiable);
    }

    public function toMail($notifiable)
    {
        return $this->employeeMailFromPayload($notifiable, $this->toArray($notifiable), __('Award notification'));
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
            //
			'data'=> __('Congratulation! An Award has been given to you'),
			'link' => '/profile#Employee_Core_hr',
        ];
    }
}
