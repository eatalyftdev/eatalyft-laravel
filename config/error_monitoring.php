<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Error Monitoring Master Switch
    |--------------------------------------------------------------------------
    |
    | When false, all error monitoring is disabled. No incidents are recorded
    | and no notifications are dispatched.
    */

    'enabled' => env('ERROR_MONITORING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Notification Channels
    |--------------------------------------------------------------------------
    */

    'email' => [
        'enabled' => env('ERROR_ALERTS_ENABLED', false),
        'recipient' => env('ERROR_ALERT_EMAIL'),
    ],

    'telegram' => [
        'enabled' => env('ERROR_TELEGRAM_ENABLED', false),
        'bot_token' => env('ERROR_TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('ERROR_TELEGRAM_CHAT_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Duplicate Suppression
    |--------------------------------------------------------------------------
    |
    | Incidents with the same fingerprint are grouped together. If the same
    * fingerprint occurs again within the suppression interval (in minutes),
    * no new notification is sent — only the occurrence count and last_seen_at
    * are updated.
    */

    'suppression_interval_minutes' => env('ERROR_SUPPRESSION_INTERVAL_MINUTES', 60),

    /*
    |--------------------------------------------------------------------------
    | Minimum Notification Severity
    |--------------------------------------------------------------------------
    |
    | Only incidents at or above this severity will trigger notifications.
    | Values: debug, info, warning, error, critical
    */

    'min_notification_severity' => env('ERROR_MIN_NOTIFICATION_SEVERITY', 'error'),

    /*
    |--------------------------------------------------------------------------
    | Environment Restrictions
    |--------------------------------------------------------------------------
    |
    | If set, monitoring only runs in the listed environments. Leave empty
    | to monitor all environments.
    */

    'environments' => env('ERROR_MONITORING_ENVIRONMENTS', null),

    /*
    |--------------------------------------------------------------------------
    | Stack Trace Visibility
    |--------------------------------------------------------------------------
    |
    | Controls whether full stack traces are visible in the admin dashboard.
    | Set to false to hide stack traces from non-superadmin users.
    */

    'show_stack_traces' => env('ERROR_SHOW_STACK_TRACES', true),

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | When true, notifications are dispatched via Laravel's queue system.
    | When false, notifications are sent synchronously.
    */

    'queue_notifications' => env('ERROR_QUEUE_NOTIFICATIONS', false),

    /*
    |--------------------------------------------------------------------------
    | Recursive Protection
    |--------------------------------------------------------------------------
    |
    | When true, the error monitoring system will not report exceptions that
    * originate from within its own code, preventing recursive reporting.
    */

    'prevent_recursion' => env('ERROR_PREVENT_RECURSION', true),

];
