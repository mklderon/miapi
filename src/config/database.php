<?php

// Obtener la zona horaria de la aplicación
$appConfig = loadConfig('app');
$timezone = $appConfig['timezone'];

// Convertir a formato MySQL (ej: -05:00 para UTC-5)
$mysqlTimezoneOffset = '';
$dateTimeZone = new DateTimeZone($timezone);
$now = new DateTime('now', $dateTimeZone);
$offset = $dateTimeZone->getOffset($now);
$hours = floor(abs($offset) / 3600);
$minutes = floor((abs($offset) - $hours * 3600) / 60);
$mysqlTimezoneOffset = ($offset >= 0 ? '+' : '-') . 
                        str_pad($hours, 2, '0', STR_PAD_LEFT) . ':' . 
                        str_pad($minutes, 2, '0', STR_PAD_LEFT);

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
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Establecer la zona horaria en la sesión MySQL
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone='{$mysqlTimezoneOffset}';"
    ]
];