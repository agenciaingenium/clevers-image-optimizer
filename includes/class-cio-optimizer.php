<?php

if (!defined('ABSPATH')) {
    exit;
}

use Spatie\ImageOptimizer\OptimizerChainFactory;

class CIO_Optimizer
{
    public function __construct()
    {
        // Hook into uploads
        add_filter('wp_generate_attachment_metadata', [$this, 'optimize_on_upload'], 10, 2);
    }

    /**
     * Main handler for upload optimization
     */
    public function optimize_on_upload($metadata, $attachment_id)
    {
        $upload_dir = wp_upload_dir();
        $base_dir = trailingslashit($upload_dir['basedir']);

        $stats = [
            'original_size' => 0,
            'optimized_size' => 0,
            'webp_size' => 0,
            'avif_size' => 0,
        ];

        // Optimize original
        if (!empty($metadata['file'])) {
            $original = $base_dir . $metadata['file'];
            if (file_exists($original)) {
                $stats['original_size'] = filesize($original);
                
                $this->optimize_file($original);
                $stats['optimized_size'] = filesize($original);

                $this->generate_webp($original);
                $webp_path = $this->get_webp_path($original);
                if (file_exists($webp_path)) {
                    $stats['webp_size'] = filesize($webp_path);
                }

                if ($this->is_avif_enabled()) {
                    $this->generate_avif($original);
                    $avif_path = $this->get_avif_path($original);
                    if (file_exists($avif_path)) {
                        $stats['avif_size'] = filesize($avif_path);
                    }
                }
            }
        }

        // Optimize sizes
        if (!empty($metadata['sizes']) && is_array($metadata['sizes'])) {
            $subdir = pathinfo($metadata['file'], PATHINFO_DIRNAME);

            foreach ($metadata['sizes'] as $size) {
                if (empty($size['file'])) {
                    continue;
                }

                $file = $base_dir . $subdir . '/' . $size['file'];
                $this->optimize_file($file);
                $this->generate_webp($file);
                
                if ($this->is_avif_enabled()) {
                    $this->generate_avif($file);
                }
            }
        }
        
        // Save stats
        update_post_meta($attachment_id, '_cio_stats', $stats);

        return $metadata;
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
            error_log('[Clever Image Optimizer] ' . $e->getMessage());
        }
    }

    public function generate_webp($file)
    {
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

    private function create_image_resource($file)
    {
        $content = @file_get_contents($file);
        if (!$content) return null;
        return @imagecreatefromstring($content);
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
        return (int) get_option('cio_webp_quality', 80);
    }
    
    private function get_avif_quality()
    {
        return (int) get_option('cio_avif_quality', 80);
    }

    private function is_avif_enabled()
    {
        return get_option('cio_enable_avif', '0') === '1';
    }
}
