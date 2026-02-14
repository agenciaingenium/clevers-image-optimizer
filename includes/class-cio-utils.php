<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Utilidades estáticas compartidas del plugin Clever Image Optimizer.
 */
class CIO_Utils
{
    /**
     * Sanitiza un valor de calidad de imagen asegurando que quede dentro del rango [min, max].
     * Si el valor casteado a entero queda fuera del rango, devuelve $default.
     *
     * @param mixed $value   Valor a sanitizar.
     * @param int   $min     Valor mínimo permitido (por defecto 1).
     * @param int   $max     Valor máximo permitido (por defecto 100).
     * @param int   $default Valor por defecto si el resultado queda fuera del rango.
     * @return int
     */
    public static function sanitize_quality($value, $min = 1, $max = 100, $default = 80)
    {
        $value = (int) $value;

        if ($value < $min || $value > $max) {
            // Intentar clamping en lugar de usar default para valores cercanos a los límites.
            if ($value < $min) {
                return $min;
            }
            return $max;
        }

        return $value;
    }
}
