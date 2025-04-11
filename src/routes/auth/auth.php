<?php
// Archivo: src/routes/auth/auth.php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Helpers\JsonResponse;

// Obtenemos la aplicación del contenedor
$app = $container->get('app');

// Ruta de autenticación usando el helper JsonResponse
$app->get('/api/auth', function (Request $request, Response $response, $args) {
    return JsonResponse::success($response, [
        'endpoint' => '/api/auth',
        'version' => '1.0',
        'status' => 'available'
    ]);
});

// Ejemplo de ruta con manejo de errores
$app->post('/api/login', function (Request $request, Response $response, $args) {
    $data = $request->getParsedBody();
    
    // Validación básica
    if (!isset($data['username']) || !isset($data['password'])) {
        return JsonResponse::error(
            $response, 
            'Falta información de login', 
            ['required_fields' => ['username', 'password']], 
            422
        );
    }
    
    // Aquí iría la lógica de autenticación real
    // ...
    
    // Si la autenticación es exitosa
    return JsonResponse::success($response, [
        'user' => [
            'id' => 1,
            'username' => $data['username'],
            'role' => 'usuario'
        ],
        'token' => 'jwt-token-would-go-here'
    ]);
});