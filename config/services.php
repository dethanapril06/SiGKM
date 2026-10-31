<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API SIADIKNONA Undana
    |--------------------------------------------------------------------------
    |
    | Integrasi read-only ke API SIADIKNONA (SIAKAD Cloud & Edlink) untuk
    | sinkronisasi master data akademik dan monev pelaksanaan perkuliahan.
    | Autentikasi menggunakan header X-App-Key dan X-Secret-Key.
    |
    */

    'siadiknona' => [
        'base_url' => env('SIADIKNONA_BASE_URL', 'https://api.undana.ac.id/siadiknona'),
        'app_key' => env('SIADIKNONA_APP_KEY'),
        'secret_key' => env('SIADIKNONA_SECRET_KEY'),
        'connect_timeout' => (int) env('SIADIKNONA_CONNECT_TIMEOUT', 20),
        'timeout' => (int) env('SIADIKNONA_TIMEOUT', 90),
        'retry_times' => (int) env('SIADIKNONA_RETRY_TIMES', 2),
        'retry_sleep' => (int) env('SIADIKNONA_RETRY_SLEEP', 1500),
        'per_page' => (int) env('SIADIKNONA_PER_PAGE', 100),
        'cache_ttl' => (int) env('SIADIKNONA_CACHE_TTL', 300),
        'verify_ssl' => (bool) env('SIADIKNONA_VERIFY_SSL', false),
        'prodi_id' => env('SIADIKNONA_PRODI_ID', '55201'),
        'prodi_nama' => env('SIADIKNONA_PRODI_NAMA', 'Ilmu Komputer'),
        'fast_snapshot' => (bool) env('SIADIKNONA_FAST_SNAPSHOT', true),
    ],

];
