<?php

if (!defined('ABSPATH')) {
    exit;
}

class CIO_Admin
{
    /** @var CIO_Optimizer */
    private $optimizer;

    public function __construct(CIO_Optimizer $optimizer)
    {
        $this->optimizer = $optimizer;

        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function add_admin_menu()
    {
        add_options_page(
            __('Clever Image Optimizer', 'clevers-image-optimizer'),
            __('Clever Image Optimizer', 'clevers-image-optimizer'),
            'manage_options',
            'clever-image-optimizer',
            [$this, 'settings_page']
        );
    }

    public function register_settings()
    {
        register_setting('cio_settings_group', 'cio_webp_quality', [
            'type' => 'integer',
            'default' => 80,
            'sanitize_callback' => [$this, 'sanitize_quality'],
        ]);

        register_setting('cio_settings_group', 'cio_enable_avif', [
            'type' => 'boolean',
            'default' => false,
            'sanitize_callback' => [$this, 'sanitize_checkbox'],
        ]);

        register_setting('cio_settings_group', 'cio_avif_quality', [
            'type' => 'integer',
            'default' => 80,
            'sanitize_callback' => [$this, 'sanitize_quality'],
        ]);

        register_setting('cio_settings_group', 'cio_batch_limit', [
            'type' => 'integer',
            'default' => 10,
            'sanitize_callback' => [$this, 'sanitize_batch_limit'],
        ]);

        register_setting('cio_settings_group', 'cio_time_limit', [
            'type' => 'integer',
            'default' => 20,
            'sanitize_callback' => [$this, 'sanitize_time_limit'],
        ]);
    }

    public function sanitize_quality($value)
    {
        $value = absint($value);

        return min($value, 100);
    }

    public function sanitize_batch_limit($value)
    {
        $value = absint($value);
        if ($value < 1) {
            $value = 1;
        }

        return min($value, 100);
    }

    public function sanitize_time_limit($value)
    {
        $value = absint($value);
        if ($value < 5) {
            $value = 5;
        }

        return min($value, 120);
    }

    public function sanitize_checkbox($value)
    {
        return $value === '1' || $value === 1 ? '1' : '0';
    }

    public function settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $this->handle_htaccess_actions();

        $rules_active = cio_rules_exist();
        $avif_supported = function_exists('imageavif');
        $queue_count = $this->optimizer->get_queue_count();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Clever Image Optimizer', 'clevers-image-optimizer'); ?></h1>

            <?php settings_errors('cio_messages'); ?>

            <?php if ($queue_count > 0) : ?>
                <div class="notice notice-info">
                    <p>
                        <?php
                        echo esc_html(
                            sprintf(
                                /* translators: %d: número de imágenes en cola */
                                _n(
                                    'Hay %d imagen pendiente de optimización en background.',
                                    'Hay %d imágenes pendientes de optimización en background.',
                                    $queue_count,
                                    'clevers-image-optimizer'
                                ),
                                $queue_count
                            )
                        );
                        ?>
                    </p>
                </div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php settings_fields('cio_settings_group'); ?>

                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e('Calidad WebP (0-100)', 'clevers-image-optimizer'); ?></th>
                        <td>
                            <input type="number" name="cio_webp_quality" value="<?php echo esc_attr(get_option('cio_webp_quality', 80)); ?>" min="0" max="100" />
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php esc_html_e('Habilitar AVIF', 'clevers-image-optimizer'); ?></th>
                        <td>
                            <input type="checkbox" name="cio_enable_avif" value="1" <?php checked('1', (string) get_option('cio_enable_avif', '0')); ?> />
                            <p class="description">
                                <?php if ($avif_supported) : ?>
                                    <span style="color:green;"><?php esc_html_e('Tu servidor soporta AVIF.', 'clevers-image-optimizer'); ?></span>
                                <?php else : ?>
                                    <span style="color:red;"><?php esc_html_e('Tu servidor no soporta AVIF (requiere imageavif en GD).', 'clevers-image-optimizer'); ?></span>
                                <?php endif; ?>
                            </p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php esc_html_e('Calidad AVIF (0-100)', 'clevers-image-optimizer'); ?></th>
                        <td>
                            <input type="number" name="cio_avif_quality" value="<?php echo esc_attr(get_option('cio_avif_quality', 80)); ?>" min="0" max="100" />
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php esc_html_e('Lote por ejecución', 'clevers-image-optimizer'); ?></th>
                        <td>
                            <input type="number" name="cio_batch_limit" value="<?php echo esc_attr(get_option('cio_batch_limit', 10)); ?>" min="1" max="100" />
                            <p class="description"><?php esc_html_e('Cantidad máxima de adjuntos procesados por cada corrida en background.', 'clevers-image-optimizer'); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php esc_html_e('Límite de tiempo por ejecución (seg)', 'clevers-image-optimizer'); ?></th>
                        <td>
                            <input type="number" name="cio_time_limit" value="<?php echo esc_attr(get_option('cio_time_limit', 20)); ?>" min="5" max="120" />
                            <p class="description"><?php esc_html_e('Evita timeouts al procesar lotes grandes.', 'clevers-image-optimizer'); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>

            <hr>

            <h2><?php esc_html_e('Configuración .htaccess', 'clevers-image-optimizer'); ?></h2>
            <p>
                <strong><?php esc_html_e('Estado:', 'clevers-image-optimizer'); ?></strong>
                <?php if ($rules_active) : ?>
                    <span style="color:green;"><?php esc_html_e('Reglas activas.', 'clevers-image-optimizer'); ?></span>
                <?php else : ?>
                    <span style="color:red;"><?php esc_html_e('Reglas no instaladas.', 'clevers-image-optimizer'); ?></span>
                <?php endif; ?>
            </p>

            <form method="post">
                <?php wp_nonce_field('cio_htaccess_action', 'cio_htaccess_nonce'); ?>
                <p>
                    <input type="submit" name="cio_add_rules" class="button button-secondary" value="<?php esc_attr_e('Activar reglas WebP/AVIF en .htaccess', 'clevers-image-optimizer'); ?>">
                    <input type="submit" name="cio_remove_rules" class="button button-secondary" value="<?php esc_attr_e('Eliminar reglas del .htaccess', 'clevers-image-optimizer'); ?>" style="margin-left:10px;">
                </p>
            </form>
        </div>
        <?php
    }

    private function handle_htaccess_actions()
    {
        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        if (!isset($_POST['cio_htaccess_nonce'])) {
            return;
        }

        $nonce = sanitize_text_field(wp_unslash($_POST['cio_htaccess_nonce']));
        if (!wp_verify_nonce($nonce, 'cio_htaccess_action')) {
            return;
        }

        if (isset($_POST['cio_add_rules'])) {
            if (cio_add_htaccess_rules()) {
                add_settings_error('cio_messages', 'cio_message', __('Reglas añadidas correctamente al .htaccess.', 'clevers-image-optimizer'), 'updated');
            } else {
                add_settings_error('cio_messages', 'cio_message', __('No se pudo escribir en el .htaccess. Revisa permisos.', 'clevers-image-optimizer'), 'error');
            }
        }

        if (isset($_POST['cio_remove_rules'])) {
            cio_remove_htaccess_rules();
            add_settings_error('cio_messages', 'cio_message', __('Reglas eliminadas del .htaccess.', 'clevers-image-optimizer'), 'updated');
        }
        // Los mensajes se renderizan dentro de settings_page() en la posición correcta del DOM.
    }
}
