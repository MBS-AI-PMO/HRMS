<?php

namespace App\Notifications;

use App\Notifications\Concerns\DeliversMailToEmployee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventNotify extends Notification
{
    use DeliversMailToEmployee, Queueable;
    public $data;

	/**
	 * Create a new notification instance.
	 *
	 * @param $data
	 */
    public function __construct($data)
    {
        //
		$this->data = $data;
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
        return $this->employeeMailFromPayload($notifiable, $this->toArray($notifiable), __('Event notification'));
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
			'data'=> __('A event has been created namely ').$this->data['event_title'].__(' on ') .$this->data['event_date']. __(' at ').$this->data['event_time'],
			'link'=> '',
		];
    }
}
