<?php

namespace App\Notifications;

use App\Models\PasswordSetupToken;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class WelcomeUserNotification extends Notification
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
        // Generate a password setup token
        $token = Str::random(64);

        // Delete any existing tokens for this user
        PasswordSetupToken::where('user_id', $notifiable->id)->delete();

        // Create new token
        PasswordSetupToken::create([
            'user_id' => $notifiable->id,
            'token' => $token,
            'expires_at' => now()->addHours(48),
        ]);

        $setupUrl = url('/password/setup/' . $token);

        return (new MailMessage)
            ->subject('Welcome to Raqtan Vendor Tool - Account Setup')
            ->greeting('Welcome, ' . $notifiable->name . '!')
            ->line('Your account has been created for the Raqtan Vendor Invoicing and Payment Release Dashboard.')
            ->line('Please click the button below to set up your password and complete your account setup.')
            ->action('Set Up Password', $setupUrl)
            ->line('This link will expire in 48 hours for security purposes.')
            ->line('After setting your password, you will need to authenticate with Two-Factor Authentication (2FA) to access the system.')
            ->line('If you did not expect this email, please contact your system administrator immediately.')
            ->salutation('Best Regards, The Raqtan Vendor Tool Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
