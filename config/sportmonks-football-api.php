<?php

return [
    'base_url' => 'https://api.sportmonks.com/v3/',
    'api_token' => env('SPORTMONKS_FOOTBALL_API_TOKEN'),
    'timezone' => env('SPORTMONKS_FOOTBALL_TIMEZONE', 'UTC'),
    'locale' => env('SPORTMONKS_FOOTBALL_LOCALE', ''),
    'return_type' => 'array', // array, collection, response, or dto
];
