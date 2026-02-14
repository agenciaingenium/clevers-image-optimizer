<?php

if (!defined('ABSPATH')) {
    exit;
}

class CIO_Media_Library
{
    private $optimizer;

    public function __construct(CIO_Optimizer $optimizer)
    {
        $this->optimizer = $optimizer;

        add_filter('manage_media_columns', [$this, 'add_column']);
        add_action('manage_media_custom_column', [$this, 'manage_column'], 10, 2);

        add_filter('bulk_actions-upload', [$this, 'register_bulk_action']);
        add_filter('handle_bulk_actions-upload', [$this, 'handle_bulk_action'], 10, 3);

        add_action('admin_notices', [$this, 'bulk_action_admin_notice']);
    }

    public function add_column($columns)
    {
        $columns['cio_optimization'] = __('Optimización', 'clevers-image-optimizer');

        return $columns;
    }

    public function manage_column($column_name, $post_id)
    {
        if ($column_name !== 'cio_optimization') {
            return;
        }

        // Mostrar la columna solo para imágenes JPEG/PNG soportadas por el plugin.
        $mime = get_post_mime_type($post_id);
        if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
            echo '<span style="color:#999;">—</span>';
            return;
        }

        $stats = get_post_meta($post_id, '_cio_stats', true);

        if ($stats && !empty($stats['original_size'])) {
            $original = (int) $stats['original_size'];
            $optimized = isset($stats['optimized_size']) ? (int) $stats['optimized_size'] : $original;

            $savings = $original - $optimized;
            $percentage = ($original > 0) ? round(($savings / $original) * 100, 1) : 0;

            echo '<div style="font-size: 12px;">';
            echo '<strong>' . esc_html__('Original:', 'clevers-image-optimizer') . '</strong> ' . esc_html(size_format($original)) . '<br>';

            if ($savings > 0) {
                echo '<strong>' . esc_html__('Optimizado:', 'clevers-image-optimizer') . '</strong> ' . esc_html(size_format($optimized) . " (-{$percentage}%)") . '<br>';
            } else {
                echo '<strong>' . esc_html__('Optimizado:', 'clevers-image-optimizer') . '</strong> ' . esc_html(size_format($optimized) . ' (0%)') . '<br>';
            }

            if (!empty($stats['webp_size'])) {
                $webp_size = (int) $stats['webp_size'];
                $webp_savings = $original - $webp_size;
                $webp_percentage = ($original > 0) ? round(($webp_savings / $original) * 100, 1) : 0;
                echo '<strong>WebP:</strong> ' . esc_html(size_format($webp_size) . " (-{$webp_percentage}%)") . '<br>';
            }

            if (!empty($stats['avif_size'])) {
                $avif_size = (int) $stats['avif_size'];
                $avif_savings = $original - $avif_size;
                $avif_percentage = ($original > 0) ? round(($avif_savings / $original) * 100, 1) : 0;
                echo '<strong>AVIF:</strong> ' . esc_html(size_format($avif_size) . " (-{$avif_percentage}%)") . '<br>';
            }
            echo '</div>';

            return;
        }

        echo '<span style="color: #999;">' . esc_html__('Pendiente o no optimizado', 'clevers-image-optimizer') . '</span>';
    }

    public function register_bulk_action($bulk_actions)
    {
        $bulk_actions['cio_optimize'] = __('Optimizar imágenes (background)', 'clevers-image-optimizer');

        return $bulk_actions;
    }

    public function handle_bulk_action($redirect_to, $doaction, $post_ids)
    {
        if ($doaction !== 'cio_optimize') {
            return $redirect_to;
        }

        $processed = $this->optimizer->enqueue_attachments($post_ids);

        return add_query_arg('cio_bulk_optimized', $processed, $redirect_to);
    }

    public function bulk_action_admin_notice()
    {
        if (empty($_REQUEST['cio_bulk_optimized'])) {
            return;
        }

        // Verificar que el referrer es válido para evitar mensajes falsos via URL externa.
        if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce(sanitize_key($_REQUEST['_wpnonce']), 'bulk-media')) {
            return;
        }

        $count = absint($_REQUEST['cio_bulk_optimized']);

        printf(
            '<div id="message" class="updated notice is-dismissible"><p>%s</p></div>',
            esc_html(sprintf(__('%d imágenes encoladas para optimización en background.', 'clevers-image-optimizer'), $count))
        );
    }
}
