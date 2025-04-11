<?php

namespace App\Helpers;

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
     * Constructor
     *
     * @param array $data Datos a validar
     */
    public function __construct(array $data)
    {
        $this->data = $data;
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