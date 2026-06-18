<?php

namespace App\Notifications;

use App\Notifications\Concerns\DeliversMailToEmployee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmployeeTerminationNotify extends Notification
{
    use DeliversMailToEmployee, Queueable;
	public $termination_date;

	/**
	 * Create a new notification instance.
	 *
	 * @param $termination_date
	 */
    public function __construct($termination_date)
    {
        //
		$this->termination_date = $termination_date;
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
        return $this->employeeMailFromPayload($notifiable, $this->toArray($notifiable), __('Termination notice'));
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
				'data' => __('You have been terminated from this company ').' '
					. __('Termination Date') . '-- ' . $this->termination_date,
				'link' => '',
			];
		}
}
