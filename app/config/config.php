<?php

declare(strict_types=1);

return [
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: 'soscur_db',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') !== false ? getenv('DB_PASS') : '',
        'charset' => 'utf8mb4',
    ],
    'sendgrid' => [
        'key' => getenv('SENDGRID_API_KEY') ?: '',
        'from' => getenv('SENDGRID_FROM_EMAIL') ?: '',
        'sandbox' => filter_var(getenv('SENDGRID_SANDBOX') ?: 'false', FILTER_VALIDATE_BOOLEAN),
        'url' => 'https://api.sendgrid.com/v3/mail/send',
    ],
    'app_url' => getenv('APP_URL') ?: '',
    'verification_hours' => 24,
];
