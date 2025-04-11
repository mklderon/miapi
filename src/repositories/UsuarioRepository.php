<?php

namespace App\Repositories;

use App\Helpers\DbHelper;

class UsuarioRepository
{
    protected $dbHelper;

    public function __construct(DbHelper $dbHelper)
    {
        $this->dbHelper = $dbHelper;
    }

    /**
     * Buscar un usuario por su email
     * 
     * @param string $email Email del usuario
     * @return array|null Datos del usuario o null si no existe
     */
    public function findByEmail(string $email): ?array
    {
        return $this->dbHelper->find('usuarios', [
            'id_usuario', 
            'cedula',
            'nombre',
            'apellidos',
            'telefono',
            'email', 
            'rol',
            'estado',
            'permiso',        
            'password',
            'created_at',
            'updated_at'
        ], [
            'email' => $email
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
}