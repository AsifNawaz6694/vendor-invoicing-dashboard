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
            ->subject('Welcome to ' . config('app.name') . ' - Set Up Your Account')
            ->greeting('Welcome, ' . $notifiable->name . '!')
            ->line('An account has been created for you at ' . config('app.name') . '.')
            ->line('Please click the button below to set up your password and complete your account setup.')
            ->action('Set Up Password', $setupUrl)
            ->line('This link will expire in 48 hours.')
            ->line('If you did not expect this email, please contact your administrator.')
            ->salutation('Regards, ' . config('app.name'));
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
