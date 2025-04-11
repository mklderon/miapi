<?php

// Cargar el autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Establecer el entorno como prueba
putenv('APP_ENV=testing');

// Crear un contenedor de prueba y configurarlo de manera similar al de producción
$container = new \DI\Container();

// Cargar configuración similar a la función configureContainer() de tu aplicación
// Incluimos el cargador de configuraciones
require_once __DIR__ . '/../src/config/config.php';

// Registramos el servicio de configuración
$container->set('config', function($c) {
    return function(string $configName) {
        return loadConfig($configName);
    };
});

// Configuramos Medoo como servicio usando la configuración de prueba
$container->set('db', function($c) {
    $config = $c->get('config');
    $dbConfig = $config('database');
    
    // Sobreescribir la configuración de la base de datos para pruebas
    // Esto asume que tienes un archivo de configuración para pruebas o sobreescribes valores
    // Si estás usando la misma base de datos, considera usar un prefijo de tabla para pruebas
    $dbConfig['database'] = getenv('DB_TEST_DATABASE') ?: $dbConfig['database'] . '_test';
    
    return new \Medoo\Medoo($dbConfig);
});

// Registramos el helper de base de datos
$container->set('dbHelper', function($c) {
    return new \App\Helpers\DbHelper($c->get('db'));
});

// Hacer el contenedor disponible globalmente para las pruebas
global $container;
$container = $container;