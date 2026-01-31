<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'logout', 'user', 'admin/*', 'auth/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://61.14.234.57:3000',
        'http://61.14.234.57:8000',
        'http://61.14.234.57',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['XSRF-TOKEN'],

    'max_age' => 0,

    'supports_credentials' => true,

];
