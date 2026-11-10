<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OTP lifetime and limits
    |--------------------------------------------------------------------------
    */

    'expires_minutes' => (int) env('OTP_EXPIRES_MINUTES', 5),

    'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),

    'resend_cooldown_seconds' => (int) env('OTP_RESEND_COOLDOWN', 60),

    /*
    | Max OTP issue requests per email+purpose within the window.
    */
    'max_requests_per_window' => (int) env('OTP_MAX_REQUESTS', 5),

    'request_window_minutes' => (int) env('OTP_REQUEST_WINDOW_MINUTES', 15),

    /*
    | Max OTP issue requests per IP within the window.
    */
    'max_requests_per_ip' => (int) env('OTP_MAX_REQUESTS_PER_IP', 20),

    /*
    | Require OTP after password on login (2FA).
    */
    'login_enabled' => filter_var(env('OTP_LOGIN_ENABLED', true), FILTER_VALIDATE_BOOL),

    /*
    | Development-only: allow revealing OTP in session when mail fails or in testing.
    | Never enable OTP_DEV_REVEAL in production.
    */
    'dev_reveal' => filter_var(env('OTP_DEV_REVEAL', false), FILTER_VALIDATE_BOOL),

    'purposes' => [
        'registration' => 'account verification',
        'login' => 'sign-in verification',
        'password_reset' => 'password reset',
        'email_change' => 'email change',
        'sensitive_action' => 'sensitive account action',
        // Legacy aliases kept for older rows / callers
        'register' => 'account verification',
        'forgot_password' => 'password reset',
    ],

    'purpose_aliases' => [
        'register' => 'registration',
        'forgot_password' => 'password_reset',
    ],

];
