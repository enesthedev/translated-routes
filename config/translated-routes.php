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

    'cache_enabled' => true,

    'cache_ttl' => 86400,

];
