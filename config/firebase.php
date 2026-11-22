<?php

return [
    'project_id' => env('FIREBASE_PROJECT_ID'),
    'auth_domain' => env('FIREBASE_AUTH_DOMAIN'),
    'storage_bucket' => env('FIREBASE_STORAGE_BUCKET'),
    'messaging_sender_id' => env('FIREBASE_MESSAGING_SENDER_ID'),
    'app_id' => env('FIREBASE_APP_ID'),
    'credentials' => env('FIREBASE_CREDENTIALS'),
    'database_id' => env('FIREBASE_DATABASE_ID'),
    'emulator' => [
        'auth_host' => env('FIREBASE_AUTH_EMULATOR_HOST'),
        'firestore_host' => env('FIRESTORE_EMULATOR_HOST'),
        'storage_host' => env('FIREBASE_STORAGE_EMULATOR_HOST'),
    ],
];
