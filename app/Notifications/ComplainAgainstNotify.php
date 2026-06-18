<?php

namespace App\Notifications;

use App\Notifications\Concerns\DeliversMailToEmployee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ComplainAgainstNotify extends Notification
{
    use DeliversMailToEmployee, Queueable;
    public $complaint_title;
    public $complaint_form;

	/**
	 * Create a new notification instance.
	 *
	 * @param $complaint_form
	 * @param $complaint_title
	 */
    public function __construct($complaint_form,$complaint_title)
    {
        //
		$this->complaint_form = $complaint_form;
		$this->complaint_title = $complaint_title;
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
        return $this->employeeMailFromPayload($notifiable, $this->toArray($notifiable), __('Complaint notification'));
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
			'data'=>$this->complaint_title.__('--A complaint has been filed against you by ') .$this->complaint_form ,
			'link' => 'ex/profile#Employee_Complaint',
        ];
    }
}
