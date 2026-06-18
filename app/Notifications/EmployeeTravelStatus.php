<?php

namespace App\Notifications;

use App\Notifications\Concerns\DeliversMailToEmployee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmployeeTravelStatus extends Notification
{
    use DeliversMailToEmployee, Queueable;
    public $status;

	/**
	 * Create a new notification instance.
	 *
	 * @param $status
	 */
    public function __construct($status)
    {
        //
		$this->status = $status;
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
        return $this->employeeMailFromPayload($notifiable, $this->toArray($notifiable), __('Travel request update'));
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
			'data'=> __('Your travel request status is --- '.$this->status),
			'link' => '/profile#Employee_travel',
        ];
    }
}
