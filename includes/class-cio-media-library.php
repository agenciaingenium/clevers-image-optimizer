<?php

if (!defined('ABSPATH')) {
    exit;
}

class CIO_Media_Library
{
    private $optimizer;

    public function __construct()
    {
        $this->optimizer = new CIO_Optimizer();

        // Columns
        add_filter('manage_media_columns', [$this, 'add_column']);
        add_action('manage_media_custom_column', [$this, 'manage_column'], 10, 2);

        // Bulk Actions
        add_filter('bulk_actions-upload', [$this, 'register_bulk_action']);
        add_filter('handle_bulk_actions-upload', [$this, 'handle_bulk_action'], 10, 3);
        
        // Admin Notice for Bulk Action
        add_action('admin_notices', [$this, 'bulk_action_admin_notice']);
    }

    public function add_column($columns)
    {
        $columns['cio_optimization'] = 'Optimización';
        return $columns;
    }

    public function manage_column($column_name, $post_id)
    {
        if ($column_name !== 'cio_optimization') {
            return;
        }

        $stats = get_post_meta($post_id, '_cio_stats', true);

        if ($stats && !empty($stats['original_size'])) {
            $original = $stats['original_size'];
            $optimized = $stats['optimized_size'] ?? $original;
            
            // Original Optimization
            $savings = $original - $optimized;
            $percentage = ($original > 0) ? round(($savings / $original) * 100, 1) : 0;
            
            echo '<div style="font-size: 12px;">';
            echo "<strong>Original:</strong> " . size_format($original) . "<br>";
            
            if ($savings > 0) {
                echo "<strong>Optimizado:</strong> " . size_format($optimized) . " (-{$percentage}%)<br>";
            } else {
                 echo "<strong>Optimizado:</strong> " . size_format($optimized) . " (0%)<br>";
            }
            
            // WebP
            if (!empty($stats['webp_size'])) {
                 $webp_savings = $original - $stats['webp_size'];
                 $webp_percentage = ($original > 0) ? round(($webp_savings / $original) * 100, 1) : 0;
                 echo "<strong>WebP:</strong> " . size_format($stats['webp_size']) . " (-{$webp_percentage}%)<br>";
            }
            
            // AVIF
            if (!empty($stats['avif_size'])) {
                 $avif_savings = $original - $stats['avif_size'];
                 $avif_percentage = ($original > 0) ? round(($avif_savings / $original) * 100, 1) : 0;
                 echo "<strong>AVIF:</strong> " . size_format($stats['avif_size']) . " (-{$avif_percentage}%)<br>";
            }
            echo '</div>';

        } else {
            echo '<span style="color: #999;">No optimizado</span>';
        }
    }

    public function register_bulk_action($bulk_actions)
    {
        $bulk_actions['cio_optimize'] = 'Optimizar Imágenes';
        return $bulk_actions;
    }

    public function handle_bulk_action($redirect_to, $doaction, $post_ids)
    {
        if ($doaction !== 'cio_optimize') {
            return $redirect_to;
        }

        $processed = 0;
        foreach ($post_ids as $post_id) {
            $metadata = wp_get_attachment_metadata($post_id);
            if ($metadata) {
                // Re-run optimization logic
                // We can call the optimizer directly
                $this->optimizer->optimize_on_upload($metadata, $post_id);
                $processed++;
            }
        }

        $redirect_to = add_query_arg('cio_bulk_optimized', $processed, $redirect_to);
        return $redirect_to;
    }

    public function bulk_action_admin_notice()
    {
        if (!empty($_REQUEST['cio_bulk_optimized'])) {
            $count = intval($_REQUEST['cio_bulk_optimized']);
            printf(
                '<div id="message" class="updated notice is-dismissible"><p>%d imágenes optimizadas correctamente.</p></div>',
                $count
            );
        }
    }
}


