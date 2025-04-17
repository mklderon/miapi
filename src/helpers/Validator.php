<?php

namespace App\Helpers;

use App\Helpers\DbHelper;

/**
 * Clase para validación de datos
 */
class Validator
{
    /**
     * Errores de validación
     *
     * @var array
     */
    private $errors = [];
    
    /**
     * Datos a validar
     *
     * @var array
     */
    private $data = [];
    
    /**
     * Instancia de DbHelper para consultas a la base de datos
     *
     * @var DbHelper
     */
    private $db;
    
    /**
     * Constructor
     *
     * @param array $data Datos a validar
     * @param DbHelper $dbHelper Instancia del helper de base de datos
     */
    public function __construct(array $data, DbHelper $dbHelper = null)
    {
        $this->data = $data;
        $this->db = $dbHelper;
    }
    
    /**
     * Valida que un campo esté presente y no esté vacío
     *
     * @param string $field Campo a validar
     * @param string $message Mensaje de error personalizado
     * @return Validator Instancia actual para encadenamiento
     */
    public function required(string $field, string $message = null): Validator
    {
        if (!isset($this->data[$field]) || empty($this->data[$field])) {
            $this->errors[$field] = $message ?? "El campo '{$field}' es requerido";
        }
        
        return $this;
    }
    
    /**
     * Valida que un campo sea un email válido
     *
     * @param string $field Campo a validar
     * @param string $message Mensaje de error personalizado
     * @return Validator Instancia actual para encadenamiento
     */
    public function email(string $field, string $message = null): Validator
    {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field] = $message ?? "El campo '{$field}' debe ser un email válido";
            }
        }
        
        return $this;
    }
    
    /**
     * Valida que un campo sea único en la base de datos
     *
     * @param string $field Campo a validar
     * @param string $table Tabla donde verificar
     * @param string $column Columna donde verificar (si es diferente al nombre del campo)
     * @param mixed $exceptId ID del registro a excluir (para actualizaciones)
     * @param string $exceptColumn Nombre de la columna de ID (por defecto 'id')
     * @param string $message Mensaje de error personalizado
     * @return Validator Instancia actual para encadenamiento
     * @throws \Exception Si no se proporcionó una instancia de DbHelper
     */
    public function unique(
        string $field, 
        string $table, 
        string $column = null, 
        $exceptId = null, 
        string $exceptColumn = 'id', 
        string $message = null
    ): Validator
    {
        // Verificar que tenemos una instancia de DbHelper
        if (!$this->db) {
            throw new \Exception('Se requiere una instancia de DbHelper para usar la validación unique');
        }
        
        // Solo validar si el campo existe y no está vacío
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            // Si no se proporciona una columna, usar el nombre del campo
            $column = $column ?? $field;
            
            // Construir las condiciones de búsqueda
            $where = [$column => $this->data[$field]];
            
            // Agregar la condición de excepción si existe
            if ($exceptId !== null) {
                $where[$exceptColumn . '[!]'] = $exceptId;
            }
            
            // Usar el método exists de DbHelper en lugar de query
            if ($this->db->exists($table, $where)) {
                $this->errors[$field] = $message ?? "El valor del campo '{$field}' ya existe";
            }
        }
        
        return $this;
    }
    
    /**
     * Valida que un campo tenga un tamaño mínimo
     *
     * @param string $field Campo a validar
     * @param int $min Tamaño mínimo
     * @param string $message Mensaje de error personalizado
     * @return Validator Instancia actual para encadenamiento
     */
    public function min(string $field, int $min, string $message = null): Validator
    {
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $min) {
            $this->errors[$field] = $message ?? "El campo '{$field}' debe tener al menos {$min} caracteres";
        }
        
        return $this;
    }
    
    /**
     * Valida que un campo tenga un tamaño máximo
     *
     * @param string $field Campo a validar
     * @param int $max Tamaño máximo
     * @param string $message Mensaje de error personalizado
     * @return Validator Instancia actual para encadenamiento
     */
    public function max(string $field, int $max, string $message = null): Validator
    {
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $max) {
            $this->errors[$field] = $message ?? "El campo '{$field}' debe tener como máximo {$max} caracteres";
        }
        
        return $this;
    }
    
    /**
     * Valida que un campo sea un número
     *
     * @param string $field Campo a validar
     * @param string $message Mensaje de error personalizado
     * @return Validator Instancia actual para encadenamiento
     */
    public function numeric(string $field, string $message = null): Validator
    {
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field] = $message ?? "El campo '{$field}' debe ser un número";
        }
        
        return $this;
    }
    
    /**
     * Valida que un campo sea igual a otro
     *
     * @param string $field Campo a validar
     * @param string $otherField Campo con el que comparar
     * @param string $message Mensaje de error personalizado
     * @return Validator Instancia actual para encadenamiento
     */
    public function equals(string $field, string $otherField, string $message = null): Validator
    {
        if (isset($this->data[$field]) && isset($this->data[$otherField])) {
            if ($this->data[$field] !== $this->data[$otherField]) {
                $this->errors[$field] = $message ?? "El campo '{$field}' debe ser igual a '{$otherField}'";
            }
        }
        
        return $this;
    }    

    /**
     * Valida que un valor esté contenido en un conjunto de valores permitidos
     *
     * @param string $field Nombre del campo a validar
     * @param array $allowedValues Array de valores permitidos
     * @param string $message Mensaje de error
     * @return $this
     */
    public function in(string $field, array $allowedValues, string $message = null): self
    {
        if (isset($this->data[$field]) && !in_array($this->data[$field], $allowedValues)) {
            $message = $message ?? "El campo '$field' debe ser uno de los siguientes valores: " . implode(', ', $allowedValues);
            $this->errors[$field][] = $message;
        }
        
        return $this;
    }
    
    /**
     * Verifica si la validación ha pasado
     *
     * @return bool True si no hay errores, false en caso contrario
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }
    
    /**
     * Verifica si la validación ha fallado
     *
     * @return bool True si hay errores, false en caso contrario
     */
    public function fails(): bool
    {
        return !$this->passes();
    }
    
    /**
     * Obtiene los errores de validación
     *
     * @return array Errores de validación
     */
    public function errors(): array
    {
        return $this->errors;
    }
    
    /**
     * Valida los datos y retorna un array con los errores o null si no hay errores
     *
     * @return array|null Errores de validación o null
     */
    public function validate(): ?array
    {
        return $this->fails() ? $this->errors() : null;
    }
}