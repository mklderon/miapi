<?php

namespace App\Helpers;

class PaginationHelper {
    public static function getParams($request, $defaultLimit = 10) {
        $params = $request->getQueryParams();
        return [
            'page' => isset($params['page']) ? (int)$params['page'] : 1,
            'limit' => isset($params['limit']) ? (int)$params['limit'] : $defaultLimit,
            'all' => isset($params['all']) && $params['all'] === 'true',            
            'exact' => isset($params['exact']) && $params['exact'] === 'true',
        ];
    }
}