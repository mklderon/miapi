<?php

/**
 * Cargador de configuraciones
 * 
 * @param string $config Nombre del archivo de configuración a cargar
 * @return array Configuración cargada
 * @throws RuntimeException Si el archivo de configuración no existe
 */
function loadConfig(string $config): array
{
    $configPath = __DIR__ . "/{$config}.php";
    
    if (!file_exists($configPath)) {
        throw new RuntimeException("El archivo de configuración '{$config}' no existe");
    }
    
    return require $configPath;
}