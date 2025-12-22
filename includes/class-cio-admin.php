<?php

if (!defined('ABSPATH')) {
    exit;
}

class CIO_Admin
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function add_admin_menu()
    {
        add_options_page(
            'Clever Image Optimizer',
            'Clever Image Optimizer',
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
            'sanitize_callback' => 'absint'
        ]);
        
        register_setting('cio_settings_group', 'cio_enable_avif', [
            'type' => 'boolean',
            'default' => false,
        ]);
        
        register_setting('cio_settings_group', 'cio_avif_quality', [
            'type' => 'integer',
            'default' => 80,
            'sanitize_callback' => 'absint'
        ]);
    }

    public function settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Handle .htaccess actions
        $this->handle_htaccess_actions();

        $rules_active = cio_rules_exist();
        $avif_supported = function_exists('imageavif');
        ?>
        <div class="wrap">
            <h1>Clever Image Optimizer</h1>

            <form method="post" action="options.php">
                <?php settings_fields('cio_settings_group'); ?>
                <?php do_settings_sections('cio_settings_group'); ?>

                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Calidad WebP (0-100)</th>
                        <td>
                            <input type="number" name="cio_webp_quality" value="<?php echo esc_attr(get_option('cio_webp_quality', 80)); ?>" min="0" max="100" />
                        </td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row">Habilitar AVIF</th>
                        <td>
                            <input type="checkbox" name="cio_enable_avif" value="1" <?php checked(1, get_option('cio_enable_avif', 0), true); ?> />
                            <p class="description">
                                <?php if ($avif_supported): ?>
                                    <span style="color:green;">Tu servidor soporta AVIF ✔</span>
                                <?php else: ?>
                                    <span style="color:red;">Tu servidor NO soporta AVIF (falta PHP 8.1+ o GD con AVIF) ✘</span>
                                <?php endif; ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row">Calidad AVIF (0-100)</th>
                        <td>
                            <input type="number" name="cio_avif_quality" value="<?php echo esc_attr(get_option('cio_avif_quality', 80)); ?>" min="0" max="100" />
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>

            <hr>

            <h2>Configuración .htaccess</h2>
            <p>
                <strong>Estado:</strong>
                <?php if ($rules_active): ?>
                    <span style="color:green;">Reglas activas ✔</span>
                <?php else: ?>
                    <span style="color:red;">Reglas NO instaladas ✘</span>
                <?php endif; ?>
            </p>

            <form method="post">
                <?php wp_nonce_field('cio_htaccess_action', 'cio_htaccess_nonce'); ?>
                <p>
                    <input type="submit" name="cio_add_rules" class="button button-secondary" value="Activar reglas WebP/AVIF en .htaccess">
                    <input type="submit" name="cio_remove_rules" class="button button-secondary" value="Eliminar reglas del .htaccess" style="margin-left:10px;">
                </p>
            </form>
        </div>
        <?php
    }

    private function handle_htaccess_actions()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cio_htaccess_nonce'])) {
            if (!wp_verify_nonce($_POST['cio_htaccess_nonce'], 'cio_htaccess_action')) {
                return;
            }

            if (isset($_POST['cio_add_rules'])) {
                if (cio_add_htaccess_rules()) {
                    add_settings_error('cio_messages', 'cio_message', 'Reglas añadidas correctamente al .htaccess.', 'updated');
                } else {
                    add_settings_error('cio_messages', 'cio_message', 'No se pudo escribir en el .htaccess. Revisa permisos.', 'error');
                }
            }

            if (isset($_POST['cio_remove_rules'])) {
                cio_remove_htaccess_rules();
                add_settings_error('cio_messages', 'cio_message', 'Reglas eliminadas del .htaccess.', 'updated');
            }
            
            settings_errors('cio_messages');
        }
    }
}


