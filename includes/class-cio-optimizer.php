<?php

if (!defined('ABSPATH')) {
    exit;
}

use Spatie\ImageOptimizer\OptimizerChainFactory;

class CIO_Optimizer
{
    const OPTION_QUEUE = 'cio_pending_queue';
    const CRON_HOOK = 'cio_process_queue';
    const LOCK_KEY = 'cio_process_queue_lock';

    public function __construct()
    {
        add_filter('wp_generate_attachment_metadata', [$this, 'queue_on_upload'], 10, 2);
        add_action(self::CRON_HOOK, [$this, 'process_queue']);
    }

    public function queue_on_upload($metadata, $attachment_id)
    {
        $this->enqueue_attachment($attachment_id);

        return $metadata;
    }

    public function enqueue_attachment($attachment_id)
    {
        $attachment_id = absint($attachment_id);
        if (!$attachment_id) {
            return false;
        }

        $queue = $this->get_queue();
        if (!in_array($attachment_id, $queue, true)) {
            $queue[] = $attachment_id;
            $this->save_queue($queue);
        }

        $this->schedule_queue_processing();

        return true;
    }

    public function enqueue_attachments(array $attachment_ids)
    {
        $ids_to_add = $this->normalize_attachment_id_list($attachment_ids);

        if (empty($ids_to_add)) {
            return 0;
        }

        // Leer la cola una sola vez para evitar múltiples writes a DB en bucle.
        $queue = $this->get_queue();
        $original_count = count($queue);

        foreach ($ids_to_add as $id) {
            if (!in_array($id, $queue, true)) {
                $queue[] = $id;
            }
        }

        $queued = count($queue) - $original_count;

        if ($queued > 0) {
            $this->save_queue($queue);
            $this->schedule_queue_processing();
        }

        return $queued;
    }

    public function process_queue()
    {
        if (get_transient(self::LOCK_KEY)) {
            return;
        }

        // Duración del lock = time_limit + 30 s de margen para evitar ejecuciones paralelas
        // si el procesamiento tarda exactamente el límite configurado.
        $time_limit = $this->get_time_limit();
        set_transient(self::LOCK_KEY, 1, $time_limit + 30);

        $queue = $this->get_queue();
        if (empty($queue)) {
            delete_transient(self::LOCK_KEY);
            return;
        }

        $batch_limit = $this->get_batch_limit();
        // $time_limit ya fue obtenido arriba para calcular la duración del lock.
        $start = microtime(true);
        $processed = 0;

        while (!empty($queue) && $processed < $batch_limit) {
            if ((microtime(true) - $start) >= $time_limit) {
                break;
            }

            $attachment_id = (int) array_shift($queue);
            $this->optimize_attachment($attachment_id);
            $processed++;
        }

        $this->save_queue($queue);

        if (!empty($queue)) {
            $this->schedule_queue_processing();
        }

        delete_transient(self::LOCK_KEY);
    }

    public function optimize_attachment($attachment_id)
    {
        $attachment_id = absint($attachment_id);
        if (!$attachment_id) {
            return false;
        }

        $metadata = wp_get_attachment_metadata($attachment_id);
        if (empty($metadata['file'])) {
            return false;
        }

        $upload_dir = wp_upload_dir();
        $base_dir = trailingslashit($upload_dir['basedir']);

        $stats = [
            'original_size' => 0,
            'optimized_size' => 0,
            'webp_size' => 0,
            'avif_size' => 0,
        ];

        $original = $base_dir . $metadata['file'];

        // PENDIENTE 1: Validación de path traversal — garantiza que el path
        // del archivo principal esté dentro del directorio de uploads.
        $upload_basedir = realpath($upload_dir['basedir']);
        $real_original  = realpath($original);
        if ($real_original === false || strpos($real_original, $upload_basedir) !== 0) {
            return new WP_Error('invalid_path', 'Path fuera del directorio de uploads.');
        }

        if ($this->is_supported_image_path($original) && file_exists($original)) {
            $stats['original_size'] = (int) filesize($original);

            $this->optimize_file($original);
            $stats['optimized_size'] = (int) filesize($original);

            $this->generate_webp($original);
            $webp_path = $this->get_webp_path($original);
            if (file_exists($webp_path)) {
                $stats['webp_size'] = (int) filesize($webp_path);
            }

            if ($this->is_avif_enabled()) {
                $this->generate_avif($original);
                $avif_path = $this->get_avif_path($original);
                if (file_exists($avif_path)) {
                    $stats['avif_size'] = (int) filesize($avif_path);
                }
            }
        }

        if (!empty($metadata['sizes']) && is_array($metadata['sizes'])) {
            $subdir = pathinfo($metadata['file'], PATHINFO_DIRNAME);

            foreach ($metadata['sizes'] as $size) {
                if (empty($size['file'])) {
                    continue;
                }

                $file = $base_dir . $subdir . '/' . $size['file'];
                if (!$this->is_supported_image_path($file) || !file_exists($file)) {
                    continue;
                }

                $this->optimize_file($file);
                $this->generate_webp($file);

                if ($this->is_avif_enabled()) {
                    $this->generate_avif($file);
                }
            }
        }

        update_post_meta($attachment_id, '_cio_stats', $stats);

        return true;
    }

