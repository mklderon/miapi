<?php
// Archivo: src/Helpers/DbHelper.php

namespace App\Helpers;

use Medoo\Medoo;

/**
 * Clase auxiliar para trabajar con la base de datos mediante Medoo
 */
class DbHelper
{
    /**
     * Instancia de Medoo
     *
     * @var Medoo
     */
    private $db;
    
    /**
     * Constructor de la clase
     *
     * @param Medoo $db Instancia de Medoo
     */
    public function __construct(Medoo $db)
    {
        $this->db = $db;
    }
    
    /**
     * Obtiene registros con paginación
     *
     * @param string $table Nombre de la tabla
     * @param array $columns Columnas a seleccionar
     * @param array $where Condiciones de la consulta
     * @param int $page Número de página
     * @param int $limit Límite de registros por página
     * @param array $orderBy Orden de los resultados
     * @return array Registros y datos de paginación
     */
    public function getPaginated(
        string $table, 
        array $columns = ['*'], 
        array $where = [], 
        int $page = 1, 
        int $limit = 10, 
        array $orderBy = ['id' => 'DESC']
    ): array {
        $offset = ($page - 1) * $limit;
        
        $items = $this->db->select($table, $columns, [
            'AND' => $where,
            'LIMIT' => [$offset, $limit],
            'ORDER' => $orderBy
        ]);
        
        $total = $this->db->count($table, ['AND' => $where]);
        
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
     * Crea un nuevo registro
     *
     * @param string $table Nombre de la tabla
     * @param array $data Datos a insertar
     * @return int|false ID del registro insertado o false si falla
     */
    public function create(string $table, array $data)
    {
        $this->db->insert($table, $data);
        return $this->db->id();
    }
    
    /**
     * Actualiza un registro
     *
     * @param string $table Nombre de la tabla
     * @param array $data Datos a actualizar
     * @param array $where Condiciones para el registro a actualizar
     * @return bool True si se actualizó correctamente
     */
    public function update(string $table, array $data, array $where): bool
    {
        $result = $this->db->update($table, $data, $where);
        return $result->rowCount() > 0;
    }
    
    /**
     * Elimina un registro
     *
     * @param string $table Nombre de la tabla
     * @param array $where Condiciones para el registro a eliminar
     * @return bool True si se eliminó correctamente
     */
    public function delete(string $table, array $where): bool
    {
        $result = $this->db->delete($table, $where);
        return $result->rowCount() > 0;
    }
    
    /**
     * Busca un registro
     *
     * @param string $table Nombre de la tabla
     * @param array $columns Columnas a seleccionar
     * @param array $where Condiciones de la consulta
     * @return array|null Registro encontrado o null
     */
    public function find(string $table, array $columns, array $where): ?array
    {
        return $this->db->get($table, $columns, $where) ?: null;
    }
    
    /**
     * Verifica si existe un registro
     *
     * @param string $table Nombre de la tabla
     * @param array $where Condiciones de la consulta
     * @return bool True si existe, false en caso contrario
     */
    public function exists(string $table, array $where): bool
    {
        return $this->db->has($table, $where);
    }
    
    /**
     * Ejecuta una transacción
     *
     * @param callable $callback Función a ejecutar dentro de la transacción
     * @return mixed Resultado de la transacción
     * @throws \Exception Si ocurre un error durante la transacción
     */
    public function transaction(callable $callback)
    {
        try {
            $this->db->pdo->beginTransaction();
            $result = $callback($this->db);
            $this->db->pdo->commit();
            return $result;
        } catch (\Exception $e) {
            $this->db->pdo->rollBack();
            throw $e;
        }
    }
    
    /**
     * Obtiene la instancia de Medoo
     *
     * @return Medoo Instancia de Medoo
     */
    public function getMedoo(): Medoo
    {
        return $this->db;
    }
}