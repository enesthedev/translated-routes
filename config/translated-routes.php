<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    |
    | Define all locales your application supports.
    |
    */

    'supported_locales' => [
        'en' => [
            'name' => 'English',
            'native' => 'English',
        ],
        'tr' => [
            'name' => 'Turkish',
            'native' => 'Türkçe',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Enable caching for translated routes to improve performance.
    |
    */

    'cache_enabled' => env('TRANSLATED_ROUTES_CACHE', true),

    'cache_ttl' => env('TRANSLATED_ROUTES_CACHE_TTL', 86400),

];