    public function optimize_file($file)
    {
        if (!file_exists($file)) {
            return;
        }

        try {
            $optimizerChain = OptimizerChainFactory::create();
            $optimizerChain->optimize($file);
        } catch (\Throwable $e) {
            CIO_Logger::warn('optimize_file failed', [
                'file'  => $file,
                'error' => $e->getMessage(),
            ], 'optimizer');
        }
    }

    public function generate_webp($file)
    {
        if (!function_exists('imagewebp')) {
            return;
        }

        $webp = $this->get_webp_path($file);

        if (file_exists($webp)) {
            return;
        }

        $img = $this->create_image_resource($file);
        if (!$img) {
            return;
        }

        $quality = $this->get_webp_quality();
        imagewebp($img, $webp, $quality);
        imagedestroy($img);
    }

    public function generate_avif($file)
    {
        if (!function_exists('imageavif')) {
            return;
        }

        $avif = $this->get_avif_path($file);

        if (file_exists($avif)) {
            return;
        }

        $img = $this->create_image_resource($file);
        if (!$img) {
            return;
        }

        $quality = $this->get_avif_quality();
        imageavif($img, $avif, $quality);
        imagedestroy($img);
    }

    /**
     * Devuelve la cantidad de adjuntos pendientes en la cola.
     */
    public function get_queue_count()
    {
        return count($this->get_queue());
    }

    public function normalize_attachment_id_list(array $attachment_ids)
    {
        $normalized = array_map('absint', $attachment_ids);
        $normalized = array_filter($normalized);

        return array_values(array_unique($normalized));
    }

    public function sanitize_quality($value)
    {
        // PENDIENTE 2: Delegamos en CIO_Utils para eliminar la implementación duplicada.
        // Usamos min=0 para mantener compatibilidad con llamadas y tests previos que
        // esperan 0 para valores negativos.
        return CIO_Utils::sanitize_quality($value, 0, 100, 80);
    }

    public function is_supported_image_path($file)
    {
        return (bool) preg_match('/\.(jpe?g|png)$/i', (string) $file);
    }

    private function get_queue()
    {
        $queue = get_option(self::OPTION_QUEUE, []);

        return is_array($queue) ? $this->normalize_attachment_id_list($queue) : [];
    }

    private function save_queue(array $queue)
    {
        update_option(self::OPTION_QUEUE, $this->normalize_attachment_id_list($queue), false);
    }

    private function schedule_queue_processing()
    {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_single_event(time() + 15, self::CRON_HOOK);
        }
    }

    private function get_batch_limit()
    {
        $limit = absint(get_option('cio_batch_limit', 10));
        if ($limit < 1) {
            $limit = 1;
        }

        return min($limit, 100);
    }

    private function get_time_limit()
    {
        $limit = absint(get_option('cio_time_limit', 20));
        if ($limit < 5) {
            $limit = 5;
        }

        return min($limit, 120);
    }

    private function create_image_resource($file)
    {
        // PENDIENTE 4: Reemplazamos la supresión de errores con @ por manejo
        // explícito mediante set_error_handler para capturar fallos sin silenciarlos.
        $read_error = null;
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Used to capture image warnings without @.
        set_error_handler(static function ($errno, $errstr) use (&$read_error) {
            $read_error = $errstr;
            return true;
        });
        $content = file_get_contents($file);
        restore_error_handler();

        if ($content === false || $content === '') {
            if ($read_error !== null) {
                CIO_Logger::debug('create_image_resource file_get_contents failed', [
                    'file'  => $file,
                    'error' => $read_error,
                ], 'image_reader');
            }
            return null;
        }

        $parse_error = null;
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Used to capture image warnings without @.
        set_error_handler(static function ($errno, $errstr) use (&$parse_error) {
            $parse_error = $errstr;
            return true;
        });
        $img = imagecreatefromstring($content);
        restore_error_handler();

        if ($img === false) {
            if ($parse_error !== null) {
                CIO_Logger::debug('create_image_resource imagecreatefromstring failed', [
                    'file'  => $file,
                    'error' => $parse_error,
                ], 'image_reader');
            }
            return null;
        }

        return $img;
    }

    private function get_webp_path($file)
    {
        $info = pathinfo($file);

        return $info['dirname'] . '/' . $info['filename'] . '.webp';
    }

    private function get_avif_path($file)
    {
        $info = pathinfo($file);

        return $info['dirname'] . '/' . $info['filename'] . '.avif';
    }

    private function get_webp_quality()
    {
        return $this->sanitize_quality(get_option('cio_webp_quality', 80));
    }

    private function get_avif_quality()
    {
        return $this->sanitize_quality(get_option('cio_avif_quality', 80));
    }

    private function is_avif_enabled()
    {
        return get_option('cio_enable_avif', '0') === '1';
    }
}
