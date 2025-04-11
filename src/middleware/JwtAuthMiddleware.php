<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use App\Helpers\JwtHelper;
use App\Helpers\JsonResponse;

class JwtAuthMiddleware implements MiddlewareInterface
{
    /**
     * Helper de JWT
     *
     * @var JwtHelper
     */
    private $jwtHelper;
    
    /**
     * Constructor
     *
     * @param JwtHelper $jwtHelper
     */
    public function __construct(JwtHelper $jwtHelper)
    {
        $this->jwtHelper = $jwtHelper;
    }
    
    /**
     * Process an incoming server request
     *
     * @param Request $request
     * @param RequestHandler $handler
     * @return Response
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        // Obtener la cabecera de autorización
        $authHeader = $request->getHeaderLine('Authorization');
        
        // Extraer el token
        $token = $this->jwtHelper->extractTokenFromHeader($authHeader);
        
        if ($token === null) {
            // Token no proporcionado
            $response = new \Slim\Psr7\Response();
            return JsonResponse::error($response, 'Token de autenticación no proporcionado', [], 401);
        }
        
        // Validar el token
        $payload = $this->jwtHelper->validateToken($token);
        
        if ($payload === null) {
            // Token inválido
            $response = new \Slim\Psr7\Response();
            return JsonResponse::error($response, 'Token de autenticación inválido o expirado', [], 401);
        }
        
        // Agregar los datos del usuario al request para que estén disponibles en la ruta
        $request = $request->withAttribute('jwt_payload', $payload);
        $request = $request->withAttribute('user', $payload['data']);
        
        // Continuar con la siguiente capa de middleware o controlador
        return $handler->handle($request);
    }
}