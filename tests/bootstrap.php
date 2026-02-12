<?php

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

if (!defined('MINUTE_IN_SECONDS')) {
    define('MINUTE_IN_SECONDS', 60);
}

$stubbed_functions = [
    'add_filter',
    'add_action',
    'get_transient',
    'set_transient',
    'delete_transient',
    'get_option',
    'update_option',
    'wp_next_scheduled',
    'wp_schedule_single_event',
    'wp_get_attachment_metadata',
    'wp_upload_dir',
    'update_post_meta',
];

foreach ($stubbed_functions as $function_name) {
    if (!function_exists($function_name)) {
        eval(sprintf('function %s(...$args) { return null; }', $function_name));
    }
}

if (!function_exists('absint')) {
    function absint($maybeint)
    {
        return abs((int) $maybeint);
    }
}

if (!function_exists('trailingslashit')) {
    function trailingslashit($value)
    {
        return rtrim($value, '/\\') . '/';
    }
}

require_once dirname(__DIR__) . '/includes/class-cio-optimizer.php';
