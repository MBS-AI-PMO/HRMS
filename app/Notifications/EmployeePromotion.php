<?php

namespace App\Notifications;

use App\Notifications\Concerns\DeliversMailToEmployee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmployeePromotion extends Notification
{
    use DeliversMailToEmployee, Queueable;

	private $promotion_title;


	/**
	 * Create a new notification instance.
	 *
	 * @param $promotion_title
	 */
	public function __construct($promotion_title)
	{
		//
		$this->promotion_title = $promotion_title;
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
        return $this->employeeMailFromPayload($notifiable, $this->toArray($notifiable), __('Promotion notification'));
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
			'data'=>__(' Congratulation!You have been promoted to  ') .$this->promotion_title ,
			'link' => '',
		];
    }
}
