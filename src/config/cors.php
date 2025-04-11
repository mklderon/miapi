<?php

// Procesar orígenes permitidos desde variables de entorno
$allowedOrigins = $_ENV['CORS_ALLOWED_ORIGINS'] ?? '*';
if ($allowedOrigins !== '*') {
    $allowedOrigins = explode(',', $allowedOrigins);
}

return [
    'allowed_origins' => $allowedOrigins,
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    'allowed_headers' => ['X-Requested-With', 'Content-Type', 'Accept', 'Origin', 'Authorization'],
    'exposed_headers' => [],
    'max_age' => 3600,
    'allow_credentials' => true,
];