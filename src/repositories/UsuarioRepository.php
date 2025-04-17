<?php

namespace App\Repositories;

use App\Helpers\DbHelper;

class UsuarioRepository
{
    protected $dbHelper;
    protected $table = 'usuarios';
    protected $primaryKey = 'id_usuario';
    protected $defaultColumns = ['cedula','nombre','apellidos','telefono','email','rol','estado','permiso','created_at','updated_at'];

    public function __construct(DbHelper $dbHelper)
    {
        $this->dbHelper = $dbHelper;
    }

    /**
     * Obtener usuarios con paginación
     *
     * @param int $page Número de página
     * @param int $limit Límite de registros por página
     * @return array Usuarios paginados
     */
    public function getPaginated(int $page = 1, int $limit = 10): array
    {
        // Calcular el offset para la paginación
        $offset = ($page - 1) * $limit;

        // Usar directamente Medoo para la paginación
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

    /**
     * Obtener todos los usuarios
     *
     * @param array $columns Columnas a seleccionar (por defecto todas excepto password)
     * @return array Lista de usuarios
     */
    public function getAll(array $columns = null): array
    {
        // Si no se especifican columnas, usamos las columnas por defecto
        if ($columns === null) {
            $columns = $this->defaultColumns;
        }

        // Usar directamente Medoo para evitar problemas con el order by
        $medoo = $this->dbHelper->getMedoo();
        return $medoo->select($this->table, $columns, [
            'ORDER' => [$this->primaryKey => 'ASC']
        ]);
    }

    /**
     * Buscar un usuario por su email
     *
     * @param string $email Email del usuario
     * @return array|null Datos del usuario o null si no existe
     */
    public function findByEmail(string $email): ?array
    {
        // Incluimos la columna 'password' para verificar credenciales
        $columns = array_merge($this->defaultColumns, ['password']);

        return $this->dbHelper->find($this->table, $columns, [
            'email' => $email
        ]);
    }
    
    public function searchUsuario(array $criteria = [], int $page = 1, int $limit = 10, bool $exactMatch = false, array $columns = null): array
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

    /**
     * Buscar un usuario por su ID
     *
     * @param int $id ID del usuario
     * @param array $columns Columnas a seleccionar (por defecto todas excepto password)
     * @return array|null Datos del usuario o null si no existe
     */
    public function findById(int $id, array $columns = null): ?array
    {
        // Si no se especifican columnas, usamos las columnas por defecto
        if ($columns === null) {
            $columns = $this->defaultColumns;
        }

        return $this->dbHelper->find($this->table, $columns, [
            $this->primaryKey => $id
        ]);
    }

    /**
     * Verificar credenciales del usuario
     *
     * @param string $email Email del usuario
     * @param string $password Contraseña sin encriptar
     * @return array|null Datos del usuario sin contraseña o null si las credenciales son inválidas
     */
    public function verificarCredenciales(string $email, string $password): ?array
    {
        $usuario = $this->findByEmail($email);

        if (!$usuario || !password_verify($password, $usuario['password'])) {
            return null;
        }

        // Remover la contraseña de los datos
        unset($usuario['password']);

        return $usuario;
    }

    /**
     * Crear un nuevo usuario
     *
     * @param array $data Datos del usuario a crear
     * @return int|bool ID del usuario creado o false si hubo un error
     */
    public function create(array $data): int|bool
    {
        // Verificar si hay una contraseña para encriptarla
        if (isset($data['password']) && !empty($data['password'])) {
            // Encriptamos la contraseña
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        // Agregar timestamps
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        // Insertar el nuevo usuario
        $result = $this->dbHelper->insert($this->table, $data);
        
        // Si la inserción fue exitosa, devolvemos el ID del usuario creado
        if ($result) {
            return $this->dbHelper->getMedoo()->id();
        }
        
        return false;
    }

    /**
     * Actualiza un usuario por su ID
     *
     * @param int $id ID del usuario a actualizar
     * @param array $data Datos a actualizar
     * @return bool True si se actualizó correctamente, false en caso contrario
     */
    public function update(int $id, array $data): bool
    {
        // Asegurarse de que no estamos intentando actualizar el ID
        if (isset($data[$this->primaryKey])) {
            unset($data[$this->primaryKey]);
        }

        // Verificar si hay un cambio de contraseña
        if (isset($data['password']) && !empty($data['password'])) {
            // Encriptamos la contraseña
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } elseif (isset($data['password']) && empty($data['password'])) {
            // Si se envió un campo password vacío, lo eliminamos para no actualizar la contraseña
            unset($data['password']);
        }

        // Actualizar el usuario
        return $this->dbHelper->update($this->table, $data, [
            $this->primaryKey => $id
        ]);
    }
    
    /**
     * Actualiza el estado de un usuario
     *
     * @param int $id ID del usuario
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
