<?php

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

if (!defined('MINUTE_IN_SECONDS')) {
    define('MINUTE_IN_SECONDS', 60);
}

if (!function_exists('add_filter')) {
    function add_filter(...$args)
    {
        return null;
    }
}

if (!function_exists('add_action')) {
    function add_action(...$args)
    {
        return null;
    }
}

if (!function_exists('get_transient')) {
    function get_transient(...$args)
    {
        return null;
    }
}

if (!function_exists('set_transient')) {
    function set_transient(...$args)
    {
        return null;
    }
}

if (!function_exists('delete_transient')) {
    function delete_transient(...$args)
    {
        return null;
    }
}

if (!function_exists('get_option')) {
    function get_option(...$args)
    {
        return null;
    }
}

if (!function_exists('update_option')) {
    function update_option(...$args)
    {
        return null;
    }
}

if (!function_exists('wp_next_scheduled')) {
    function wp_next_scheduled(...$args)
    {
        return null;
    }
}

if (!function_exists('wp_schedule_single_event')) {
    function wp_schedule_single_event(...$args)
    {
        return null;
    }
}

if (!function_exists('wp_get_attachment_metadata')) {
    function wp_get_attachment_metadata(...$args)
    {
        return null;
    }
}

if (!function_exists('wp_upload_dir')) {
    function wp_upload_dir(...$args)
    {
        return null;
    }
}

if (!function_exists('update_post_meta')) {
    function update_post_meta(...$args)
    {
        return null;
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

require_once dirname(__DIR__) . '/includes/class-cio-utils.php';
require_once dirname(__DIR__) . '/includes/class-cio-optimizer.php';
