<?php
// Archivo: src/Middleware/CorsMiddleware.php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class CorsMiddleware implements MiddlewareInterface
{
    /**
     * Configuración de CORS
     *
     * @var array
     */
    private $config;
    
    /**
     * Constructor
     *
     * @param array $config Configuración de CORS
     */
    public function __construct(array $config)
    {
        $this->config = $config;
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
        $response = $handler->handle($request);
        
        // Origen permitido
        $origin = $request->getHeaderLine('Origin');
        
        if (!empty($origin)) {
            if ($this->config['allowed_origins'] === '*') {
                $response = $response->withHeader('Access-Control-Allow-Origin', '*');
            } elseif (in_array($origin, $this->config['allowed_origins'])) {
                $response = $response->withHeader('Access-Control-Allow-Origin', $origin);
            }
        }
        
        // Métodos permitidos
        $response = $response->withHeader('Access-Control-Allow-Methods', 
            implode(', ', $this->config['allowed_methods']));
        
        // Cabeceras permitidas
        $response = $response->withHeader('Access-Control-Allow-Headers', 
            implode(', ', $this->config['allowed_headers']));
        
        // Cabeceras expuestas
        if (!empty($this->config['exposed_headers'])) {
            $response = $response->withHeader('Access-Control-Expose-Headers', 
                implode(', ', $this->config['exposed_headers']));
        }
        
        // Tiempo máximo de caché
        $response = $response->withHeader('Access-Control-Max-Age', 
            (string)$this->config['max_age']);
        
        // Permitir credenciales
        if ($this->config['allow_credentials']) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }
        
        return $response;
    }
}