<?php
// Archivo: src/config/app.php

/**
 * Convertir formato UTC±N a zonas horarias estándar de PHP
 * Nota: Esta función es local al ámbito de este archivo
 */
$parseTimezone = function($timezone) {
    // Si ya es un identificador de zona horaria válido, usarlo directamente
    if (in_array($timezone, timezone_identifiers_list())) {
        return $timezone;
    }
    
    // Intentar convertir formato UTC±N
    if (preg_match('/^UTC([+-])(\d+)$/', $timezone, $matches)) {
        $sign = $matches[1] === '+' ? '-' : '+'; // Invertir el signo (UTC+5 = GMT-5)
        $hours = (int)$matches[2];
        
        // Mapeo simple de desplazamientos UTC comunes a zonas horarias
        $timezoneMap = [
            '-12' => 'Pacific/Kwajalein',
            '-11' => 'Pacific/Samoa',
            '-10' => 'Pacific/Honolulu',
            '-9' => 'America/Juneau',
            '-8' => 'America/Los_Angeles',
            '-7' => 'America/Denver',
            '-6' => 'America/Chicago',
            '-5' => 'America/New_York',
            '-4' => 'America/Halifax',
            '-3' => 'America/Sao_Paulo',
            '-2' => 'Atlantic/South_Georgia',
            '-1' => 'Atlantic/Azores',
            '0' => 'Europe/London',
            '+1' => 'Europe/Paris',
            '+2' => 'Europe/Helsinki',
            '+3' => 'Europe/Moscow',
            '+4' => 'Asia/Dubai',
            '+5' => 'Asia/Karachi',
            '+5.5' => 'Asia/Kolkata',
            '+6' => 'Asia/Dhaka',
            '+7' => 'Asia/Bangkok',
            '+8' => 'Asia/Singapore',
            '+9' => 'Asia/Tokyo',
            '+10' => 'Pacific/Guam',
            '+11' => 'Pacific/Noumea',
            '+12' => 'Pacific/Auckland'
        ];
        
        $key = $sign . $hours;
        return isset($timezoneMap[$key]) ? $timezoneMap[$key] : 'UTC';
    }
    
    // Si el formato no coincide, usar UTC como fallback
    return 'UTC';
};

// Aplicar la función para obtener la zona horaria
$timezone = $parseTimezone($_ENV['TIMEZONE'] ?? 'UTC');

return [
    'name' => $_ENV['APP_NAME'] ?? 'Slim API',
    'env' => $_ENV['APP_ENV'] ?? 'development',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN),
    'url' => $_ENV['APP_URL'] ?? 'http://localhost:4321/miapi',
    'timezone' => $timezone,
    'version' => '1.0.0',
];