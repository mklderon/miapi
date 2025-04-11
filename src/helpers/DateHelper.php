<?php
// Archivo: src/Helpers/DateHelper.php

namespace App\Helpers;

use DateTime;
use DateTimeZone;

/**
 * Clase auxiliar para manejar fechas
 */
class DateHelper
{
    /**
     * Obtiene la fecha y hora actual en formato Y-m-d H:i:s
     *
     * @return string
     */
    public static function now(): string
    {
        return date('Y-m-d H:i:s');
    }
    
    /**
     * Formatea una fecha para almacenar en la base de datos
     *
     * @param string|DateTime|null $date Fecha a formatear
     * @param string $format Formato de salida
     * @return string|null
     */
    public static function formatForDatabase($date = null, string $format = 'Y-m-d H:i:s'): ?string
    {
        if ($date === null) {
            $date = new DateTime();
        } elseif (is_string($date)) {
            $date = new DateTime($date);
        }
        
        return $date->format($format);
    }
    
    /**
     * Convierte una fecha de la base de datos a la zona horaria de la aplicación
     *
     * @param string $date Fecha en formato Y-m-d H:i:s
     * @param string $format Formato de salida
     * @return string
     */
    public static function formatFromDatabase(string $date, string $format = 'Y-m-d H:i:s'): string
    {
        // Asumimos que la base de datos usa UTC
        $dateTime = new DateTime($date, new DateTimeZone('UTC'));
        
        // Convertir a la zona horaria de la aplicación
        $dateTime->setTimezone(new DateTimeZone(date_default_timezone_get()));
        
        return $dateTime->format($format);
    }
    
    /**
     * Obtiene la diferencia en segundos entre dos fechas
     *
     * @param string|DateTime $date1
     * @param string|DateTime $date2
     * @return int
     */
    public static function diffInSeconds($date1, $date2): int
    {
        if (is_string($date1)) {
            $date1 = new DateTime($date1);
        }
        
        if (is_string($date2)) {
            $date2 = new DateTime($date2);
        }
        
        return abs($date1->getTimestamp() - $date2->getTimestamp());
    }
    
    /**
     * Obtiene la diferencia en días entre dos fechas
     *
     * @param string|DateTime $date1
     * @param string|DateTime $date2
     * @return int
     */
    public static function diffInDays($date1, $date2): int
    {
        return floor(self::diffInSeconds($date1, $date2) / 86400);
    }
    
    /**
     * Verifica si una fecha es anterior a otra
     *
     * @param string|DateTime $date
     * @param string|DateTime|null $comparedTo
     * @return bool
     */
    public static function isBefore($date, $comparedTo = null): bool
    {
        if ($comparedTo === null) {
            $comparedTo = new DateTime();
        } elseif (is_string($comparedTo)) {
            $comparedTo = new DateTime($comparedTo);
        }
        
        if (is_string($date)) {
            $date = new DateTime($date);
        }
        
        return $date < $comparedTo;
    }
    
    /**
     * Verifica si una fecha es posterior a otra
     *
     * @param string|DateTime $date
     * @param string|DateTime|null $comparedTo
     * @return bool
     */
    public static function isAfter($date, $comparedTo = null): bool
    {
        if ($comparedTo === null) {
            $comparedTo = new DateTime();
        } elseif (is_string($comparedTo)) {
            $comparedTo = new DateTime($comparedTo);
        }
        
        if (is_string($date)) {
            $date = new DateTime($date);
        }
        
        return $date > $comparedTo;
    }
}