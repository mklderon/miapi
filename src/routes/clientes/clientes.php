<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Helpers\JsonResponse;
use App\Helpers\DateHelper;
use App\Helpers\ControllerHelper;
use App\Helpers\PaginationHelper;
use App\Middleware\JwtAuthMiddleware;
use App\Repositories\ClienteRepository;

$app = $container->get('app');
$jwtHelper = $container->get('jwtHelper');
$validator = $container->get('validator');

$container->set('clienteRepository', function ($container) {
    return new clienteRepository($container->get('dbHelper'));
});

$app->group('/api/clientes', function ($group) use ($container, $validator) {
    $group->get('', function (Request $request, Response $response) use ($container) {
        return ControllerHelper::handleRequest(
            function() use ($request, $response, $container) {

                $pagination = PaginationHelper::getParams($request);
                $page = $pagination['page'];
                $limit = $pagination['limit'];
                $all = $pagination['all'];

                $clienteRepository = $container->get('clienteRepository');

                if ($all) {
                    $clientes = $clienteRepository->getAll();

                    return JsonResponse::success($response, [
                        'message' => 'Clientes obtenidos correctamente',
                        'clientes' => $clientes,
                        'total' => count($clientes)
                    ]);            
                } else {
                    $resultado = $clienteRepository->getPaginated($page, $limit);
                    
                    return JsonResponse::success($response, [
                        'message' => 'Clientes obtenidos correctamente',
                        'clientes' => $resultado['items'],
                        'pagination' => $resultado['pagination']
                    ]);
                }
            }, $response, 'Error al obtener los clientes'
        );
    });

    $group->post('', function (Request $request, Response $response) use ($container, $validator) {
        return ControllerHelper::handleRequest(
            function() use ($request, $response, $container, $validator) {
                // Obtener los datos del cuerpo de la petición
                $data = $request->getParsedBody();
                
                // Validar los datos recibidos
                $validator = $validator($data);
                
                $validator->required('cedula', 'La cédula es obligatoria')
                         ->required('nombre', 'El nombre es obligatorio')
                         ->required('apellidos', 'Los apellidos son obligatorios')
                         ->required('email', 'El email es obligatorio')
                         ->email('email', 'El formato del email es inválido')
                         ->unique('email', 'clientes', 'email', null, 'id_cliente', 'Este email ya está registrado')
                         ->unique('cedula', 'clientes', 'cedula', null, 'id_cliente', 'Esta cédula ya está registrada')
                         ->max('nombre', 100, 'El nombre no debe exceder los 100 caracteres')
                         ->max('apellidos', 100, 'Los apellidos no deben exceder los 100 caracteres')
                         ->max('email', 150, 'El email no debe exceder los 150 caracteres');
                
                // Validar el teléfono si está presente
                if (isset($data['telefono']) && !empty($data['telefono'])) {
                    $validator->max('telefono', 20, 'El teléfono no debe exceder los 20 caracteres');
                }
                
                // Validar dirección si está presente
                if (isset($data['direccion']) && !empty($data['direccion'])) {
                    $validator->max('direccion', 200, 'La dirección no debe exceder los 200 caracteres');
                }
                
                // Validar barrio si está presente
                if (isset($data['barrio']) && !empty($data['barrio'])) {
                    $validator->max('barrio', 100, 'El barrio no debe exceder los 100 caracteres');
                }
                
                // Si la validación falla, retornar errores
                if ($validator->fails()) {
                    return JsonResponse::error($response,
                        'Error de validación',
                        ['errors' => $validator->errors()],
                        422
                    );
                }
                
                // Preparar datos para inserción, añadiendo campos faltantes
                $clienteData = [
                    'cedula' => $data['cedula'],
                    'nombre' => $data['nombre'],
                    'apellidos' => $data['apellidos'],
                    'email' => $data['email'],
                    'telefono' => $data['telefono'] ?? null,
                    'direccion' => $data['direccion'] ?? null,
                    'barrio' => $data['barrio'] ?? null,
                    'estado' => $data['estado'] ?? 'activo', // Estado por defecto
                    'created_at' => DateHelper::now(),
                    'updated_at' => DateHelper::now()
                ];
                
                // Obtener el repositorio de clientes
                $clienteRepository = $container->get('clienteRepository');
                
                // Insertar el cliente
                $id_cliente = $clienteRepository->create($clienteData);
                
                // Obtener el cliente recién creado
                $cliente = $clienteRepository->getById($id_cliente);
                
                // Retornar respuesta exitosa
                return JsonResponse::success($response, [
                        'message' => 'Cliente creado correctamente',
                        'cliente' => $cliente
                    ], 201
                );
            }, $response, 'Error al crear el cliente'
        );
    });

    $group->get('/{id}', function (Request $request, Response $response, array $args) use ($container) {
        return ControllerHelper::handleRequest(
            function() use ($request, $response, $container, $args) {
                // Obtener el ID del cliente
                $id = (int)$args['id'];
                
                // Obtener el repositorio de clientes
                $clienteRepository = $container->get('clienteRepository');
                
                // Buscar el cliente por ID
                $cliente = $clienteRepository->getById($id);
                
                // Verificar si el cliente existe
                if (!$cliente) {
                    return JsonResponse::error($response,
                        'Cliente no encontrado',
                        ['id' => $id],
                        404
                    );
                }
                
                // Retornar el cliente
                return JsonResponse::success($response, [
                    'message' => 'Cliente obtenido correctamente',
                    'cliente' => $cliente
                ]);
            }, $response, 'Error al obtener el cliente'
        );
    });

    $group->get('/search', function (Request $request, Response $response) use ($container) {
        try {
            // $params = $request->getQueryParams();
            $pagination = PaginationHelper::getParams($request);
            $page = $pagination['page'];
            $limit = $pagination['limit'];
            $all = $pagination['all'];
            $exactMatch = $pagination['exact'];
            
            // Criterios de búsqueda
            $criteria = [];
            
            // Agregar criterios de búsqueda si están presentes
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
            
            if (isset($params['barrio'])) {
                $criteria['barrio'] = $params['barrio'];
            }
            
            if (isset($params['direccion'])) {
                $criteria['direccion'] = $params['direccion'];
            }
            
            $clienteRepository = $container->get('clienteRepository');
            
            // Si no hay criterios de búsqueda, devolver un error
            if (empty($criteria)) {
                return JsonResponse::error(
                    $response,
                    'Debe proporcionar al menos un criterio de búsqueda',
                    [],
                    400
                );
            }
            
            $resultado = $clienteRepository->searchClientes($criteria, $page, $limit, $exactMatch);
            
            return JsonResponse::success($response, [
                'message' => 'Búsqueda realizada correctamente',
                'clientes' => $resultado['items'],
                'pagination' => $resultado['pagination']
            ]);
        } catch (\Exception $e) {
            return JsonResponse::error(
                $response,
                'Error al buscar clientes',
                ['error' => $e->getMessage()],
                500
            );
        }
    });

    $group->put('/{id}', function (Request $request, Response $response, array $args) use ($container, $validator) {
        return ControllerHelper::handleRequest(
            function() use ($request, $response, $container, $validator, $args) {
                // Obtener el ID del cliente de los parámetros de la ruta
                $id = (int)$args['id'];
                
                // Obtener los datos del cuerpo de la petición
                $data = $request->getParsedBody();
                
                // Obtener el repositorio de clientes
                $clienteRepository = $container->get('clienteRepository');
                
                // Verificar si el cliente existe
                $clienteExistente = $clienteRepository->getById($id);
                if (!$clienteExistente) {
                    return JsonResponse::error($response,
                        'Cliente no encontrado',
                        ['id' => $id],
                        404
                    );
                }
                
                // Validar los datos recibidos
                $validator = $validator($data);
                
                // Validaciones para los campos que se pueden actualizar
                if (isset($data['cedula'])) {
                    $validator->required('cedula', 'La cédula es obligatoria')
                             ->unique('cedula', 'clientes', 'cedula', $data['cedula'], 'id_cliente', $id, 'Esta cédula ya está registrada');
                }
                
                if (isset($data['nombre'])) {
                    $validator->required('nombre', 'El nombre es obligatorio')
                             ->max('nombre', 100, 'El nombre no debe exceder los 100 caracteres');
                }
                
                if (isset($data['apellidos'])) {
                    $validator->required('apellidos', 'Los apellidos son obligatorios')
                             ->max('apellidos', 100, 'Los apellidos no deben exceder los 100 caracteres');
                }
                
                if (isset($data['email'])) {
                    $validator->required('email', 'El email es obligatorio')
                             ->email('email', 'El formato del email es inválido')
                             ->max('email', 150, 'El email no debe exceder los 150 caracteres')
                             ->unique('email', 'clientes', 'email', $data['email'], 'id_cliente', $id, 'Este email ya está registrado');
                }
                
                // Validar el teléfono si está presente
                if (isset($data['telefono']) && !empty($data['telefono'])) {
                    $validator->max('telefono', 20, 'El teléfono no debe exceder los 20 caracteres');
                }
                
                // Validar dirección si está presente
                if (isset($data['direccion']) && !empty($data['direccion'])) {
                    $validator->max('direccion', 200, 'La dirección no debe exceder los 200 caracteres');
                }
                
                // Validar barrio si está presente
                if (isset($data['barrio']) && !empty($data['barrio'])) {
                    $validator->max('barrio', 100, 'El barrio no debe exceder los 100 caracteres');
                }
                
                // Validar estado si está presente
                if (isset($data['estado'])) {
                    $validator->in('estado', ['activo', 'inactivo'], 'El estado debe ser activo o inactivo');
                }
                
                // Si la validación falla, retornar errores
                if ($validator->fails()) {
                    return JsonResponse::error($response,
                        'Error de validación',
                        ['errors' => $validator->errors()],
                        422
                    );
                }
                
                // Preparar datos para actualización
                $clienteData = array_filter([
                    'cedula' => $data['cedula'] ?? null,
                    'nombre' => $data['nombre'] ?? null,
                    'apellidos' => $data['apellidos'] ?? null,
                    'email' => $data['email'] ?? null,
                    'telefono' => $data['telefono'] ?? null,
                    'direccion' => $data['direccion'] ?? null,
                    'barrio' => $data['barrio'] ?? null,
                    'estado' => $data['estado'] ?? null,
                    'updated_at' => DateHelper::now()
                ], function($value) {
                    return $value !== null;
                });
                
                // Actualizar el cliente
                $rowCount = $clienteRepository->update($id, $clienteData);
                
                // Obtener el cliente actualizado
                $cliente = $clienteRepository->getById($id);
                
                // Retornar respuesta exitosa
                return JsonResponse::success($response, [
                        'message' => 'Cliente actualizado correctamente',
                        'cliente' => $cliente,
                        'rows_affected' => $rowCount
                    ]
                );
            }, $response, 'Error al actualizar el cliente'
        );
    });

    $group->patch('/{id}/estado', function (Request $request, Response $response, array $args) use ($container, $validator) {
        return ControllerHelper::handleRequest(
            function() use ($request, $response, $container, $validator, $args) {
                // Obtener el ID del cliente de los parámetros de la ruta
                $id = (int)$args['id'];
                
                // Obtener los datos del cuerpo de la petición
                $data = $request->getParsedBody();
                
                // Validar que el estado esté presente
                if (!isset($data['estado'])) {
                    return JsonResponse::error($response,
                        'El estado es obligatorio',
                        [],
                        422
                    );
                }
                
                // Validar el valor del estado
                $validator = $validator($data);
                $validator->in('estado', ['activo', 'inactivo'], 'El estado debe ser activo o inactivo');
                
                // Si la validación falla, retornar errores
                if ($validator->fails()) {
                    return JsonResponse::error($response,
                        'Error de validación',
                        ['errors' => $validator->errors()],
                        422
                    );
                }
                
                // Obtener el repositorio de clientes
                $clienteRepository = $container->get('clienteRepository');
                
                // Verificar si el cliente existe
                $clienteExistente = $clienteRepository->getById($id);
                if (!$clienteExistente) {
                    return JsonResponse::error($response,
                        'Cliente no encontrado',
                        ['id' => $id],
                        404
                    );
                }
                
                // Actualizar solo el estado del cliente
                $rowCount = $clienteRepository->updateStatus($id, $data['estado']);
                
                // Obtener el cliente actualizado
                $cliente = $clienteRepository->getById($id);
                
                // Retornar respuesta exitosa
                return JsonResponse::success($response, [
                        'message' => 'Estado del cliente actualizado correctamente',
                        'cliente' => $cliente,
                        'rows_affected' => $rowCount
                    ]
                );
            }, $response, 'Error al actualizar el estado del cliente'
        );
    });
});