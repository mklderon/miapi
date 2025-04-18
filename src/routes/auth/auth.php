<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Helpers\JsonResponse;
use App\Helpers\DateHelper;
use App\Middleware\JwtAuthMiddleware;
use App\Repositories\UsuarioRepository;

// Obtenemos las dependencias necesarias del contenedor
$app = $container->get('app');
$jwtHelper = $container->get('jwtHelper');
$validator = $container->get('validator');

// Registrar el repositorio en el contenedor
$container->set('usuarioRepository', function ($container) {
    return new UsuarioRepository($container->get('dbHelper'));
});

// Ruta de login
$app->post('/api/login', function (Request $request, Response $response, $args) use ($container, $jwtHelper) {
    // Obtener los datos del cuerpo de la solicitud
    $data = $request->getParsedBody();

    // Verificar si $data es null y convertirlo a un array vacío si es así
    if ($data === null) {
        $data = [];
    }
    
    // Obtener el factory de validator del contenedor y crear una instancia
    $validator = $container->get('validator')($data);
    $validator->required('email', 'El email es requerido')
              ->email('email', 'El formato del email no es válido')
              ->required('password', 'La contraseña es requerida')
              ->min('password', 6, 'La contraseña debe tener al menos 6 caracteres');
    
    if ($validator->fails()) {
        return JsonResponse::error(
            $response, 
            'Errores de validación', 
            ['errors' => $validator->errors()], 
            422
        );
    }
    
    // Usar el repositorio para verificar credenciales
    $usuarioRepository = $container->get('usuarioRepository');
    $user = $usuarioRepository->verificarCredenciales($data['email'], $data['password']);
    
    // Verificar si las credenciales son válidas
    if (!$user) {
        return JsonResponse::error($response, 'Credenciales inválidas', [], 401);
    }
    
    // Generar token JWT
    $token = $jwtHelper->generateToken($user);
    
    // Respuesta exitosa
    return JsonResponse::success($response, [
        'message' => 'Login exitoso',
        'user' => $user,
        'token' => $token
    ]);
});

// Ruta para cerrar sesión (en el cliente simplemente se elimina el token)
$app->post('/api/logout', function (Request $request, Response $response, $args) {
    return JsonResponse::success($response, [
        'message' => 'Sesión cerrada exitosamente'
    ]);
});

// Creamos un grupo de rutas protegidas
$app->group('/api/protected', function ($group) use ($jwtHelper) {

    // Ruta protegida que requiere autenticación
    $group->get('/user/profile', function (Request $request, Response $response, $args) {
        // Los datos del usuario están disponibles gracias al middleware
        $user = $request->getAttribute('user');
        
        return JsonResponse::success($response, [
            'message' => 'Perfil obtenido exitosamente',
            'user' => $user
        ]);
    });

    // Ruta para refrescar el token
    $group->post('/refresh-token', function (Request $request, Response $response, $args) use ($jwtHelper) {
        // Los datos del usuario están disponibles gracias al middleware
        $user = $request->getAttribute('user');
        
        // Generar un nuevo token
        $token = $jwtHelper->generateToken($user);
        
        return JsonResponse::success($response, [
            'message' => 'Token refrescado exitosamente',
            'token' => $token
        ]);
    });
})->add(new JwtAuthMiddleware($container->get('jwtHelper')));