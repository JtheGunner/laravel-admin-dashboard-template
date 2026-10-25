<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Static service token
    |--------------------------------------------------------------------------
    | Bearer token for server-to-server access, verified by the
    | AuthenticateApiToken middleware. Set it as a secret in production and
    | never commit it. An empty value rejects every request (fail closed).
    */
    'token' => env('API_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Allowed CORS origins
    |--------------------------------------------------------------------------
    | Comma-separated list in API_ALLOWED_ORIGINS, read by config/cors.php.
    | Irrelevant for server-to-server consumers, but covers browser calls.
    */
    'allowed_origins' => array_values(array_filter(
        array_map('trim', explode(',', (string) env('API_ALLOWED_ORIGINS', '')))
    )),
];
