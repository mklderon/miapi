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

// Rutas de usuarios
$app->group('/api/usuarios', function ($group) use ($container) {
    
    // Ruta para obtener todos los usuarios
    $group->get('', function (Request $request, Response $response) use ($container) {
        try {
            // Obtener parámetros de paginación
            $params = $request->getQueryParams();
            $page = isset($params['page']) ? (int)$params['page'] : 1;
            $limit = isset($params['limit']) ? (int)$params['limit'] : 10;
            
            // Si se solicita todos los usuarios sin paginación
            $all = isset($params['all']) && $params['all'] === 'true';
            
            // Obtener el repositorio
            $usuarioRepository = $container->get('usuarioRepository');
            
            if ($all) {
                // Obtener todos los usuarios sin paginación
                $usuarios = $usuarioRepository->getAll();
                
                // Retornar la respuesta con los usuarios
                return JsonResponse::success($response, [
                    'message' => 'Usuarios obtenidos exitosamente',
                    'usuarios' => $usuarios,
                    'total' => count($usuarios)
                ]);
            } else {
                // Obtener usuarios con paginación
                $resultado = $usuarioRepository->getPaginated($page, $limit);
                
                // Retornar la respuesta con los usuarios
                return JsonResponse::success($response, [
                    'message' => 'Usuarios obtenidos exitosamente',
                    'usuarios' => $resultado['items'],
                    'pagination' => $resultado['pagination']
                ]);
            }
        } catch (\Exception $e) {
            // Manejar errores
            return JsonResponse::error(
                $response, 
                'Error al obtener los usuarios', 
                ['error' => $e->getMessage()], 
                500
            );
        }
    });
    
    // Ruta para obtener un usuario por ID
    $group->get('/{id}', function (Request $request, Response $response, array $args) use ($container) {
        try {
            // Obtener el ID del usuario
            $id = $args['id'];
            
            // Obtener el repositorio
            $usuarioRepository = $container->get('usuarioRepository');
            
            // Buscar el usuario por ID
            $usuario = $usuarioRepository->findById($id);
            
            // Verificar si el usuario existe
            if (!$usuario) {
                return JsonResponse::error(
                    $response, 
                    'Usuario no encontrado', 
                    ['id' => $id], 
                    404
                );
            }
            
            // Retornar la respuesta con el usuario
            return JsonResponse::success($response, [
                'message' => 'Usuario obtenido exitosamente',
                'usuario' => $usuario
            ]);
        } catch (\Exception $e) {
            // Manejar errores
            return JsonResponse::error(
                $response, 
                'Error al obtener el usuario', 
                ['error' => $e->getMessage()], 
                500
            );
        }
    });
    
    // Aquí puedes agregar otras rutas para usuarios
    // - POST para crear usuarios
    
    // Ruta para actualizar un usuario (permitiendo actualización parcial)
    $group->put('/{id}', function (Request $request, Response $response, array $args) use ($container) {
        try {
            // Obtener el ID del usuario
            $id = $args['id'];
            
            // Obtener el repositorio
            $usuarioRepository = $container->get('usuarioRepository');
            
            // Verificar si el usuario existe
            $usuario = $usuarioRepository->findById($id);
            if (!$usuario) {
                return JsonResponse::error(
                    $response, 
                    'Usuario no encontrado', 
                    ['id' => $id], 
                    404
                );
            }
            
            // Obtener los datos del cuerpo de la solicitud
            $data = $request->getParsedBody();
            
            // Verificar que se proporcionó al menos un campo para actualizar
            if (empty($data)) {
                return JsonResponse::error(
                    $response,
                    'No se proporcionaron datos para actualizar',
                    [],
                    422
                );
            }
            
            // Crear instancia del validador con los datos y el helper de DB
            $validator = new \App\Helpers\Validator($data, $container->get('dbHelper'));
            
            // Aplicar reglas de validación solo a los campos proporcionados
            if (isset($data['nombre'])) {
                $validator->min('nombre', 2);
            }
            
            if (isset($data['apellidos'])) {
                $validator->min('apellidos', 2);
            }
            
            if (isset($data['email'])) {
                $validator->email('email')
                        ->unique('email', 'usuarios', null, $id, 'id_usuario');
            }
            
            if (isset($data['telefono']) && $data['telefono'] !== null) {
                $validator->min('telefono', 8);
                $validator->numeric('telefono');
            }
            
            // Verificar si hay errores de validación
            if ($validator->fails()) {
                return JsonResponse::error(
                    $response,
                    'Error de validación',
                    ['errors' => $validator->errors()],
                    422
                );
            }
            
            // Agregar fecha de actualización
            $data['updated_at'] = DateHelper::formatForDatabase();
            
            // Actualizar el usuario
            $updated = $usuarioRepository->update($id, $data);
            
            if (!$updated) {
                return JsonResponse::error(
                    $response,
                    'No se pudo actualizar el usuario',
                    [],
                    500
                );
            }
            
            // Obtener el usuario actualizado
            $usuarioActualizado = $usuarioRepository->findById($id);
            
            // Retornar respuesta exitosa
            return JsonResponse::success($response, [
                'message' => 'Usuario actualizado exitosamente',
                'usuario' => $usuarioActualizado
            ]);
            
        } catch(\Exception $e) {
            // Manejar errores
            return JsonResponse::error(
                $response,
                'Error al actualizar el usuario',
                ['error' => $e->getMessage()],
                500
            );
        }
    });

    // - DELETE para eliminar usuarios
    
})->add(new JwtAuthMiddleware($container->get('jwtHelper'))); // Protegemos las rutas con JWT