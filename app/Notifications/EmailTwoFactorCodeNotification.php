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
            ->subject('Your Login Verification Code - ' . config('app.name'))
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your verification code is:')
            ->line('**' . $code . '**')
            ->line('This code will expire in ' . $expiryMinutes . ' minutes.')
            ->line('If you did not request this code, please ignore this email and ensure your account is secure.')
            ->salutation('Regards, ' . config('app.name'));
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
