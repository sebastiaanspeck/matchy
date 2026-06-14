<?php

return [

    'name' => env('APP_NAME', 'Matchy'),

    'env' => env('APP_ENV', 'production'),

    'debug' => env('APP_DEBUG', false),

    'url' => env('APP_URL', 'http://localhost'),

    'timezone' => 'Europe/Amsterdam',

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    'maintenance' => ['driver' => 'file'],

    'aliases' => Illuminate\Support\Facades\Facade::defaultAliases()->merge([
        'Agent' => Jenssegers\Agent\Facades\Agent::class,
    ])->toArray(),

];
