<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Email Two-Factor Authentication
    |--------------------------------------------------------------------------
    |
    | These settings control the email-based two-factor authentication feature.
    | You can configure the code length, expiry time, and other settings.
    |
    */

    'email' => [
        'code_length' => 6,
        'expiry_minutes' => env('EMAIL_2FA_EXPIRY_MINUTES', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Two-Factor Method
    |--------------------------------------------------------------------------
    |
    | This setting determines the default 2FA method for new users.
    | Options: 'email', 'totp'
    |
    */

    'default_method' => env('DEFAULT_2FA_METHOD', 'email'),

];
