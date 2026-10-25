<?php

declare(strict_types=1);

return [
    /*
    | CORS for the API only. Origins come from config/api.php
    | (API_ALLOWED_ORIGINS). The token travels in the Authorization header,
    | so no cookies/credentials are involved.
    */
    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => config('api.allowed_origins'),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
