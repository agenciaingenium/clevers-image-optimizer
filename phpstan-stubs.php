<?php

declare(strict_types=1);

/**
 * WordPress stubs for PHPStan.
 *
 * Covers the function/global surface that the plugin touches during
 * runtime so static analysis does not flag every call as missing.
 */

namespace {
    if (!defined('ABSPATH')) {
        define('ABSPATH', __DIR__ . '/');
    }
    if (!defined('MINUTE_IN_SECONDS')) {
        define('MINUTE_IN_SECONDS', 60);
    }
    if (!defined('HOUR_IN_SECONDS')) {
        define('HOUR_IN_SECONDS', 3600);
    }
    if (!defined('DAY_IN_SECONDS')) {
        define('DAY_IN_SECONDS', 86400);
    }

    if (!function_exists('add_action')) { function add_action(...$args) {} }
    if (!function_exists('add_filter')) { function add_filter(...$args) {} }
    if (!function_exists('apply_filters')) { function apply_filters($tag, $value, ...$rest) { return $value; } }
    if (!function_exists('do_action')) { function do_action(...$args) {} }
    if (!function_exists('get_option')) { function get_option($name, $default = false) { return $default; } }
    if (!function_exists('update_option')) { function update_option($name, $value, $autoload = null) { return true; } }
    if (!function_exists('delete_option')) { function delete_option($name) { return true; } }
    if (!function_exists('get_transient')) { function get_transient($name) { return false; } }
    if (!function_exists('set_transient')) { function set_transient($name, $value, $expiration = 0) { return true; } }
    if (!function_exists('delete_transient')) { function delete_transient($name) { return true; } }
    if (!function_exists('wp_next_scheduled')) { function wp_next_scheduled($hook, $args = []) { return false; } }
    if (!function_exists('wp_schedule_single_event')) { function wp_schedule_single_event($timestamp, $hook, $args = []) { return true; } }
    if (!function_exists('wp_unschedule_event')) { function wp_unschedule_event($timestamp, $hook, $args = []) { return true; } }
    if (!function_exists('wp_get_attachment_metadata')) { function wp_get_attachment_metadata($attachment_id, $unfiltered = false) { return []; } }
    if (!function_exists('wp_update_attachment_metadata')) { function wp_update_attachment_metadata($data, $attachment_id) { return true; } }
    if (!function_exists('wp_upload_dir')) {
        function wp_upload_dir($time = null, $create_dir = true)
        {
            return ['basedir' => '/tmp', 'baseurl' => 'https://example.test'];
        }
    }
    if (!function_exists('update_post_meta')) { function update_post_meta($id, $key, $value) { return true; } }
    if (!function_exists('get_post_meta')) { function get_post_meta($id, $key, $single = false) { return ''; } }
    if (!function_exists('absint')) { function absint($maybeint) { return abs((int) $maybeint); } }
    if (!function_exists('trailingslashit')) { function trailingslashit($value) { return rtrim((string) $value, '/\\') . '/'; } }
    if (!function_exists('untrailingslashit')) { function untrailingslashit($value) { return rtrim((string) $value, '/\\'); } }
    if (!function_exists('plugin_dir_path')) { function plugin_dir_path($file) { return rtrim(dirname((string) $file), '/\\') . '/'; } }
    if (!function_exists('plugin_dir_url')) { function plugin_dir_url($file) { return ''; } }
    if (!function_exists('register_activation_hook')) { function register_activation_hook($file, $callback) {} }
    if (!function_exists('register_deactivation_hook')) { function register_deactivation_hook($file, $callback) {} }
    if (!function_exists('is_admin')) { function is_admin() { return false; } }
    if (!function_exists('admin_url')) { function admin_url($path = '', $scheme = 'admin') { return 'https://example.test/wp-admin/' . ltrim($path, '/'); } }
    if (!function_exists('esc_url')) { function esc_url($url) { return (string) $url; } }
    if (!function_exists('esc_html__')) { function esc_html__($text, $domain = '') { return (string) $text; } }
    if (!function_exists('esc_attr__')) { function esc_attr__($text, $domain = '') { return (string) $text; } }
    if (!function_exists('esc_attr')) { function esc_attr($text) { return (string) $text; } }
    if (!function_exists('__')) { function __($text, $domain = '') { return (string) $text; } }
    if (!function_exists('wp_kses_post')) { function wp_kses_post($content) { return (string) $content; } }
    if (!function_exists('wp_die')) { function wp_die($message = '', $title = '', $args = []) { throw new \RuntimeException((string) $message); } }
    if (!function_exists('register_setting')) { function register_setting(...$args) {} }
    if (!function_exists('add_settings_field')) { function add_settings_field(...$args) {} }
    if (!function_exists('add_settings_section')) { function add_settings_section(...$args) {} }
    if (!function_exists('add_submenu_page')) { function add_submenu_page(...$args) { return ''; } }
    if (!function_exists('current_user_can')) { function current_user_can($capability, ...$args) { return true; } }
    if (!function_exists('wp_json_encode')) { function wp_json_encode($data, $options = 0, $depth = 512) { return json_encode($data, $options, $depth); } }
    if (!function_exists('check_admin_referer')) { function check_admin_referer($action = -1, $query_arg = '_wpnonce') { return true; } }
    if (!function_exists('wp_create_nonce')) { function wp_create_nonce($action = -1) { return 'nonce'; } }
    if (!function_exists('wp_send_json_success')) { function wp_send_json_success($data = null) {} }
    if (!function_exists('wp_send_json_error')) { function wp_send_json_error($data = null) {} }
    if (!function_exists('get_attached_file')) { function get_attached_file($attachment_id) { return ''; } }
}
