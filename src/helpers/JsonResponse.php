<?php
// Archivo: src/helpers/JsonResponse.php

namespace App\Helpers;

use Psr\Http\Message\ResponseInterface as Response;

class JsonResponse
{
    /**
     * Genera una respuesta JSON de éxito
     *
     * @param Response $response El objeto Response de Slim
     * @param array $data Los datos a devolver
     * @param int $status Código de estado HTTP
     * @return Response
     */
    public static function success(Response $response, array $data = [], int $status = 200): Response
    {
        $payload = [
            'status' => 'success',
            'data' => $data
        ];

        return self::respond($response, $payload, $status);
    }

    /**
     * Genera una respuesta JSON de error
     *
     * @param Response $response El objeto Response de Slim
     * @param string $message Mensaje de error
     * @param array $details Detalles adicionales del error
     * @param int $status Código de estado HTTP
     * @return Response
     */
    public static function error(Response $response, string $message, array $details = [], int $status = 400): Response
    {
        $payload = [
            'status' => 'error',
            'message' => $message
        ];

        if (!empty($details)) {
            $payload['details'] = $details;
        }

        return self::respond($response, $payload, $status);
    }

    /**
     * Método interno para generar la respuesta
     *
     * @param Response $response El objeto Response de Slim
     * @param array $payload Los datos a enviar
     * @param int $status Código de estado HTTP
     * @return Response
     */
    private static function respond(Response $response, array $payload, int $status): Response
    {
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $response->getBody()->write($json);

        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus($status);
    }
}
