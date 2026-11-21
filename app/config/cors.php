<?php

declare(strict_types=1);

$defaultOrigins = [
    'http://localhost',
    'http://127.0.0.1',
    'http://localhost:3000',
    'http://127.0.0.1:3000',
    'http://localhost:5500',
    'http://127.0.0.1:5500',
    'http://localhost:8000',
    'http://127.0.0.1:8000',
    'http://localhost:8080',
    'http://127.0.0.1:8080',
];

$configuredOrigins = getenv('CORS_ALLOWED_ORIGINS');
$origins = $configuredOrigins !== false && trim($configuredOrigins) !== ''
    ? explode(',', $configuredOrigins)
    : $defaultOrigins;

$origins = array_values(array_unique(array_filter(array_map(
    static fn (string $origin): string => rtrim(trim($origin), '/'),
    $origins
))));

return [
    'allowed_origins' => $origins,
    'allowed_methods' => ['GET', 'POST', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_headers' => ['Accept', 'Content-Type', 'X-Requested-With', 'X-CSRF-Token'],
    'max_age' => 600,
    'allow_credentials' => !in_array('*', $origins, true),
];
