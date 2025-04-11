<?php

return [
    'name' => $_ENV['APP_NAME'] ?? 'Slim API',
    'env' => $_ENV['APP_ENV'] ?? 'development',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN),
    'url' => $_ENV['APP_URL'] ?? 'http://localhost:8080/miapi',
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'UTC',
    'version' => '1.0.0',
];