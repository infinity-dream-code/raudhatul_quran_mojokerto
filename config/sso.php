<?php

return [

    'cyber_key_table' => env('SSO_CYBER_KEY_TABLE', 'cyber_key'),

    /** Filter kolom kunci (opsional), mis. kltq */
    'kunci' => env('SSO_KUNCI'),

    'modules' => [
        'presensi' => [
            'enabled' => (bool) env('SSO_MODULE_PRESENSI_ENABLED', false),
            'url' => env('SSO_MODULE_PRESENSI_URL', ''),
            'label' => 'Presensi',
        ],
        'sikeu' => [
            'enabled' => true,
            'label' => 'SIKEU',
        ],
        'cashless' => [
            'enabled' => (bool) env('SSO_MODULE_CASHLESS_ENABLED', true),
            'url' => env('SSO_MODULE_CASHLESS_URL', ''),
            'label' => 'Cashless',
        ],
    ],

];
