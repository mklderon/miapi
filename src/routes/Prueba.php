<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

$app->get('/prueba', function (Request $request, Response $response) {
    $response->getBody()->write("Prueba de ruta realizada con exito!");
    return $response;
});