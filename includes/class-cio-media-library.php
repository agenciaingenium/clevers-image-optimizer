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

        // PENDIENTE 3: Acción individual "Re-optimizar" en Media Library.
        add_filter('media_row_actions', [$this, 'add_reoptimize_row_action'], 10, 2);
        add_action('wp_ajax_cio_reoptimize_single', [$this, 'ajax_reoptimize_single']);
        add_action('admin_footer-upload.php', [$this, 'reoptimize_js']);
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

    // -------------------------------------------------------------------------
    // PENDIENTE 3: Acción individual "Re-optimizar"
    // -------------------------------------------------------------------------

    /**
     * Agrega la row action "Re-optimizar" en la lista de medios para imágenes JPEG/PNG.
     *
     * @param array    $actions Acciones existentes de la fila.
     * @param \WP_Post $post    Post del attachment.
     * @return array
     */
    public function add_reoptimize_row_action(array $actions, $post)
    {
        $mime = get_post_mime_type($post->ID);
        if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
            return $actions;
        }

        if (!current_user_can('upload_files') && !current_user_can('manage_options')) {
            return $actions;
        }

        $nonce = wp_create_nonce('cio_reoptimize_' . $post->ID);
        $label = esc_html__('Re-optimizar', 'clevers-image-optimizer');

        $actions['cio_reoptimize'] = sprintf(
            '<a href="#" class="cio-reoptimize-single" data-id="%d" data-nonce="%s">%s</a>',
            esc_attr($post->ID),
            esc_attr($nonce),
            $label
        );

        return $actions;
    }

    /**
     * Handler AJAX para re-optimizar un attachment individual.
     * Verifica nonce y capacidad, luego encola el attachment.
     */
    public function ajax_reoptimize_single()
    {
        $attachment_id = isset($_POST['attachment_id']) ? absint($_POST['attachment_id']) : 0;

        if (!$attachment_id) {
            wp_send_json_error(['message' => __('ID de adjunto inválido.', 'clevers-image-optimizer')]);
        }

        // Verificar capacidad.
        if (!current_user_can('upload_files') && !current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permiso para realizar esta acción.', 'clevers-image-optimizer')]);
        }

        // Verificar nonce específico del attachment.
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'cio_reoptimize_' . $attachment_id)) {
            wp_send_json_error(['message' => __('Nonce inválido.', 'clevers-image-optimizer')]);
        }

        $queued = $this->optimizer->enqueue_attachment($attachment_id);

        if ($queued) {
            wp_send_json_success(['message' => __('Imagen encolada para re-optimización en background.', 'clevers-image-optimizer')]);
        } else {
            wp_send_json_error(['message' => __('No se pudo encolar la imagen (ya estaba en cola o ID inválido).', 'clevers-image-optimizer')]);
        }
    }

    /**
     * Imprime el script JS que gestiona los clicks en ".cio-reoptimize-single"
     * y muestra feedback básico de éxito/error inline.
     */
    public function reoptimize_js()
    {
        ?>
        <script>
        (function($) {
            $(document).on('click', '.cio-reoptimize-single', function(e) {
                e.preventDefault();

                var $link = $(this);
                var attachmentId = $link.data('id');
                var nonce = $link.data('nonce');

                $link.text('<?php echo esc_js(__('Re-optimizando…', 'clevers-image-optimizer')); ?>');

                $.post(ajaxurl, {
                    action: 'cio_reoptimize_single',
                    attachment_id: attachmentId,
                    nonce: nonce
                }, function(response) {
                    if (response.success) {
                        $link.replaceWith(
                            '<span style="color:green;">' +
                            '<?php echo esc_js(__('✓ Encolada', 'clevers-image-optimizer')); ?>' +
                            '</span>'
                        );
                    } else {
                        var msg = (response.data && response.data.message)
                            ? response.data.message
                            : '<?php echo esc_js(__('Error desconocido.', 'clevers-image-optimizer')); ?>';
                        $link.replaceWith('<span style="color:red;">' + msg + '</span>');
                    }
                }).fail(function() {
                    $link.replaceWith(
                        '<span style="color:red;"><?php echo esc_js(__('Error de conexión.', 'clevers-image-optimizer')); ?></span>'
                    );
                });
            });
        }(jQuery));
        </script>
        <?php
    }
}
