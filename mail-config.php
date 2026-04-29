<?php

// Load .env file if it exists
$envFile = __DIR__ . '/.env';
if (is_file($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\"'");
            putenv("{$key}={$value}");
        }
    }
}

return [
    'host' => getenv('SMTP_HOST') ?: 'smtp.gmail.com',
    'username' => getenv('SMTP_USERNAME') ?: 'highdigitech1906@gmail.com',
    'password' => getenv('SMTP_PASSWORD') ?: 'pgucfyoecabfrkpm',
    'port' => (int) (getenv('SMTP_PORT') ?: 587),
    'encryption' => getenv('SMTP_ENCRYPTION') ?: 'tls',
    'from_email' => getenv('MAIL_FROM_EMAIL') ?: 'highdigitech1906@gmail.com',
    'from_name' => getenv('MAIL_FROM_NAME') ?: 'Website Query',
    'to_email' => getenv('MAIL_TO_EMAIL') ?: 'highdigitech1906@gmail.com',
    'to_name' => getenv('MAIL_TO_NAME') ?: 'Highdigitech',
];
