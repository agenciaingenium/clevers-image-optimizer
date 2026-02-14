<?php
/**
 * Plugin Name: Clever Image Optimizer
 * Description: Optimización local de imágenes + WebP + AVIF para sitios gestionados por Clever.
 * Author: Clevers.dev
 * Version: 0.3.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Tested up to: 6.8
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: clevers-image-optimizer
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
}

require_once plugin_dir_path(__FILE__) . 'includes/htaccess.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-cio-optimizer.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-cio-admin.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-cio-media-library.php';

function cio_load_textdomain()
{
    load_plugin_textdomain('clevers-image-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'cio_load_textdomain');

function cio_init()
{
    $optimizer = new CIO_Optimizer();

    if (is_admin()) {
        new CIO_Admin($optimizer);
        new CIO_Media_Library($optimizer);
    }
}
add_action('plugins_loaded', 'cio_init', 20);

register_activation_hook(__FILE__, function () {
    $missing = [];

    if (!extension_loaded('gd') && !extension_loaded('imagick')) {
        $missing[] = __('Se requiere la extensión PHP <strong>GD</strong> o <strong>Imagick</strong> para generar imágenes WebP/AVIF.', 'clevers-image-optimizer');
    }

    if (!empty($missing)) {
        $message  = '<p>' . implode('</p><p>', $missing) . '</p>';
        $message .= '<p><a href="' . esc_url(admin_url('plugins.php')) . '">' . __('&laquo; Volver a plugins', 'clevers-image-optimizer') . '</a></p>';
        wp_die($message, __('Clever Image Optimizer – Requisitos no cumplidos', 'clevers-image-optimizer'));
    }
});

register_deactivation_hook(__FILE__, function () {
    $timestamp = wp_next_scheduled(CIO_Optimizer::CRON_HOOK);
    if ($timestamp) {
        wp_unschedule_event($timestamp, CIO_Optimizer::CRON_HOOK);
    }
});
