<?php

namespace App\Repositories;

use App\Helpers\DbHelper;

class UsuarioRepository
{
    protected $dbHelper;
    protected $defaultColumns;

    public function __construct(DbHelper $dbHelper)
    {
        $this->dbHelper = $dbHelper;
        $this->defaultColumns = [
            'id_usuario',
            'cedula',
            'nombre',
            'apellidos',
            'telefono',
            'email',
            'rol',
            'estado',
            'permiso',
            'created_at',
            'updated_at'
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
        return $medoo->select('usuarios', $columns, [
            'ORDER' => ['id_usuario' => 'DESC']
        ]);
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

        $items = $medoo->select('usuarios', $this->defaultColumns, [
            'LIMIT' => [$offset, $limit],
            'ORDER' => ['id_usuario' => 'DESC']
        ]);

        $total = $medoo->count('usuarios');

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
     * Buscar un usuario por su email
     *
     * @param string $email Email del usuario
     * @return array|null Datos del usuario o null si no existe
     */
    public function findByEmail(string $email): ?array
    {
        // Incluimos la columna 'password' para verificar credenciales
        $columns = array_merge($this->defaultColumns, ['password']);

        return $this->dbHelper->find('usuarios', $columns, [
            'email' => $email
        ]);
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

        return $this->dbHelper->find('usuarios', $columns, [
            'id_usuario' => $id
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
     * Actualiza un usuario por su ID
     *
     * @param int $id ID del usuario a actualizar
     * @param array $data Datos a actualizar
     * @return bool True si se actualizó correctamente, false en caso contrario
     */
    public function update(int $id, array $data): bool
    {
        // Asegurarse de que no estamos intentando actualizar el ID
        if (isset($data['id_usuario'])) {
            unset($data['id_usuario']);
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
        return $this->dbHelper->update('usuarios', $data, [
            'id_usuario' => $id
        ]);
    }
}
