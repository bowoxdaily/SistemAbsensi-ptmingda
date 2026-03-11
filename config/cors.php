<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which origins are allowed to make cross-origin requests.
    | The 'supports_credentials' option must be true when using Sanctum
    | SPA (session-based) auth. For Bearer token auth (external apps),
    | it can be false with any origin.
    |
    | For production, replace 'allowed_origins' => ['*'] with the specific
    | domains of your client applications, e.g.:
    |   'allowed_origins' => ['https://app.contoh.com', 'https://erp.perusahaan.com'],
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Set to true only if using Sanctum SPA (cookie-based) auth from a specific domain.
    // For Bearer token-based external apps, keep as false.
    'supports_credentials' => false,

];
