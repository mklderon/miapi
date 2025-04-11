<?php

return [
    'type' => $_ENV['DB_DRIVER'] ?? 'mysql',
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'database' => $_ENV['DB_NAME'],
    'username' => $_ENV['DB_USER'],
    'password' => $_ENV['DB_PASS'],
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_general_ci',
    'port' => $_ENV['DB_PORT'] ?? 3306,
    'prefix' => $_ENV['DB_PREFIX'] ?? '',
    // Opciones adicionales de PDO para mejor rendimiento y seguridad
    'option' => [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
];