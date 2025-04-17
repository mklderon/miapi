<?php

namespace App\Repositories;

use App\Helpers\DateHelper;
use App\Helpers\DbHelper;

class clienteRepository
{
    protected $dbHelper;
    protected $table = 'clientes';
    protected $primaryKey = 'id_cliente';
    protected $defaultColumns = ['cedula', 'nombre', 'apellidos', 'email', 'telefono', 'direccion', 'barrio', 'estado', 'created_at', 'updated_at'];

    public function __construct(DbHelper $dbHelper)
    {
        $this->dbHelper = $dbHelper;
    }

    public function getPaginated(int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        
        $medoo = $this->dbHelper->getMedoo();

        $items = $medoo->select($this->table, $this->defaultColumns, [
            'LIMIT' => [$offset, $limit],
            'ORDER' => [$this->primaryKey => 'ASC']
        ]);

        $total = $medoo->count($this->table);

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

    public function getAll(array $columns = null): array
    {
        if ($columns === null) {
            $columns = $this->defaultColumns;
        }

        $medoo = $this->dbHelper->getMedoo();
        return $medoo->select($this->table, $columns, [
            'ORDER' => [$this->primaryKey => 'ASC']
        ]);
    }
    
    public function searchClientes(array $criteria = [], int $page = 1, int $limit = 10, bool $exactMatch = false, array $columns = null): array
    {
        if ($columns === null) {
            $columns = $this->defaultColumns;
        }
        
        $medoo = $this->dbHelper->getMedoo();
        $where = [];
        
        if (!empty($criteria)) {
            if (isset($criteria[$this->primaryKey])) {
                $where[$this->primaryKey] = $criteria[$this->primaryKey];
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
        
        $offset = ($page - 1) * $limit;
        
        $where['ORDER'] = [$this->primaryKey => 'ASC'];
        $where['LIMIT'] = [$offset, $limit];
        
        $items = $medoo->select($this->table, $columns, $where);
        
        $whereCount = $where;
        unset($whereCount['LIMIT'], $whereCount['ORDER']);
        $total = $medoo->count($this->table, $whereCount);
        
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
    
    public function create(array $data): int
    {
        $medoo = $this->dbHelper->getMedoo();
        
        $medoo->insert($this->table, $data);
        
        // Retornar el ID del cliente insertado
        return $medoo->id();
    }
    
    public function getById(int $id, array $columns = null): ?array
    {
        if ($columns === null) {
            $columns = $this->defaultColumns;
        }
        
        $medoo = $this->dbHelper->getMedoo();
        
        $cliente = $medoo->get($this->table, $columns, [
            $this->primaryKey => $id
        ]);
        
        return $cliente ?: null;
    }
    
    public function update(int $id, array $data): int
    {        
        $data['updated_at'] = DateHelper::now();
        
        $medoo = $this->dbHelper->getMedoo();
        
        $result = $medoo->update($this->table, $data, [
            $this->primaryKey => $id
        ]);
        
        return $result->rowCount();
    }
    
    public function updateStatus(int $id, string $estado): int
    {
        return $this->update($id, [
            'estado' => $estado
        ]);
    }
}