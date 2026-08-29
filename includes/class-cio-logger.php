<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Logger estructurado del plugin Clever Image Optimizer.
 *
 * Centraliza las llamadas a `error_log` con un nivel de severidad y un
 * contexto serializable, de modo que las herramientas de observabilidad
 * (logs de WP, sumarios CI, dashboards externos) puedan filtrar y
 * correlacionar eventos.
 */
class CIO_Logger
{
    public const DEBUG = 'debug';
    public const INFO  = 'info';
    public const WARN  = 'warn';
    public const ERROR = 'error';

    /**
     * Emite un mensaje estructurado a través de `error_log`.
     *
     * @param string               $severity  Una de las constantes de severidad.
     * @param string               $message   Mensaje legible.
     * @param array<string, mixed> $context   Datos adicionales serializables.
     * @param string|null          $channel   Canal lógico (subcomponente).
     */
    public static function log(string $severity, string $message, array $context = [], ?string $channel = null)
    {
        $severity = self::normaliseSeverity($severity);

        $payload = [
            'plugin'   => 'clevers-image-optimizer',
            'severity' => $severity,
            'channel'  => $channel ?? 'core',
            'message'  => $message,
            'context'  => $context,
            'ts'       => gmdate('c'),
        ];

        error_log('[clevers-image-optimizer] ' . $severity . ' ' . wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public static function debug(string $message, array $context = [], ?string $channel = null): void
    {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        self::log(self::DEBUG, $message, $context, $channel);
    }

    public static function info(string $message, array $context = [], ?string $channel = null): void
    {
        self::log(self::INFO, $message, $context, $channel);
    }

    public static function warn(string $message, array $context = [], ?string $channel = null): void
    {
        self::log(self::WARN, $message, $context, $channel);
    }

    public static function error(string $message, array $context = [], ?string $channel = null): void
    {
        self::log(self::ERROR, $message, $context, $channel);
    }

    private static function normaliseSeverity(string $severity): string
    {
        $severity = strtolower($severity);
        if (!in_array($severity, [self::DEBUG, self::INFO, self::WARN, self::ERROR], true)) {
            return self::INFO;
        }
        return $severity;
    }
}
