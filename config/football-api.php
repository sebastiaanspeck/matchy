<?php

return [
    'provider' => env('FOOTBALL_API_PROVIDER', 'api-football'),

    'api_football' => [
        'base_url' => 'https://v3.football.api-sports.io/',
        'api_key' => env('API_FOOTBALL_KEY'),
        'timezone' => env('API_FOOTBALL_TIMEZONE', 'UTC'),
    ],

    'sportmonks' => [
        'api_token' => env('SPORTMONKS_FOOTBALL_API_TOKEN'),
        'timezone' => env('SPORTMONKS_FOOTBALL_TIMEZONE', 'UTC'),
    ],
];
