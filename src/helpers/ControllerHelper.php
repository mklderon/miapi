<?php

namespace App\Helpers;

class ControllerHelper {
    public static function handleRequest($callback, $response, $errorMessage) {
        try {
            return $callback();
        } catch (\Exception $e) {
            return JsonResponse::error(
                $response,
                $errorMessage,
                ['error' => $e->getMessage()],
                500
            );
        }
    }
}