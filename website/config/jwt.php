<?php

return [
    // HS256 signing secret. Must be at least 32 bytes; generate with `openssl rand -base64 48`.
    'secret' => env('JWT_SECRET'),

    'issuer' => env('JWT_ISSUER', env('APP_URL', 'http://localhost')),

    // Access tokens are stateless, so keep them short-lived.
    'access_ttl_minutes' => (int) env('JWT_ACCESS_TTL_MINUTES', 15),

    'refresh_ttl_days' => (int) env('JWT_REFRESH_TTL_DAYS', 14),
];
