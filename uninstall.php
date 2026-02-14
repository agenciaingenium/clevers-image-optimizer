<?php
/**
 * Uninstall script for Clever Image Optimizer.
 *
 * Fired when the plugin is uninstalled via WordPress admin.
 * Removes all plugin options and transients from the database.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// --- Options ---
$options = [
    'cio_webp_quality',
    'cio_enable_avif',
    'cio_avif_quality',
    'cio_batch_limit',
    'cio_time_limit',
    'cio_pending_queue', // CIO_Optimizer::OPTION_QUEUE
];

foreach ($options as $option) {
    delete_option($option);
}

// --- Transients ---
delete_transient('cio_process_queue_lock'); // CIO_Optimizer::LOCK_KEY

// --- Post meta ---
// Remove optimization stats stored per attachment
delete_post_meta_by_key('_cio_stats');

// --- Scheduled events ---
$timestamp = wp_next_scheduled('cio_process_queue'); // CIO_Optimizer::CRON_HOOK
if ($timestamp) {
    wp_unschedule_event($timestamp, 'cio_process_queue');
}
