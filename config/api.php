<?php

return [
    'jwt_secret' => env('API_JWT_SECRET', env('APP_KEY')),
    'access_ttl' => (int) env('API_ACCESS_TTL', 60 * 24 * 30),
    'refresh_ttl' => (int) env('API_REFRESH_TTL', 60 * 24 * 90),
];
