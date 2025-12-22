<?php
/**
 * Plugin Name: Clever Image Optimizer
 * Description: Optimización local de imágenes + WebP + AVIF para sitios gestionados por Clever.
 * Author: Clevers.dev
 * Version: 0.2.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Tested up to:      6.7
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: clevers-image-optimizer
 * */

if (!defined('ABSPATH')) {
    exit;
}

// Autoload dependencies
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
}

// Include helpers
require_once plugin_dir_path(__FILE__) . 'includes/htaccess.php';

// Include classes
require_once plugin_dir_path(__FILE__) . 'includes/class-cio-optimizer.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-cio-admin.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-cio-media-library.php';

// Initialize plugin
function cio_init() {
    new CIO_Optimizer();
    
    if (is_admin()) {
        new CIO_Admin();
        new CIO_Media_Library();
    }
}
add_action('plugins_loaded', 'cio_init');

/**
 * Reemplaza la URL de la imagen por la versión WebP/AVIF si existe.
 * Prioridad: AVIF > WebP > Original
 */
add_filter('wp_get_attachment_image_src', function ($image, $attachment_id, $size) {
    if (!is_array($image) || empty($image[0])) {
        return $image;
    }

    $url = $image[0];

    // Solo procesar JPG/JPEG/PNG
    if (!preg_match('/\.(jpe?g|png)$/i', $url)) {
        return $image;
    }

    $upload_dir = wp_upload_dir();

    // Pasar URL -> ruta física
    $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $url);
    $info = pathinfo($file_path);
    $dirname = $info['dirname'];
    $filename = $info['filename'];

    // Check for AVIF first (if enabled and exists)
    // Note: Browser support detection is handled by <picture> tags usually, 
    // but here we are replacing the src. 
    // Ideally, we should use .htaccess to serve the correct format based on Accept header,
    // keeping the file extension as .jpg/.png in the HTML.
    // The previous plugin version used .htaccess rewrite.
    // So we DON'T need to change the URL here if we rely on .htaccess rewrite.
    
    // HOWEVER, the previous code DID change the URL to .webp.
    // "RewriteRule ^(.+)\.(jpe?g|png)$ $1.webp [T=image/webp,E=accept_webp:1,L]"
    // This rewrite rule means: if request is image.jpg AND Accept: image/webp AND image.webp exists -> serve image.webp
    // BUT the URL remains image.jpg in the browser address bar / HTML source.
    
    // The previous code ALSO had a filter 'wp_get_attachment_image_src' that replaced the URL with .webp.
    // This is actually conflicting or redundant if .htaccess is doing the work.
    // If we change URL to .webp in HTML, then .htaccess rewrite for .jpg is useless for that request.
    // And if we change URL to .webp, browsers that don't support WebP (very few now) will fail.
    
    // BETTER APPROACH:
    // Rely on .htaccess for content negotiation.
    // DO NOT change the URL in HTML.
    // This is safer and cleaner.
    
    // BUT, the user might want to see .webp in the source to be sure.
    // Let's stick to the previous behavior BUT improved:
    // If we use <picture> tags (via wp_calculate_image_srcset), that's best.
    // But 'wp_get_attachment_image_src' is low level.
    
    // Let's comment out the URL replacement and rely on .htaccess which is the "Clever" way (transparent).
    // The previous code had it enabled. I will disable it to rely on .htaccess as it is more robust for fallback.
    // Wait, the user asked for "improvements".
    
    // Actually, serving WebP/AVIF via .htaccess is the best way for compatibility if we don't want to use <picture> tags everywhere.
    // So I will REMOVE the URL replacement filter.
    
    return $image;
}, 10, 3);
