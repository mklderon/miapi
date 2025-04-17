<?php

namespace App\Repositories;

use App\Helpers\DateHelper;
use App\Helpers\DbHelper;

class clienteRepository
{
    protected $dbHelper;
    protected $defaultColumns = ['id_cliente', 'cedula', 'nombre', 'apellidos', 'email', 'telefono', 'direccion', 'barrio', 'estado', 'created_at', 'updated_at'];

    public function __construct(DbHelper $dbHelper)
    {
        $this->dbHelper = $dbHelper;
    }

    public function getAll(array $columns = null): array
    {
        if ($columns === null) {
            $columns = $this->defaultColumns;
        }

        $medoo = $this->dbHelper->getMedoo();
        return $medoo->select('clientes', $columns, [
            'ORDER' => ['id_cliente' => 'DESC']
        ]);
    }

    public function getPaginated(int $page = 1, int $limit = 10): array
    {
        // Calcular el offset para la paginación
        $offset = ($page - 1) * $limit;

        // Usar directamente Medoo para la paginación
        $medoo = $this->dbHelper->getMedoo();

        $items = $medoo->select('clientes', $this->defaultColumns, [
            'LIMIT' => [$offset, $limit],
            'ORDER' => ['id_cliente' => 'DESC']
        ]);

        $total = $medoo->count('clientes');

        return [
            'items' => $items,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ]
        ];
    }
    
    /**
     * Busca clientes según los criterios proporcionados utilizando Medoo
     * 
     * @param array $criteria Criterios de búsqueda (id_cliente, nombre, apellidos, cedula, telefono, email, estado)
     * @param int $page Número de página
     * @param int $limit Límite de resultados por página
     * @param bool $exactMatch Si es true, busca coincidencias exactas; si es false, busca coincidencias parciales
     * @param array $columns Columnas a seleccionar (por defecto usa las columnas predefinidas)
     * @return array Resultados paginados
     */
    public function searchClientes(array $criteria = [], int $page = 1, int $limit = 10, bool $exactMatch = false, array $columns = null): array
    {
        if ($columns === null) {
            $columns = $this->defaultColumns;
        }
        
        $medoo = $this->dbHelper->getMedoo();
        $where = [];
        
        // Construir condiciones de búsqueda para Medoo
        if (!empty($criteria)) {
            if (isset($criteria['id_cliente'])) {
                $where['id_cliente'] = $criteria['id_cliente'];
            }
            
            if (isset($criteria['cedula'])) {
                $where['cedula'] = $criteria['cedula'];
            }
            
            if (isset($criteria['nombre'])) {
                if ($exactMatch) {
                    $where['nombre'] = $criteria['nombre'];
                } else {
                    $where['nombre[~]'] = $criteria['nombre'];
                }
            }
            
            if (isset($criteria['apellidos'])) {
                if ($exactMatch) {
                    $where['apellidos'] = $criteria['apellidos'];
                } else {
                    $where['apellidos[~]'] = $criteria['apellidos'];
                }
            }
            
            // Búsqueda por nombre completo
            if (isset($criteria['nombre_completo'])) {
                if ($exactMatch) {
                    // Para búsqueda exacta, verificamos si coincide exactamente con nombre o apellidos
                    $where['OR'] = [
                        'nombre' => $criteria['nombre_completo'],
                        'apellidos' => $criteria['nombre_completo']
                    ];
                } else {
                    // Para coincidencia parcial, buscamos en ambos campos
                    $where['OR'] = [
                        'nombre[~]' => $criteria['nombre_completo'],
                        'apellidos[~]' => $criteria['nombre_completo']
                    ];
                }
            }
            
            if (isset($criteria['telefono'])) {
                $where['telefono'] = $criteria['telefono'];
            }
            
            if (isset($criteria['email'])) {
                if ($exactMatch) {
                    $where['email'] = $criteria['email'];
                } else {
                    $where['email[~]'] = $criteria['email'];
                }
            }
            
            if (isset($criteria['estado'])) {
                $where['estado'] = $criteria['estado'];
            }
            
            if (isset($criteria['barrio'])) {
                if ($exactMatch) {
                    $where['barrio'] = $criteria['barrio'];
                } else {
                    $where['barrio[~]'] = $criteria['barrio'];
                }
            }
            
            if (isset($criteria['direccion'])) {
                if ($exactMatch) {
                    $where['direccion'] = $criteria['direccion'];
                } else {
                    $where['direccion[~]'] = $criteria['direccion'];
                }
            }
        }
        
        // Calcular el offset para la paginación
        $offset = ($page - 1) * $limit;
        
        // Agregar ordenamiento y paginación a las condiciones
        $where['ORDER'] = ['id_cliente' => 'DESC'];
        $where['LIMIT'] = [$offset, $limit];
        
        // Ejecutar la consulta con Medoo
        $items = $medoo->select('clientes', $columns, $where);
        
        // Contar el total de registros para la paginación
        // Eliminamos LIMIT y ORDER para el conteo
        $whereCount = $where;
        unset($whereCount['LIMIT'], $whereCount['ORDER']);
        $total = $medoo->count('clientes', $whereCount);
        
        // Calcular información de paginación
        $totalPages = ceil($total / $limit);
        
        return [
            'items' => $items,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => $totalPages,
                'from' => $total > 0 ? ($page - 1) * $limit + 1 : 0,
                'to' => min($page * $limit, $total)
            ]
        ];
    }

    
    /**
     * Crea un nuevo cliente
     *
     * @param array $data Datos del cliente
     * @return int ID del cliente creado
     */
    public function create(array $data): int
    {
        $medoo = $this->dbHelper->getMedoo();
        
        // Insertar el cliente
        $medoo->insert('clientes', $data);
        
        // Retornar el ID del cliente insertado
        return $medoo->id();
    }
    
    /**
     * Obtiene un cliente por su ID
     *
     * @param int $id ID del cliente
     * @param array $columns Columnas a seleccionar
     * @return array|null Datos del cliente o null si no existe
     */
    public function getById(int $id, array $columns = null): ?array
    {
        if ($columns === null) {
            $columns = $this->defaultColumns;
        }
        
        $medoo = $this->dbHelper->getMedoo();
        
        $cliente = $medoo->get('clientes', $columns, [
            'id_cliente' => $id
        ]);
        
        return $cliente ?: null;
    }
    
    /**
     * Actualiza un cliente
     *
     * @param int $id ID del cliente
     * @param array $data Datos a actualizar
     * @return int Número de filas afectadas
     */
    public function update(int $id, array $data): int
    {
        // Asegurarse de actualizar la fecha de modificación
        $data['updated_at'] = DateHelper::now();
        
        $medoo = $this->dbHelper->getMedoo();
        
        $result = $medoo->update('clientes', $data, [
            'id_cliente' => $id
        ]);
        
        return $result->rowCount();
    }
    
    /**
     * Elimina un cliente
     *
     * @param int $id ID del cliente
     * @return int Número de filas afectadas
     */
    public function delete(int $id): int
    {
        $medoo = $this->dbHelper->getMedoo();
        
        $result = $medoo->delete('clientes', [
            'id_cliente' => $id
        ]);
        
        return $result->rowCount();
    }
    
    /**
     * Actualiza el estado de un cliente
     *
     * @param int $id ID del cliente
     * @param string $estado Nuevo estado ('activo' o 'inactivo')
     * @return int Número de filas afectadas
     */
    public function updateStatus(int $id, string $estado): int
    {
        return $this->update($id, [
            'estado' => $estado
        ]);
    }
}