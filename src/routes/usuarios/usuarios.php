<?php

use App\Helpers\ControllerHelper;
use App\Helpers\DateHelper;
use App\Helpers\JsonResponse;
use App\Helpers\PaginationHelper;
use App\Middleware\JwtAuthMiddleware;
use App\Repositories\UsuarioRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Obtenemos las dependencias necesarias del contenedor
$app = $container->get('app');
$jwtHelper = $container->get('jwtHelper');
$validator = $container->get('validator');

// Registrar el repositorio en el contenedor
$container->set('usuarioRepository', function ($container) {

    return new UsuarioRepository($container->get('dbHelper'));
});

// Rutas de usuarios
$app->group('/api/usuarios', function ($group) use ($container, $validator) {
    
    // Ruta para obtener un usuario por cualquier dato
    $group->get('/search', function (Request $request, Response $response) use ($container) {

        return ControllerHelper::handleRequest(
            function () use ($request, $response, $container) {
                $pagination = PaginationHelper::getParams($request);
                $page = $pagination['page'];
                $limit = $pagination['limit'];
                $all = $pagination['all'];
                $exactMatch = $pagination['exact'];

                $params = $request->getQueryParams();
            
                $criteria = [];
                
                if (isset($params['id_cliente'])) {
                    $criteria['id_cliente'] = (int)$params['id_cliente'];
                }
                
                if (isset($params['cedula'])) {
                    $criteria['cedula'] = $params['cedula'];
                }
                
                if (isset($params['nombre'])) {
                    $criteria['nombre'] = $params['nombre'];
                }
                
                if (isset($params['apellidos'])) {
                    $criteria['apellidos'] = $params['apellidos'];
                }
                
                if (isset($params['nombre_completo'])) {
                    $criteria['nombre_completo'] = $params['nombre_completo'];
                }
                
                if (isset($params['telefono'])) {
                    $criteria['telefono'] = $params['telefono'];
                }
                
                if (isset($params['email'])) {
                    $criteria['email'] = $params['email'];
                }
                
                if (isset($params['estado'])) {
                    $criteria['estado'] = $params['estado'];
                }
                
                $usuarioRepository = $container->get('usuarioRepository');
                
                if (empty($criteria)) {

                    return JsonResponse::error(
                        $response,
                        'Debe proporcionar al menos un criterio de búsqueda',
                        [],
                        400
                    );
                }
                
                $resultado = $usuarioRepository->searchUsuario($criteria, $page, $limit, $exactMatch);
                
                return JsonResponse::success($response, [
                    'message' => 'Búsqueda realizada correctamente',
                    'usuarios' => $resultado['items'],
                    'pagination' => $resultado['pagination']
                ]);
            }, $response, 'Error al obtener el usuario'
        );
    });

    // Ruta para obtener todos los usuarios
    $group->get('', function (Request $request, Response $response) use ($container) {

        return ControllerHelper::handleRequest(
            function () use ($request, $response, $container) {
                
                $pagination = PaginationHelper::getParams($request);
                $page = $pagination['page'];
                $limit = $pagination['limit'];
                $all = $pagination['all'];

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
                
            }, $response, 'Error al obtener los usuarios'
        );
    });

    // Ruta para obtener un usuario por ID
    $group->get('/{id}', function (Request $request, Response $response, array $args) use ($container) {

        return ControllerHelper::handleRequest(
            function () use ($request, $response, $args, $container) {

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
            }, $response, 'Error al obtener el usuario'
        );
    });

    // Aquí puedes agregar otras rutas para usuarios
    // - POST para crear usuarios

    // Ruta para actualizar el estado de un usuario
    

    $group->patch('/{id}/estado', function (Request $request, Response $response, array $args) use ($container, $validator) {
        
        return ControllerHelper::handleRequest(
            function () use ($request, $response, $container, $validator, $args) {

                $id = (int) $args['id'];

                $data = $request->getParsedBody();

                if (!isset($data['estado'])) {
                    return JsonResponse::error($response,
                        'El estado es obligatorio',
                        [],
                        422);
                }

                $validator = $validator($data);
                $validator->in('estado', [1, 2], 'El estado debe ser activo o inactivo');

                if ($validator->fails()) {

                    return JsonResponse::error($response,
                        'Error de validación',
                        ['errors' => $validator->errors()],
                        422);
                }

                $clienteRepository = $container->get('clienteRepository');
                $clienteExistente = $clienteRepository->getById($id);

                if (!$clienteExistente) {

                    return JsonResponse::error($response,
                        'Cliente no encontrado',
                        ['id' => $id],
                        404);
                }

                $rowCount = $clienteRepository->updateStatus($id, $data['estado']);

                $cliente = $clienteRepository->getById($id);

                return JsonResponse::success($response, [
                    'message' => 'Estado del cliente actualizado correctamente',
                    'cliente' => $cliente,
                    'rows_affected' => $rowCount
                ]);
            }, $response, 'Error al actualizar el estado del cliente'
        );
    });

    // Ruta para actualizar un usuario (permitiendo actualización parcial)
    $group->put('/{id}', function (Request $request, Response $response, array $args) use ($container) {

        return ControllerHelper::handleRequest(
            function () use ($request, $response, $args, $container) {

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

                    $validator
                        ->email('email')
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
            }, $response, 'Error al actualizar el usuario'
        );
    });

    // - DELETE para eliminar usuarios
})->add(new JwtAuthMiddleware($container->get('jwtHelper')));  // Protegemos las rutas con JWT
