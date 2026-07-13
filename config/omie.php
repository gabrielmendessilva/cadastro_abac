<?php

return [
    'api' => [
        'url'     => env('URL_OMIE', 'https://app.omie.com.br/api/'),
        'key'     => env('OMIE_APP_KEY'),
        'secret'  => env('OMIE_APP_SECRET'),
        'timeout' => (int) env('OMIE_TIMEOUT', 30),
    ],
];
