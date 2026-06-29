<?php

return [

    'cyber_key_table' => env('SSO_CYBER_KEY_TABLE', 'cyber_key'),

    /** Filter kolom kunci (opsional), mis. kltq */
    'kunci' => env('SSO_KUNCI'),

    'modules' => [
        'presensi' => [
            'enabled' => (bool) env('SSO_MODULE_PRESENSI_ENABLED', true),
            'url' => env('SSO_MODULE_PRESENSI_URL', 'https://presensi-raudhatulquran.smartpayment.co.id/login.php'),
            'label' => 'Presensi',
            'use_signed_token' => (bool) env('SSO_MODULE_PRESENSI_USE_SIGNED_TOKEN', true),
        ],
        'sikeu' => [
            'enabled' => true,
            'label' => 'SIKEU',
            'url' => env('SSO_MODULE_SIKEU_URL', ''),
        ],
        'cashless' => [
            'enabled' => (bool) env('SSO_MODULE_CASHLESS_ENABLED', true),
            'url' => env('SSO_MODULE_CASHLESS_URL', ''),
            'label' => 'Cashless',
            'use_signed_token' => (bool) env('SSO_MODULE_CASHLESS_USE_SIGNED_TOKEN', false),
        ],
    ],

    'token' => [
        'secret' => env('SSO_SHARED_SECRET', 'ganti-dengan-secret-yang-aman'),
        'ttl' => (int) env('SSO_TOKEN_TTL', 300),
    ],

];
