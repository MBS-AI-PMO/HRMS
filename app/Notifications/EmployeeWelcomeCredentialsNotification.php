<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmployeeWelcomeCredentialsNotification extends Notification
{
    public function __construct(
        private string $employeeName,
        private string $username,
        private string $plainPassword,
        private string $staffId,
        private string $loginUrl
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Your HRMS account credentials'))
            ->greeting(__('Hello').' '.$this->employeeName)
            ->line(__('Your employee account has been created. Please use the credentials below to login:'))
            ->line(__('Staff Id').': **'.$this->staffId.'**')
            ->line(__('Username').': **'.$this->username.'**')
            ->line(__('Password').': **'.$this->plainPassword.'**')
            ->action(__('Login'), $this->loginUrl)
            ->line(__('Please change your password after first login if your organization requires it.'));
    }
}
