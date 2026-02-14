<?php

use PHPUnit\Framework\TestCase;

final class CIOOptimizerTest extends TestCase
{
    public function test_sanitize_quality_bounds_values(): void
    {
        $optimizer = new CIO_Optimizer();

        $this->assertSame(0, $optimizer->sanitize_quality(-10));
        $this->assertSame(80, $optimizer->sanitize_quality(80));
        $this->assertSame(100, $optimizer->sanitize_quality(999));
    }

    /**
     * PENDIENTE 5: Renombrado para reflejar con precisión el comportamiento de absint():
     * los IDs negativos se convierten a positivos (no se eliminan), y los ceros sí se eliminan.
     */
    public function test_normalize_attachment_id_list_converts_negatives_and_removes_zeros(): void
    {
        $optimizer = new CIO_Optimizer();

        // Caso base: strings numéricos, ceros y duplicados.
        $ids = $optimizer->normalize_attachment_id_list([10, '20', 0, -5, 10, 'abc']);
        $this->assertSame([10, 20, 5], $ids);

        // Caso explícito para IDs negativos: absint() los convierte a positivos.
        // -3 → absint(-3) = 3, que es válido y se incluye.
        $ids_negative = $optimizer->normalize_attachment_id_list([-3, -7, 3]);
        $this->assertContains(3, $ids_negative, 'absint() convierte -3 en 3, que debe aparecer en el resultado.');
        $this->assertContains(7, $ids_negative, 'absint() convierte -7 en 7, que debe aparecer en el resultado.');

        // Los ceros (IDs inválidos) deben eliminarse.
        $ids_zeros = $optimizer->normalize_attachment_id_list([0, 0, 5]);
        $this->assertNotContains(0, $ids_zeros, 'Los IDs 0 deben ser filtrados.');
        $this->assertSame([5], $ids_zeros);
    }

    public function test_is_supported_image_path_accepts_jpg_jpeg_png_only(): void
    {
        $optimizer = new CIO_Optimizer();

        $this->assertTrue($optimizer->is_supported_image_path('/tmp/photo.jpg'));
        $this->assertTrue($optimizer->is_supported_image_path('/tmp/photo.JPEG'));
        $this->assertTrue($optimizer->is_supported_image_path('/tmp/photo.png'));
        $this->assertFalse($optimizer->is_supported_image_path('/tmp/photo.webp'));
        $this->assertFalse($optimizer->is_supported_image_path('/tmp/photo.gif'));
    }
}
