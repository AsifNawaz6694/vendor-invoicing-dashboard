<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailTwoFactorCodeNotification extends Notification
{

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $code = $notifiable->email_two_factor_code;
        $expiryMinutes = config('two-factor.email.expiry_minutes', 10);

        return (new MailMessage)
            ->subject('Raqtan Vendor Tool - Login Verification Code')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your secure verification code for Raqtan Vendor Tool is:')
            ->line('**' . $code . '**')
            ->line('This code will expire in ' . $expiryMinutes . ' minutes for security purposes.')
            ->line('Please enter this code to complete your login to the Vendor Invoicing and Payment Release Dashboard.')
            ->line('If you did not attempt to log in, please ignore this email and contact your system administrator if you suspect unauthorized access.')
            ->salutation('Best Regards, The Raqtan Vendor Tool Security Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'code' => $notifiable->email_two_factor_code,
        ];
    }
}
