<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Helpers\JsonResponse;

// Obtenemos la aplicación y la configuración del contenedor
$app = $container->get('app');
$config = $container->get('config');

// Ruta para mostrar información de la aplicación
$app->get('/api/info', function (Request $request, Response $response, $args) use ($config) {
    // Obtenemos la configuración de la aplicación
    $appConfig = $config('app');
    
    // Respuesta exitosa con información de la aplicación
    return JsonResponse::success($response, [
        'app' => [
            'name' => $appConfig['name'],
            'version' => $appConfig['version'],
            'environment' => $appConfig['env']
        ],
        'server_time' => date('Y-m-d H:i:s')
    ]);
});