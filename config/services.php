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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'ws_amal_fatimah' => [
        'url' => env('WS_AMAL_FATIMAH_URL', 'http://103.23.103.43/ws_CLIENT/raudhatul_quran_mojokerto/index.php'),
        'jwt_key' => env('WS_AMAL_FATIMAH_JWT_KEY'),
        'timeout' => (int) env('WS_AMAL_FATIMAH_TIMEOUT', 8),
        'connect_timeout' => (int) env('WS_AMAL_FATIMAH_CONNECT_TIMEOUT', 2),
        /** true = pindah kelas selalu lewat DB lokal (SIKEU_DB_*), bukan WS remote */
        'local_pindah_kelas' => (bool) env('WS_AMAL_FATIMAH_LOCAL_PINDAH_KELAS', false),
    ],

    'turnstile' => [
        'site_key' => env(
            config('app.env') === 'local'
                ? 'TURNSTILE_SITE_KEY_LOCAL'
                : 'TURNSTILE_SITE_KEY_PROD'
        ),
        'secret_key' => env(
            config('app.env') === 'local'
                ? 'TURNSTILE_SECRET_KEY_LOCAL'
                : 'TURNSTILE_SECRET_KEY_PROD'
        ),
    ],

];
